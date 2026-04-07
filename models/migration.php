<?php

require_once __DIR__ . '/../routes/config.php';

$status = [];

function addStatus(string $type, string $key, string $message): void
{
    global $status;
    $status[] = ["status" => $type, $key => $message];
}

function createDatabase(): void
{
    try {
        $conn = getPDOConnectionDB(false);
        $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . "
                     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        addStatus("success", "database", "Database created or already exists.");
    } catch (PDOException $e) {
        addStatus("error", "database", "Database creation failed: " . $e->getMessage());
        exit;
    }
}

function createTables(): string
{
    global $status;

    try {
        createDatabase();
        $conn = getPDOConnectionDB();

        // DROP TABLES IN REVERSE ORDER TO AVOID FK ISSUES
        $tablesToDrop = [
            'notifications', 'activity_logs', 'payments', 'invoices', 
            'tasks', 'servers', 'project_services', 'projects', 
            'pack_services', 'services', 'pack_templates', 'clients', 'admin'
        ];
        foreach ($tablesToDrop as $table) {
            $conn->exec("DROP TABLE IF EXISTS $table");
        }
        addStatus("success", "cleanup", "Existing tables dropped for clean migration.");

        // 1. Admin Table
        $conn->exec("CREATE TABLE IF NOT EXISTS admin (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            username   VARCHAR(100)  NOT NULL UNIQUE,
            name       VARCHAR(255)  NOT NULL,
            password   VARCHAR(255)  NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        addStatus("success", "admin", "Table 'admin' OK.");

        // 2. Clients Table
        $conn->exec("CREATE TABLE IF NOT EXISTS clients (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            phone      VARCHAR(50),
            email      VARCHAR(255) NOT NULL UNIQUE,
            address    TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (email)
        ) ENGINE=InnoDB");
        addStatus("success", "clients", "Table 'clients' OK.");

        // 3. Pack Templates
        $conn->exec("CREATE TABLE IF NOT EXISTS pack_templates (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        addStatus("success", "pack_templates", "Table 'pack_templates' OK.");

        $conn->exec("CREATE TABLE IF NOT EXISTS services (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            name       VARCHAR(255) NOT NULL,
            price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            description TEXT,
            hours      DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
            cost       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        addStatus("success", "services", "Table 'services' OK.");

        // 5. Pack Services (Pivot Table Template)
        $conn->exec("CREATE TABLE IF NOT EXISTS pack_services (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            pack_template_id INT NOT NULL,
            service_id       INT NOT NULL,
            FOREIGN KEY (pack_template_id) REFERENCES pack_templates(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            UNIQUE KEY uq_pack_service (pack_template_id, service_id)
        ) ENGINE=InnoDB");
        addStatus("success", "pack_services", "Table 'pack_services' OK.");

        // 6. Projects
        $conn->exec("CREATE TABLE IF NOT EXISTS projects (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            name             VARCHAR(255) NOT NULL,
            description      TEXT,
            start_date       DATE,
            end_date         DATE,
            status           ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
            total_cost       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            client_id        INT NOT NULL,
            pack_id          INT NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
            FOREIGN KEY (pack_id) REFERENCES pack_templates(id) ON DELETE SET NULL,
            INDEX (status)
        ) ENGINE=InnoDB");
        addStatus("success", "projects", "Table 'projects' OK.");

        
        // 8. Servers
        $conn->exec("CREATE TABLE IF NOT EXISTS servers (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            project_id  INT NOT NULL,
            name        VARCHAR(100),
            description TEXT,
            total_hours DECIMAL(8,2) NOT NULL DEFAULT 0.00,
            total_cost  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX (project_id)
        ) ENGINE=InnoDB");
        addStatus("success", "servers", "Table 'servers' OK.");
        // 7. Project Services
        $conn->exec("CREATE TABLE IF NOT EXISTS project_services (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            project_id       INT NOT NULL,
            service_id       INT NOT NULL,
            price            DECIMAL(10,2) NOT NULL,
            status           ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            UNIQUE KEY uq_project_service (project_id, service_id)
        ) ENGINE=InnoDB");
        addStatus("success", "project_services", "Table 'project_services' OK.");

        // 9. Tasks (Single Level)
        $conn->exec("CREATE TABLE IF NOT EXISTS tasks (
            id                 INT AUTO_INCREMENT PRIMARY KEY,
            project_services_id INT NOT NULL,
            title              VARCHAR(255) NOT NULL,
            description        TEXT,
            start_date         DATE,
            end_date           DATE,
            priority           ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
            status             ENUM('todo', 'in_progress', 'done') NOT NULL DEFAULT 'todo',
            total_hours        DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
            total_cost         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_services_id) REFERENCES project_services(id) ON DELETE CASCADE,
            INDEX (project_services_id)
        ) ENGINE=InnoDB");
        addStatus("success", "tasks", "Table 'tasks' OK.");

        // FINANCIAL & STATUS TRIGGERS: TASKS -> PROJECT_SERVICES
        foreach (['INSERT', 'UPDATE', 'DELETE'] as $event) {
            $ref    = ($event === 'DELETE') ? 'OLD' : 'NEW';
            $suffix = strtolower($event);
            
            $conn->exec("DROP TRIGGER IF EXISTS after_{$suffix}_task_financials");
            // NOTE: Changing DELIMITER isn't needed with PDO exec() because it sends the whole statement at once.
            $conn->exec("
                CREATE TRIGGER after_{$suffix}_task_financials
                AFTER {$event} ON tasks
                FOR EACH ROW
                BEGIN
                    DECLARE v_status ENUM('pending', 'in_progress', 'completed');
                    DECLARE v_total_tasks INT;
                    DECLARE v_done_tasks INT;
                    DECLARE v_in_progress_tasks INT;

                    -- Update project_service cost based on tasks
                    UPDATE project_services 
                    SET price = (SELECT IFNULL(SUM(total_cost), 0) FROM tasks WHERE project_services_id = {$ref}.project_services_id)
                    WHERE id = {$ref}.project_services_id;

                    -- Calc statuses
                    SELECT COUNT(*) INTO v_total_tasks FROM tasks WHERE project_services_id = {$ref}.project_services_id;
                    SELECT COUNT(*) INTO v_done_tasks FROM tasks WHERE project_services_id = {$ref}.project_services_id AND status = 'done';
                    SELECT COUNT(*) INTO v_in_progress_tasks FROM tasks WHERE project_services_id = {$ref}.project_services_id AND status = 'in_progress';

                    IF v_total_tasks = 0 THEN
                        SET v_status = 'pending';
                    ELSEIF v_total_tasks = v_done_tasks THEN
                        SET v_status = 'completed';
                    ELSEIF v_in_progress_tasks > 0 OR v_done_tasks > 0 THEN
                        SET v_status = 'in_progress';
                    ELSE
                        SET v_status = 'pending';
                    END IF;

                    UPDATE project_services SET status = v_status WHERE id = {$ref}.project_services_id;
                END
            ");
        }
        addStatus("success", "triggers_tasks", "Advanced triggers on tasks (price and status) OK.");

        // FINANCIAL & STATUS TRIGGERS: PROJECT_SERVICES -> PROJECTS
        foreach (['UPDATE'] as $event) {
            $ref    = 'NEW';
            $suffix = strtolower($event);
            $conn->exec("DROP TRIGGER IF EXISTS after_{$suffix}_service_financials");
            $conn->exec("
                CREATE TRIGGER after_{$suffix}_service_financials
                AFTER {$event} ON project_services
                FOR EACH ROW
                BEGIN
                    DECLARE v_status ENUM('pending', 'in_progress', 'completed', 'cancelled');
                    DECLARE v_total_srv INT;
                    DECLARE v_comp_srv INT;
                    DECLARE v_prog_srv INT;

                    -- Update total cost for the project
                    UPDATE projects SET
                        total_cost = (SELECT IFNULL(SUM(price), 0) FROM project_services WHERE project_id = NEW.project_id),
                        remaining_amount = (SELECT IFNULL(SUM(price), 0) FROM project_services WHERE project_id = NEW.project_id) 
                                         - (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE project_id = NEW.project_id)
                    WHERE id = NEW.project_id;

                    -- Update project status based on services
                    SELECT COUNT(*) INTO v_total_srv FROM project_services WHERE project_id = NEW.project_id;
                    SELECT COUNT(*) INTO v_comp_srv FROM project_services WHERE project_id = NEW.project_id AND status = 'completed';
                    SELECT COUNT(*) INTO v_prog_srv FROM project_services WHERE project_id = NEW.project_id AND status = 'in_progress';

                    -- Only auto-update if not cancelled
                    IF (SELECT status FROM projects WHERE id = NEW.project_id) != 'cancelled' THEN
                        IF v_total_srv = 0 THEN
                            SET v_status = 'pending';
                        ELSEIF v_total_srv = v_comp_srv THEN
                            SET v_status = 'completed';
                        ELSEIF v_prog_srv > 0 OR v_comp_srv > 0 THEN
                            SET v_status = 'in_progress';
                        ELSE
                            SET v_status = 'pending';
                        END IF;

                        UPDATE projects SET status = v_status WHERE id = NEW.project_id;
                    END IF;
                END
            ");
        }
        addStatus("success", "triggers_services", "Advanced triggers on project_services (cost and status) OK.");

        // 9. Invoices
        $conn->exec("CREATE TABLE IF NOT EXISTS invoices (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            project_id  INT NOT NULL,
            client_id   INT NOT NULL,
            amount      DECIMAL(10,2) NOT NULL,
            due_date    DATE,
            issued_date DATE DEFAULT (CURRENT_DATE),
            status      ENUM('draft', 'sent', 'paid', 'overdue') NOT NULL DEFAULT 'draft',
            notes       TEXT,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
            INDEX (project_id),
            INDEX (client_id)
        ) ENGINE=InnoDB");
        addStatus("success", "invoices", "Table 'invoices' OK.");

        // 10. Payments
        $conn->exec("CREATE TABLE IF NOT EXISTS payments (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            project_id     INT NOT NULL,
            client_id      INT NOT NULL,
            invoice_id     INT NULL,
            amount         DECIMAL(10,2) NOT NULL,
            payment_date   DATE NOT NULL,
            payment_method ENUM('transfer', 'check', 'cash', 'card', 'other') DEFAULT 'transfer',
            notes          TEXT,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
            INDEX (project_id),
            INDEX (invoice_id)
        ) ENGINE=InnoDB");
        addStatus("success", "payments", "Table 'payments' OK.");

        // FINANCIAL & INVOICE TRIGGERS ON PAYMENTS
        foreach (['INSERT', 'UPDATE', 'DELETE'] as $event) {
            $ref    = ($event === 'DELETE') ? 'OLD' : 'NEW';
            $suffix = strtolower($event);
            $conn->exec("DROP TRIGGER IF EXISTS after_{$suffix}_payment_financials");
            $conn->exec("
                CREATE TRIGGER after_{$suffix}_payment_financials
                AFTER {$event} ON payments
                FOR EACH ROW
                BEGIN
                    -- Update Project remaining_amount
                    UPDATE projects SET
                        remaining_amount = total_cost - (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE project_id = {$ref}.project_id)
                    WHERE id = {$ref}.project_id;

                    -- Auto-update Invoice status if paid in full
                    IF {$ref}.invoice_id IS NOT NULL THEN
                        IF (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE invoice_id = {$ref}.invoice_id) >= 
                           (SELECT amount FROM invoices WHERE id = {$ref}.invoice_id) THEN
                            UPDATE invoices SET status = 'paid' WHERE id = {$ref}.invoice_id;
                        ELSE
                            UPDATE invoices SET status = 'sent' WHERE id = {$ref}.invoice_id AND status = 'paid';
                        END IF;
                    END IF;
                END
            ");
        }
        addStatus("success", "triggers_payments", "Financial triggers on payments (projects and invoices) OK.");

        // 11. Activity Logs (Polymorphic)
        $conn->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            action     ENUM('create', 'update', 'delete', 'login', 'logout') NOT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id  INT NOT NULL,
            old_value  JSON,
            new_value  JSON,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (entity_type, entity_id),
            INDEX (user_id)
        ) ENGINE=InnoDB");
        addStatus("success", "activity_logs", "Table 'activity_logs' OK.");

        // 12. Notifications
        $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     INT NOT NULL,
            type        ENUM('info', 'warning', 'error', 'success') NOT NULL,
            message     TEXT NOT NULL,
            is_read     BOOLEAN NOT NULL DEFAULT FALSE,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (is_read)
        ) ENGINE=InnoDB");
        addStatus("success", "notifications", "Table 'notifications' OK.");


        // CLEANUP: Drop old subtask tables
        $conn->exec("DROP TABLE IF EXISTS work_logs");
        $conn->exec("DROP TABLE IF EXISTS tache");
        addStatus("success", "cleanup", "Legacy subtask tables dropped.");

    } catch (PDOException $e) {
        addStatus("error", "general", "Process Error : " . $e->getMessage());
    }

    return json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// Logic to check if migration should run
if ((isset($_GET['page']) && $_GET['page'] === 'database') || (basename($_SERVER['PHP_SELF']) == 'migration.php')) {
    $result = createTables();
    if (php_sapi_name() === 'cli') {
        echo $result;
    } else {
        echo "<pre>" . $result . "</pre>";
    }
}