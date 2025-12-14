<?php

class Database
{

    private $host;
    private $username;
    private $password;
    private $dbname;

    private static $instance = null;

    public function __construct()
    {
        // Load from environment variables or use defaults (for dev)
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->dbname = getenv('DB_NAME') ?: 'tutor';
    }

    public function connect()
    {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_PERSISTENT => false // Set to true if needed for performance
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                die("Database connection failed. Please contact support.");
            }
        }
        return self::$instance;
    }
}