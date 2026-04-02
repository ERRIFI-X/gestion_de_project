<?php


require_once './routes/config.php';

$status = [];
function createDatabase()
{
    try {
        $conn = getPDOConnectionDB(false); // Connect without specifying the database
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
        $conn->exec($sql);

        $status[] = ["status" => "success", "database" => "Base de données créée avec succès ou déjà existante.<br>"];
    } catch (PDOException $e) {
        $status[] = ["status" => "error", "database" => "Erreur lors de la création de la base de données : " . $e->getMessage()];
    }
}

function createTables()
{
    try {
        createDatabase();
        $conn = getPDOConnectionDB();


        $sqlClients = "CREATE TABLE IF NOT EXISTS clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            phone varchar(20) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE
        )";
        $conn->exec($sqlClients);
        $status[] = ["status" => "success", "clients" => "Table 'clients' créée avec succès ou déjà existante"];

        $sqlProjects = "CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            start_date DATE,
            end_date DATE,
            client_id INT,
            remaining_amount DECIMAL(10,2) DEFAULT 0;
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
        )";
        $conn->exec($sqlProjects);
        $status[] = ["status" => "success", "projects" => "Table 'projects' créée avec succès ou déjà existante"];

        $sqlTasks = "CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            start_date DATE,
            end_date DATE,
            total_hours INT,
            total_cost DECIMAL(10, 2),
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        )";
        $conn->exec($sqlTasks);
        $status[] = ["status" => "success", "tasks" => "Table 'tasks' créée avec succès ou déjà existante"];

        $sqlTache = "CREATE TABLE IF NOT EXISTS tache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_id INT NOT NULL,
            hours_worked INT,
            cost DECIMAL(10, 2),
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
        )";
        $conn->exec($sqlTache);
        $status[] = ["status" => "success", "tache" => "Table 'tache' créée avec succès ou déjà existante"];
        $conn->exec("
                    CREATE TRIGGER IF NOT EXISTS after_insert_tache
                    AFTER INSERT ON tache
                    FOR EACH ROW
                    BEGIN
                        UPDATE tasks
                        SET total_cost = (
                            SELECT IFNULL(SUM(cost), 0)
                            FROM tache
                            WHERE task_id = NEW.task_id
                        )
                        WHERE id = NEW.task_id;
                    END;
                ");

        $conn->exec("
            CREATE TRIGGER IF NOT EXISTS after_update_tache
            AFTER UPDATE ON tache
            FOR EACH ROW
            BEGIN
                UPDATE tasks
                SET total_cost = (
                    SELECT IFNULL(SUM(cost), 0)
                    FROM tache
                    WHERE task_id = NEW.task_id
                )
                WHERE id = NEW.task_id;
            END;
        ");

        $conn->exec("
                CREATE TRIGGER IF NOT EXISTS after_delete_tache
                AFTER DELETE ON tache
                FOR EACH ROW
                BEGIN
                    UPDATE tasks
                    SET total_cost = (
                        SELECT IFNULL(SUM(cost), 0)
                        FROM tache
                        WHERE task_id = OLD.task_id
                    )
                    WHERE id = OLD.task_id;
                END;
            ");


        $sqlPayments = "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            client_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            payment_date DATE,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        )";
        $conn->exec($sqlPayments);
        $status[] = ["status" => "success", "payments" => "Table 'payments' créée avec succès ou déjà existante"];

        $conn->exec("
            CREATE TRIGGER after_insert_payment
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    UPDATE projects
    SET remaining_amount = (
        total_cost - (
            SELECT IFNULL(SUM(amount), 0)
            FROM payments
            WHERE project_id = NEW.project_id
        )
    )
    WHERE id = NEW.project_id;
END;
        ");

        $sqlAdmin = "CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->exec($sqlAdmin);
        $status[] = ["status" => "success", "admin" => "Table 'admin' créée avec succès ou déjà existante"];
    } catch (PDOException $e) {
        $status[] = ["status" => "error", "admin" => "Erreur lors de la création de la table 'admin' : " . $e->getMessage()];
    }
    return json_encode($status);
}
