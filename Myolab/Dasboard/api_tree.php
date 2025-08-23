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

try {
    // Kategorileri ve laboratuvarları tek sorguda al
    $stmt = $pdo->prepare("
        SELECT 
            c.id as category_id,
            c.name as category_name,
            l.id as lab_id,
            l.name as lab_name,
            l.redirect_url
        FROM categories c
        LEFT JOIN laboratories l ON c.id = l.category_id
        ORDER BY c.name, l.name
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
                'name' => $row['lab_name'],
                'redirect_url' => $row['redirect_url']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'data' => $tree]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ağaç yapısı oluşturulurken hata oluştu']);
}
?>
