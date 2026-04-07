<?php

require_once __DIR__ . '/../models/Sql.php';

class Project
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    public function getAll()
    {
        return $this->sql->getAll("
            SELECT p.*, c.name as client_name, pt.name as pack_name
            FROM projects p 
            JOIN clients c ON p.client_id = c.id 
            LEFT JOIN pack_templates pt ON p.pack_id = pt.id
            ORDER BY p.created_at DESC
        ");
    }

    public function show($id)
    {
        $project = $this->sql->getId("
            SELECT p.*, c.name as client_name, c.email as client_email, pt.name as pack_name
            FROM projects p 
            JOIN clients c ON p.client_id = c.id 
            LEFT JOIN pack_templates pt ON p.pack_id = pt.id
            WHERE p.id = :id", 
            ['id' => $id]
        );

        if ($project) {
            // 1. Get Project Services
            $services = $this->sql->getAll("
                SELECT ps.*, s.name as service_name 
                FROM project_services ps 
                JOIN services s ON ps.service_id = s.id 
                WHERE ps.project_id = :project_id", 
                ['project_id' => $id]
            );

            // 2. For each service, get its tasks
            foreach ($services as &$service) {
                $service['tasks'] = $this->sql->getAll("
                    SELECT * FROM tasks 
                    WHERE project_services_id = :ps_id
                ", ['ps_id' => $service['id']]);
            }
            $project['services'] = $services;

            // 3. Get Servers (Financial info only, tasks are now in services)
            $project['servers'] = $this->sql->getAll("
                SELECT * FROM servers WHERE project_id = :project_id
            ", ['project_id' => $id]);

            // 4. Financial Summary
            $project['all_tasks_count'] = $this->sql->getId("
                SELECT COUNT(*) as count 
                FROM tasks t 
                JOIN project_services ps ON t.project_services_id = ps.id 
                WHERE ps.project_id = :id", 
                ['id' => $id]
            )['count'];
        }

        return $project;
    }

    public function store($data)
    {
        $pdo = $this->sql->getPdo();
        
        try {
            $pdo->beginTransaction();

            // 1. Create Project
            $stmt = $pdo->prepare("
                INSERT INTO projects (name, description, start_date, end_date, client_id, pack_id, status) 
                VALUES (:name, :description, :start_date, :end_date, :client_id, :pack_id, :status)
            ");
            
            $stmt->execute([
                ':name' => htmlspecialchars($data['name']),
                ':description' => htmlspecialchars($data['description'] ?? ''),
                ':start_date' => $data['start_date'] ?? null,
                ':end_date' => $data['end_date'] ?? null,
                ':client_id' => (int)$data['client_id'],
                ':pack_id' => !empty($data['pack_id']) ? (int)$data['pack_id'] : null,
                ':status' => $data['status'] ?? 'pending'
            ]);

            $projectId = $pdo->lastInsertId();

            // 2. If a pack is selected, copy services from the template
            if (!empty($data['pack_id'])) {
                $stmtServices = $pdo->prepare("
                    INSERT INTO project_services (project_id, service_id, price)
                    SELECT :project_id, service_id, s.price
                    FROM pack_services ps
                    JOIN services s ON ps.service_id = s.id
                    WHERE ps.pack_template_id = :pack_id
                ");
                $stmtServices->execute([
                    ':project_id' => $projectId,
                    ':pack_id' => (int)$data['pack_id']
                ]);

                // Update project total_cost based on added services
                $stmtUpdateCost = $pdo->prepare("
                    UPDATE projects 
                    SET total_cost = (SELECT SUM(price) FROM project_services WHERE project_id = :project_id),
                        remaining_amount = (SELECT SUM(price) FROM project_services WHERE project_id = :project_id)
                    WHERE id = :project_id
                ");
                $stmtUpdateCost->execute([':project_id' => $projectId]);
            }

            $pdo->commit();
            return ['success' => true, 'id' => $projectId];

        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function update($id, $data)
    {
        return ['success' => $this->sql->update("
            UPDATE projects SET 
                name = :name, 
                description = :description, 
                start_date = :start_date, 
                end_date = :end_date, 
                status = :status,
                pack_id = :pack_id
            WHERE id = :id", 
            [
                ':id' => $id,
                ':name' => htmlspecialchars($data['name']),
                ':description' => htmlspecialchars($data['description'] ?? ''),
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'],
                ':status' => $data['status'],
                ':pack_id' => !empty($data['pack_id']) ? (int)$data['pack_id'] : null
            ]
        )];
    }

    public function delete($id)
    {
        return ['success' => $this->sql->delete("DELETE FROM projects WHERE id = :id", ['id' => $id])];
    }
}

