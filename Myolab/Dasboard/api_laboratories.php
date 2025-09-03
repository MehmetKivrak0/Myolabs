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

// Çıktı buffer'ını temizle
if (ob_get_level()) {
    ob_end_clean();
}

// Debug için log dosyasına yaz
error_log('=== API Laboratories başlatıldı ===');
error_log('Server: ' . ($_SERVER['SERVER_NAME'] ?? 'UNKNOWN'));
error_log('Document Root: ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'UNKNOWN'));
error_log('Script Path: ' . ($_SERVER['SCRIPT_NAME'] ?? 'UNKNOWN'));

session_start();

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
    error_log('Database config dosyası yükleniyor...');
    
    // Config dosyası var mı kontrol et
    $configPath = '../Database/config.php';
    if (!file_exists($configPath)) {
        throw new Exception("Config dosyası bulunamadı: $configPath");
    }
    
    require_once $configPath;
    error_log('Database config yüklendi');
    
    error_log('Database instance oluşturuluyor...');
    $database = Database::getInstance();
    error_log('Database instance oluşturuldu');
    
    error_log('Database connection alınıyor...');
    $mysqli = $database->getConnection();
    error_log('Database connection başarılı');
} catch(Exception $e) {
    error_log('Database hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($method) {
    case 'GET':
        if ($action === 'get_all') {
            // Tüm laboratuvarları getir
            try {
                $sql = "SELECT l.*, c.name as category_name 
                        FROM laboratories l 
                        LEFT JOIN categories c ON l.category_id = c.id 
                        ORDER BY c.name, l.name";
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Sorgu hatası: " . $mysqli->error);
                }
                
                $laboratories = [];
                while ($row = $result->fetch_assoc()) {
                    $laboratories[] = $row;
                }
                
                echo json_encode(['success' => true, 'laboratories' => $laboratories]);
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Laboratuvarlar listelenirken hata oluştu: ' . $e->getMessage()]);
            }
        } else if ($action === 'delete') {
            // GET ile laboratuvar silme (InfinityFree hosting için)
            error_log('GET delete called for laboratory with action: ' . $action);
            error_log('All GET parameters: ' . json_encode($_GET));
            
            $labId = $_GET['id'] ?? null;
            error_log('Laboratory ID from GET: ' . ($labId ?? 'NULL'));
            
            if ($labId && $labId !== 'null' && $labId !== '') {
                error_log('Laboratory ID found: ' . $labId);
            }
            
            if (!$labId) {
                error_log('Laboratory ID missing - returning 400 error');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
                exit();
            }
            
            error_log('GET delete called for laboratory ID: ' . $labId);
            
            try {
                // Önce laboratuvar bilgilerini al (klasör adı için)
                $sql = "SELECT l.id, l.name, l.category_id, c.name as category_name 
                        FROM laboratories l 
                        LEFT JOIN categories c ON l.category_id = c.id 
                        WHERE l.id = '" . $mysqli->real_escape_string($labId) . "'";
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Laboratuvar bilgileri alınırken hata: " . $mysqli->error);
                }
                
                $lab = $result->fetch_assoc();
                
                if (!$lab) {
                    error_log("Laboratuvar bulunamadı. ID: " . $labId);
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Laboratuvar bulunamadı']);
                    exit();
                }
                
                error_log("Laboratuvar bulundu: " . $lab['name'] . " (ID: " . $lab['id'] . ")");
                
                // Laboratuvarı sil
                $sql = "DELETE FROM laboratories WHERE id = '" . $mysqli->real_escape_string($labId) . "'";
                
                if (!$mysqli->query($sql)) {
                    throw new Exception("Silme hatası: " . $mysqli->error);
                }
                
                // Klasörü ve içindekileri sil (kategori adına göre)
                $safeCategoryName = sanitizeFolderName($lab['category_name']);
            $safeLabName = sanitizeFolderName($lab['name']);
            $uploadDir = '../image/uploads/' . $safeCategoryName . '_' . $safeLabName;
                
                if (is_dir($uploadDir)) {
                    // Klasör içindeki tüm dosyaları sil
                    $files = glob($uploadDir . '/*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                    // Boş klasörü sil
                    rmdir($uploadDir);
                }
                
                echo json_encode(['success' => true, 'message' => 'Laboratuvar başarıyla silindi']);
            } catch(Exception $e) {
                error_log('Error deleting laboratory: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar silinirken hata oluştu: ' . $e->getMessage()]);
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
                $sql = "SELECT l.*, c.name as category_name 
                        FROM laboratories l 
                        LEFT JOIN categories c ON l.category_id = c.id 
                        WHERE l.id = '" . $mysqli->real_escape_string($labId) . "'";
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Sorgu hatası: " . $mysqli->error);
                }
                
                $laboratory = $result->fetch_assoc();
                
                if ($laboratory) {
                    echo json_encode(['success' => true, 'data' => $laboratory]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Laboratuvar bulunamadı']);
                }
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar getirilirken hata oluştu: ' . $e->getMessage()]);
            }
        } else if ($action === 'check_exists') {
            // Laboratuvarın var olup olmadığını kontrol et (debug için)
            $labId = $_GET['id'] ?? null;
            
            if (!$labId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
                exit();
            }
            
            try {
                $sql = "SELECT COUNT(*) as count FROM laboratories WHERE id = '" . $mysqli->real_escape_string($labId) . "'";
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Sorgu hatası: " . $mysqli->error);
                }
                
                $row = $result->fetch_assoc();
                $exists = $row['count'] > 0;
                
                echo json_encode([
                    'success' => true, 
                    'exists' => $exists, 
                    'count' => $row['count'],
                    'message' => $exists ? 'Laboratuvar mevcut' : 'Laboratuvar bulunamadı'
                ]);
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Kontrol hatası: ' . $e->getMessage()]);
            }
        } else {
            // Laboratuvarları listele (kategori bazında)
            $categoryId = $_GET['category_id'] ?? null;
            
            try {
                if ($categoryId) {
                    // Belirli bir kategorinin laboratuvarlarını getir
                    $sql = "SELECT l.*, c.name as category_name 
                            FROM laboratories l 
                            LEFT JOIN categories c ON l.category_id = c.id 
                            WHERE l.category_id = '" . $mysqli->real_escape_string($categoryId) . "' 
                            ORDER BY l.name";
                } else {
                    // Tüm laboratuvarları getir
                    $sql = "SELECT l.*, c.name as category_name 
                            FROM laboratories l 
                            LEFT JOIN categories c ON l.category_id = c.id 
                            ORDER BY c.name, l.name";
                }
                
                $result = $mysqli->query($sql);
                
                if ($result === false) {
                    throw new Exception("Sorgu hatası: " . $mysqli->error);
                }
                
                $laboratories = [];
                while ($row = $result->fetch_assoc()) {
                    $laboratories[] = $row;
                }
                
                echo json_encode(['success' => true, 'data' => $laboratories]);
            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Laboratuvarlar listelenirken hata oluştu: ' . $e->getMessage()]);
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
            $sql = "SELECT id FROM categories WHERE id = '" . $mysqli->real_escape_string($input['category_id']) . "'";
            $result = $mysqli->query($sql);
            
            if ($result === false) {
                throw new Exception("Kategori kontrol hatası: " . $mysqli->error);
            }
            
            if (!$result->fetch_assoc()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz kategori ID']);
                exit();
            }
            
            $sql = "INSERT INTO laboratories (name, category_id) VALUES ('" . 
                   $mysqli->real_escape_string(trim($input['name'])) . "', '" . 
                   $mysqli->real_escape_string($input['category_id']) . "')";
            
            if (!$mysqli->query($sql)) {
                throw new Exception("Laboratuvar ekleme hatası: " . $mysqli->error);
            }
            
            $labId = $mysqli->insert_id;
            $labName = trim($input['name']);
            $categoryId = $input['category_id'];
            
            // Kategori adını al
            $sql = "SELECT name FROM categories WHERE id = '" . $mysqli->real_escape_string($categoryId) . "'";
            $result = $mysqli->query($sql);
            $category = $result->fetch_assoc();
            $categoryName = $category['name'];
            
            // Laboratuvar için dinamik klasör oluştur (kategori adına göre)
            $safeCategoryName = sanitizeFolderName($categoryName);
            $safeLabName = sanitizeFolderName($labName);
            $uploadDir = '../image/uploads/' . $safeCategoryName . '_' . $safeLabName;
            
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    // Klasör oluşturulamadıysa uyarı ver ama işleme devam et
                    error_log("Laboratuvar klasörü oluşturulamadı: " . $uploadDir);
                }
            }
            
            // Eklenen laboratuvarı döndür
            $sql = "SELECT l.*, c.name as category_name 
                    FROM laboratories l 
                    LEFT JOIN categories c ON l.category_id = c.id 
                    WHERE l.id = '" . $mysqli->real_escape_string($labId) . "'";
            $result = $mysqli->query($sql);
            $newLab = $result->fetch_assoc();
            
            // Klasör durumu hakkında bilgi ekle
            $folderCreated = is_dir($uploadDir);
            $newLab['upload_folder'] = $safeCategoryName . '_' . $safeLabName;
            $newLab['folder_created'] = $folderCreated;
            
            $message = 'Laboratuvar başarıyla eklendi';
            if ($folderCreated) {
                $message .= ' ve resim klasörü oluşturuldu';
            } else {
                $message .= ' ancak resim klasörü oluşturulamadı';
            }
            
            echo json_encode(['success' => true, 'message' => $message, 'data' => $newLab]);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar eklenirken hata oluştu: ' . $e->getMessage()]);
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
            $sql = "SELECT id FROM categories WHERE id = '" . $mysqli->real_escape_string($input['category_id']) . "'";
            $result = $mysqli->query($sql);
            
            if ($result === false) {
                throw new Exception("Kategori kontrol hatası: " . $mysqli->error);
            }
            
            if (!$result->fetch_assoc()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz kategori ID']);
                exit();
            }
            
            // Eski laboratuvar bilgilerini al (klasör yeniden adlandırma için)
            $sql = "SELECT l.id, l.name, l.category_id, c.name as category_name 
                    FROM laboratories l 
                    LEFT JOIN categories c ON l.category_id = c.id 
                    WHERE l.id = '" . $mysqli->real_escape_string($input['id']) . "'";
            $result = $mysqli->query($sql);
            $oldLab = $result->fetch_assoc();
            
            if (!$oldLab) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar bulunamadı']);
                exit();
            }
            
            $newLabName = trim($input['name']);
            $newCategoryId = $input['category_id'];
            
            // Yeni kategori adını al
            $sql = "SELECT name FROM categories WHERE id = '" . $mysqli->real_escape_string($newCategoryId) . "'";
            $result = $mysqli->query($sql);
            $newCategory = $result->fetch_assoc();
            $newCategoryName = $newCategory['name'];
            
            // Laboratuvarı güncelle
            $sql = "UPDATE laboratories SET name = '" . $mysqli->real_escape_string($newLabName) . "', 
                    category_id = '" . $mysqli->real_escape_string($newCategoryId) . "' 
                    WHERE id = '" . $mysqli->real_escape_string($input['id']) . "'";
            
            if (!$mysqli->query($sql)) {
                throw new Exception("Güncelleme hatası: " . $mysqli->error);
            }
            
            // Eğer isim veya kategori değiştiyse klasörü yeniden adlandır
            if ($oldLab['name'] !== $newLabName || $oldLab['category_id'] != $newCategoryId) {
                $oldSafeCategoryName = sanitizeFolderName($oldLab['category_name']);
                $oldSafeLabName = sanitizeFolderName($oldLab['name']);
                $newSafeCategoryName = sanitizeFolderName($newCategoryName);
                $newSafeLabName = sanitizeFolderName($newLabName);
                
                $oldUploadDir = '../image/uploads/' . $oldSafeCategoryName . '_' . $oldSafeLabName;
                $newUploadDir = '../image/uploads/' . $newSafeCategoryName . '_' . $newSafeLabName;
                
                if (is_dir($oldUploadDir) && !is_dir($newUploadDir)) {
                    rename($oldUploadDir, $newUploadDir);
                } elseif (!is_dir($oldUploadDir) && !is_dir($newUploadDir)) {
                    // Eski klasör yoksa yeni klasör oluştur
                    mkdir($newUploadDir, 0755, true);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Laboratuvar başarıyla güncellendi']);
        } catch(Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar güncellenirken hata oluştu: ' . $e->getMessage()]);
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
            $sql = "SELECT l.id, l.name, l.category_id, c.name as category_name 
                    FROM laboratories l 
                    LEFT JOIN categories c ON l.category_id = c.id 
                    WHERE l.id = '" . $mysqli->real_escape_string($input['id']) . "'";
            $result = $mysqli->query($sql);
            
            if ($result === false) {
                throw new Exception("Laboratuvar bilgileri alınırken hata: " . $mysqli->error);
            }
            
            $lab = $result->fetch_assoc();
            
            if (!$lab) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Laboratuvar bulunamadı']);
                exit();
            }
            
            // Laboratuvarı sil
            $sql = "DELETE FROM laboratories WHERE id = '" . $mysqli->real_escape_string($input['id']) . "'";
            
            if (!$mysqli->query($sql)) {
                throw new Exception("Silme hatası: " . $mysqli->error);
            }
            
            // Klasörü ve içindekileri sil (kategori adına göre)
            $safeCategoryName = sanitizeFolderName($lab['category_name']);
            $safeLabName = sanitizeFolderName($lab['name']);
            $uploadDir = '../image/uploads/' . $safeCategoryName . '_' . $safeLabName;
            
            if (is_dir($uploadDir)) {
                // Klasör içindeki tüm dosyaları sil
                $files = glob($uploadDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                // Boş klasörü sil
                rmdir($uploadDir);
            }
            
            echo json_encode(['success' => true, 'message' => 'Laboratuvar başarıyla silindi']);
        } catch(Exception $e) {
            error_log('Error deleting laboratory: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar silinirken hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
