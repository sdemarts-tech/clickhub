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
    $featured_image = isset($_POST['featured_image']) ? sanitize($_POST['featured_image']) : '';
    $min_score_required = intval($_POST['min_score_required']);
    $play_cooldown_minutes = intval($_POST['play_cooldown_minutes']);
    $max_plays_per_day = intval($_POST['max_plays_per_day']);
    
    if (empty($name) || empty($file_path)) {
        $error = 'Name and file path are required';
    } else {
        $gameData = [
            'name' => $name,
            'description' => $description,
            'file_path' => $file_path,
            'scoring_type' => 'score-based',
            'min_score_required' => $min_score_required,
            'play_cooldown_minutes' => $play_cooldown_minutes,
            'max_plays_per_day' => $max_plays_per_day,
            'status' => 'active'
        ];
        
        // Only add featured_image if it's not empty
        if (!empty($featured_image)) {
            $gameData['featured_image'] = $featured_image;
        }
        
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

// Handle edit game
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_game'])) {
    $gameId = sanitize($_POST['game_id']);
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $file_path = sanitize($_POST['file_path']);
    $featured_image = isset($_POST['featured_image']) ? sanitize($_POST['featured_image']) : '';
    $min_score_required = intval($_POST['min_score_required']);
    $play_cooldown_minutes = intval($_POST['play_cooldown_minutes']);
    $max_plays_per_day = intval($_POST['max_plays_per_day']);
    
    if (empty($name) || empty($file_path)) {
        $error = 'Name and file path are required';
    } else {
        $gameData = [
            'name' => $name,
            'description' => $description,
            'file_path' => $file_path,
            'min_score_required' => $min_score_required,
            'play_cooldown_minutes' => $play_cooldown_minutes,
            'max_plays_per_day' => $max_plays_per_day
        ];
        
        // Only add featured_image if it's not empty
        if (!empty($featured_image)) {
            $gameData['featured_image'] = $featured_image;
        }
        
        $result = $supabase->update(TABLE_GAMES, $gameData, ['id' => $gameId]);
        
        if (!isset($result['error'])) {
            setSuccessMessage('Game updated successfully!');
            header('Location: manage-games.php');
            exit;
        } else {
            $error = 'Error updating game: ' . $result['error'];
        }
    }
}

