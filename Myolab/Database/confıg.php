<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    private $pdo;
    private static $instance = null;
    
    private function __construct() {
        $this->detectEnvironment();
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $this->pdo = new PDO($dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            throw new Exception("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    private function detectEnvironment() {
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        $http_host = $_SERVER['HTTP_HOST'] ?? '';
        $document_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        
        if (strpos($server_name, 'localhost') !== false || 
            strpos($http_host, 'localhost') !== false || 
            strpos($server_name, '127.0.0.1') !== false ||
            strpos($http_host, '127.0.0.1') !== false ||
            strpos($document_root, 'wamp64') !== false ||
            strpos($document_root, 'xampp') !== false) {
            $this->host = 'localhost';
            $this->db_name = 'myolab';
            $this->username = 'root';
            $this->password = '';
        } else { // Okul sunucusu bilgileri buraya girilecek
            $this->host = '192.168.1.100';
            $this->db_name = 'myolab_production';
            $this->username = 'myolab_user';
            $this->password = 'secure_password_123';
        }
    }
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function getConnection() {
        return $this->pdo;
    }
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Sorgu hatası: " . $e->getMessage());
        }
    }
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    public function commit() {
        return $this->pdo->commit();
    }
    public function rollback() {
        return $this->pdo->rollback();
    }
    public function close() {
        $this->pdo = null;
        self::$instance = null;
    }
}

?>
