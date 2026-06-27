<?php
class Database {
    // === RENDER POSTGRESQL CREDENTIALS ===
    private $host = "dpg-xxxxx.onrender.com";  // Your Render PostgreSQL host
    private $db_name = "staysmart_db";
    private $username = "staysmart_user";
    private $password = "your_password";
    private $port = "5432";
    
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // PostgreSQL connection
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
