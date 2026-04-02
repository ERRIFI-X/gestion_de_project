<?php

require_once __DIR__ . '/../models/Sql.php';


class Clients
{

   public function getAll()
    {
        $sql = new Sql();
        return $sql->getAll("SELECT * FROM clients");
    }

    public function show($id)
    {
        $sql = new Sql();
        return $sql->getId("SELECT * FROM clients WHERE id = :id", ['id' => $id]);
    }

    public function store($name, $phone, $email)
    {
        $error = [];

            if (empty($name)) {
                $error[] = "Le nom est requis.";
            }
            if (empty($phone)) {
                $error[] = "Le numéro de téléphone est requis.";
            } elseif (!preg_match('/^\d{10}$/', $phone)) {
                $error[] = "Le numéro de téléphone doit être composé de 10 chiffres.";
            }
            if (empty($email)) {
                $error[] = "L'email est requis.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error[] = "L'email n'est pas valide.";
            }
        if (!empty($error)) {
            return ['success' => false, 'errors' => $error];
        }
        $sql = new Sql();
        return $sql->update("INSERT INTO clients (name, phone, email) VALUES (:name, :phone, :email)", ['name' => $name, 'phone' => $phone, 'email' => $email]);
    }

    public function update($id, $name, $phone, $email)
    {
        $error = [];

            if (empty($name)) {
                $error[] = "Le nom est requis.";
            }
            if (empty($phone)) {
                $error[] = "Le numéro de téléphone est requis.";
            } elseif (!preg_match('/^\d{10}$/', $phone)) {
                $error[] = "Le numéro de téléphone doit être composé de 10 chiffres.";
            }
            if (empty($email)) {
                $error[] = "L'email est requis.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error[] = "L'email n'est pas valide.";
            }
        if (!empty($error)) {
            return ['success' => false, 'errors' => $error];
        }

        $sql = new Sql();
        return $sql->update("UPDATE clients SET name = :name, phone = :phone, email = :email WHERE id = :id", ['id' => $id, 'name' => $name, 'phone' => $phone, 'email' => $email]);
    }

    public function delete($id)
    {
        $sql = new Sql();
        return $sql->update("DELETE FROM clients WHERE id = :id", ['id' => $id]);
    }
}
?>