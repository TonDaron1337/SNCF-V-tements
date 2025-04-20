<?php
class Database {
    private $host = "localhost";
    private $db_name = "sncf_vetements";
    private $username = "root";
    private $password = "plopplip";
    private $conn;

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Définir l'URL de base pour les redirections
            define('BASE_URL', 'http://163.172.223.91/');
            
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Erreur de connexion : " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
        }
    }
}