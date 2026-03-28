<?php
/** * WATCHLIST PRO - PHP/MySQL for Vercel + Alwaysdata
 * Author: Soulayman Elkharraz
 */

// --- 1. DATABASE CONFIGURATION (Change these in Vercel Env Vars or here) ---
$host = getenv('DB_HOST') ?: 'mysql-jbala.alwaysdata.net';
$db   = getenv('DB_NAME') ?: 'jbala_watch';
$user = getenv('DB_USER') ?: 'jbala';
$pass = getenv('DB_PASS') ?: 'sql@2006';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 2. ACTIONS (Add, Watch, Delete) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_item']) && !empty($_POST['title'])) {
            $stmt = $pdo->prepare("INSERT INTO watchlist (title, category) VALUES (?, ?)");
            $stmt->execute([$_POST['title'], $_POST['category']]);
        }
        header("Location: " . $_SERVER['PHP_SELF']); // Refresh to prevent form resubmission
        exit;
    }

    if (isset($_GET['action'])) {
        $id = (int)$_GET['id'];
        if ($_GET['action'] === 'watch') {
            $pdo->prepare("UPDATE watchlist SET status='watched', watched_at=NOW() WHERE id=?")->execute([$id]);
        } elseif ($_GET['action'] === 'delete') {
            $pdo->prepare("DELETE FROM watchlist WHERE id=?")->execute([$id]);
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // --- 3. DATA FETCHING ---
    $to_watch = $pdo->query("SELECT * FROM watchlist WHERE status='to-watch' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $history  = $pdo->query("SELECT * FROM watchlist WHERE status='watched' ORDER BY watched_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("<div style='color:red; font-family:sans-serif;'>Connection Error: " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Watchlist</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --accent: #38bdf8; --text: #f8fafc; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        h1, h2 { color: var(--accent); }
        
        /* Form Styling */
        form { background: var(--card); padding: 15px; border-radius: 12px; display: flex; gap: 10px; margin-bottom: 30px; }
        input, select, button { padding: 10px; border-radius: 6px; border: none; }
        input[type="text"] { flex-grow: 1; }
        button { background: var(--accent); color: #000; font-weight: bold; cursor: pointer; }
        
        /* List Styling */
        .item-card { background: var(--card); padding: 15px; border-radius: 10px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid var(--accent); }
        .badge { font-size: 10px; text-transform: uppercase; background: #334155; padding: 2px 6px; border-radius: 4px; color: var(--accent); }
        .actions a { text-decoration: none; margin-left: 10px; font-size: 18px; }
        
        .history { opacity: 0.6; font-size: 0.9em; }
        .history-card { border-left-color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 My Watchlist</h1>

        <form method="POST">
            <input type="text" name="title" placeholder="What's next? (e.g. One Piece)" required>
            <select name="category">
                <option value="Anime">Anime</option>
                <option value="Movie">Movie</option>
                <option value="Series">Series</option>
                <option value="Manga">Manga</option>
            </select>
            <button type="submit" name="add_item">Add</button>
        </form>

        <h2>To Watch (<?= count($to_watch) ?>)</h2>
        <?php foreach ($to_watch as $item): ?>
            <div class="item-card">
                <div>
                    <span class="badge"><?= $item['category'] ?></span><br>
                    <strong><?= htmlspecialchars($item['title']) ?></strong>
                </div>
                <div class="actions">
                    <a href="?action=watch&id=<?= $item['id'] ?>" title="Mark Watched">✅</a>
                    <a href="?action=delete&id=<?= $item['id'] ?>" onclick="return confirm('Delete?')" title="Remove">🗑️</a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($history): ?>
            <h2 class="history">Recent History</h2>
            <?php foreach ($history as $item): ?>
                <div class="item-card history-card history">
                    <div>
                        <strong><?= htmlspecialchars($item['title']) ?></strong><br>
                        <small>Watched: <?= date('M d, Y', strtotime($item['watched_at'])) ?></small>
                    </div>
                    <div class="actions">
                        <a href="?action=delete&id=<?= $item['id'] ?>" onclick="return confirm('Delete permanently?')">🗑️</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>