// Check if editing a game
$editGame = null;
if (isset($_GET['edit'])) {
    $editId = sanitize($_GET['edit']);
    $editResult = $supabase->select(TABLE_GAMES, '*', ['id' => $editId]);
    if (!empty($editResult) && !isset($editResult['error'])) {
        $editGame = $editResult[0];
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

// Get all games using wrapper
$games = $supabase->select(TABLE_GAMES);

// Ensure we have a valid array
if (!is_array($games) || isset($games['error'])) {
    $games = [];
}

// Remove any potential duplicates by ID
$uniqueGames = [];
$seenIds = [];
foreach ($games as $game) {
    if (is_array($game) && isset($game['id'])) {
        $gameId = $game['id'];
        if (!in_array($gameId, $seenIds)) {
            $uniqueGames[] = $game;
            $seenIds[] = $gameId;
        }
    }
}
$games = $uniqueGames;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Games - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 5px;
            background: #f9f9f9;
        }
        .image-preview img {
            width: 100%;
            height: auto;
            border-radius: 4px;
        }
        .no-preview {
            padding: 20px;
            text-align: center;
            color: #999;
            font-style: italic;
        }
        .game-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
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
                <h2><?php echo $editGame ? 'Edit Game' : 'Add New Game'; ?></h2>
                <?php if ($editGame): ?>
                    <div class="info-message" style="margin-bottom: 20px;">
                        Editing: <strong><?php echo htmlspecialchars($editGame['name']); ?></strong>
                        <a href="manage-games.php" class="btn btn-small" style="float:right;">Cancel Edit</a>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="admin-form">
                    <?php if ($editGame): ?>
                        <input type="hidden" name="game_id" value="<?php echo $editGame['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Game Name *</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo $editGame ? htmlspecialchars($editGame['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="min_score_required">Minimum Score Required *</label>
                            <input type="number" id="min_score_required" name="min_score_required" 
                                   value="<?php echo $editGame ? $editGame['min_score_required'] : 10; ?>" required min="0">
                            <small>Players must reach this score to earn points</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="play_cooldown_minutes">Cooldown (minutes) *</label>
                            <input type="number" id="play_cooldown_minutes" name="play_cooldown_minutes" 
                                   value="<?php echo $editGame ? $editGame['play_cooldown_minutes'] : 60; ?>" required min="1">
                            <small>Time before player can replay this game</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_plays_per_day">Max Plays Per Day *</label>
                            <input type="number" id="max_plays_per_day" name="max_plays_per_day" 
                                   value="<?php echo $editGame ? ($editGame['max_plays_per_day'] ?? 3) : 3; ?>" required min="1">
                            <small>How many times this game can be played per day</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"><?php echo $editGame ? htmlspecialchars($editGame['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="file_path">File Path (URL or local path) *</label>
                        <input type="text" id="file_path" name="file_path" 
                               value="<?php echo $editGame ? htmlspecialchars($editGame['file_path']) : ''; ?>"
                               placeholder="games/snake.html" required>
                        <small>Example: games/snake.html or https://example.com/game.html</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="featured_image">Featured Image URL (Optional)</label>
                        <input type="text" id="featured_image" name="featured_image" 
                               value="<?php echo $editGame && isset($editGame['featured_image']) ? htmlspecialchars($editGame['featured_image']) : ''; ?>"
                               placeholder="https://example.com/images/game.jpg"
                               oninput="updateImagePreview(this.value)">
                        <small>Full URL or relative path to game image (leave blank to use emoji icon 🎮)</small>
                        
                        <div id="imagePreview" class="image-preview">
                            <?php if ($editGame && isset($editGame['featured_image']) && !empty($editGame['featured_image'])): ?>
                                <img src="<?php echo htmlspecialchars($editGame['featured_image']); ?>" 
                                     alt="Preview" 
                                     onerror="this.parentElement.innerHTML='<div class=\'no-preview\'>❌ Image not found</div>'">
                            <?php else: ?>
                                <div class="no-preview">No image - will show 🎮 emoji</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-box" style="margin-top:15px; background:#e7f3ff; padding:15px; border-radius:5px;">
                        <strong>ℹ️ Note:</strong> Players earn points equal to their game score. 
                        Make sure your game sends the score via postMessage when game ends.
                    </div>
                    
                    <button type="submit" name="<?php echo $editGame ? 'edit_game' : 'add_game'; ?>" class="btn btn-primary">
                        <?php echo $editGame ? '💾 Update Game' : '➕ Add Game'; ?>
                    </button>
                </form>
            </div>

            <div class="table-section">
                <h2>Existing Games (<?php echo count($games); ?>)</h2>
                <?php if (empty($games)): ?>
                    <p>No games added yet.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Min Score</th>
                                    <th>Cooldown</th>
                                    <th>Plays/Day</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($games as $game): ?>
                                    <tr>
                                        <td>
                                            <?php if (isset($game['featured_image']) && !empty($game['featured_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($game['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($game['name']); ?>"
                                                     class="game-thumb"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                                <span style="display:none; font-size:40px;">🎮</span>
                                            <?php else: ?>
                                                <span style="font-size:40px;">🎮</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($game['name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars(substr($game['description'], 0, 50)); ?><?php echo strlen($game['description']) > 50 ? '...' : ''; ?></small>
                                        </td>
                                        <td><?php echo $game['min_score_required'] ?? 0; ?></td>
                                        <td><?php echo $game['play_cooldown_minutes'] ?? 60; ?> min</td>
                                        <td><?php echo $game['max_plays_per_day'] ?? 3; ?></td>
                                        <td>
                                            <?php if ($game['status'] === 'active'): ?>
                                                <span class="badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge-gray">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <a href="?edit=<?php echo $game['id']; ?>" 
                                               class="btn btn-small btn-secondary">
                                                ✏️ Edit
                                            </a>
                                            <a href="?toggle=<?php echo $game['status']; ?>&id=<?php echo $game['id']; ?>" 
                                               class="btn btn-small"
                                               onclick="return confirm('Toggle game status?');">
                                                <?php echo $game['status'] === 'active' ? '🔴 Deactivate' : '✅ Activate'; ?>
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
    
    <script>
        function updateImagePreview(url) {
            const preview = document.getElementById('imagePreview');
            url = url.trim();
            
            if (url === '') {
                preview.innerHTML = '<div class="no-preview">No image - will show 🎮 emoji</div>';
            } else {
                preview.innerHTML = '<img src="' + url + '" alt="Preview" onerror="this.parentElement.innerHTML=\'<div class=\\\'no-preview\\\'>❌ Image not found or invalid URL</div>\'">';
            }
        }
    </script>
</body>
</html>