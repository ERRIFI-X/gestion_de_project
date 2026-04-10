<?php

require_once __DIR__ . '/../models/Sql.php';

class NotificationsController
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    public function getAll()
    {
        return $this->sql->getAll("SELECT * FROM notifications ORDER BY created_at DESC");
    }

    public function getByUser($userId)
    {
        return $this->sql->getAll(
            "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC",
            [':user_id' => (int)$userId]
        );
    }

    public function create($data)
    {
        $id = $this->sql->create(
            "INSERT INTO notifications (user_id, type, message) VALUES (:user_id, :type, :message)",
            [
                ':user_id' => (int)$data['user_id'],
                ':type' => $data['type'] ?? 'info',
                ':message' => htmlspecialchars($data['message'] ?? '')
            ]
        );
        return ['success' => (bool)$id, 'id' => $id];
    }

    public function markAsRead($id)
    {
        $result = $this->sql->update(
            "UPDATE notifications SET is_read = TRUE WHERE id = :id",
            [':id' => (int)$id]
        );
        return ['success' => $result];
    }

    public function markAllAsRead($userId)
    {
        $result = $this->sql->update(
            "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id AND is_read = FALSE",
            [':user_id' => (int)$userId]
        );
        return ['success' => $result];
    }

    public function delete($id)
    {
        $result = $this->sql->delete("DELETE FROM notifications WHERE id = :id", [':id' => (int)$id]);
        return ['success' => $result];
    }

    public function deleteAll($userId)
    {
        $result = $this->sql->delete(
            "DELETE FROM notifications WHERE user_id = :user_id",
            [':user_id' => (int)$userId]
        );
        return ['success' => $result];
    }

    public function getUnreadCount($userId)
    {
        $result = $this->sql->getAll(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = FALSE",
            [':user_id' => (int)$userId]
        );
        return ['count' => $result[0]['count'] ?? 0];
    }
}
