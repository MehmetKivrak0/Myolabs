<?php
// Debug test dosyası
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

echo "Debug Test Başladı<br>";

try {
    echo "1. Veritabanı config yükleniyor...<br>";
    require_once '../Database/config.php';
    echo "2. Database instance oluşturuluyor...<br>";
    $database = Database::getInstance();
    echo "3. Bağlantı alınıyor...<br>";
    $mysqli = $database->getConnection();
    echo "4. Bağlantı başarılı!<br>";
    
    // Test sorgusu
    echo "5. Test sorgusu çalıştırılıyor...<br>";
    $result = $mysqli->query("SELECT COUNT(*) as count FROM devices");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "6. Cihaz sayısı: " . $row['count'] . "<br>";
    } else {
        echo "6. Sorgu hatası: " . $mysqli->error . "<br>";
    }
    
    echo "✅ Tüm testler başarılı!<br>";
    
} catch(Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "Debug Test Tamamlandı<br>";
?>
