<?php
// Hata raporlamayı kapat (production için)
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

// Sadece POST, PUT, DELETE işlemleri için oturum kontrolü
$method = $_SERVER['REQUEST_METHOD'];
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
        exit();
    }
}

try {
    require_once '../Database/confıg.php';
    $database = Database::getInstance();
    $pdo = $database->getConnection();
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($method) {
    case 'GET':
        if ($action === 'get_by_id') {
            // Belirli bir cihazı getir
            $deviceId = $_GET['id'] ?? null;
            
            if (!$deviceId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cihaz ID gereklidir']);
                exit();
            }
            
            try {
                $stmt = $pdo->prepare("
                    SELECT d.*, ei.url as image_url 
                    FROM devices d 
                    LEFT JOIN devices_images ei ON d.id = ei.equipment_id 
                    WHERE d.id = ?
                ");
                $stmt->execute([$deviceId]);
                $device = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($device) {
                    echo json_encode(['success' => true, 'device' => $device]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Cihaz bulunamadı']);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihaz getirilirken hata oluştu']);
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
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM devices WHERE lab_id = ?");
                $checkStmt->execute([$labId]);
                $deviceCount = $checkStmt->fetch()['count'];
                error_log('Raw device count for lab_id ' . $labId . ': ' . $deviceCount . ' at ' . date('Y-m-d H:i:s'));
                
                // Cihazları ve resimlerini birlikte getir
                $stmt = $pdo->prepare("
                    SELECT d.*, ei.url as image_url 
                    FROM devices d 
                    LEFT JOIN devices_images ei ON d.id = ei.equipment_id 
                    WHERE d.lab_id = ? 
                    ORDER BY d.order_num ASC, d.created_at ASC
                ");
                $stmt->execute([$labId]);
                $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log('Found ' . count($devices) . ' devices for lab_id: ' . $labId);
                
                // Her cihaz için debug bilgisi
                foreach ($devices as $device) {
                    error_log('Device: ID=' . $device['id'] . ', Name=' . $device['device_name'] . ', Image=' . ($device['image_url'] ?? 'NULL'));
                }
                
                echo json_encode(['success' => true, 'devices' => $devices]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihazlar listelenirken hata oluştu']);
            }
        }
        break;
        
    case 'POST':
        // Yeni cihaz ekle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['lab_id']) || !isset($input['device_name']) || empty(trim($input['device_name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID ve cihaz adı gereklidir']);
            exit();
        }
        
        try {
            // Laboratuvar var mı kontrol et
            $stmt = $pdo->prepare("SELECT id FROM laboratories WHERE id = ?");
            $stmt->execute([$input['lab_id']]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz laboratuvar ID']);
                exit();
            }
            
            $stmt = $pdo->prepare("INSERT INTO devices (lab_id, device_name, device_model, device_count, purpose, order_num) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['lab_id'],
                trim($input['device_name']),
                trim($input['device_model'] ?? ''),
                $input['device_count'] ?? 1,
                trim($input['purpose'] ?? ''),
                $input['order_num'] ?? 0
            ]);
            
            $deviceId = $pdo->lastInsertId();
            
            // Eğer resim URL'i varsa devices_images tablosuna ekle
            if (!empty(trim($input['image_url'] ?? ''))) {
                $stmt = $pdo->prepare("INSERT INTO devices_images (equipment_id, url, alt_text, order_num, added_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $deviceId,
                    trim($input['image_url']),
                    trim($input['device_name']), // Alt text olarak cihaz adını kullan
                    $input['order_num'] ?? 0,
                    $_SESSION['username'] ?? 'unknown'
                ]);
            }
            
            // Eklenen cihazı döndür
            $stmt = $pdo->prepare("SELECT * FROM devices WHERE id = ?");
            $stmt->execute([$deviceId]);
            $newDevice = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'message' => 'Cihaz başarıyla eklendi', 'data' => $newDevice]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Cihaz eklenirken hata oluştu']);
        }
        break;
        
    case 'PUT':
        // Cihaz güncelle
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Debug bilgisi
        error_log('PUT request data: ' . json_encode($input));
        
        if (!isset($input['id']) || !isset($input['device_name']) || empty(trim($input['device_name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cihaz ID ve adı gereklidir']);
            exit();
        }
        
        try {
            // Order num değerini kontrol et
            $orderNum = isset($input['order_num']) ? intval($input['order_num']) : 0;
            error_log('Order num value: ' . $orderNum);
            
            $stmt = $pdo->prepare("UPDATE devices SET lab_id = ?, device_name = ?, device_model = ?, device_count = ?, purpose = ?, order_num = ? WHERE id = ?");
            $stmt->execute([
                $input['lab_id'] ?? 1,
                trim($input['device_name']),
                trim($input['device_model'] ?? ''),
                $input['device_count'] ?? 1,
                trim($input['purpose'] ?? ''),
                $orderNum,
                $input['id']
            ]);
            
            // Resim URL'i varsa devices_images tablosunu güncelle
            if (!empty(trim($input['image_url'] ?? ''))) {
                // Önce mevcut resmi sil
                $stmt = $pdo->prepare("DELETE FROM devices_images WHERE equipment_id = ?");
                $stmt->execute([$input['id']]);
                
                // Yeni resmi ekle
                $stmt = $pdo->prepare("INSERT INTO devices_images (equipment_id, url, alt_text, order_num, added_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $input['id'],
                    trim($input['image_url']),
                    trim($input['device_name']),
                    $orderNum,
                    $_SESSION['username'] ?? 'unknown'
                ]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Cihaz başarıyla güncellendi']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Cihaz güncellenirken hata oluştu']);
        }
        break;
        
    case 'DELETE':
        // Cihaz sil (tekli veya toplu)
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Toplu silme kontrolü
        if (isset($input['ids']) && is_array($input['ids'])) {
            // Toplu silme
            if (empty($input['ids'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Silinecek cihaz seçilmedi']);
                exit();
            }
            
            try {
                // Debug bilgisi
                error_log('BULK DELETE devices request for IDs: ' . implode(',', $input['ids']) . ' at ' . date('Y-m-d H:i:s'));
                
                // Transaction başlat
                $pdo->beginTransaction();
                
                // Önce cihazlara ait resimleri sil
                $placeholders = str_repeat('?,', count($input['ids']) - 1) . '?';
                $stmt = $pdo->prepare("DELETE FROM devices_images WHERE equipment_id IN ($placeholders)");
                $stmt->execute($input['ids']);
                error_log('Deleted images for device IDs: ' . implode(',', $input['ids']));
                
                // Sonra cihazları sil
                $stmt = $pdo->prepare("DELETE FROM devices WHERE id IN ($placeholders)");
                $stmt->execute($input['ids']);
                error_log('Deleted devices with IDs: ' . implode(',', $input['ids']));
                
                // Transaction'ı tamamla
                $pdo->commit();
                
                $deletedCount = count($input['ids']);
                echo json_encode(['success' => true, 'message' => $deletedCount . ' cihaz ve ilgili resimler başarıyla silindi']);
            } catch(PDOException $e) {
                // Hata durumunda rollback
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihazlar silinirken hata oluştu']);
            }
        } else {
            // Tekli silme (mevcut kod)
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cihaz ID gereklidir']);
                exit();
            }
            
            try {
                // Debug bilgisi
                error_log('DELETE device request for ID: ' . $input['id'] . ' at ' . date('Y-m-d H:i:s'));
                
                // Önce cihaza ait resimleri sil (CASCADE ile otomatik silinir ama güvenlik için)
                $stmt = $pdo->prepare("DELETE FROM devices_images WHERE equipment_id = ?");
                $stmt->execute([$input['id']]);
                error_log('Deleted images for device ID: ' . $input['id']);
                
                // Sonra cihazı sil
                $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
                $stmt->execute([$input['id']]);
                error_log('Deleted device ID: ' . $input['id']);
                
                echo json_encode(['success' => true, 'message' => 'Cihaz ve ilgili resimler başarıyla silindi']);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihaz silinirken hata oluştu']);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen HTTP metodu']);
        break;
}
?>
