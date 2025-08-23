<?php
// Hata raporlamayı kapat (production için)
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

// Kullanıcı giriş yapmamışsa hata döndür
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}

try {
    require_once 'Database/confıg.php';
    $database = Database::getInstance();
    $pdo = $database->getConnection();
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Laboratuvarları listele (kategori bazında)
        $categoryId = $_GET['category_id'] ?? null;
        
        try {
            if ($categoryId) {
                // Belirli bir kategorinin laboratuvarlarını getir
                $stmt = $pdo->prepare("SELECT l.*, c.name as category_name 
                                      FROM laboratories l 
                                      LEFT JOIN categories c ON l.category_id = c.id 
                                      WHERE l.category_id = ? 
                                      ORDER BY l.name");
                $stmt->execute([$categoryId]);
            } else {
                // Tüm laboratuvarları getir
                $stmt = $pdo->prepare("SELECT l.*, c.name as category_name 
                                      FROM laboratories l 
                                      LEFT JOIN categories c ON l.category_id = c.id 
                                      ORDER BY c.name, l.name");
                $stmt->execute();
            }
            
            $laboratories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $laboratories]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Laboratuvarlar listelenirken hata oluştu']);
        }
        break;
        
    case 'POST':
        // Yeni laboratuvar ekle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['name']) || empty(trim($input['name'])) || 
            !isset($input['category_id']) || !isset($input['redirect_url'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar adı, kategori ID ve yönlendirme URL\'si gereklidir']);
            exit();
        }
        
        try {
            // Kategori var mı kontrol et
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->execute([$input['category_id']]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz kategori ID']);
                exit();
            }
            
            $stmt = $pdo->prepare("INSERT INTO laboratories (name, category_id, redirect_url) VALUES (?, ?, ?)");
            $stmt->execute([
                trim($input['name']),
                $input['category_id'],
                trim($input['redirect_url'])
            ]);
            
            $labId = $pdo->lastInsertId();
            
            // Eklenen laboratuvarı döndür
            $stmt = $pdo->prepare("SELECT l.*, c.name as category_name 
                                  FROM laboratories l 
                                  LEFT JOIN categories c ON l.category_id = c.id 
                                  WHERE l.id = ?");
            $stmt->execute([$labId]);
            $newLab = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'message' => 'Laboratuvar başarıyla eklendi', 'data' => $newLab]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar eklenirken hata oluştu']);
        }
        break;
        
    case 'PUT':
        // Laboratuvar güncelle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || !isset($input['name']) || empty(trim($input['name'])) || 
            !isset($input['category_id']) || !isset($input['redirect_url'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID, adı, kategori ID ve yönlendirme URL\'si gereklidir']);
            exit();
        }
        
        try {
            // Kategori var mı kontrol et
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->execute([$input['category_id']]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz kategori ID']);
                exit();
            }
            
            $stmt = $pdo->prepare("UPDATE laboratories SET name = ?, category_id = ?, redirect_url = ? WHERE id = ?");
            $stmt->execute([
                trim($input['name']),
                $input['category_id'],
                trim($input['redirect_url']),
                $input['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Laboratuvar başarıyla güncellendi']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar güncellenirken hata oluştu']);
        }
        break;
        
    case 'DELETE':
        // Laboratuvar sil
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM laboratories WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Laboratuvar başarıyla silindi']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar silinirken hata oluştu']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen HTTP metodu']);
        break;
}
?>
