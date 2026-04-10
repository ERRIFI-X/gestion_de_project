<?php

require_once __DIR__ . '/routes/config.php';
require_once __DIR__ . '/models/Sql.php';

$sql = new Sql();

try {
    echo "--- Starting Simplified Fake Data Seeding ---\n";

    // 0. Clear existing data
    $sql->getPdo()->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = ['notifications', 'activity_logs', 'payments', 'invoices', 'tasks', 'services', 'projects', 'clients'];
    foreach ($tables as $table) {
        $sql->getPdo()->exec("DELETE FROM $table");
        $sql->getPdo()->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
    }
    $sql->getPdo()->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "0. Existing data cleared.\n";

    // 1. Ensure Admin exists
    $password = password_hash('admin123', PASSWORD_BCRYPT);
    $sql->create("INSERT IGNORE INTO admin (username, name, password) VALUES ('admin', 'Super Admin', :p)", [
        ':p' => $password
    ]);
    echo "1. Admin 'admin' ready.\n";

    // 2. Seed Clients
    $clientIds = [];
    $names = ['Alpha Corp', 'Beta Systems', 'Gamma Tech', 'Delta Solutions', 'Epsilon Group'];
    foreach ($names as $i => $name) {
        $id = $sql->create("INSERT INTO clients (name, email, phone) VALUES (:n, :e, :p)", [
            ':n' => $name,
            ':e' => strtolower($name) . "@example.com",
            ':p' => "06" . str_repeat($i + 1, 8)
        ]);
        $clientIds[] = $id;
    }
    echo "2. 5 Clients seeded.\n";

    // 3. Seed Projects
    $projectIds = [];
    $projects = [
        ['E-commerce Site', 'Online store with complex inventory.'],
        ['Company Portfolio', 'Simple showcase website.'],
        ['Banking App Security', 'Financial security audit and implementation.']
    ];
    foreach ($projects as $i => $p) {
        $pId = $sql->create("INSERT INTO projects (name, description, client_id, status) VALUES (:n, :d, :c, :s)", [
            ':n' => $p[0],
            ':d' => $p[1],
            ':c' => $clientIds[$i % count($clientIds)],
            ':s' => 'in_progress'
        ]);
        $projectIds[] = $pId;
    }
    echo "3. 3 Projects seeded.\n";

    // 4. Seed Services
    $serviceIds = [];
    foreach ($projectIds as $pId) {
        $id1 = $sql->create("INSERT INTO services (project_id, name) VALUES (:p, :n)", [
            ':p' => $pId,
            ':n' => "Frontend Development"
        ]);
        $id2 = $sql->create("INSERT INTO services (project_id, name) VALUES (:p, :n)", [
            ':p' => $pId,
            ':n' => "Backend API"
        ]);
        $serviceIds[] = $id1;
        $serviceIds[] = $id2;
    }
    echo "4. Services seeded.\n";

    // 5. Seed Tasks
    foreach ($serviceIds as $sId) {
        for ($j = 1; $j <= 2; $j++) {
            $isOverdue = ($j == 1);
            $startDate = $isOverdue ? date('Y-m-d', strtotime('-10 days')) : date('Y-m-d', strtotime('-2 days'));
            $endDate = $isOverdue ? date('Y-m-d', strtotime('-2 days')) : date('Y-m-d', strtotime('+5 days'));

            $sql->create("INSERT INTO tasks (service_id, title, description, total_cost, total_hours, status, start_date, end_date) VALUES (:s, :t, :d, :c, :h, :st, :sd, :ed)", [
                ':s' => $sId,
                ':t'  => "Task $j for Service " . $sId,
                ':d'  => "Description for task $j in service $sId",
                ':c'  => rand(500, 2000),
                ':h'  => rand(2, 6),
                ':st' => 'todo', // Intentionally set to 'todo' so the autoUpdate logic hits it
                ':sd' => $startDate,
                ':ed' => $endDate
            ]);
        }
    }
    echo "5. Tasks (linked to Services) seeded.\n";

    // 6. Seed Invoices
    $invoiceIds = [];
    foreach ($projectIds as $pId) {
        $cId = $clientIds[$pId % count($clientIds)];
        $amount = rand(2000, 5000);
        $iId = $sql->create("INSERT INTO invoices (project_id, client_id, amount, due_date, status) VALUES (:p, :c, :a, DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), 'sent')", [
            ':p' => $pId,
            ':c' => $cId,
            ':a' => $amount
        ]);
        $invoiceIds[$pId] = ['id' => $iId, 'amount' => $amount, 'client_id' => $cId];
    }
    echo "6. Invoices seeded.\n";

    // 7. Seed Payments to trigger financial and invoice updates
    foreach ($projectIds as $pId) {
        $inv = $invoiceIds[$pId];
        $sql->create("INSERT INTO payments (project_id, client_id, invoice_id, amount, payment_date, payment_method) VALUES (:p, :c, :i, :a, :d, :m)", [
            ':p' => $pId,
            ':c' => $inv['client_id'],
            ':i' => $inv['id'],
            ':a' => $inv['amount'],
            ':d' => date('Y-m-d'),
            ':m' => 'transfer'
        ]);
    }
    echo "7. Payments seeded (Financial & Invoice Triggers executed).\n";

    echo "\n--- SEEDING COMPLETED SUCCESSFULLY ---\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
