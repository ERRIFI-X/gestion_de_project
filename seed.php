<?php

require_once __DIR__ . '/routes/config.php';
require_once __DIR__ . '/models/Sql.php';

$sql = new Sql();

try {
    echo "--- Starting Fake Data Seeding ---\n";

    // 1. Ensure Admin exists
    $password = password_hash('admin123', PASSWORD_BCRYPT);
    $sql->create("INSERT IGNORE INTO admin (id, username, name, password) VALUES (1, 'admin', 'Super Admin', :p)", [
        ':p' => $password
    ]);
    echo "1. Admin 'admin' ready (Pass: admin123)\n";

    // 2. Seed 5 Clients
    $clientIds = [];
    $names = ['Tech Horizons', 'Creative Minds', 'Global Logix', 'Swift Solutions', 'Eco Builders'];
    foreach ($names as $i => $name) {
        $id = $sql->create("INSERT INTO clients (name, email, phone) VALUES (:n, :e, :p)", [
            ':n' => $name,
            ':e' => strtolower(str_replace(' ', '', $name)) . i . '@example.com',
            ':p' => '060000000' . $i
        ]);
        $clientIds[] = $id;
    }
    echo "2. 5 Clients added.\n";

    // 3. Seed 3 Projects with different statuses
    $projectStatuses = ['pending', 'in_progress', 'completed'];
    $projectIds = [];
    foreach ($projectStatuses as $i => $status) {
        $id = $sql->create("INSERT INTO projects (name, description, client_id, status) VALUES (:n, :d, :c, :s)", [
            ':n' => "Project " . ($i + 1) . " ($status)",
            ':d' => "Detailed biological testing for project " . ($i + 1),
            ':c' => $clientIds[$i % count($clientIds)],
            ':s' => $status
        ]);
        $projectIds[] = $id;
    }
    echo "3. 3 Projects added.\n";

    // 4. Seed Tasks for each project
    foreach ($projectIds as $pId) {
        for ($j = 1; $j <= 4; $j++) {
            $cost = rand(500, 2000);
            $hours = rand(5, 30);
            $sql->create("INSERT INTO tasks (project_id, title, total_cost, total_hours, status) VALUES (:p, :t, :c, :h, :s)", [
                ':p' => $pId,
                ':t' => "Task $j for Project $pId",
                ':c' => $cost,
                ':h' => $hours,
                ':s' => ($j % 2 == 0) ? 'done' : 'in_progress'
            ]);
        }
    }
    echo "4. 12 Tasks added (Triggering project budget updates).\n";

    // 5. Seed some payments
    foreach ($projectIds as $pId) {
        $sql->create("INSERT INTO payments (project_id, client_id, amount, payment_date, payment_method) VALUES (:p, :c, :a, :d, :m)", [
            ':p' => $pId,
            ':c' => $clientIds[$pId % 5],
            ':a' => rand(200, 1000),
            ':d' => date('Y-m-d'),
            ':m' => 'cash'
        ]);
    }
    echo "5. Initial payments recorded (Triggering project remaining balance updates).\n";

    echo "\n--- SEEDING COMPLETED SUCCESSFULLY ---\n";
    echo "You can now login with 'admin' / 'admin123' to start testing.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
