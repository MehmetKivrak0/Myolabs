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
            $stmt = $pdo->prepare("SELECT * FROM lab_contents WHERE lab_id = ? ORDER BY content_type");
            $stmt->execute([$labId]);
            $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // İçerikleri tip bazında organize et
            $organizedContents = [];
            foreach ($contents as $content) {
                // Sadece content_type dolu olanları al
                if ($content['content_type'] && !empty($content['content_type'])) {
                    $organizedContents[$content['content_type']] = $content;
                }
            }
            

            

            
            echo json_encode(['success' => true, 'data' => $organizedContents]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'İçerikler listelenirken hata oluştu']);
        }
        break;
        
    case 'POST':
        // Yeni içerik ekle veya güncelle
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Debug için gelen veriyi logla
        error_log('Received content data: ' . json_encode($input));
        
        if (!isset($input['lab_id']) || !isset($input['content_type']) || !isset($input['content_value'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Laboratuvar ID, içerik tipi ve değeri gereklidir',
                'received_data' => $input,
                'missing_fields' => [
                    'lab_id' => isset($input['lab_id']),
                    'content_type' => isset($input['content_type']),
                    'content_value' => isset($input['content_value'])
                ]
            ]);
            exit();
        }
        
        // content_type boş mu kontrol et
        if (empty($input['content_type'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'İçerik tipi boş olamaz',
                'received_data' => $input
            ]);
            exit();
        }
        
        // Geçerli content_type kontrolü
        $validTypes = ['main_image', 'about_text', 'lab_title', 'detail_about_text'];
        if (!in_array($input['content_type'], $validTypes)) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Geçersiz içerik tipi: ' . $input['content_type'],
                'valid_types' => $validTypes,
                'received_type' => $input['content_type']
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
            
            // Mevcut içerik var mı kontrol et
            $stmt = $pdo->prepare("SELECT id FROM lab_contents WHERE lab_id = ? AND content_type = ?");
            $stmt->execute([$input['lab_id'], $input['content_type']]);
            $existingContent = $stmt->fetch();
            
            // Debug için log
            error_log('Checking existing content for lab_id: ' . $input['lab_id'] . ', content_type: "' . $input['content_type'] . '"');
            error_log('Existing content found: ' . ($existingContent ? 'YES' : 'NO'));
            
            if ($existingContent) {
                // Güncelle
                $stmt = $pdo->prepare("UPDATE lab_contents SET content_value = ?, alt_text = ?, updated_at = CURRENT_TIMESTAMP, added_by = ? WHERE id = ?");
                $stmt->execute([
                    trim($input['content_value']),
                    !empty($input['alt_text']) ? trim($input['alt_text']) : null,
                    $_SESSION['username'] ?? 'unknown',
                    $existingContent['id']
                ]);
                $message = 'İçerik başarıyla güncellendi';
            } else {
                // Yeni ekle
                $stmt = $pdo->prepare("INSERT INTO lab_contents (lab_id, content_type, content_value, alt_text, added_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $input['lab_id'],
                    $input['content_type'],
                    trim($input['content_value']),
                    !empty($input['alt_text']) ? trim($input['alt_text']) : null,
                    $_SESSION['username'] ?? 'unknown'
                ]);
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
        
        if (!isset($input['lab_id']) || !isset($input['content_type'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Laboratuvar ID ve içerik tipi gereklidir']);
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM lab_contents WHERE lab_id = ? AND content_type = ?");
            $stmt->execute([$input['lab_id'], $input['content_type']]);
            
            echo json_encode(['success' => true, 'message' => 'İçerik başarıyla silindi']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'İçerik silinirken hata oluştu']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen HTTP metodu']);
        break;
}
?>
