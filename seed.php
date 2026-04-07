<?php

require_once __DIR__ . '/routes/config.php';
require_once __DIR__ . '/models/Sql.php';

$sql = new Sql();

try {
    echo "--- Starting Advanced Fake Data Seeding ---\n";

    // 0. Clear existing data
    $sql->getPdo()->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = ['notifications', 'activity_logs', 'payments', 'invoices', 'tasks', 'servers', 'project_services', 'projects', 'pack_services', 'services', 'pack_templates', 'clients'];
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

    // 2. Seed Services with new fields
    $servicesData = [
        ['Web Hosting', 1200.00, 'Cloud hosting for production', 0, 800.00],
        ['Domain Registration', 200.00, 'Yearly renewal', 0, 150.00],
        ['SSL Certificate', 150.00, 'PositiveSSL', 0, 50.00],
        ['SEO Optimization', 3000.00, 'On-page and Off-page SEO', 20, 1000.00],
        ['Security Audit', 5000.00, 'Complete pentest', 40, 2000.00],
        ['Monthly Maintenance', 800.00, 'Bug fixes and updates', 10, 300.00]
    ];
    $serviceIds = [];
    foreach ($servicesData as $s) {
        $id = $sql->create("INSERT INTO services (name, price, description, hours, cost) VALUES (:n, :p, :d, :h, :c)", [
            ':n' => $s[0], 
            ':p' => $s[1],
            ':d' => $s[2],
            ':h' => $s[3],
            ':c' => $s[4]
        ]);
        $serviceIds[$s[0]] = $id;
    }
    echo "2. Services seeded.\n";

    // 3. Seed Pack Templates
    $packs = [
        'Starter Pack' => ['Web Hosting', 'Domain Registration'],
        'Business Pack' => ['Web Hosting', 'Domain Registration', 'SSL Certificate', 'Monthly Maintenance'],
        'Enterprise Pack' => ['Web Hosting', 'Domain Registration', 'SSL Certificate', 'SEO Optimization', 'Security Audit', 'Monthly Maintenance']
    ];
    $packIds = [];
    foreach ($packs as $pName => $sList) {
        $pId = $sql->create("INSERT INTO pack_templates (name) VALUES (:n)", [':n' => $pName]);
        $packIds[$pName] = $pId;
        foreach ($sList as $sName) {
            $sql->create("INSERT INTO pack_services (pack_template_id, service_id) VALUES (:pid, :sid)", [
                ':pid' => $pId,
                ':sid' => $serviceIds[$sName]
            ]);
        }
    }
    echo "3. Pack Templates and pivot links seeded.\n";

    // 4. Seed Clients
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
    echo "4. 5 Clients seeded.\n";

    // 5. Seed Projects and link to Services/Packs
    $projects = [
        ['E-commerce Site', 'Business Pack'],
        ['Company Portfolio', 'Starter Pack'],
        ['Banking App Security', 'Enterprise Pack']
    ];
    $projectIds = [];
    foreach ($projects as $i => $p) {
        $pId = $sql->create("INSERT INTO projects (name, description, client_id, pack_id, status) VALUES (:n, :d, :c, :pk, :s)", [
            ':n' => $p[0],
            ':d' => "Project description for " . $p[0],
            ':c' => $clientIds[$i % count($clientIds)],
            ':pk' => $packIds[$p[1]],
            ':s' => 'in_progress'
        ]);
        $projectIds[] = $pId;

        // Add services to this project from the pack
        foreach ($packs[$p[1]] as $sName) {
            $sql->create("INSERT INTO project_services (project_id, service_id, price) VALUES (:pid, :sid, :pr)", [
                ':pid' => $pId,
                ':sid' => $serviceIds[$sName],
                ':pr'  => 0 // Trigger could handle this, but we'll set 0 or default
            ]);
        }
    }
    echo "5. 3 Projects linked with Services seeded.\n";

    // 6. Seed Servers
    $pServerIds = [];
    foreach ($projectIds as $pId) {
        $sId = $sql->create("INSERT INTO servers (project_id, name, description) VALUES (:p, :n, :d)", [
            ':p' => $pId,
            ':n' => "SRV-" . strtoupper(bin2hex(random_bytes(2))),
            ':d' => "Main server for project $pId"
        ]);
        $pServerIds[$pId] = $sId;
    }
    echo "6. Servers seeded.\n";

    // 7. Seed Tasks with Links
    foreach ($projectIds as $pId) {
        $pServices = $sql->getAll("SELECT id FROM project_services WHERE project_id = :p", [':p' => $pId]);
        foreach ($pServices as $ps) {
            for ($j = 1; $j <= 2; $j++) {
                $sql->create("INSERT INTO tasks (project_services_id, title, total_cost, total_hours, status) VALUES (:ps, :t, :c, :h, :st)", [
                    ':ps' => $ps['id'],
                    ':t'  => "Task $j for Service " . $ps['id'],
                    ':c'  => rand(500, 2000),
                    ':h'  => rand(2, 6),
                    ':st' => ($j % 2 == 0) ? 'done' : 'in_progress'
                ]);
            }
        }
    }
    echo "7. Tasks (linked to Services) seeded.\n";

    // 8. Seed Invoices
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
    echo "8. Invoices seeded.\n";

    // 9. Seed Payments to trigger financial and invoice updates
    foreach ($projectIds as $pId) {
        $inv = $invoiceIds[$pId];
        // Create a payment that pays the invoice in full
        $sql->create("INSERT INTO payments (project_id, client_id, invoice_id, amount, payment_date, payment_method) VALUES (:p, :c, :i, :a, :d, :m)", [
            ':p' => $pId,
            ':c' => $inv['client_id'],
            ':i' => $inv['id'],
            ':a' => $inv['amount'], // Paying the full amount should trigger 'paid' status
            ':d' => date('Y-m-d'),
            ':m' => 'transfer'
        ]);
    }
    echo "9. Payments seeded (Financial & Invoice Triggers executed).\n";

    echo "\n--- SEEDING COMPLETED SUCCESSFULLY ---\n";
    echo "Access your API now. Projects are populated with financial data via Triggers.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

