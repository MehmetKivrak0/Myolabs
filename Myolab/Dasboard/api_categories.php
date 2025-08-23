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
        // Kategorileri listele
        try {
            $stmt = $pdo->prepare("SELECT c.id, c.name FROM categories c ORDER BY c.name");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $categories]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kategoriler listelenirken hata oluştu']);
        }
        break;
        
    case 'POST':
        // Yeni kategori ekle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['name']) || empty(trim($input['name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kategori adı gereklidir']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([
                trim($input['name'])
            ]);
            
            $categoryId = $pdo->lastInsertId();
            
            // Eklenen kategoriyi döndür
            $stmt = $pdo->prepare("SELECT c.id, c.name FROM categories c WHERE c.id = ?");
            $stmt->execute([$categoryId]);
            $newCategory = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'message' => 'Kategori başarıyla eklendi', 'data' => $newCategory]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kategori eklenirken hata oluştu']);
        }
        break;
        
    case 'PUT':
        // Kategori güncelle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || !isset($input['name']) || empty(trim($input['name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kategori ID ve adı gereklidir']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([
                trim($input['name']),
                $input['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Kategori başarıyla güncellendi']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kategori güncellenirken hata oluştu']);
        }
        break;
        
    case 'DELETE':
        // Kategori sil (soft delete)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kategori ID gereklidir']);
            exit();
        }
        
        try {
            // Önce bu kategoriye bağlı laboratuvarlar var mı kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM laboratories WHERE category_id = ?");
            $stmt->execute([$input['id']]);
            $labCount = $stmt->fetchColumn();
            
            if ($labCount > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Bu kategoriye bağlı laboratuvarlar bulunmaktadır. Önce laboratuvarları siliniz.']);
                exit();
            }
            
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Kategori başarıyla silindi']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kategori silinirken hata oluştu']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen HTTP metodu']);
        break;
}
?>
