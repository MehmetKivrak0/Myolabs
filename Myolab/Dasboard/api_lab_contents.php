<?php
// Hata raporlamayı aç (debug için)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

// Kullanıcı giriş yapmamışsa hata döndür (geçici olarak kapatıldı)
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}
*/

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



switch($method) {
    case 'GET':
        // Laboratuvar içeriklerini listele
        $labId = $_GET['lab_id'] ?? null;
        
        if (!$labId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
            exit();
        }
        

        
        try {
            $stmt = $pdo->prepare("SELECT * FROM lab_content_new WHERE lab_id = ?");
            $stmt->execute([$labId]);
            $content = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'İçerikler listelenirken hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Yeni içerik ekle veya güncelle
        $input = json_decode(file_get_contents('php://input'), true);
        
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
            // Laboratuvar var mı kontrol et
            $stmt = $pdo->prepare("SELECT id FROM laboratories WHERE id = ?");
            $stmt->execute([$input['lab_id']]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Geçersiz laboratuvar ID']);
                exit();
            }
            
            // Önce mevcut kayıt var mı kontrol et
            $checkStmt = $pdo->prepare("SELECT id FROM lab_content_new WHERE lab_id = ?");
            $checkStmt->execute([$input['lab_id']]);
            $existingContent = $checkStmt->fetch();
            
            if ($existingContent) {
                // Mevcut kaydı güncelle
                $updateFields = [];
                $updateValues = [];
                
                // Gelen verileri kontrol et ve güncelle
                if (isset($input['lab_title'])) {
                    $updateFields[] = "lab_title = ?";
                    $updateValues[] = trim($input['lab_title']);
                }
                if (isset($input['main_image'])) {
                    $updateFields[] = "main_image = ?";
                    $updateValues[] = trim($input['main_image']);
                }
                if (isset($input['catalog_info'])) {
                    $updateFields[] = "catalog_info = ?";
                    $updateValues[] = trim($input['catalog_info']);
                }
                if (isset($input['detail_page_info'])) {
                    $updateFields[] = "detail_page_info = ?";
                    $updateValues[] = trim($input['detail_page_info']);
                }
                if (isset($input['alt_text'])) {
                    $updateFields[] = "alt_text = ?";
                    $updateValues[] = trim($input['alt_text']);
                }
                
                $updateFields[] = "added_by = ?";
                $updateValues[] = $_SESSION['username'] ?? 'unknown';
                
                $updateValues[] = $existingContent['id'];
                
                $sql = "UPDATE lab_content_new SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateValues);
                
                $message = 'İçerik başarıyla güncellendi';
            } else {
                // Yeni kayıt ekle
                $insertFields = ["lab_id"];
                $insertValues = [$input['lab_id']];
                $placeholders = ["?"];
                
                if (isset($input['lab_title'])) {
                    $insertFields[] = "lab_title";
                    $insertValues[] = trim($input['lab_title']);
                    $placeholders[] = "?";
                }
                if (isset($input['main_image'])) {
                    $insertFields[] = "main_image";
                    $insertValues[] = trim($input['main_image']);
                    $placeholders[] = "?";
                }
                if (isset($input['catalog_info'])) {
                    $insertFields[] = "catalog_info";
                    $insertValues[] = trim($input['catalog_info']);
                    $placeholders[] = "?";
                }
                if (isset($input['detail_page_info'])) {
                    $insertFields[] = "detail_page_info";
                    $insertValues[] = trim($input['detail_page_info']);
                    $placeholders[] = "?";
                }
                if (isset($input['alt_text'])) {
                    $insertFields[] = "alt_text";
                    $insertValues[] = trim($input['alt_text']);
                    $placeholders[] = "?";
                }
                
                $insertFields[] = "added_by";
                $insertValues[] = $_SESSION['username'] ?? 'unknown';
                $placeholders[] = "?";
                
                $sql = "INSERT INTO lab_content_new (" . implode(", ", $insertFields) . ") VALUES (" . implode(", ", $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($insertValues);
                
                $message = 'İçerik başarıyla eklendi';
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
        } catch(PDOException $e) {
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
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['lab_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM lab_content_new WHERE lab_id = ?");
            $stmt->execute([$input['lab_id']]);
            
            echo json_encode(['success' => true, 'message' => 'İçerik başarıyla silindi']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'İçerik silinirken hata oluştu: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen HTTP metodu']);
        break;
}
?>
