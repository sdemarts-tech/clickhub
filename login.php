<?php
require_once 'includes/config.php';
require_once 'includes/supabase.php';
require_once 'includes/functions.php';

redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Get user by username
        $user = getUserByUsername($username);
        
        if (!$user) {
            $error = 'Invalid username or password';
        } elseif ($user['status'] === 'pending') {
            $error = 'Your account is pending approval';
        } elseif ($user['status'] === 'rejected') {
            $error = 'Your account has been rejected';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Invalid username or password';
        } else {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['points'] = $user['points'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? false;
            
            // Force redirect
            header('Location: dashboard.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>Login</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php
            $successMsg = getSuccessMessage();
            if ($successMsg):
            ?>
                <div class="success-message"><?php echo $successMsg; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <p class="auth-footer">
                Don't have an account? <a href="signup.php">Sign up here</a>
            </p>
            
            <p class="auth-footer">
                <a href="index.php">← Back to Home</a>
            </p>
        </div>
    </div>
</body>
</html>