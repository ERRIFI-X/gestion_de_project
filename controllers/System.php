<?php

require_once __DIR__ . '/../models/Sql.php';

class System
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    // --- Activity Logs ---

    public function logActivity($userId, $action, $entityType, $entityId, $oldValue = null, $newValue = null)
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return $this->sql->create(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, old_value, new_value, ip_address) 
             VALUES (:user_id, :action, :entity_type, :entity_id, :old_val, :new_val, :ip)",
            [
                ':user_id' => $userId,
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':old_val' => $oldValue ? json_encode($oldValue) : null,
                ':new_val' => $newValue ? json_encode($newValue) : null,
                ':ip' => $ipAddress
            ]
        );
    }

    public function getLogs()
    {
        return $this->sql->getAll("
            SELECT al.*, a.username 
            FROM activity_logs al 
            JOIN admin a ON al.user_id = a.id 
            ORDER BY al.created_at DESC 
            LIMIT 100
        ");
    }

    // --- Notifications ---

    public function createNotification($userId, $type, $message)
    {
        return $this->sql->create(
            "INSERT INTO notifications (user_id, type, message) VALUES (:user_id, :type, :message)",
            [
                ':user_id' => $userId,
                ':type' => $type,
                ':message' => $message
            ]
        );
    }

    public function getNotifications($userId)
    {
        return $this->sql->getAll("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC", ['user_id' => $userId]);
    }

    public function markAsRead($id)
    {
        return $this->sql->update("UPDATE notifications SET is_read = TRUE WHERE id = :id", ['id' => $id]);
    }
}
