<?php
session_start();
header('Content-Type: application/json');

try {
    // Tüm session verilerini temizle
    session_unset();
    session_destroy();
    
    // Başarılı response
    echo json_encode([
        'success' => true,
        'message' => 'Başarıyla çıkış yapıldı'
    ]);
    
} catch (Exception $e) {
    // Hata response
    echo json_encode([
        'success' => false,
        'message' => 'Çıkış yapılırken hata oluştu: ' . $e->getMessage()
    ]);
}
?>
