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
        $this->establishConnection();
    }
    
    private function establishConnection() {
        $hosts = [$this->host];
        
        // Eğer production ortamındaysak alternatif sunucuları da dene
        if (strpos($this->host, 'epizy.com') !== false) {
            $hosts = [
                'sql106.epizy.com',
                'sql107.epizy.com', 
                'sql108.epizy.com',
                'sql109.epizy.com',
                'sql110.epizy.com'
            ];
        }
        
        $lastError = '';
        
        foreach ($hosts as $host) {
            try {
                error_log("Trying to connect to: $host");
                $this->connection = new mysqli($host, $this->username, $this->password, $this->db_name);
                
                if ($this->connection->connect_error) {
                    $lastError = "Bağlantı hatası ($host): " . $this->connection->connect_error;
                    error_log($lastError);
                    continue; // Sonraki sunucuyu dene
                }
                
                $this->connection->set_charset("utf8mb4");
                error_log("Successfully connected to: $host");
                return; // Başarılı bağlantı
                
            } catch (Exception $e) {
                $lastError = "Bağlantı hatası ($host): " . $e->getMessage();
                error_log($lastError);
                continue; // Sonraki sunucuyu dene
            }
        }
        
        // Tüm sunucular başarısız oldu
        throw new Exception("Tüm veritabanı sunucularına bağlanılamadı. Son hata: " . $lastError);
    }
    
    private function detectEnvironment() {
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        $http_host = $_SERVER['HTTP_HOST'] ?? '';
        $document_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        
        // Debug için log ekle
        error_log("Environment Detection - Server: $server_name, Host: $http_host, Root: $document_root");
        
        if (strpos($server_name, 'localhost') !== false || 
            strpos($http_host, 'localhost') !== false || 
            strpos($server_name, '127.0.0.1') !== false ||
            strpos($http_host, '127.0.0.1') !== false ||
            strpos($document_root, 'wamp64') !== false ||
            strpos($document_root, 'xampp') !== false) {
            // Local development
            $this->host = 'localhost';
            $this->db_name = 'myolab';
            $this->username = 'root';
            $this->password = '';
            error_log("Using LOCAL database configuration");
        } else { 
            // Production - InfinityFree hosting
            $this->host = 'sql106.epizy.com'; // İlk sunucu (establishConnection'da alternatifler denenir)
            $this->db_name = 'if0_39813695_myolab';
            $this->username = 'if0_39813695';
            $this->password = 'OydNXRS1I0cYUP';
            error_log("Using PRODUCTION database configuration: " . $this->host);
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
