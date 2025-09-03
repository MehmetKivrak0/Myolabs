<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        $this->detectEnvironment();
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            if ($this->connection->connect_error) {
                throw new Exception("Bağlantı hatası: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
            
        } catch (Exception $e) {
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
            $this->host = 'sql106.epizy.com';
            $this->db_name = 'if0_39813695_myolab';
            $this->username = 'if0_39813695';
            $this->password = 'OydNXRS1I0cYUP';
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            if (!empty($params)) {
                $sql = $this->prepareQuery($sql, $params);
            }
            
            $result = $this->connection->query($sql);
            
            if ($result === false) {
                throw new Exception("Sorgu hatası: " . $this->connection->error);
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception("Sorgu hatası: " . $e->getMessage());
        }
    }
    
    private function prepareQuery($sql, $params) {
        // Basit parametre hazırlama (mysqli_prepare yerine)
        $escapedParams = [];
        foreach ($params as $param) {
            $escapedParams[] = $this->connection->real_escape_string($param);
        }
        
        // ? işaretlerini değiştir
        $count = 0;
        return preg_replace_callback('/\?/', function() use (&$count, $escapedParams) {
            return "'" . $escapedParams[$count++] . "'";
        }, $sql);
    }
    
    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result->fetch_assoc();
    }
    
    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    
    public function execute($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $this->connection->affected_rows;
    }
    
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
        $this->connection = null;
        self::$instance = null;
    }
}

?>
