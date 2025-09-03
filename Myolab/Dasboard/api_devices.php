<?php
// CORS Headers - En başta ve güçlü
if (function_exists('header')) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    header('Content-Type: application/json; charset=utf8mb4');
}

// OPTIONS request için preflight kontrolü - En başta
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (function_exists('http_response_code')) {
        http_response_code(200);
    }
    if (function_exists('header')) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }
    exit();
}

// Hata raporlamayı aç (debug için)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Sadece POST, PUT, DELETE işlemleri için oturum kontrolü (geçici olarak kapatıldı)
$method = $_SERVER['REQUEST_METHOD'];
/*
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
        exit();
    }
}
*/

try {
    require_once '../Database/config.php';
    $database = Database::getInstance();
    $mysqli = $database->getConnection();
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($method) {
    case 'GET':
        if ($action === 'delete') {
            // GET ile cihaz silme (InfinityFree hosting için)
            $deviceId = $_GET['id'] ?? null;
            
            if (!$deviceId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cihaz ID gereklidir']);
                exit();
            }
            
            error_log('GET delete called for device ID: ' . $deviceId);
            
            try {
                // Önce resimleri sil
                $sql = "DELETE FROM devices_images WHERE devices_id = '" . $mysqli->real_escape_string($deviceId) . "'";
                $mysqli->query($sql);
                
                // Sonra cihazı sil
                $sql = "DELETE FROM devices WHERE id = '" . $mysqli->real_escape_string($deviceId) . "'";
                
                if (!$mysqli->query($sql)) {
                    throw new Exception("Silme hatası: " . $mysqli->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Cihaz başarıyla silindi']);
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihaz silinirken hata oluştu: ' . $e->getMessage()]);
            }
        } else if ($action === 'update') {
            // GET ile cihaz güncelleme (InfinityFree hosting için)
            error_log('GET update called with params: ' . json_encode($_GET));
            
            $deviceId = $_GET['id'] ?? null;
            $labId = $_GET['lab_id'] ?? null;
            $deviceName = $_GET['device_name'] ?? null;
            $deviceModel = $_GET['device_model'] ?? '';
            $deviceCount = $_GET['device_count'] ?? null;
            $purpose = $_GET['purpose'] ?? '';
            $orderNum = $_GET['order_num'] ?? 0;
            $imageUrl = $_GET['image_url'] ?? null;
            
            if (!$deviceId || !$labId || !$deviceName || !$deviceCount) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Gerekli alanlar eksik']);
                exit();
            }
            
            error_log('GET update called for device ID: ' . $deviceId);
            
            try {
                // Cihazı güncelle
                $sql = "UPDATE devices SET lab_id = '" . $mysqli->real_escape_string($labId) . "', 
                        device_name = '" . $mysqli->real_escape_string(trim($deviceName)) . "', 
                        device_model = '" . $mysqli->real_escape_string($deviceModel) . "', 
                        device_count = '" . $mysqli->real_escape_string($deviceCount) . "', 
                        purpose = '" . $mysqli->real_escape_string($purpose) . "', 
                        order_num = '" . $mysqli->real_escape_string($orderNum) . "' 
                        WHERE id = '" . $mysqli->real_escape_string($deviceId) . "'";
                
                if (!$mysqli->query($sql)) {
                    throw new Exception("Güncelleme hatası: " . $mysqli->error);
                }
                
                // Eğer resim URL'i değiştiyse devices_images tablosunu güncelle
                if (isset($imageUrl)) {
                    // Önce eski resimleri sil
                    $sql = "DELETE FROM devices_images WHERE devices_id = '" . $mysqli->real_escape_string($deviceId) . "'";
                    $mysqli->query($sql);
                    
                    // Yeni resmi ekle
                    if (!empty($imageUrl)) {
                        $sql = "INSERT INTO devices_images (devices_id, url, alt_text, order_num, added_by) VALUES ('" . 
                               $mysqli->real_escape_string($deviceId) . "', '" . 
                               $mysqli->real_escape_string($imageUrl) . "', '" . 
                               $mysqli->real_escape_string($_GET['alt_text'] ?? '') . "', '" . 
                               $mysqli->real_escape_string($_GET['image_order'] ?? 0) . "', '" . 
                               $mysqli->real_escape_string($_SESSION['username'] ?? 'unknown') . "')";
                        
                        if (!$mysqli->query($sql)) {
                            error_log("Resim ekleme hatası: " . $mysqli->error);
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Cihaz başarıyla güncellendi']);
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihaz güncellenirken hata oluştu: ' . $e->getMessage()]);
            }
        } else if ($action === 'get_by_id') {
            // Belirli bir cihazı getir
            $deviceId = $_GET['id'] ?? null;
            
            if (!$deviceId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cihaz ID gereklidir']);
                exit();
            }
            
            try {
                $sql = "
                    SELECT d.*, ei.url as image_url 
                    FROM devices d 
                    LEFT JOIN devices_images ei ON d.id = ei.devices_id 
                    WHERE d.id = '" . $mysqli->real_escape_string($deviceId) . "'
                ";
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Sorgu hatası: " . $mysqli->error);
                }
                
                $device = $result->fetch_assoc();
                
                if ($device) {
                    echo json_encode(['success' => true, 'device' => $device]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Cihaz bulunamadı']);
                }
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihaz getirilirken hata oluştu: ' . $e->getMessage()]);
            }
        } else {
            // Laboratuvar cihazlarını listele (action parametresi olmadan da çalışsın)
            $labId = $_GET['lab_id'] ?? null;
            
            if (!$labId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
                exit();
            }
            
            try {
                // Debug bilgisi
                error_log('GET devices for lab_id: ' . $labId . ' at ' . date('Y-m-d H:i:s'));
                
                // Önce devices tablosunu kontrol et
                $sql = "SELECT COUNT(*) as count FROM devices WHERE lab_id = '" . $mysqli->real_escape_string($labId) . "'";
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Kontrol hatası: " . $mysqli->error);
                }
                
                $row = $result->fetch_assoc();
                $deviceCount = $row['count'];
                error_log('Raw device count for lab_id ' . $labId . ': ' . $deviceCount . ' at ' . date('Y-m-d H:i:s'));
                
                // Cihazları ve resimlerini birlikte getir
                $sql = "
                    SELECT d.*, ei.url as image_url 
                    FROM devices d 
                    LEFT JOIN devices_images ei ON d.id = ei.devices_id 
                    WHERE d.lab_id = '" . $mysqli->real_escape_string($labId) . "' 
                    ORDER BY d.order_num ASC, d.created_at ASC
                ";
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Cihaz listesi hatası: " . $mysqli->error);
                }
                
                $devices = [];
                while ($row = $result->fetch_assoc()) {
                    $devices[] = $row;
                }
                
                error_log('Found ' . count($devices) . ' devices for lab_id: ' . $labId);
                
                // Her cihaz için debug bilgisi
                foreach ($devices as $device) {
                    error_log('Device: ID=' . $device['id'] . ', Name=' . $device['device_name'] . ', Image=' . ($device['image_url'] ?? 'NULL'));
                }
                
                echo json_encode(['success' => true, 'devices' => $devices]);
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihazlar listelenirken hata oluştu: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'POST':
        // Yeni cihaz ekle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['lab_id']) || !isset($input['device_name']) || 
            !isset($input['device_count']) || empty(trim($input['device_name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID, cihaz adı ve sayısı gereklidir']);
            exit();
        }
        
        try {
            // Laboratuvar var mı kontrol et
            $sql = "SELECT id FROM laboratories WHERE id = '" . $mysqli->real_escape_string($input['lab_id']) . "'";
            $result = $mysqli->query($sql);
            
            if ($result === false) {
                throw new Exception("Laboratuvar kontrol hatası: " . $mysqli->error);
            }
            
            if (!$result->fetch_assoc()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz laboratuvar ID']);
                exit();
            }
            
            // Cihazı ekle
            $sql = "INSERT INTO devices (lab_id, device_name, device_model, device_count, purpose, order_num) VALUES ('" . 
                   $mysqli->real_escape_string($input['lab_id']) . "', '" . 
                   $mysqli->real_escape_string(trim($input['device_name'])) . "', '" . 
                   $mysqli->real_escape_string($input['device_model'] ?? '') . "', '" . 
                   $mysqli->real_escape_string($input['device_count']) . "', '" . 
                   $mysqli->real_escape_string($input['purpose'] ?? '') . "', '" . 
                   $mysqli->real_escape_string($input['order_num'] ?? 0) . "')";
            
            if (!$mysqli->query($sql)) {
                throw new Exception("Cihaz ekleme hatası: " . $mysqli->error);
            }
            
            $deviceId = $mysqli->insert_id;
            
            // Eğer resim URL'i varsa devices_images tablosuna ekle
            if (!empty($input['image_url'])) {
                $sql = "INSERT INTO devices_images (devices_id, url, alt_text, order_num, added_by) VALUES ('" . 
                       $mysqli->real_escape_string($deviceId) . "', '" . 
                       $mysqli->real_escape_string($input['image_url']) . "', '" . 
                       $mysqli->real_escape_string($input['alt_text'] ?? '') . "', '" . 
                       $mysqli->real_escape_string($input['image_order'] ?? 0) . "', '" . 
                       $mysqli->real_escape_string($_SESSION['username'] ?? 'unknown') . "')";
                
                if (!$mysqli->query($sql)) {
                    error_log("Resim ekleme hatası: " . $mysqli->error);
                }
            }
            
            // Eklenen cihazı döndür
            $sql = "SELECT * FROM devices WHERE id = '" . $mysqli->real_escape_string($deviceId) . "'";
            $result = $mysqli->query($sql);
            $newDevice = $result->fetch_assoc();
            
            echo json_encode(['success' => true, 'message' => 'Cihaz başarıyla eklendi', 'data' => $newDevice]);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Cihaz eklenirken hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Cihaz güncelle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || !isset($input['lab_id']) || !isset($input['device_name']) || 
            !isset($input['device_count']) || empty(trim($input['device_name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cihaz ID, laboratuvar ID, cihaz adı ve sayısı gereklidir']);
            exit();
        }
        
        try {
            // Cihazı güncelle
            $sql = "UPDATE devices SET lab_id = '" . $mysqli->real_escape_string($input['lab_id']) . "', 
                    device_name = '" . $mysqli->real_escape_string(trim($input['device_name'])) . "', 
                    device_model = '" . $mysqli->real_escape_string($input['device_model'] ?? '') . "', 
                    device_count = '" . $mysqli->real_escape_string($input['device_count']) . "', 
                    purpose = '" . $mysqli->real_escape_string($input['purpose'] ?? '') . "', 
                    order_num = '" . $mysqli->real_escape_string($input['order_num'] ?? 0) . "' 
                    WHERE id = '" . $mysqli->real_escape_string($input['id']) . "'";
            
            if (!$mysqli->query($sql)) {
                throw new Exception("Güncelleme hatası: " . $mysqli->error);
            }
            
            // Eğer resim URL'i değiştiyse devices_images tablosunu güncelle
            if (isset($input['image_url'])) {
                // Önce eski resimleri sil
                $sql = "DELETE FROM devices_images WHERE devices_id = '" . $mysqli->real_escape_string($input['id']) . "'";
                $mysqli->query($sql);
                
                // Yeni resmi ekle
                if (!empty($input['image_url'])) {
                    $sql = "INSERT INTO devices_images (devices_id, url, alt_text, order_num, added_by) VALUES ('" . 
                           $mysqli->real_escape_string($input['id']) . "', '" . 
                           $mysqli->real_escape_string($input['image_url']) . "', '" . 
                           $mysqli->real_escape_string($input['alt_text'] ?? '') . "', '" . 
                           $mysqli->real_escape_string($input['image_order'] ?? 0) . "', '" . 
                           $mysqli->real_escape_string($_SESSION['username'] ?? 'unknown') . "')";
                    
                    if (!$mysqli->query($sql)) {
                        error_log("Resim ekleme hatası: " . $mysqli->error);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Cihaz başarıyla güncellendi']);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Cihaz güncellenirken hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Debug bilgisi
        error_log('DELETE method called - Action: ' . ($action ?? 'none'));
        error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
        error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
        
        if ($action === 'delete_multiple') {
            // Birden fazla cihazı sil
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['device_ids']) || !is_array($input['device_ids']) || empty($input['device_ids'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Silinecek cihaz ID\'leri gereklidir']);
                exit();
            }
            
            try {
                // Transaction başlat
                $mysqli->begin_transaction();
                
                $deviceIds = array_map(function($id) use ($mysqli) {
                    return "'" . $mysqli->real_escape_string($id) . "'";
                }, $input['device_ids']);
                
                $placeholders = implode(',', $deviceIds);
                
                // Önce resimleri sil
                $sql = "DELETE FROM devices_images WHERE devices_id IN ($placeholders)";
                if (!$mysqli->query($sql)) {
                    throw new Exception("Resim silme hatası: " . $mysqli->error);
                }
                
                // Sonra cihazları sil
                $sql = "DELETE FROM devices WHERE id IN ($placeholders)";
                if (!$mysqli->query($sql)) {
                    throw new Exception("Cihaz silme hatası: " . $mysqli->error);
                }
                
                // Transaction'ı onayla
                $mysqli->commit();
                
                echo json_encode(['success' => true, 'message' => 'Seçilen cihazlar başarıyla silindi']);
            } catch(Exception $e) {
                // Hata durumunda rollback yap
                $mysqli->rollback();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihazlar silinirken hata oluştu: ' . $e->getMessage()]);
            }
        } else {
            // Tek cihazı sil
            error_log('Single device delete called');
            $input = json_decode(file_get_contents('php://input'), true);
            error_log('Raw input: ' . file_get_contents('php://input'));
            error_log('Decoded input: ' . json_encode($input));
            
            if (!isset($input['id'])) {
                error_log('Device ID missing');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cihaz ID gereklidir']);
                exit();
            }
            
            error_log('Deleting device ID: ' . $input['id']);
            
            try {
                // Önce resimleri sil
                $sql = "DELETE FROM devices_images WHERE devices_id = '" . $mysqli->real_escape_string($input['id']) . "'";
                $mysqli->query($sql);
                
                // Sonra cihazı sil
                $sql = "DELETE FROM devices WHERE id = '" . $mysqli->real_escape_string($input['id']) . "'";
                
                if (!$mysqli->query($sql)) {
                    throw new Exception("Silme hatası: " . $mysqli->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Cihaz başarıyla silindi']);
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihaz silinirken hata oluştu: ' . $e->getMessage()]);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
