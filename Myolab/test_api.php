<?php
// Test script to identify 500 errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 API Test - 500 Hata Analizi</h2>";

// Test 1: Basic PHP
echo "<h3>1. PHP Environment</h3>";
echo "<p>✅ PHP Version: " . phpversion() . "</p>";
echo "<p>✅ PDO Support: " . (class_exists('PDO') ? 'Yes' : 'No') . "</p>";
echo "<p>✅ JSON Support: " . (function_exists('json_encode') ? 'Yes' : 'No') . "</p>";

// Test 2: File includes
echo "<h3>2. File Includes</h3>";
try {
    if (file_exists('Database/confıg.php')) {
        echo "<p>✅ Database config file exists</p>";
        require_once 'Database/confıg.php';
        echo "<p>✅ Database config file loaded</p>";
    } else {
        echo "<p>❌ Database config file NOT found</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Error loading config: " . $e->getMessage() . "</p>";
    exit;
}

// Test 3: Database class
echo "<h3>3. Database Class</h3>";
try {
    if (class_exists('Database')) {
        echo "<p>✅ Database class exists</p>";
        $database = Database::getInstance();
        echo "<p>✅ Database instance created</p>";
    } else {
        echo "<p>❌ Database class NOT found</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Error creating database instance: " . $e->getMessage() . "</p>";
    exit;
}

// Test 4: Database connection
echo "<h3>4. Database Connection</h3>";
try {
    $pdo = $database->getConnection();
    echo "<p>✅ Database connection established</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 5: Test query
echo "<h3>5. Test Query</h3>";
try {
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "<p>✅ Test query successful: " . $result['test'] . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Test query failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 6: Check tables
echo "<h3>6. Database Tables</h3>";
$tables = ['users', 'categories', 'laboratories'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
            
            // Check record count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "<p>   Records: $count</p>";
        } else {
            echo "<p>❌ Table '$table' does NOT exist</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
    }
}

// Test 7: API endpoint test
echo "<h3>7. API Endpoint Test</h3>";
try {
    // Test the categories API logic
    $stmt = $pdo->prepare("SELECT c.id, c.name FROM categories c ORDER BY c.name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Categories query successful</p>";
    echo "<p>   Found " . count($categories) . " categories</p>";
    
    // Test JSON encoding
    $json = json_encode(['success' => true, 'data' => $categories]);
    if ($json !== false) {
        echo "<p>✅ JSON encoding successful</p>";
        echo "<p>   JSON length: " . strlen($json) . " characters</p>";
    } else {
        echo "<p>❌ JSON encoding failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ API test failed: " . $e->getMessage() . "</p>";
}

echo "<h3>✅ Test Complete!</h3>";
echo "<p>If you see any ❌ errors above, those need to be fixed first.</p>";
echo "<p>If everything shows ✅, the issue might be in the web server configuration.</p>";
?>
