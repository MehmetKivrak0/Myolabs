<?php
// Hata raporlamayı aç (debug için)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Kullanıcı giriş yapmamışsa hata döndür
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit();
}

try {
    require_once '../Database/config.php';
    $database = Database::getInstance();
    $mysqli = $database->getConnection();
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage(),
        'error_details' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
    exit();
}

try {
    // Kategorileri ve laboratuvarları tek sorguda al
    $sql = "
        SELECT 
            c.id as category_id,
            c.name as category_name,
            l.id as lab_id,
            l.name as lab_name
        FROM myo_categories c
        LEFT JOIN myo_laboratories l ON c.id = l.category_id
        ORDER BY c.name, l.name
    ";
    $result = $mysqli->query($sql);
    
    if ($result === false) {
        throw new Exception("Sorgu hatası: " . $mysqli->error);
    }
    
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    
    // Hiyerarşik yapıyı oluştur
    $tree = [];
    $categories = [];
    
    foreach ($results as $row) {
        $categoryId = $row['category_id'];
        
        // Kategori henüz eklenmemişse ekle
        if (!isset($categories[$categoryId])) {
            $categories[$categoryId] = [
                'id' => $categoryId,
                'name' => $row['category_name'],
                'laboratories' => []
            ];
            $tree[] = &$categories[$categoryId];
        }
        
        // Laboratuvar varsa ekle
        if ($row['lab_id']) {
            $categories[$categoryId]['laboratories'][] = [
                'id' => $row['lab_id'],
                'name' => $row['lab_name']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'data' => $tree]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ağaç yapısı oluşturulurken hata oluştu',
        'error_details' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>
