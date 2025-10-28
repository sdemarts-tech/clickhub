<?php
// RESET ADMIN PASSWORD
// Upload this as reset-password.php and run it once

require_once 'includes/config.php';
require_once 'includes/supabase.php';

echo "<h1>Reset Admin Password</h1>";

// Generate a fresh password hash for "admin123"
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<p>New password: <strong>admin123</strong></p>";
echo "<p>Generated hash: <code>" . $hash . "</code></p>";

// Update the admin user
$result = $supabase->update(
    TABLE_USERS,
    ['password' => $hash],
    ['username' => 'admin']
);

if (isset($result['error'])) {
    echo "<p style='color:red;'>ERROR: " . $result['error'] . "</p>";
} else {
    echo "<p style='color:green;'>✓ Password updated successfully!</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    
    // Test the password
    $user = $supabase->select(TABLE_USERS, '*', ['username' => 'admin']);
    if (!empty($user) && password_verify($password, $user[0]['password'])) {
        echo "<p style='color:green;'>✓ Password verification test PASSED!</p>";
    } else {
        echo "<p style='color:red;'>✗ Password verification test FAILED</p>";
    }
}

echo "<hr>";
echo "<p><strong>After successful reset, delete this file for security!</strong></p>";
?>