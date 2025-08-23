<?php
// Test version of categories API without session requirements
header('Content-Type: application/json');

try {
    require_once 'Database/confıg.php';
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    // Test the categories query
    $stmt = $pdo->prepare("SELECT c.id, c.name FROM categories c ORDER BY c.name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Categories API test successful',
        'data' => $categories,
        'count' => count($categories)
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Categories API test failed',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
