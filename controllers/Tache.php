<?php 

require_once __DIR__ . '/../models/Sql.php';
class Tache
{

   public function getAll()
    {
        $sql = new Sql();
        return $sql->getAll("SELECT * FROM tache");
    }

    public function show($id)
    {
        $sql = new Sql();
        return $sql->getId("SELECT * FROM tache WHERE id = :id", ['id' => $id]);
    }

    public function store($name, $description, $status, $task_id)
    {
        $sql = new Sql();
        return $sql->update("INSERT INTO tache (name, description, status, task_id) VALUES (:name, :description, :status, :task_id)", ['name' => $name, 'description' => $description, 'status' => $status, 'task_id' => $task_id]);
    }

    public function update($id, $name, $description, $status, $task_id)
    {
        $sql = new Sql();
        return $sql->update("UPDATE tache SET name = :name, description = :description, status = :status, task_id = :task_id WHERE id = :id", ['id' => $id, 'name' => $name, 'description' => $description, 'status' => $status, 'task_id' => $task_id]);
    }

    public function delete($id)
    {
        $sql = new Sql();
        return $sql->update("DELETE FROM tache WHERE id = :id", ['id' => $id]);
    }
}














?>