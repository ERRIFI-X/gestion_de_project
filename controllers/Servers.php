<?php

require_once __DIR__ . '/../models/Sql.php';

class Servers
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    public function getAll($project_id = null)
    {
        $query = "SELECT * FROM servers";
        $params = [];
        if ($project_id) {
            $query .= " WHERE project_id = :project_id";
            $params = [':project_id' => $project_id];
        }
        return $this->sql->getAll($query, $params);
    }

    public function show($id)
    {
        $server = $this->sql->getId("SELECT * FROM servers WHERE id = :id", ['id' => $id]);

        if (!$server) {
            return ['success' => false, 'error' => 'Server not found'];
        }
        $server['tasks'] = $this->sql->getAll("SELECT * FROM tasks WHERE server_id = :server_id", ['server_id' => $id]);
        return $server;
    }

    public function store($data)
    {
        $id = $this->sql->create(
            "INSERT INTO servers (project_id, name, description) 
             VALUES (:project_id, :name, :description)",
            [
                ':project_id' => (int)$data['project_id'],
                ':name' => htmlspecialchars($data['name'] ?? ''),
                ':description' => htmlspecialchars($data['description'] ?? '')
            ]
        );
        return ['success' => (bool)$id, 'id' => $id];
    }

    public function update($id, $data)
    {
        $result = $this->sql->update(
            "UPDATE servers SET 
                name = :name, 
                description = :description
             WHERE id = :id",
            [
                ':id' => $id,
                ':name' => htmlspecialchars($data['name'] ?? ''),
                ':description' => htmlspecialchars($data['description'] ?? '')
            ]
        );
        return ['success' => $result];
    }

    public function delete($id)
    {
        return ['success' => $this->sql->delete("DELETE FROM servers WHERE id = :id", ['id' => $id])];
    }
}

?>
