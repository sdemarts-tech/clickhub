<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/supabase.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin access
requireAdmin();

// Get stats
$totalUsers = count($supabase->select(TABLE_USERS, 'id', []));
$pendingUsers = count($supabase->select(TABLE_USERS, 'id', ['status' => 'pending']));
$totalGames = count($supabase->select(TABLE_GAMES, 'id', []));
$totalPlays = count($supabase->select(TABLE_GAME_PLAYS, 'id', []));
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
        <header>
            <h1>Admin Dashboard</h1>
            <nav>
                <a href="../dashboard.php">User Dashboard</a>
                <a href="approve-users.php">Approve Users</a>
                <a href="manage-games.php">Manage Games</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p class="stat-number"><?php echo $totalUsers; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Approval</h3>
                <p class="stat-number"><?php echo $pendingUsers; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Games</h3>
                <p class="stat-number"><?php echo $totalGames; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Plays</h3>
                <p class="stat-number"><?php echo $totalPlays; ?></p>
            </div>
        </div>

        <div class="admin-links">
            <a href="approve-users.php" class="btn btn-primary">Approve Pending Users</a>
            <a href="manage-games.php" class="btn btn-primary">Manage Games</a>
        </div>
    </div>

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }

        .admin-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .admin-links .btn {
            flex: 1;
        }
    </style>
</body>
</html>