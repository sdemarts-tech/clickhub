<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

redirectIfLoggedIn();

$error = '';
$success = '';
$referrer_username = isset($_GET['ref']) ? sanitize($_GET['ref']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username exists
        $existingUser = getUserByUsername($username);
        if ($existingUser) {
            $error = 'Username already taken';
        } else {
            // Check if email exists
            $existingEmail = getUserByEmail($email);
            if ($existingEmail) {
                $error = 'Email already registered';
            } else {
                // Get referrer ID if referrer username provided
                $referrerId = null;
                if (!empty($referrer_username)) {
                    $referrer = getUserByUsername($referrer_username);
                    if ($referrer) {
                        $referrerId = $referrer['id'];
                    }
                }
                
                // Create user in database
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'referrer_id' => $referrerId,
                    'points' => 0,
                    'status' => 'pending'
                ];
                
                $result = $supabase->insert(TABLE_USERS, $userData);
                
                if (isset($result['error'])) {
                    $error = 'Registration failed: ' . $result['error'];
                } else {
                    $success = 'Registration successful! Please wait for admin approval.';
                    
                    // Create referral record if there's a referrer
                    if ($referrerId && !empty($result[0]['id'])) {
                        $referralData = [
                            'referrer_id' => $referrerId,
                            'referee_id' => $result[0]['id'],
                            'commission_earned' => 0,
                            'status' => 'pending'
                        ];
                        $supabase->insert(TABLE_REFERRALS, $referralData);
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>Create Account</h1>
            
            <?php if ($referrer_username): ?>
                <div class="info-message">
                    <p>📢 Referred by: <strong><?php echo htmlspecialchars($referrer_username); ?></strong></p>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo $success; ?>
                    <p><a href="login.php">Go to Login</a></p>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small>Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
                </form>
            <?php endif; ?>
            
            <p class="auth-footer">
                Already have an account? <a href="login.php">Login here</a>
            </p>
            
            <p class="auth-footer">
                <a href="index.php">← Back to Home</a>
            </p>
        </div>
    </div>
</body>
</html>