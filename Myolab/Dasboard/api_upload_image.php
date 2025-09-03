<?php
// Hata raporlamayı kapat (production için)
error_reporting(0);
ini_set('display_errors', 0);

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
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST metodu desteklenir']);
    exit();
}

// Laboratuvar ID kontrolü (opsiyonel - cihaz düzenleme için)
$labId = $_POST['lab_id'] ?? null;
$deviceId = $_POST['device_id'] ?? null;

// Eğer lab_id yoksa device_id'den laboratuvar bilgisini al
if (!$labId && $deviceId) {
    try {
        $sql = "SELECT l.*, c.name as category_name FROM devices d 
                INNER JOIN laboratories l ON d.lab_id = l.id 
                LEFT JOIN categories c ON l.category_id = c.id 
                WHERE d.id = '" . $mysqli->real_escape_string($deviceId) . "'";
        $result = $mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $lab = $result->fetch_assoc();
            $labId = $lab['id'];
        }
    } catch(Exception $e) {
        // Hata durumunda devam et
    }
}

// Eğer hala lab_id yoksa varsayılan klasör kullan
if (!$labId) {
    $labId = 'default';
    $lab = [
        'name' => 'default',
        'category_name' => 'default'
    ];
}

// Laboratuvar bilgilerini al (eğer henüz alınmamışsa)
if (!isset($lab) || !$lab) {
    try {
        $sql = "SELECT l.*, c.name as category_name FROM laboratories l LEFT JOIN categories c ON l.category_id = c.id WHERE l.id = '" . $mysqli->real_escape_string($labId) . "'";
        $result = $mysqli->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $lab = $result->fetch_assoc();
        }
        
        if (!$lab && $labId !== 'default') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Geçersiz laboratuvar ID']);
            exit();
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Laboratuvar bilgileri alınamadı: ' . $e->getMessage()]);
        exit();
    }
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
$safeCategoryName = sanitizeFolderName($lab['category_name']);
$safeLabName = sanitizeFolderName($lab['name']);
$uploadDir = '../image/uploads/' . $safeCategoryName . '_' . $safeLabName;

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
