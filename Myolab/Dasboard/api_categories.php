<?php
// Hata raporlamayı aç (debug için)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Türkçe karakterleri güvenli klasör isimlerine dönüştürür
 * @param string $text Dönüştürülecek metin
 * @return string Güvenli klasör ismi
 */
function sanitizeFolderName($text) {
    // Türkçe karakterleri İngilizce karşılıklarıyla değiştir
    $turkishChars = [
        'ç' => 'c', 'Ç' => 'C',
        'ğ' => 'g', 'Ğ' => 'G', 
        'ı' => 'i', 'I' => 'I',
        'İ' => 'I', 'i' => 'i',
        'ö' => 'o', 'Ö' => 'O',
        'ş' => 's', 'Ş' => 'S',
        'ü' => 'u', 'Ü' => 'U'
    ];
    
    // Türkçe karakterleri değiştir
    $text = strtr($text, $turkishChars);
    
    // Sadece alfanumerik karakterler ve alt çizgi bırak
    $text = preg_replace('/[^a-zA-Z0-9]/', '_', $text);
    
    // Birden fazla alt çizgiyi tek alt çizgiye çevir
    $text = preg_replace('/_+/', '_', $text);
    
    // Başta ve sonda alt çizgi varsa kaldır
    $text = trim($text, '_');
    
    // Boş string kontrolü
    if (empty($text)) {
        $text = 'default';
    }
    
    return $text;
}

// CORS header'ları ekle
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf8mb4');

// OPTIONS request için preflight kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Kullanıcı giriş yapmamışsa hata döndür (geçici olarak kapatıldı)
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
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

switch($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        if ($action === 'delete') {
            // GET ile kategori silme (InfinityFree hosting için)
            error_log('GET delete called for category with action: ' . $action);
            error_log('All GET parameters: ' . json_encode($_GET));
            
            $categoryId = $_GET['id'] ?? null;
            error_log('Category ID from GET: ' . ($categoryId ?? 'NULL'));
            
            // Test modu kapatıldı - gerçek silme işlemi yapılacak
            if ($categoryId && $categoryId !== 'null' && $categoryId !== '') {
                error_log('Category ID found: ' . $categoryId);
                // Test modu kapatıldı, devam et
            }
            
            if (!$categoryId) {
                error_log('Category ID missing - returning 400 error');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Kategori ID gereklidir']);
                exit();
            }
            
            error_log('GET delete called for category ID: ' . $categoryId);
            
            try {
                // Önce bu kategoriye bağlı laboratuvarlar var mı kontrol et
                $sql = "SELECT COUNT(*) as count FROM laboratories WHERE category_id = '" . $mysqli->real_escape_string($categoryId) . "'";
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Kontrol hatası: " . $mysqli->error);
                }
                
                $row = $result->fetch_assoc();
                $labCount = $row['count'];
                
                if ($labCount > 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Bu kategoriye bağlı laboratuvarlar bulunmaktadır. Önce laboratuvarları siliniz.']);
                    exit();
                }
                
                $sql = "DELETE FROM categories WHERE id = '" . $mysqli->real_escape_string($categoryId) . "'";
                
                if (!$mysqli->query($sql)) {
                    throw new Exception("Silme hatası: " . $mysqli->error);
                }
                
                echo json_encode(['success' => true, 'message' => 'Kategori başarıyla silindi']);
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Kategori silinirken hata oluştu: ' . $e->getMessage()]);
            }
        } else {
            // Kategorileri listele
            try {
                $sql = "SELECT c.id, c.name FROM categories c ORDER BY c.name";
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Sorgu hatası: " . $mysqli->error);
                }
                
                $categories = [];
                while ($row = $result->fetch_assoc()) {
                    $categories[] = $row;
                }
                
                echo json_encode(['success' => true, 'data' => $categories]);
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Kategoriler listelenirken hata oluştu: ' . $e->getMessage()]);
            }
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
            // Kategori adını temizle (Türkçe karakterler için)
            $cleanCategoryName = sanitizeFolderName(trim($input['name']));
            
            $sql = "INSERT INTO categories (name) VALUES ('" . $mysqli->real_escape_string($cleanCategoryName) . "')";
            
            if (!$mysqli->query($sql)) {
                throw new Exception("Kategori ekleme hatası: " . $mysqli->error);
            }
            
            $categoryId = $mysqli->insert_id;
            
            // Eklenen kategoriyi döndür
            $sql = "SELECT c.id, c.name FROM categories c WHERE c.id = '" . $mysqli->real_escape_string($categoryId) . "'";
            $result = $mysqli->query($sql);
            $newCategory = $result->fetch_assoc();
            
            echo json_encode(['success' => true, 'message' => 'Kategori başarıyla eklendi', 'data' => $newCategory]);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kategori eklenirken hata oluştu: ' . $e->getMessage()]);
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
            // Kategori adını temizle (Türkçe karakterler için)
            $cleanCategoryName = sanitizeFolderName(trim($input['name']));
            
            $sql = "UPDATE categories SET name = '" . $mysqli->real_escape_string($cleanCategoryName) . "' 
                    WHERE id = '" . $mysqli->real_escape_string($input['id']) . "'";
            
            if (!$mysqli->query($sql)) {
                throw new Exception("Güncelleme hatası: " . $mysqli->error);
            }
            
            echo json_encode(['success' => true, 'message' => 'Kategori başarıyla güncellendi']);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kategori güncellenirken hata oluştu: ' . $e->getMessage()]);
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
            $sql = "SELECT COUNT(*) as count FROM laboratories WHERE category_id = '" . $mysqli->real_escape_string($input['id']) . "'";
            $result = $mysqli->query($sql);
            
            if ($result === false) {
                throw new Exception("Kontrol hatası: " . $mysqli->error);
            }
            
            $row = $result->fetch_assoc();
            $labCount = $row['count'];
            
            if ($labCount > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Bu kategoriye bağlı laboratuvarlar bulunmaktadır. Önce laboratuvarları siliniz.']);
                exit();
            }
            
            $sql = "DELETE FROM categories WHERE id = '" . $mysqli->real_escape_string($input['id']) . "'";
            
            if (!$mysqli->query($sql)) {
                throw new Exception("Silme hatası: " . $mysqli->error);
            }
            
            error_log('Category deleted successfully from database');
            echo json_encode(['success' => true, 'message' => 'Kategori başarıyla silindi']);
        } catch(Exception $e) {
            error_log('Error deleting category: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Kategori silinirken hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen HTTP metodu']);
        break;
}
?>
