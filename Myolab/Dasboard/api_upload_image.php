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
    require_once '../Database/confıg.php';
    $database = Database::getInstance();
    $pdo = $database->getConnection();
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST metodu desteklenir']);
    exit();
}

// Laboratuvar ID kontrolü
$labId = $_POST['lab_id'] ?? null;
if (!$labId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Laboratuvar ID gereklidir']);
    exit();
}

// Laboratuvar bilgilerini al
try {
    $stmt = $pdo->prepare("SELECT l.*, c.name as category_name FROM laboratories l LEFT JOIN categories c ON l.category_id = c.id WHERE l.id = ?");
    $stmt->execute([$labId]);
    $lab = $stmt->fetch();
    
    if (!$lab) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Geçersiz laboratuvar ID']);
        exit();
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Laboratuvar bilgileri alınamadı']);
    exit();
}

// Dosya yükleme kontrolü
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resim yüklenemedi']);
    exit();
}

$file = $_FILES['image'];

// Dosya türü kontrolü
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sadece resim dosyaları kabul edilir (JPG, PNG, GIF, WebP)']);
    exit();
}

// Dosya boyutu kontrolü (5MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dosya boyutu 5MB\'dan büyük olamaz']);
    exit();
}

// Klasör yolu oluştur - laboratuvar klasörüne yükle
$uploadDir = '../image/uploads/' . preg_replace('/[^a-zA-Z0-9]/', '_', $lab['category_name']) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $lab['name']);

// Klasör yoksa oluştur
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Klasör oluşturulamadı']);
        exit();
    }
}

// Benzersiz dosya adı oluştur
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$fileName = 'device_' . time() . '_' . uniqid() . '.' . $fileExtension;
$filePath = $uploadDir . '/' . $fileName;

// Aynı boyutta dosya var mı kontrol et (kopya önleme)
$existingFiles = glob($uploadDir . '/*');
foreach ($existingFiles as $existingFile) {
    if (filesize($existingFile) === $file['size']) {
        // Aynı boyutta dosya var, yeni yükleme yapma
        $existingFileName = basename($existingFile);
        echo json_encode([
            'success' => true,
            'message' => 'Bu resim zaten mevcut, yeni yükleme yapılmadı',
            'data' => [
                'file_name' => $existingFileName,
                'file_path' => $existingFile,
                'url' => 'image/uploads/' . preg_replace('/[^a-zA-Z0-9]/', '_', $lab['category_name']) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $lab['name']) . '/' . $existingFileName,
                'size' => $file['size'],
                'type' => $file['type']
            ]
        ]);
        exit();
    }
}

// Dosyayı yükle
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Dosya yüklenemedi']);
    exit();
}

// Başarılı yanıt
echo json_encode([
    'success' => true,
    'message' => 'Resim başarıyla yüklendi',
    'data' => [
        'file_name' => $fileName,
        'file_path' => $filePath,
        'url' => 'image/uploads/' . preg_replace('/[^a-zA-Z0-9]/', '_', $lab['category_name']) . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $lab['name']) . '/' . $fileName,
        'size' => $file['size'],
        'type' => $file['type']
    ]
]);
?>
