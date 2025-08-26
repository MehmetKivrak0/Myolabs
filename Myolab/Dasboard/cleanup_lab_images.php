<?php
// Laboratuvar klasörlerindeki gereksiz resimleri temizleme scripti
// Bu script, laboratuvar klasörlerindeki resimleri siler çünkü artık sadece uploads klasöründe tutulacak

require_once 'Database/confıg.php';
$database = Database::getInstance();
$pdo = $database->getConnection();

echo "Laboratuvar klasörlerindeki resimler temizleniyor...\n";

// Laboratuvar klasörlerini listele
$labDirs = [
    'image/uploads/Bilgisayar_Bil_lab1',
    'image/uploads/Bilgisayar_Bil_lab2'
];

foreach ($labDirs as $labDir) {
    if (is_dir($labDir)) {
        echo "Klasör temizleniyor: $labDir\n";
        
        $files = glob($labDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileName = basename($file);
                echo "Siliniyor: $fileName\n";
                unlink($file);
            }
        }
        
        // Klasörü sil
        rmdir($labDir);
        echo "Klasör silindi: $labDir\n";
    }
}

echo "Temizlik tamamlandı!\n";
echo "Artık resimler sadece image/uploads klasöründe tutulacak.\n";
?>
