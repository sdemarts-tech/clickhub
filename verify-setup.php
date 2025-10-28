<?php
// ============================================
// FILE VERIFICATION SCRIPT
// Run this to check if all your files are working
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Clickhub File Verification</h1>";
echo "<hr>";

// List of all main files that should exist
$mainFiles = [
    'index.php',
    'login.php',
    'signup.php',
    'dashboard.php',
    'captcha.php',
    'games.php',
    'play-game.php',
    'logout.php',
    'admin/index.php',
    'admin/approve-users.php',
    'admin/manage-games.php'
];

$includeFiles = [
    'includes/config.php',
    'includes/supabase.php',
    'includes/functions.php'
];

// Check include files first
echo "<h2>1. Critical Include Files</h2>";
foreach ($includeFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file EXISTS<br>";
        
        // Try to include it
        try {
            if ($file === 'includes/config.php') {
                require_once $file;
                echo "   ✅ Config loaded successfully<br>";
                
                // Check constants
                $requiredConstants = [
                    'SUPABASE_URL',
                    'SUPABASE_ANON_KEY',
                    'SUPABASE_SERVICE_KEY',
                    'TURNSTILE_SITE_KEY',
                    'TURNSTILE_SECRET_KEY',
                    'TABLE_USERS',
                    'TABLE_GAMES',
                    'TABLE_GAME_PLAYS',
                    'TABLE_CAPTCHA_SOLVES',
                    'TABLE_REFERRALS'
                ];
                
                foreach ($requiredConstants as $const) {
                    if (defined($const)) {
                        $value = constant($const);
                        if (strpos($const, 'YOUR_') === false && $value !== 'YOUR_PROJECT_ID.supabase.co' && !empty($value)) {
                            echo "   ✅ $const is defined<br>";
                        } else {
                            echo "   ⚠️ $const needs to be set with real value<br>";
                        }
                    } else {
                        echo "   ❌ $const is NOT defined<br>";
                    }
                }
            } elseif ($file === 'includes/supabase.php') {
                require_once $file;
                echo "   ✅ Supabase loaded successfully<br>";
                
                // Check if class exists
                if (class_exists('SupabaseClient')) {
                    echo "   ✅ SupabaseClient class exists<br>";
                    
                    // Try to instantiate
                    try {
                        $testClient = new SupabaseClient();
                        echo "   ✅ SupabaseClient instantiated<br>";
                    } catch (Exception $e) {
                        echo "   ❌ Error instantiating: " . $e->getMessage() . "<br>";
                    }
                } else {
                    echo "   ❌ SupabaseClient class NOT found<br>";
                }
            } elseif ($file === 'includes/functions.php') {
                require_once $file;
                echo "   ✅ Functions loaded successfully<br>";
                
                // Check if key functions exist
                $requiredFunctions = [
                    'isLoggedIn',
                    'requireLogin',
                    'isAdmin',
                    'getUserById',
                    'getUserByUsername',
                    'verifyTurnstile'
                ];
                
                foreach ($requiredFunctions as $func) {
                    if (function_exists($func)) {
                        echo "   ✅ Function $func() exists<br>";
                    } else {
                        echo "   ❌ Function $func() NOT found<br>";
                    }
                }
            }
        } catch (Exception $e) {
            echo "   ❌ ERROR loading $file: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ $file is MISSING<br>";
    }
    echo "<br>";
}

echo "<hr>";

// Check main files
echo "<h2>2. Main Application Files</h2>";
foreach ($mainFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file EXISTS<br>";
        
        // Check if it includes the required files
        $content = file_get_contents($file);
        
        $hasConfig = (strpos($content, "require_once 'includes/config.php'") !== false) ||
                     (strpos($content, 'require_once "includes/config.php"') !== false) ||
                     (strpos($content, "require_once __DIR__ . '/includes/config.php'") !== false);
        
        $hasSupabase = (strpos($content, "require_once 'includes/supabase.php'") !== false) ||
                       (strpos($content, 'require_once "includes/supabase.php"') !== false) ||
                       (strpos($content, "require_once __DIR__ . '/includes/supabase.php'") !== false);
        
        $hasFunctions = (strpos($content, "require_once 'includes/functions.php'") !== false) ||
                        (strpos($content, 'require_once "includes/functions.php"') !== false) ||
                        (strpos($content, "require_once __DIR__ . '/includes/functions.php'") !== false);
        
        echo "   " . ($hasConfig ? "✅" : "⚠️") . " Config include<br>";
        echo "   " . ($hasSupabase ? "✅" : "⚠️") . " Supabase include<br>";
        echo "   " . ($hasFunctions ? "✅" : "⚠️") . " Functions include<br>";
        
    } else {
        echo "⚠️ $file is MISSING (might be okay if not created yet)<br>";
    }
    echo "<br>";
}

echo "<hr>";

// Test database connection
echo "<h2>3. Database Connection Test</h2>";
if (defined('SUPABASE_URL') && class_exists('SupabaseClient')) {
    try {
        $supabase = new SupabaseClient();
        echo "✅ Supabase client created<br>";
        
        // Try a simple query
        $result = $supabase->select(TABLE_USERS, 'id,username', []);
        
        if (isset($result['error'])) {
            echo "❌ Database query error: " . $result['error'] . "<br>";
        } else {
            echo "✅ Database connection successful!<br>";
            echo "   Found " . count($result) . " users in database<br>";
        }
    } catch (Exception $e) {
        echo "❌ Connection error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Cannot test - config or supabase not loaded<br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>Check all items above. Fix any ❌ or ⚠️ issues before using the site.</p>";
echo "<p><strong>Delete this file after verification!</strong></p>";
?>