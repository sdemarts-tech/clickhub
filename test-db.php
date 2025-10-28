<?php
// TEST DATABASE CONNECTION
// Upload this as test-db.php and visit it in your browser

require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

echo "<h1>Database Connection Test</h1>";

// Test 1: Check if config is loaded
echo "<h2>1. Configuration Check</h2>";
echo "Supabase URL: " . SUPABASE_URL . "<br>";
echo "Anon Key: " . substr(SUPABASE_ANON_KEY, 0, 20) . "...<br>";
echo "Service Key: " . substr(SUPABASE_SERVICE_KEY, 0, 20) . "...<br>";

// Test 2: Try to get all users
echo "<h2>2. Fetch All Users</h2>";
$allUsers = $supabase->select(TABLE_USERS);

if (isset($allUsers['error'])) {
    echo "<p style='color:red;'>ERROR: " . $allUsers['error'] . "</p>";
} else {
    echo "<p style='color:green;'>Success! Found " . count($allUsers) . " user(s)</p>";
    echo "<pre>";
    print_r($allUsers);
    echo "</pre>";
}

// Test 3: Try to get admin user specifically
echo "<h2>3. Fetch Admin User</h2>";
$adminUser = getUserByUsername('admin');

if ($adminUser) {
    echo "<p style='color:green;'>Admin user found!</p>";
    echo "<pre>";
    print_r($adminUser);
    echo "</pre>";
    
    // Test password
    echo "<h3>Password Test:</h3>";
    $testPassword = 'admin123';
    echo "Testing password: " . $testPassword . "<br>";
    echo "Stored hash: " . $adminUser['password'] . "<br>";
    
    if (password_verify($testPassword, $adminUser['password'])) {
        echo "<p style='color:green;'>✓ Password verification SUCCESS!</p>";
    } else {
        echo "<p style='color:red;'>✗ Password verification FAILED</p>";
        
        // Try to create correct hash
        $correctHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "Correct hash should be something like: " . $correctHash . "<br>";
    }
} else {
    echo "<p style='color:red;'>Admin user NOT found!</p>";
}

// Test 4: Test login logic
echo "<h2>4. Login Logic Test</h2>";
$username = 'admin';
$password = 'admin123';

$user = getUserByUsername($username);

if (!$user) {
    echo "<p style='color:red;'>User not found</p>";
} elseif ($user['status'] === 'pending') {
    echo "<p style='color:orange;'>Account is pending approval</p>";
} elseif ($user['status'] === 'rejected') {
    echo "<p style='color:red;'>Account has been rejected</p>";
} elseif (!password_verify($password, $user['password'])) {
    echo "<p style='color:red;'>Password does not match</p>";
} else {
    echo "<p style='color:green;'>✓ LOGIN WOULD SUCCEED!</p>";
}
?>