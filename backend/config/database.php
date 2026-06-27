<?php
class Database {
    // === USE YOUR DATABASE CREDENTIALS ===
    private $host = "sql212.infinityfree.com";
    private $db_name = "if0_42284197_staysmart_db";
    private $username = "if0_42284197";
    private $password = "oyatNAOUEYMSr";
    
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                  $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
