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
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $tablesToDrop = [
            'pack_services', 'packs', 'notifications', 'activity_logs', 'payments', 'invoices', 
            'tasks', 'services', 'projects', 'clients', 'admin'
        ];
        foreach ($tablesToDrop as $table) {
            $conn->exec("DROP TABLE IF EXISTS $table");
        }
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
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

        // 3. Packs (templates, independent from projects)
        $conn->exec("CREATE TABLE IF NOT EXISTS packs (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            name             VARCHAR(255) NOT NULL,
            description      TEXT,
            total_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        addStatus("success", "packs", "Table 'packs' OK.");

        // 4. Pack Services (services inside a pack)
        $conn->exec("CREATE TABLE IF NOT EXISTS pack_services (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            pack_id          INT NOT NULL,
            name             VARCHAR(255) NOT NULL,
            description      TEXT,
            price            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (pack_id) REFERENCES packs(id) ON DELETE CASCADE,
            INDEX (pack_id)
        ) ENGINE=InnoDB");
        addStatus("success", "pack_services", "Table 'pack_services' OK.");

        // TRIGGER: Auto-update pack.total_price when pack_services change
        foreach (['INSERT', 'UPDATE', 'DELETE'] as $event) {
            $ref    = ($event === 'DELETE') ? 'OLD' : 'NEW';
            $suffix = strtolower($event);
            $conn->exec("DROP TRIGGER IF EXISTS after_{$suffix}_pack_service_price");
            $conn->exec("
                CREATE TRIGGER after_{$suffix}_pack_service_price
                AFTER {$event} ON pack_services
                FOR EACH ROW
                BEGIN
                    UPDATE packs 
                    SET total_price = (
                        SELECT IFNULL(SUM(price), 0) 
                        FROM pack_services 
                        WHERE pack_id = {$ref}.pack_id
                    )
                    WHERE id = {$ref}.pack_id;
                END
            ");
        }
        addStatus("success", "triggers_pack_services", "Triggers on pack_services (total_price) OK.");

        // 5. Projects (with optional pack_id)
        $conn->exec("CREATE TABLE IF NOT EXISTS projects (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            name             VARCHAR(255) NOT NULL,
            description      TEXT,
            start_date       DATE,
            end_date         DATE,
            status           ENUM('pending', 'in_progress', 'completed', 'cancelled', 'overdue') NOT NULL DEFAULT 'pending',
            total_cost       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            remaining_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            client_id        INT NOT NULL,
            pack_id          INT NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
            FOREIGN KEY (pack_id) REFERENCES packs(id) ON DELETE SET NULL,
            INDEX (status),
            INDEX (pack_id)
        ) ENGINE=InnoDB");
        addStatus("success", "projects", "Table 'projects' OK.");

        // 4. Services (New Table)
        $conn->exec("CREATE TABLE IF NOT EXISTS services (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            project_id       INT NOT NULL,
            name             VARCHAR(255) NOT NULL,
            price            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status           ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX (project_id)
        ) ENGINE=InnoDB");
        addStatus("success", "services", "Table 'services' OK.");

        // 5. Tasks (Linked to Service)
        $conn->exec("CREATE TABLE IF NOT EXISTS tasks (
            id                 INT AUTO_INCREMENT PRIMARY KEY,
            service_id         INT NOT NULL,
            title              VARCHAR(255) NOT NULL,
            description        TEXT,
            start_date         DATE,
            end_date           DATE,
            priority           ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
            status             ENUM('todo', 'in_progress', 'done', 'overdue') NOT NULL DEFAULT 'todo',
            total_hours        DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
            total_cost         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            INDEX (service_id)
        ) ENGINE=InnoDB");
        addStatus("success", "tasks", "Table 'tasks' OK.");

        // FINANCIAL & STATUS TRIGGERS: TASKS -> SERVICES
        foreach (['INSERT', 'UPDATE', 'DELETE'] as $event) {
            $ref    = ($event === 'DELETE') ? 'OLD' : 'NEW';
            $suffix = strtolower($event);
            
            $conn->exec("DROP TRIGGER IF EXISTS after_{$suffix}_task_financials");
            $conn->exec("
                CREATE TRIGGER after_{$suffix}_task_financials
                AFTER {$event} ON tasks
                FOR EACH ROW
                BEGIN
                    DECLARE v_status ENUM('pending', 'in_progress', 'completed');
                    DECLARE v_total_tasks INT;
                    DECLARE v_done_tasks INT;
                    DECLARE v_in_progress_tasks INT;

                    -- Update parent Service price based on tasks
                    UPDATE services 
                    SET price = (SELECT IFNULL(SUM(total_cost), 0) FROM tasks WHERE service_id = {$ref}.service_id)
                    WHERE id = {$ref}.service_id;

                    -- Calc statuses
                    SELECT COUNT(*) INTO v_total_tasks FROM tasks WHERE service_id = {$ref}.service_id;
                    SELECT COUNT(*) INTO v_done_tasks FROM tasks WHERE service_id = {$ref}.service_id AND status = 'done';
                    SELECT COUNT(*) INTO v_in_progress_tasks FROM tasks WHERE service_id = {$ref}.service_id AND status = 'in_progress';

                    IF v_total_tasks = 0 THEN
                        SET v_status = 'pending';
                    ELSEIF v_total_tasks = v_done_tasks THEN
                        SET v_status = 'completed';
                    ELSEIF v_in_progress_tasks > 0 OR v_done_tasks > 0 THEN
                        SET v_status = 'in_progress';
                    ELSE
                        SET v_status = 'pending';
                    END IF;

                    UPDATE services SET status = v_status WHERE id = {$ref}.service_id;
                END
            ");
        }
        addStatus("success", "triggers_tasks", "Financial and status triggers on tasks (cascading to services) OK.");

        // FINANCIAL TRIGGERS: SERVICES -> PROJECTS
        foreach (['INSERT', 'UPDATE', 'DELETE'] as $event) {
            $ref    = ($event === 'DELETE') ? 'OLD' : 'NEW';
            $suffix = strtolower($event);
            
            $conn->exec("DROP TRIGGER IF EXISTS after_{$suffix}_service_financials");
            $conn->exec("
                CREATE TRIGGER after_{$suffix}_service_financials
                AFTER {$event} ON services
                FOR EACH ROW
                BEGIN
                    -- Update project totals based on services
                    UPDATE projects SET
                        total_cost       = (SELECT IFNULL(SUM(price), 0) FROM services WHERE project_id = {$ref}.project_id),
                        remaining_amount = (SELECT IFNULL(SUM(price), 0) FROM services WHERE project_id = {$ref}.project_id)
                                         - (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE project_id = {$ref}.project_id)
                    WHERE id = {$ref}.project_id;

                    -- Update project status based on services
                    BEGIN
                        DECLARE v_proj_status ENUM('pending', 'in_progress', 'completed', 'cancelled');
                        DECLARE v_total_srv INT;
                        DECLARE v_comp_srv INT;
                        DECLARE v_prog_srv INT;

                        SELECT COUNT(*) INTO v_total_srv FROM services WHERE project_id = {$ref}.project_id;
                        SELECT COUNT(*) INTO v_comp_srv FROM services WHERE project_id = {$ref}.project_id AND status = 'completed';
                        SELECT COUNT(*) INTO v_prog_srv FROM services WHERE project_id = {$ref}.project_id AND status = 'in_progress';

                        IF (SELECT status FROM projects WHERE id = {$ref}.project_id) != 'cancelled' THEN
                            IF v_total_srv = 0 THEN
                                SET v_proj_status = 'pending';
                            ELSEIF v_total_srv = v_comp_srv THEN
                                SET v_proj_status = 'completed';
                            ELSEIF v_prog_srv > 0 OR v_comp_srv > 0 THEN
                                SET v_proj_status = 'in_progress';
                            ELSE
                                SET v_proj_status = 'pending';
                            END IF;

                            UPDATE projects SET status = v_proj_status WHERE id = {$ref}.project_id;
                        END IF;
                    END;
                END
            ");
        }
        addStatus("success", "triggers_services", "Financial triggers on services (cascading to projects) OK.");


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

        // CLEANUP: Drop old tables
        $conn->exec("DROP TABLE IF EXISTS work_logs");
        $conn->exec("DROP TABLE IF EXISTS tache");
        $conn->exec("DROP TABLE IF EXISTS offers");
        $conn->exec("DROP TABLE IF EXISTS offer_servers");
        addStatus("success", "cleanup", "Legacy tables dropped.");

    } catch (PDOException $e) {
        addStatus("error", "general", "Process Error : " . $e->getMessage());
    }

    return json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}