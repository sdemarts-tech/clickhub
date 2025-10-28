<?php
require_once '../includes/config.php';
require_once '../includes/supabase.php';
require_once '../includes/functions.php';

requireAdmin();

// Get statistics
$allUsers = $supabase->select(TABLE_USERS);
$totalUsers = is_array($allUsers) ? count($allUsers) : 0;

$pendingUsers = $supabase->select(TABLE_USERS, '*', ['status' => 'pending']);
$totalPending = is_array($pendingUsers) ? count($pendingUsers) : 0;

$approvedUsers = $supabase->select(TABLE_USERS, '*', ['status' => 'approved']);
$totalApproved = is_array($approvedUsers) ? count($approvedUsers) : 0;

// Calculate total points distributed
$totalPoints = 0;
if (is_array($allUsers)) {
    foreach ($allUsers as $user) {
        $totalPoints += $user['points'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>⚙️ Admin Dashboard</h1>
                <div class="user-info">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../dashboard.php" class="btn btn-small">← User Dashboard</a>
                    <a href="../logout.php" class="btn btn-small">Logout</a>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <?php
            $successMsg = getSuccessMessage();
            $errorMsg = getErrorMessage();
            if ($successMsg):
            ?>
                <div class="success-message"><?php echo $successMsg; ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="error-message"><?php echo $errorMsg; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $totalUsers; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $totalPending; ?></h3>
                        <p>Pending Approval</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $totalApproved; ?></h3>
                        <p>Approved Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($totalPoints); ?></h3>
                        <p>Total Points Distributed</p>
                    </div>
                </div>
            </div>

            <div class="admin-actions">
                <h2>Quick Actions</h2>
                <div class="action-grid">
                    <div class="action-card">
                        <h3>👥 User Management</h3>
                        <p>Approve or reject pending user registrations</p>
                        <?php if ($totalPending > 0): ?>
                            <a href="approve-users.php" class="btn btn-primary">
                                Approve Users (<?php echo $totalPending; ?>)
                            </a>
                        <?php else: ?>
                            <button class="btn btn-disabled" disabled>No Pending Users</button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-card">
                        <h3>🎮 Game Management</h3>
                        <p>Add, edit, or manage available games</p>
                        <a href="manage-games.php" class="btn btn-primary">Manage Games</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>