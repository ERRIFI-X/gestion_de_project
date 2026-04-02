<?php

require_once __DIR__ . '/../models/Sql.php';
class Project
{
    public function getAll()
    {
        $sql = new Sql();
        return $sql->getAll("SELECT * FROM projects");
    }

    public function show($id)
    {
        $sql = new Sql();
        return $sql->getId("SELECT * FROM projects WHERE id = :id", ['id' => $id]);
    }

    public function store($name, $description, $start_date, $end_date, $client_id)
    {
        $sql = new Sql();
        return $sql->update("INSERT INTO projects (name, description, start_date, end_date, client_id) VALUES (:name, :description, :start_date, :end_date, :client_id)", ['name' => $name, 'description' => $description, 'start_date' => $start_date, 'end_date' => $end_date, 'client_id' => $client_id]);
    }

    public function update($id, $name, $description, $start_date, $end_date, $client_id)
    {
        $sql = new Sql();
        return $sql->update("UPDATE projects SET name = :name, description = :description, start_date = :start_date, end_date = :end_date, client_id = :client_id WHERE id = :id", ['id' => $id, 'name' => $name, 'description' => $description, 'start_date' => $start_date, 'end_date' => $end_date, 'client_id' => $client_id]);
    }

    public function delete($id)
    {
        $sql = new Sql();
        return $sql->update("DELETE FROM projects WHERE id = :id", ['id' => $id]);
    }
}







?>