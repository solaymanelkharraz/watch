<?php
// --- CONFIG & DB ---
$host = getenv('DB_HOST') ?: 'mysql-jbala.alwaysdata.net';
$db   = getenv('DB_NAME') ?: 'jbala_watch';
$user = getenv('DB_USER') ?: 'jbala';
$pass = getenv('DB_PASS') ?: 'sql@2006';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    
    // --- API LOGIC (NO AUTH NEEDED) ---
    function fetchMediaData($title, $type) {
        $data = ['poster' => '', 'summary' => '', 'total' => 0];
        $q = urlencode($title);

        if ($type == 'Movie' || $type == 'Series') {
            $url = "https://api.tvmaze.com/singlesearch/shows?q=$q";
            $res = @json_decode(file_get_contents($url), true);
            if ($res) {
                $data['poster'] = $res['image']['medium'] ?? '';
                $data['summary'] = strip_tags($res['summary'] ?? '');
                // TVmaze doesn't give a simple "total episodes" count in one call, 
                // but for simplicity, we'll leave it 0 or fetch from a nested call.
            }
        } else {
            // Anime / Manga (Jikan)
            $url = "https://api.jikan.moe/v4/" . strtolower($type) . "?q=$q&limit=1";
            $res = @json_decode(file_get_contents($url), true);
            if (!empty($res['data'][0])) {
                $item = $res['data'][0];
                $data['poster'] = $item['images']['jpg']['image_url'] ?? '';
                $data['summary'] = $item['synopsis'] ?? '';
                $data['total'] = $item['episodes'] ?? $item['chapters'] ?? 0;
            }
        }
        return $data;
    }

    // --- ACTIONS ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
        $meta = fetchMediaData($_POST['title'], $_POST['category']);
        $stmt = $pdo->prepare("INSERT INTO watchlist (title, category, poster_url, summary, total_eps) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['title'], $_POST['category'], $meta['poster'], $meta['summary'], $meta['total']]);
    }

    if (isset($_GET['action'])) {
        $id = (int)$_GET['id'];
        if ($_GET['action'] === 'plus') { // Increments episode count
            $pdo->prepare("UPDATE watchlist SET current_ep = current_ep + 1 WHERE id = ?")->execute([$id]);
        }
        if ($_GET['action'] === 'watch') {
            $pdo->prepare("UPDATE watchlist SET status='watched', watched_at=NOW() WHERE id=?")->execute([$id]);
        }
        if ($_GET['action'] === 'delete') {
            $pdo->prepare("DELETE FROM watchlist WHERE id=?")->execute([$id]);
        }
        header("Location: index.php"); exit;
    }

    $cats = ['Movie', 'Series', 'Anime', 'Manga'];
    $data = [];
    foreach($cats as $c) {
        $stmt = $pdo->prepare("SELECT * FROM watchlist WHERE category=? AND status='to-watch' ORDER BY id DESC");
        $stmt->execute([$c]);
        $data[$c] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { /* Error handling */ }
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soulayman's Watchlist</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0b0e14; color: #fff; margin: 0; padding: 15px; }
        .container { max-width: 900px; margin: auto; }
        .add-form { background: #1a1f29; padding: 20px; border-radius: 12px; display: flex; gap: 10px; margin-bottom: 30px; }
        input, select, button { padding: 12px; border-radius: 6px; border: none; }
        input { flex: 1; background: #262c3a; color: #fff; }
        button { background: #00d1b2; color: #fff; cursor: pointer; font-weight: bold; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: #1a1f29; border-radius: 12px; overflow: hidden; display: flex; transition: 0.3s; position: relative; }
        .card:hover { transform: translateY(-5px); background: #222936; }
        .card img { width: 100px; height: 150px; object-fit: cover; }
        .info { padding: 15px; flex: 1; }
        .title { font-weight: bold; font-size: 1.1rem; margin-bottom: 5px; color: #00d1b2; }
        .summary { font-size: 0.8rem; opacity: 0.6; height: 45px; overflow: hidden; margin-bottom: 10px; }
        .progress { font-size: 0.9rem; font-weight: bold; }
        
        .actions { position: absolute; bottom: 10px; right: 10px; display: flex; gap: 10px; }
        .actions a { text-decoration: none; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem; background: #262c3a; color: #fff; }
        .cat-head { border-bottom: 2px solid #00d1b2; padding-bottom: 5px; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Smart Watchlist</h1>
        
        <form class="add-form" method="POST">
            <input type="text" name="title" placeholder="Search and add..." required>
            <select name="category">
                <option value="Anime">Anime</option><option value="Movie">Movie</option>
                <option value="Series">Series</option><option value="Manga">Manga</option>
            </select>
            <button type="submit" name="add_item">+ Add</button>
        </form>

        <?php foreach($data as $cat => $items): if(!empty($items)): ?>
            <h2 class="cat-head"><?= $cat ?></h2>
            <div class="grid">
                <?php foreach($items as $item): ?>
                <div class="card">
                    <img src="<?= $item['poster_url'] ?: 'https://via.placeholder.com/100x150' ?>">
                    <div class="info">
                        <div class="title"><?= htmlspecialchars($item['title']) ?></div>
                        <div class="summary"><?= htmlspecialchars($item['summary']) ?></div>
                        <div class="progress">
                            Ep: <?= $item['current_ep'] ?> / <?= $item['total_eps'] ?: '??' ?>
                        </div>
                    </div>
                    <div class="actions">
                        <a href="?action=plus&id=<?= $item['id'] ?>" style="background: #00d1b2;">+1 Ep</a>
                        <a href="?action=watch&id=<?= $item['id'] ?>" style="background: #4a4a4a;">Done</a>
                        <a href="?action=delete&id=<?= $item['id'] ?>" style="background: #ff3860;">×</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; endforeach; ?>
    </div>
</body>
</html>