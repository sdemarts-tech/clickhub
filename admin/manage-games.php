<?php
require_once '../includes/config.php';
require_once '../includes/supabase.php';
require_once '../includes/functions.php';

requireAdmin();

$error = '';
$success = '';

// Handle add game
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $file_path = sanitize($_POST['file_path']);
    $points_reward = intval($_POST['points_reward']);
    
    if (empty($name) || empty($file_path)) {
        $error = 'Name and file path are required';
    } else {
        $gameData = [
            'name' => $name,
            'description' => $description,
            'file_path' => $file_path,
            'points_reward' => $points_reward,
            'status' => 'active'
        ];
        
        $result = $supabase->insert(TABLE_GAMES, $gameData);
        
        if (!isset($result['error'])) {
            setSuccessMessage('Game added successfully!');
            header('Location: manage-games.php');
            exit;
        } else {
            $error = 'Error adding game: ' . $result['error'];
        }
    }
}

// Handle toggle status
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $gameId = sanitize($_GET['id']);
    $newStatus = $_GET['toggle'] === 'active' ? 'inactive' : 'active';
    
    $result = $supabase->update(TABLE_GAMES, ['status' => $newStatus], ['id' => $gameId]);
    
    if (!isset($result['error'])) {
        setSuccessMessage('Game status updated!');
    } else {
        setErrorMessage('Error updating game status');
    }
    
    header('Location: manage-games.php');
    exit;
}

// Get all games
$games = $supabase->select(TABLE_GAMES);
if (isset($games['error'])) {
    $games = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Games - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>🎮 Manage Games</h1>
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
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-section">
                <h2>Add New Game</h2>
                <form method="POST" action="" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Game Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="points_reward">Points Reward *</label>
                            <input type="number" id="points_reward" name="points_reward" 
                                   value="<?php echo POINTS_PER_GAME; ?>" required min="1">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="file_path">File Path (URL or local path) *</label>
                        <input type="text" id="file_path" name="file_path" 
                               placeholder="games/snake.html" required>
                        <small>Example: games/snake.html or https://example.com/game.html</small>
                    </div>
                    
                    <button type="submit" name="add_game" class="btn btn-primary">Add Game</button>
                </form>
            </div>

            <div class="table-section">
                <h2>Existing Games</h2>
                <?php if (empty($games)): ?>
                    <p>No games added yet.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Points</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($games as $game): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($game['name']); ?></td>
                                        <td><?php echo htmlspecialchars($game['description']); ?></td>
                                        <td><?php echo $game['points_reward']; ?> pts</td>
                                        <td>
                                            <?php if ($game['status'] === 'active'): ?>
                                                <span class="badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge-gray">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <a href="?toggle=<?php echo $game['status']; ?>&id=<?php echo $game['id']; ?>" 
                                               class="btn btn-small">
                                                <?php echo $game['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>