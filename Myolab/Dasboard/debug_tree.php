<?php
// Debug script for tree API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Tree API Debug</h2>";

// Test 1: Check if session is working
echo "<h3>1. Session Test</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "User ID in session: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Username in session: " . ($_SESSION['username'] ?? 'NOT SET') . "<br>";

// Test 2: Check database connection
echo "<h3>2. Database Connection Test</h3>";
try {
    require_once '../Database/confıg.php';
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    echo "✅ Database connection successful<br>";
    
    // Test 3: Check if tables exist
    echo "<h3>3. Database Tables Test</h3>";
    $tables = ['categories', 'laboratories', 'lab_content_new'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            echo "Table '$table': " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
            
            if ($exists) {
                // Check table structure
                $stmt = $pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll();
                echo "Columns in $table:<br>";
                foreach ($columns as $column) {
                    echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
                }
                echo "<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
        }
    }
    
    // Test 4: Check if data exists
    echo "<h3>4. Data Test</h3>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
        $categoryCount = $stmt->fetch()['count'];
        echo "Categories count: $categoryCount<br>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM laboratories");
        $labCount = $stmt->fetch()['count'];
        echo "Laboratories count: $labCount<br>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM lab_content_new");
        $contentCount = $stmt->fetch()['count'];
        echo "Lab content new count: $contentCount<br>";
        
        // Test the actual query from api_tree.php
        echo "<h3>5. Tree Query Test</h3>";
        $stmt = $pdo->prepare("
            SELECT 
                c.id as category_id,
                c.name as category_name,
                l.id as lab_id,
                l.name as lab_name
            FROM categories c
            LEFT JOIN laboratories l ON c.id = l.category_id
            ORDER BY c.name, l.name
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Query executed successfully. Found " . count($results) . " rows<br>";
        
        if (count($results) > 0) {
            echo "Sample data:<br>";
            foreach (array_slice($results, 0, 3) as $row) {
                echo "- Category: " . $row['category_name'] . " | Lab: " . ($row['lab_name'] ?? 'NULL') . "<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error in data test: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<h3>6. File Path Test</h3>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Config file path: " . __DIR__ . "/../Database/confıg.php<br>";
echo "Config file exists: " . (file_exists(__DIR__ . "/../Database/confıg.php") ? "✅ YES" : "❌ NO") . "<br>";
?>
