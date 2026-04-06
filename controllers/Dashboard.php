<?php

require_once __DIR__ . '/../models/Sql.php';

class Dashboard
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    public function getSummary()
    {
        $clientsCount = $this->sql->getId("SELECT COUNT(*) as count FROM clients")['count'] ?? 0;
        $projectsCount = $this->sql->getId("SELECT COUNT(*) as count FROM projects")['count'] ?? 0;
        $tasksCount = $this->sql->getId("SELECT COUNT(*) as count FROM tasks")['count'] ?? 0;
        $invoicesCount = $this->sql->getId("SELECT COUNT(*) as count FROM invoices")['count'] ?? 0;
        
        $revenueData = $this->sql->getId("SELECT SUM(amount) as total FROM payments");
        $totalRevenue = (float)($revenueData['total'] ?? 0);

        $projectStatusResults = $this->sql->getAll("SELECT status, COUNT(*) as count FROM projects GROUP BY status");
        $statusDistribution = [];
        foreach ($projectStatusResults as $row) {
            $statusDistribution[$row['status']] = (int)$row['count'];
        }

        return [
            'success' => true,
            'data' => [
                'total_clients' => (int)$clientsCount,
                'total_projects' => (int)$projectsCount,
                'total_tasks' => (int)$tasksCount,
                'total_invoices' => (int)$invoicesCount,
                'total_revenue' => $totalRevenue,
                'project_status' => $statusDistribution
            ]
        ];
    }
}

?>