<?php
// --- 1. CONFIGURATION ---
$host = getenv('DB_HOST') ?: 'mysql-jbala.alwaysdata.net';
$db   = getenv('DB_NAME') ?: 'jbala_watch';
$user = getenv('DB_USER') ?: 'jbala';
$pass = getenv('DB_PASS') ?: 'sql@2006';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 2. ACTIONS ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
        $stmt = $pdo->prepare("INSERT INTO watchlist (title, category) VALUES (?, ?)");
        $stmt->execute([$_POST['title'], $_POST['category']]);
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }

    if (isset($_GET['action'])) {
        $id = (int)$_GET['id'];
        if ($_GET['action'] === 'watch') $pdo->prepare("UPDATE watchlist SET status='watched', watched_at=NOW() WHERE id=?")->execute([$id]);
        if ($_GET['action'] === 'delete') $pdo->prepare("DELETE FROM watchlist WHERE id=?")->execute([$id]);
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }

    // --- 3. FETCH DATA SEPARATELY ---
    $cats = ['Movie', 'Series', 'Anime', 'Manga'];
    $lists = [];
    foreach ($cats as $cat) {
        $stmt = $pdo->prepare("SELECT * FROM watchlist WHERE category = ? AND status = 'to-watch' ORDER BY id DESC");
        $stmt->execute([$cat]);
        $lists[$cat] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $history = $pdo->query("SELECT * FROM watchlist WHERE status='watched' ORDER BY watched_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Watchlist</title>
    <style>
        :root { --bg: #0f172a; --card: #1e293b; --accent: #38bdf8; --text: #f1f5f9; --green: #22c55e; --red: #ef4444; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: auto; }
        
        /* Input Area */
        .add-box { background: var(--card); padding: 20px; border-radius: 15px; margin-bottom: 30px; border: 1px solid #334155; }
        form { display: flex; gap: 10px; flex-wrap: wrap; }
        input, select, button { padding: 12px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: white; outline: none; }
        input { flex: 2; min-width: 200px; }
        button { background: var(--accent); color: #0f172a; font-weight: bold; cursor: pointer; border: none; flex: 1; }

        /* Category Sections */
        .section { margin-bottom: 40px; }
        .section-title { border-left: 4px solid var(--accent); padding-left: 15px; margin-bottom: 15px; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px; }
        
        .item { background: var(--card); padding: 12px 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; transition: 0.2s; }
        .item:hover { transform: translateX(5px); background: #2d3748; }
        .item-title { font-weight: 500; }
        
        .btns a { text-decoration: none; margin-left: 15px; font-size: 1.1rem; }
        .btn-check { color: var(--green); }
        .btn-del { color: var(--red); }

        /* History */
        .history-list { font-size: 0.9rem; opacity: 0.7; }
        .history-item { border-bottom: 1px solid #334155; padding: 8px 0; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Watchlist Dashboard</h1>

        <div class="add-box">
            <form method="POST">
                <input type="text" name="title" placeholder="What are you watching?" required>
                <select name="category">
                    <option value="Movie">Movie</option>
                    <option value="Series">Series</option>
                    <option value="Anime">Anime</option>
                    <option value="Manga">Manga</option>
                </select>
                <button type="submit" name="add_item">Add to List</button>
            </form>
        </div>

        <?php foreach ($lists as $catName => $items): ?>
            <div class="section">
                <h2 class="section-title"><?= $catName ?>s (<?= count($items) ?>)</h2>
                <?php if (empty($items)): ?>
                    <p style="color: #64748b; font-size: 0.9rem;">Nothing here yet.</p>
                <?php endif; ?>
                <?php foreach ($items as $item): ?>
                    <div class="item">
                        <span class="item-title"><?= htmlspecialchars($item['title']) ?></span>
                        <div class="btns">
                            <a href="?action=watch&id=<?= $item['id'] ?>" class="btn-check" title="Mark Watched">✔</a>
                            <a href="?action=delete&id=<?= $item['id'] ?>" class="btn-del" onclick="return confirm('Delete?')" title="Delete">✖</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($history): ?>
            <div class="section" style="margin-top: 60px;">
                <h2 class="section-title" style="border-color: #64748b;">Recently Watched</h2>
                <div class="history-list">
                    <?php foreach ($history as $h): ?>
                        <div class="history-item">
                            <span><?= htmlspecialchars($h['title']) ?> (<?= $h['category'] ?>)</span>
                            <span><?= date('M d', strtotime($h['watched_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>