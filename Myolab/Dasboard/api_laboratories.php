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
        if ($action === 'get_all') {
            // Tüm laboratuvarları getir (cihaz listesi için)
            try {
                $stmt = $pdo->prepare("SELECT l.*, c.name as category_name 
                                      FROM laboratories l 
                                      LEFT JOIN categories c ON l.category_id = c.id 
                                      ORDER BY c.name, l.name");
                $stmt->execute();
                
                $laboratories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'laboratories' => $laboratories]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Laboratuvarlar listelenirken hata oluştu']);
            }
        } else if ($action === 'get_by_id') {
            // Belirli bir laboratuvarı ID ile getir
            $labId = $_GET['id'] ?? null;
            
            if (!$labId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
                exit();
            }
            
            try {
                $stmt = $pdo->prepare("SELECT l.*, c.name as category_name 
                                      FROM laboratories l 
                                      LEFT JOIN categories c ON l.category_id = c.id 
                                      WHERE l.id = ?");
                $stmt->execute([$labId]);
                
                $laboratory = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($laboratory) {
                    echo json_encode(['success' => true, 'data' => $laboratory]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Laboratuvar bulunamadı']);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar getirilirken hata oluştu']);
            }
        } else {
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
        }
        break;
        
    case 'POST':
        // Yeni laboratuvar ekle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['name']) || empty(trim($input['name'])) || 
            !isset($input['category_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar adı ve kategori ID gereklidir']);
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
            
            $stmt = $pdo->prepare("INSERT INTO laboratories (name, category_id) VALUES (?, ?)");
            $stmt->execute([
                trim($input['name']),
                $input['category_id']
            ]);
            
            $labId = $pdo->lastInsertId();
            $labName = trim($input['name']);
            $categoryId = $input['category_id'];
            
            // Kategori adını al
            $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch();
            $categoryName = $category['name'];
            
            // Laboratuvar için dinamik klasör oluştur (kategori adına göre)
            $uploadDir = '../image/uploads/' . preg_replace('/[^a-zA-Z0-9]/', '_', $categoryName) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $labName);
            
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    // Klasör oluşturulamadıysa uyarı ver ama işleme devam et
                    error_log("Laboratuvar klasörü oluşturulamadı: " . $uploadDir);
                }
            }
            
            // Eklenen laboratuvarı döndür
            $stmt = $pdo->prepare("SELECT l.*, c.name as category_name 
                                  FROM laboratories l 
                                  LEFT JOIN categories c ON l.category_id = c.id 
                                  WHERE l.id = ?");
            $stmt->execute([$labId]);
            $newLab = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Klasör durumu hakkında bilgi ekle
            $folderCreated = is_dir($uploadDir);
            $newLab['upload_folder'] = preg_replace('/[^a-zA-Z0-9]/', '_', $categoryName) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $labName);
            $newLab['folder_created'] = $folderCreated;
            
            $message = 'Laboratuvar başarıyla eklendi';
            if ($folderCreated) {
                $message .= ' ve resim klasörü oluşturuldu';
            } else {
                $message .= ' ancak resim klasörü oluşturulamadı';
            }
            
            echo json_encode(['success' => true, 'message' => $message, 'data' => $newLab]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar eklenirken hata oluştu']);
        }
        break;
        
    case 'PUT':
        // Laboratuvar güncelle
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || !isset($input['name']) || empty(trim($input['name'])) || 
            !isset($input['category_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID, adı ve kategori ID gereklidir']);
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
            
            // Eski laboratuvar bilgilerini al (klasör yeniden adlandırma için)
            $stmt = $pdo->prepare("SELECT l.id, l.name, l.category_id, c.name as category_name FROM laboratories l LEFT JOIN categories c ON l.category_id = c.id WHERE l.id = ?");
            $stmt->execute([$input['id']]);
            $oldLab = $stmt->fetch();
            
            if (!$oldLab) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar bulunamadı']);
                exit();
            }
            
            $newLabName = trim($input['name']);
            $newCategoryId = $input['category_id'];
            
            // Yeni kategori adını al
            $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
            $stmt->execute([$newCategoryId]);
            $newCategory = $stmt->fetch();
            $newCategoryName = $newCategory['name'];
            
            // Laboratuvarı güncelle
            $stmt = $pdo->prepare("UPDATE laboratories SET name = ?, category_id = ? WHERE id = ?");
            $stmt->execute([
                $newLabName,
                $newCategoryId,
                $input['id']
            ]);
            
            // Eğer isim veya kategori değiştiyse klasörü yeniden adlandır
            if ($oldLab['name'] !== $newLabName || $oldLab['category_id'] != $newCategoryId) {
                $oldUploadDir = '../image/uploads/' . preg_replace('/[^a-zA-Z0-9]/', '_', $oldLab['category_name']) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $oldLab['name']);
                $newUploadDir = '../image/uploads/' . preg_replace('/[^a-zA-Z0-9]/', '_', $newCategoryName) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $newLabName);
                
                if (is_dir($oldUploadDir) && !is_dir($newUploadDir)) {
                    rename($oldUploadDir, $newUploadDir);
                } elseif (!is_dir($oldUploadDir) && !is_dir($newUploadDir)) {
                    // Eski klasör yoksa yeni klasör oluştur
                    mkdir($newUploadDir, 0755, true);
                }
            }
            
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
            // Önce laboratuvar bilgilerini al (klasör adı için)
            $stmt = $pdo->prepare("SELECT l.id, l.name, l.category_id, c.name as category_name FROM laboratories l LEFT JOIN categories c ON l.category_id = c.id WHERE l.id = ?");
            $stmt->execute([$input['id']]);
            $lab = $stmt->fetch();
            
            if (!$lab) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar bulunamadı']);
                exit();
            }
            
            // Laboratuvarı sil
            $stmt = $pdo->prepare("DELETE FROM laboratories WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            // Klasörü ve içindekileri sil (kategori adına göre)
            $uploadDir = '../image/uploads/' . preg_replace('/[^a-zA-Z0-9]/', '_', $lab['category_name']) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $lab['name']);
            
            if (is_dir($uploadDir)) {
                // Klasör içindeki dosyaları sil
                $files = glob($uploadDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                // Klasörü sil
                rmdir($uploadDir);
            }
            
            echo json_encode(['success' => true, 'message' => 'Laboratuvar ve ilgili dosyalar başarıyla silindi']);
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
