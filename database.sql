CREATE DATABASE IF NOT EXISTS gestion_de_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_de_project;

-- 1. Admin Table
CREATE TABLE IF NOT EXISTS admin (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100)  NOT NULL UNIQUE,
    name       VARCHAR(255)  NOT NULL,
    password   VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Clients Table
CREATE TABLE IF NOT EXISTS clients (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    phone      VARCHAR(50),
    email      VARCHAR(255) NOT NULL UNIQUE,
    address    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (email)
) ENGINE=InnoDB;

-- 3. Projects
CREATE TABLE IF NOT EXISTS projects (
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
    INDEX (client_id),
    INDEX (pack_id),
    INDEX (status)
) ENGINE=InnoDB;

-- 4. Tasks
CREATE TABLE IF NOT EXISTS tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    start_date  DATE,
    end_date    DATE,
    priority    ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    status      ENUM('todo', 'in_progress', 'done', 'overdue') NOT NULL DEFAULT 'todo',
    total_hours DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    total_cost  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX (project_id)
) ENGINE=InnoDB;

-- FINANCIAL TRIGGERS ON TASKS -> PROJECTS
DELIMITER $$

CREATE TRIGGER after_insert_task_financials
AFTER INSERT ON tasks
FOR EACH ROW
BEGIN
    UPDATE projects SET
        total_cost       = (SELECT IFNULL(SUM(total_cost), 0) FROM tasks WHERE project_id = NEW.project_id),
        remaining_amount = (SELECT IFNULL(SUM(total_cost), 0) FROM tasks WHERE project_id = NEW.project_id)
                         - (SELECT IFNULL(SUM(amount), 0)     FROM payments WHERE project_id = NEW.project_id)
    WHERE id = NEW.project_id;
END$$

CREATE TRIGGER after_update_task_financials
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    UPDATE projects SET
        total_cost       = (SELECT IFNULL(SUM(total_cost), 0) FROM tasks WHERE project_id = NEW.project_id),
        remaining_amount = (SELECT IFNULL(SUM(total_cost), 0) FROM tasks WHERE project_id = NEW.project_id)
                         - (SELECT IFNULL(SUM(amount), 0)     FROM payments WHERE project_id = NEW.project_id)
    WHERE id = NEW.project_id;
END$$

CREATE TRIGGER after_delete_task_financials
AFTER DELETE ON tasks
FOR EACH ROW
BEGIN
    UPDATE projects SET
        total_cost       = (SELECT IFNULL(SUM(total_cost), 0) FROM tasks WHERE project_id = OLD.project_id),
        remaining_amount = (SELECT IFNULL(SUM(total_cost), 0) FROM tasks WHERE project_id = OLD.project_id)
                         - (SELECT IFNULL(SUM(amount), 0)     FROM payments WHERE project_id = OLD.project_id)
    WHERE id = OLD.project_id;
END$$

DELIMITER ;

-- 5. Invoices
CREATE TABLE IF NOT EXISTS invoices (
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
) ENGINE=InnoDB;

-- 6. Payments
CREATE TABLE IF NOT EXISTS payments (
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
) ENGINE=InnoDB;

-- FINANCIAL TRIGGERS ON PAYMENTS -> PROJECTS
DELIMITER $$

CREATE TRIGGER after_insert_payment_financials
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    UPDATE projects SET
        remaining_amount = total_cost - (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE project_id = NEW.project_id)
    WHERE id = NEW.project_id;
END$$

CREATE TRIGGER after_update_payment_financials
AFTER UPDATE ON payments
FOR EACH ROW
BEGIN
    UPDATE projects SET
        remaining_amount = total_cost - (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE project_id = NEW.project_id)
    WHERE id = NEW.project_id;
END$$

CREATE TRIGGER after_delete_payment_financials
AFTER DELETE ON payments
FOR EACH ROW
BEGIN
    UPDATE projects SET
        remaining_amount = total_cost - (SELECT IFNULL(SUM(amount), 0) FROM payments WHERE project_id = OLD.project_id)
    WHERE id = OLD.project_id;
END$$

DELIMITER ;

-- 7. Activity Logs (Polymorphic)
CREATE TABLE IF NOT EXISTS activity_logs (
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
) ENGINE=InnoDB;

-- 8. Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        ENUM('info', 'warning', 'error', 'success') NOT NULL,
    message     TEXT NOT NULL,
    is_read     BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (is_read)
) ENGINE=InnoDB;

-- 9. Servers
CREATE TABLE IF NOT EXISTS servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(100),
    description TEXT,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX (project_id)
) ENGINE=InnoDB;

-- 10. Packs (templates, independent from projects)
CREATE TABLE IF NOT EXISTS packs (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(255) NOT NULL,
    description      TEXT,
    total_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 11. Pack Services (services inside a pack)
CREATE TABLE IF NOT EXISTS pack_services (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    pack_id          INT NOT NULL,
    name             VARCHAR(255) NOT NULL,
    description      TEXT,
    price            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pack_id) REFERENCES packs(id) ON DELETE CASCADE,
    INDEX (pack_id)
) ENGINE=InnoDB;
