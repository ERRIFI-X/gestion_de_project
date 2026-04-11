<?php

require_once __DIR__ . '/../models/Sql.php';

class Project
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    public function autoUpdateStatuses()
    {
        // 1. Update projects to 'in_progress' if start_date has arrived and status is 'pending'
        $this->sql->update("
            UPDATE projects 
            SET status = 'in_progress' 
            WHERE status = 'pending' 
            AND start_date IS NOT NULL 
            AND start_date <= CURRENT_DATE()
        ");

        // 2. Update projects to 'overdue' if end_date has passed and they are not 'completed' or 'cancelled'
        $this->sql->update("
            UPDATE projects 
            SET status = 'overdue' 
            WHERE status NOT IN ('completed', 'cancelled') 
            AND end_date IS NOT NULL 
            AND end_date < CURRENT_DATE()
        ");
    }

    public function getAll()
    {
        $this->autoUpdateStatuses();

        return $this->sql->getAll("
            SELECT p.*, c.name as client_name
            FROM projects p 
            JOIN clients c ON p.client_id = c.id 
            ORDER BY p.created_at DESC
        ");
    }

    public function show($id)
    {
        $this->autoUpdateStatuses();

        require_once __DIR__ . '/Tasks.php';
        $tasksController = new Tasks();
        $tasksController->autoUpdateStatuses();

        $project = $this->sql->getId("
            SELECT p.*, c.name as client_name, c.email as client_email
            FROM projects p 
            JOIN clients c ON p.client_id = c.id 
            WHERE p.id = :id", 
            ['id' => $id]
        );

        if ($project) {
            // Get Services for this project
            $services = $this->sql->getAll("
                SELECT * FROM services WHERE project_id = :project_id
            ", ['project_id' => $id]);

            // For each service, get its tasks
            foreach ($services as &$service) {
                $service['tasks'] = $this->sql->getAll("
                    SELECT * FROM tasks WHERE service_id = :service_id
                ", ['service_id' => $service['id']]);
            }

            $project['services'] = $services;
        }

        return $project;
    }

    public function store($data)
    {
        $pdo = $this->sql->getPdo();
        
        try {
            $pdo->beginTransaction();

            $pack_id = !empty($data['pack_id']) ? (int)$data['pack_id'] : null;

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
                ':pack_id' => $pack_id,
                ':status' => $data['status'] ?? 'pending'
            ]);

            $projectId = $pdo->lastInsertId();

            // 2. If pack_id is provided, copy pack services to the project's services
            if ($pack_id) {
                $stmtGetServices = $pdo->prepare("SELECT * FROM pack_services WHERE pack_id = :pack_id");
                $stmtGetServices->execute([':pack_id' => $pack_id]);
                $services = $stmtGetServices->fetchAll(PDO::FETCH_ASSOC);

                if ($services) {
                    $stmtInsertService = $pdo->prepare("
                        INSERT INTO services (project_id, name, price, status) 
                        VALUES (:project_id, :name, :price, 'pending')
                    ");

                    foreach ($services as $service) {
                        $stmtInsertService->execute([
                            ':project_id' => $projectId,
                            ':name' => $service['name'],
                            ':price' => $service['price']
                        ]);
                    }
                }
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

