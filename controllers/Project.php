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
            SELECT p.*, c.name as client_name 
            FROM projects p 
            JOIN clients c ON p.client_id = c.id 
            ORDER BY p.created_at DESC
        ");
    }

    public function show($id)
    {
        $project = $this->sql->getId("
            SELECT p.*, c.name as client_name, c.email as client_email 
            FROM projects p 
            JOIN clients c ON p.client_id = c.id 
            WHERE p.id = :id", 
            ['id' => $id]
        );

        if ($project) {
            // Get associated services (project scope)
            $project['services'] = $this->sql->getAll("
                SELECT ps.*, s.name as service_name 
                FROM project_services ps 
                JOIN services s ON ps.service_id = s.id 
                WHERE ps.project_id = :project_id", 
                ['project_id' => $id]
            );

            // Get associated tasks (single level)
            $project['tasks'] = $this->sql->getAll("
                SELECT * FROM tasks WHERE project_id = :project_id", 
                ['project_id' => $id]
            );
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
                INSERT INTO projects (name, description, start_date, end_date, client_id, pack_template_id, status) 
                VALUES (:name, :description, :start_date, :end_date, :client_id, :template_id, :status)
            ");
            
            $stmt->execute([
                ':name' => htmlspecialchars($data['name']),
                ':description' => htmlspecialchars($data['description'] ?? ''),
                ':start_date' => $data['start_date'] ?? null,
                ':end_date' => $data['end_date'] ?? null,
                ':client_id' => (int)$data['client_id'],
                ':template_id' => !empty($data['pack_template_id']) ? (int)$data['pack_template_id'] : null,
                ':status' => $data['status'] ?? 'pending'
            ]);

            $projectId = $pdo->lastInsertId();

            // 2. If a template is selected, copy services
            if (!empty($data['pack_template_id'])) {
                $stmtServices = $pdo->prepare("
                    INSERT INTO project_services (project_id, service_id, price)
                    SELECT :project_id, service_id, s.price
                    FROM pack_services ps
                    JOIN services s ON ps.service_id = s.id
                    WHERE ps.pack_template_id = :template_id
                ");
                $stmtServices->execute([
                    ':project_id' => $projectId,
                    ':template_id' => (int)$data['pack_template_id']
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
                status = :status 
            WHERE id = :id", 
            [
                ':id' => $id,
                ':name' => htmlspecialchars($data['name']),
                ':description' => htmlspecialchars($data['description'] ?? ''),
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'],
                ':status' => $data['status']
            ]
        )];
    }

    public function delete($id)
    {
        return ['success' => $this->sql->delete("DELETE FROM projects WHERE id = :id", ['id' => $id])];
    }
}
