<?php
/**
 * Database connection helper.
 * Update the connection settings below to match your environment.
 */
class Database {
    private $host = '127.0.0.1';
    private $db   = 'auth_system';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';
    private $pdo = null;

    public function getConnection() {
        if ($this->pdo) return $this->pdo;

        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            return $this->pdo;
        } catch (PDOException $e) {
            // Friendly message — change for production as needed
            echo "DB Connection Error: " . $e->getMessage();
            exit;
        }
    }
}

?>
