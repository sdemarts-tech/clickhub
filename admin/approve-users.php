<?php
require_once '../includes/config.php';
require_once '../includes/supabase.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = sanitize($_POST['user_id']);
    $action = sanitize($_POST['action']);
    
    if ($action === 'approve') {
        // Update user status
        $result = $supabase->update(TABLE_USERS, ['status' => 'approved'], ['id' => $userId]);
        
        if (!isset($result['error'])) {
            // Check if user has a referrer
            $user = getUserById($userId);
            if ($user && $user['referrer_id']) {
                // Award commission to referrer
                updateUserPoints($user['referrer_id'], POINTS_REFERRAL_COMMISSION);
                
                // Update referral record
                $referrals = $supabase->select(TABLE_REFERRALS, '*', [
                    'referee_id' => $userId,
                    'status' => 'pending'
                ]);
                
                if (!empty($referrals) && !isset($referrals['error'])) {
                    $supabase->update(
                        TABLE_REFERRALS,
                        [
                            'commission_earned' => POINTS_REFERRAL_COMMISSION,
                            'status' => 'completed'
                        ],
                        ['id' => $referrals[0]['id']]
                    );
                }
            }
            
            setSuccessMessage('User approved successfully!');
        } else {
            setErrorMessage('Error approving user: ' . $result['error']);
        }
    } elseif ($action === 'reject') {
        $result = $supabase->update(TABLE_USERS, ['status' => 'rejected'], ['id' => $userId]);
        
        if (!isset($result['error'])) {
            setSuccessMessage('User rejected.');
        } else {
            setErrorMessage('Error rejecting user: ' . $result['error']);
        }
    }
    
    header('Location: approve-users.php');
    exit;
}

// Get pending users
$pendingUsers = $supabase->select(TABLE_USERS, '*', ['status' => 'pending']);
if (isset($pendingUsers['error'])) {
    $pendingUsers = [];
}

// Get referrer info for each user
foreach ($pendingUsers as &$user) {
    if ($user['referrer_id']) {
        $referrer = getUserById($user['referrer_id']);
        $user['referrer_username'] = $referrer ? $referrer['username'] : 'Unknown';
    } else {
        $user['referrer_username'] = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Users - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>👥 Approve Users</h1>
            <div class="header-nav">
                <a href="index.php" class="btn btn-small">← Admin Dashboard</a>
            </div>
        </header>

        <main class="admin-content">
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

            <?php if (empty($pendingUsers)): ?>
                <div class="info-message">
                    <h3>No Pending Users</h3>
                    <p>All registrations have been processed.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Referred By</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingUsers as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['referrer_username']): ?>
                                            <span class="badge">👥 <?php echo htmlspecialchars($user['referrer_username']); ?></span>
                                        <?php else: ?>
                                            <span class="badge-gray">Direct</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDateTime($user['created_at']); ?></td>
                                    <td class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-small">✓ Approve</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-small" 
                                                    onclick="return confirm('Are you sure you want to reject this user?')">
                                                ✗ Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>