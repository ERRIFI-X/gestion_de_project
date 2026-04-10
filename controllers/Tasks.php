<?php

require_once __DIR__ . '/../models/Sql.php';

class Tasks
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    public function autoUpdateStatuses()
    {
        // 1. Update tasks to 'in_progress' if start_date has arrived and status is 'todo'
        $this->sql->update("
            UPDATE tasks 
            SET status = 'in_progress' 
            WHERE status = 'todo' 
            AND start_date IS NOT NULL 
            AND start_date <= CURRENT_DATE()
        ");

        // 2. Update tasks to 'overdue' if end_date has passed and they are not 'done'
        $this->sql->update("
            UPDATE tasks 
            SET status = 'overdue' 
            WHERE status != 'done' 
            AND end_date IS NOT NULL 
            AND end_date < CURRENT_DATE()
        ");
    }

    public function getAll($service_id = null)
    {
        $this->autoUpdateStatuses();

        $query = "SELECT * FROM tasks";
        $params = [];
        if ($service_id) {
            $query .= " WHERE service_id = :service_id";
            $params = [':service_id' => $service_id];
        }
        return $this->sql->getAll($query, $params);
    }

    public function show($id)
    {
        return $this->sql->getId("SELECT * FROM tasks WHERE id = :id", ['id' => $id]);
    }

    public function store($data)
    {
        $id = $this->sql->create(
            "INSERT INTO tasks (service_id, title, description, start_date, end_date, priority, status, total_hours, total_cost) 
             VALUES (:service_id, :title, :description, :start_date, :end_date, :priority, :status, :total_hours, :total_cost)",
            [
                ':service_id' => (int)$data['service_id'],
                ':title' => htmlspecialchars($data['title']),
                ':description' => htmlspecialchars($data['description'] ?? ''),
                ':start_date' => $data['start_date'] ?? null,
                ':end_date' => $data['end_date'] ?? null,
                ':priority' => $data['priority'] ?? 'medium',
                ':status' => $data['status'] ?? 'todo',
                ':total_hours' => (float)($data['total_hours'] ?? 0),
                ':total_cost' => (float)($data['total_cost'] ?? 0)
            ]
        );
        return ['success' => (bool)$id, 'id' => $id];
    }

    public function update($id, $data)
    {
        $result = $this->sql->update(
            "UPDATE tasks SET 
                title = :title, 
                description = :description, 
                start_date = :start_date, 
                end_date = :end_date, 
                priority = :priority, 
                status = :status,
                total_hours = :total_hours,
                total_cost = :total_cost
             WHERE id = :id",
            [
                ':id' => $id,
                ':title' => htmlspecialchars($data['title']),
                ':description' => htmlspecialchars($data['description'] ?? ''),
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'],
                ':priority' => $data['priority'],
                ':status' => $data['status'],
                ':total_hours' => (float)($data['total_hours'] ?? 0),
                ':total_cost' => (float)($data['total_cost'] ?? 0)
            ]
        );
        return ['success' => $result];
    }

    public function delete($id)
    {
        return ['success' => $this->sql->delete("DELETE FROM tasks WHERE id = :id", ['id' => $id])];
    }
}




?> 