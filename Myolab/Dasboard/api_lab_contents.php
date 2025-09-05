<?php
// Hata raporlamayı kapat (production için)
error_reporting(0);
ini_set('display_errors', 0);

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

// Debug log başlat
error_log('=== API Lab Contents başlatıldı ===');
error_log('Request Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
error_log('Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN'));
error_log('Script Name: ' . ($_SERVER['SCRIPT_NAME'] ?? 'UNKNOWN'));



try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    error_log('Session başlatıldı - ID: ' . session_id());
} catch (Exception $e) {
    error_log('Session hatası: ' . $e->getMessage());
    // Session hatası olsa bile devam et
}



// Kullanıcı giriş yapmamışsa hata döndür (geçici olarak kapatıldı)
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}
*/

try {
    error_log('Database config dosyası yükleniyor...');
    
    // Config dosyası var mı kontrol et
    $configPath = '../Database/config.php';
    if (!file_exists($configPath)) {
        throw new Exception('Config dosyası bulunamadı: ' . $configPath);
    }
    
    require_once $configPath;
    error_log('Database config yüklendi');
    
    // Database class var mı kontrol et
    if (!class_exists('Database')) {
        throw new Exception('Database class bulunamadı');
    }
    
    error_log('Database instance oluşturuluyor...');
    $database = Database::getInstance();
    error_log('Database instance oluşturuldu');
    
    error_log('Database connection alınıyor...');
    $mysqli = $database->getConnection();
    error_log('Database connection başarılı');
    
    // Connection test et
    if (!$mysqli || $mysqli->connect_error) {
        throw new Exception('Database bağlantı hatası: ' . ($mysqli ? $mysqli->connect_error : 'Connection null'));
    }
    
} catch(Exception $e) {
    error_log('Database hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Method kontrolü
if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Desteklenmeyen HTTP metodu: ' . $method]);
    exit();
}

switch($method) {
    case 'GET':
        error_log('GET metodu çağrıldı');
        // Laboratuvar içeriklerini listele
        $labId = $_GET['lab_id'] ?? null;
        error_log('Lab ID: ' . ($labId ?? 'NULL'));
        
        if (!$labId) {
            error_log('Lab ID eksik');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
            exit();
        }
        
        // Lab ID format kontrolü
        if (!is_numeric($labId)) {
            error_log('Geçersiz Lab ID formatı: ' . $labId);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Geçersiz laboratuvar ID formatı']);
            exit();
        }
        

        
        try {
            // Önce tablo var mı kontrol et
            $tableExists = false;
            try {
                $result = $mysqli->query("SHOW TABLES LIKE 'myo_lab_content_new'");
                $tableExists = $result && $result->num_rows > 0;
            } catch (Exception $e) {
                $tableExists = false;
            }
            
            if ($tableExists) {
                // Tablo varsa normal sorgu yap - Prepared Statement kullan
                $sql = "SELECT * FROM myo_lab_content_new WHERE lab_id = ?";
                $stmt = $mysqli->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare hatası: " . $mysqli->error);
                }
                
                $stmt->bind_param('i', $labId);
                $stmt->execute();
                $result = $stmt->get_result();
                $content = $result->fetch_assoc();
                $stmt->close();
                
                if ($content) {
                    // Yeni tablo yapısına göre organize et
                    $organizedContent = [
                        'lab_title' => [
                            'content_value' => $content['lab_title']
                        ],
                        'main_image' => [
                            'content_value' => $content['main_image']
                        ],
                        'catalog_info' => [
                            'content_value' => $content['catalog_info']
                        ],
                        'detail_page_info' => [
                            'content_value' => $content['detail_page_info']
                        ],
                        'alt_text' => [
                            'content_value' => $content['alt_text']
                        ]
                    ];
                    
                    echo json_encode(['success' => true, 'data' => $organizedContent]);
                } else {
                    echo json_encode(['success' => true, 'data' => null]);
                }
            } else {
                // Tablo yoksa boş veri döndür
                echo json_encode(['success' => true, 'data' => null]);
            }
        } catch(Exception $e) {
            error_log('Content list error: ' . $e->getMessage());
            error_log('Error file: ' . $e->getFile() . ':' . $e->getLine());
            
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'İçerikler listelenirken hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Yeni içerik ekle veya güncelle
        $rawInput = file_get_contents('php://input');
        error_log('Raw input: ' . $rawInput);
        
        $input = json_decode($rawInput, true);
        
        // JSON decode hatası kontrol et
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode hatası: ' . json_last_error_msg());
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Geçersiz JSON formatı: ' . json_last_error_msg(),
                'raw_input' => $rawInput
            ]);
            exit();
        }
        
        // Debug için gelen veriyi logla
        error_log('Received content data: ' . json_encode($input));
        
        if (!isset($input['lab_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Laboratuvar ID gereklidir',
                'received_data' => $input
            ]);
            exit();
        }
        
        try {
            // Laboratuvar var mı kontrol et - Prepared Statement kullan
            $sql = "SELECT id FROM myo_laboratories WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare hatası: " . $mysqli->error);
            }
            
            $stmt->bind_param('i', $input['lab_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if (!$result->fetch_assoc()) {
                $stmt->close();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz laboratuvar ID']);
                exit();
            }
            $stmt->close();
            
            // Önce tablo var mı kontrol et
            $tableExists = false;
            try {
                $result = $mysqli->query("SHOW TABLES LIKE 'myo_lab_content_new'");
                $tableExists = $result && $result->num_rows > 0;
            } catch (Exception $e) {
                $tableExists = false;
            }
            
            if (!$tableExists) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'İçerik tablosu henüz oluşturulmamış']);
                exit();
            }
            
            // Önce mevcut kayıt var mı kontrol et - Prepared Statement kullan
            $sql = "SELECT id FROM myo_lab_content_new WHERE lab_id = ?";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare hatası: " . $mysqli->error);
            }
            
            $stmt->bind_param('i', $input['lab_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $existingContent = $result->fetch_assoc();
            $stmt->close();
            
            if ($existingContent) {
                // Mevcut kaydı güncelle - Prepared Statement kullan
                $updateFields = [];
                $updateValues = [];
                $types = '';
                
                // Gelen verileri kontrol et ve güncelle
                if (isset($input['lab_title'])) {
                    $updateFields[] = "lab_title = ?";
                    $updateValues[] = trim($input['lab_title']);
                    $types .= 's';
                }
                if (isset($input['main_image'])) {
                    $updateFields[] = "main_image = ?";
                    $updateValues[] = trim($input['main_image']);
                    $types .= 's';
                }
                if (isset($input['catalog_info'])) {
                    $updateFields[] = "catalog_info = ?";
                    $updateValues[] = trim($input['catalog_info']);
                    $types .= 's';
                }
                if (isset($input['detail_page_info'])) {
                    $updateFields[] = "detail_page_info = ?";
                    $updateValues[] = trim($input['detail_page_info']);
                    $types .= 's';
                }
                if (isset($input['alt_text'])) {
                    $updateFields[] = "alt_text = ?";
                    $updateValues[] = trim($input['alt_text']);
                    $types .= 's';
                }
                
                $updateFields[] = "added_by = ?";
                $updateValues[] = $_SESSION['username'] ?? 'unknown';
                $types .= 's';
                
                // WHERE clause için ID ekle
                $updateValues[] = $existingContent['id'];
                $types .= 'i';
                
                $sql = "UPDATE myo_lab_content_new SET " . implode(", ", $updateFields) . " WHERE id = ?";
                
                // Prepared Statement kullan
                $stmt = $mysqli->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare hatası: " . $mysqli->error);
                }
                
                // Bind parameters
                $stmt->bind_param($types, ...$updateValues);
                
                if (!$stmt->execute()) {
                    throw new Exception("Güncelleme hatası: " . $stmt->error);
                }
                
                $stmt->close();
                $message = 'İçerik başarıyla güncellendi';
            } else {
                // Yeni kayıt ekle - Prepared Statement kullan
                $insertFields = ["lab_id"];
                $insertValues = [$input['lab_id']];
                $placeholders = ["?"];
                $types = 'i'; // lab_id integer
                
                if (isset($input['lab_title'])) {
                    $insertFields[] = "lab_title";
                    $insertValues[] = trim($input['lab_title']);
                    $placeholders[] = "?";
                    $types .= 's';
                }
                if (isset($input['main_image'])) {
                    $insertFields[] = "main_image";
                    $insertValues[] = trim($input['main_image']);
                    $placeholders[] = "?";
                    $types .= 's';
                }
                if (isset($input['catalog_info'])) {
                    $insertFields[] = "catalog_info";
                    $insertValues[] = trim($input['catalog_info']);
                    $placeholders[] = "?";
                    $types .= 's';
                }
                if (isset($input['detail_page_info'])) {
                    $insertFields[] = "detail_page_info";
                    $insertValues[] = trim($input['detail_page_info']);
                    $placeholders[] = "?";
                    $types .= 's';
                }
                if (isset($input['alt_text'])) {
                    $insertFields[] = "alt_text";
                    $insertValues[] = trim($input['alt_text']);
                    $placeholders[] = "?";
                    $types .= 's';
                }
                
                $insertFields[] = "added_by";
                $insertValues[] = $_SESSION['username'] ?? 'unknown';
                $placeholders[] = "?";
                $types .= 's';
                
                $sql = "INSERT INTO myo_lab_content_new (" . implode(", ", $insertFields) . ") VALUES (" . implode(", ", $placeholders) . ")";
                
                // Prepared Statement kullan
                $stmt = $mysqli->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare hatası: " . $mysqli->error);
                }
                
                // Bind parameters
                $stmt->bind_param($types, ...$insertValues);
                
                if (!$stmt->execute()) {
                    throw new Exception("Ekleme hatası: " . $stmt->error);
                }
                
                $stmt->close();
                $message = 'İçerik başarıyla eklendi';
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
        } catch(Exception $e) {
            error_log('Content save error: ' . $e->getMessage());
            error_log('Error file: ' . $e->getFile() . ':' . $e->getLine());
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'İçerik kaydedilirken hata oluştu: ' . $e->getMessage(),
                'error_details' => [
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
        break;
        
    case 'DELETE':
        // İçerik sil
        $rawInput = file_get_contents('php://input');
        error_log('Raw input (DELETE): ' . $rawInput);
        
        $input = json_decode($rawInput, true);
        
        // JSON decode hatası kontrol et
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode hatası (DELETE): ' . json_last_error_msg());
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Geçersiz JSON formatı: ' . json_last_error_msg(),
                'raw_input' => $rawInput
            ]);
            exit();
        }
        
        if (!isset($input['lab_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
            exit();
        }
        
        try {
            // Önce tablo var mı kontrol et
            $tableExists = false;
            try {
                $result = $mysqli->query("SHOW TABLES LIKE 'myo_lab_content_new'");
                $tableExists = $result && $result->num_rows > 0;
            } catch (Exception $e) {
                $tableExists = false;
            }
            
            if (!$tableExists) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'İçerik tablosu henüz oluşturulmamış']);
                exit();
            }
            
            // Prepared Statement kullan
            $sql = "DELETE FROM myo_lab_content_new WHERE lab_id = ?";
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare hatası: " . $mysqli->error);
            }
            
            $stmt->bind_param('i', $input['lab_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Silme hatası: " . $stmt->error);
            }
            
            $stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'İçerik başarıyla silindi']);
        } catch(Exception $e) {
            error_log('Content delete error: ' . $e->getMessage());
            error_log('Error file: ' . $e->getFile() . ':' . $e->getLine());
            
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'İçerik silinirken hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    default:
        error_log('Desteklenmeyen HTTP metodu: ' . $method);
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen HTTP metodu']);
        break;
}

error_log('=== API Lab Contents tamamlandı ===');
?>
