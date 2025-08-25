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
        if ($action === 'get_by_lab') {
            // Laboratuvar cihazlarını listele
            $labId = $_GET['lab_id'] ?? null;
            
            if (!$labId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
                exit();
            }
            
            try {
                // Cihazları ve resimlerini birlikte getir
                $stmt = $pdo->prepare("
                    SELECT d.*, ei.url as image_url 
                    FROM devices d 
                    LEFT JOIN equipment_images ei ON d.id = ei.equipment_id 
                    WHERE d.lab_id = ? 
                    ORDER BY d.order_num ASC, d.created_at ASC
                ");
                $stmt->execute([$labId]);
                $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'devices' => $devices]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Cihazlar listelenirken hata oluştu']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Geçersiz action']);
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
            
            // Eğer resim URL'i varsa equipment_images tablosuna ekle
            if (!empty(trim($input['image_url'] ?? ''))) {
                $stmt = $pdo->prepare("INSERT INTO equipment_images (equipment_id, url, alt_text, order_num, added_by) VALUES (?, ?, ?, ?, ?)");
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
        
        if (!isset($input['id']) || !isset($input['device_name']) || empty(trim($input['device_name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cihaz ID ve adı gereklidir']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE devices SET device_name = ?, device_model = ?, device_count = ?, purpose = ?, order_num = ? WHERE id = ?");
            $stmt->execute([
                trim($input['device_name']),
                trim($input['device_model'] ?? ''),
                $input['device_count'] ?? 1,
                trim($input['purpose'] ?? ''),
                $input['order_num'] ?? 0,
                $input['id']
            ]);
            
            // Resim URL'i varsa equipment_images tablosunu güncelle
            if (!empty(trim($input['image_url'] ?? ''))) {
                // Önce mevcut resmi sil
                $stmt = $pdo->prepare("DELETE FROM equipment_images WHERE equipment_id = ?");
                $stmt->execute([$input['id']]);
                
                // Yeni resmi ekle
                $stmt = $pdo->prepare("INSERT INTO equipment_images (equipment_id, url, alt_text, order_num, added_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $input['id'],
                    trim($input['image_url']),
                    trim($input['device_name']),
                    $input['order_num'] ?? 0,
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
        // Cihaz sil
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cihaz ID gereklidir']);
            exit();
        }
        
        try {
            // Önce cihaza ait resimleri sil (CASCADE ile otomatik silinir ama güvenlik için)
            $stmt = $pdo->prepare("DELETE FROM equipment_images WHERE equipment_id = ?");
            $stmt->execute([$input['id']]);
            
            // Sonra cihazı sil
            $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Cihaz ve ilgili resimler başarıyla silindi']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Cihaz silinirken hata oluştu']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen HTTP metodu']);
        break;
}
?>
