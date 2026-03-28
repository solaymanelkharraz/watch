<?php
// --- CONFIG ---
$host = getenv('DB_HOST') ?: 'mysql-jbala.alwaysdata.net';
$db   = getenv('DB_NAME') ?: 'jbala_watch';
$user = getenv('DB_USER') ?: 'jbala';
$pass = getenv('DB_PASS') ?: 'sql@2006';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- LOGIC: FETCH DATA FROM API ---
    function getMediaInfo($title, $type) {
        $q = urlencode($title);
        if ($type == 'Anime' || $type == 'Manga') {
            $url = "https://api.jikan.moe/v4/".strtolower($type)."?q=$q&limit=1";
            $res = @json_decode(file_get_contents($url), true);
            $item = $res['data'][0] ?? null;
            return [
                'poster' => $item['images']['jpg']['large_image_url'] ?? '',
                'summary' => $item['synopsis'] ?? 'No description.',
                'total' => $item['episodes'] ?? $item['chapters'] ?? 0
            ];
        } else {
            $url = "https://api.tvmaze.com/singlesearch/shows?q=$q";
            $res = @json_decode(file_get_contents($url), true);
            return [
                'poster' => $res['image']['original'] ?? '',
                'summary' => strip_tags($res['summary'] ?? 'No description.'),
                'total' => 0
            ];
        }
    }

    // --- ACTIONS ---
    if (isset($_POST['add_item'])) {
        $info = getMediaInfo($_POST['title'], $_POST['category']);
        $stmt = $pdo->prepare("INSERT INTO watchlist (title, category, poster_url, summary, total_eps) VALUES (?,?,?,?,?)");
        $stmt->execute([$_POST['title'], $_POST['category'], $info['poster'], $info['summary'], $info['total']]);
        header("Location: index.php"); exit;
    }

    if (isset($_POST['update_ep'])) {
        $stmt = $pdo->prepare("UPDATE watchlist SET current_ep = ? WHERE id = ?");
        $stmt->execute([$_POST['new_ep'], $_POST['id']]);
        header("Location: index.php?view=" . $_POST['id']); exit;
    }

    if (isset($_GET['delete'])) {
        $pdo->prepare("DELETE FROM watchlist WHERE id=?")->execute([$_GET['delete']]);
        header("Location: index.php"); exit;
    }

    // --- ROUTING ---
    $view_id = $_GET['view'] ?? null;
    $media_item = null;
    if ($view_id) {
        $stmt = $pdo->prepare("SELECT * FROM watchlist WHERE id = ?");
        $stmt->execute([$view_id]);
        $media_item = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $cats = ['Anime', 'Movie', 'Series', 'Manga'];
    $data = [];
    foreach($cats as $c) {
        $stmt = $pdo->prepare("SELECT * FROM watchlist WHERE category=? ORDER BY id DESC");
        $stmt->execute([$c]);
        $data[$c] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) { $error = $e->getMessage(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamTracker Pro</title>
    <style>
        :root { --bg: #050505; --card: #121212; --accent: #00f2ff; --text: #ffffff; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        
        /* Input */
        .search-bar { background: var(--card); padding: 15px; border-radius: 12px; display: flex; gap: 10px; margin-bottom: 30px; border: 1px solid #222; }
        input, select, button { background: #1a1a1a; color: white; border: 1px solid #333; padding: 12px; border-radius: 8px; }
        button { background: var(--accent); color: black; font-weight: bold; border: none; cursor: pointer; }

        /* Grid */
        .cat-title { font-size: 1.5rem; margin: 30px 0 15px; border-left: 4px solid var(--accent); padding-left: 15px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 20px; }
        .card { background: var(--card); border-radius: 12px; overflow: hidden; cursor: pointer; transition: 0.3s; border: 1px solid #222; text-decoration: none; color: white; }
        .card:hover { transform: scale(1.05); border-color: var(--accent); }
        .card img { width: 100%; height: 240px; object-fit: cover; }
        .card-info { padding: 10px; font-size: 0.9rem; text-align: center; }

        /* Detail View */
        .detail-view { display: flex; gap: 30px; background: var(--card); padding: 30px; border-radius: 20px; border: 1px solid #222; flex-wrap: wrap; }
        .detail-img { width: 300px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,242,255,0.2); }
        .detail-content { flex: 1; min-width: 300px; }
        .back-btn { display: inline-block; margin-bottom: 20px; color: var(--accent); text-decoration: none; font-weight: bold; }
        .ep-box { margin-top: 20px; background: #1a1a1a; padding: 20px; border-radius: 12px; }
        .ep-input { width: 80px; font-size: 1.2rem; text-align: center; }
    </style>
</head>
<body>
    <div class="container">

        <?php if ($media_item): ?>
            <a href="index.php" class="back-btn">← Back to Dashboard</a>
            <div class="detail-view">
                <img src="<?= $media_item['poster_url'] ?>" class="detail-img">
                <div class="detail-content">
                    <h1 style="margin-top:0;"><?= htmlspecialchars($media_item['title']) ?></h1>
                    <p style="color: #888; line-height: 1.6;"><?= htmlspecialchars($media_item['summary']) ?></p>
                    
                    <div class="ep-box">
                        <h3>Currently Watching</h3>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $media_item['id'] ?>">
                            <span>Episode / Chapter:</span>
                            <input type="number" name="new_ep" class="ep-input" value="<?= $media_item['current_ep'] ?>">
                            <button type="submit" name="update_ep">Save Progress</button>
                        </form>
                        <p><small>Total available: <?= $media_item['total_eps'] ?: 'Ongoing' ?></small></p>
                    </div>
                    
                    <a href="?delete=<?= $media_item['id'] ?>" style="color: #ff4444; text-decoration: none; display: block; margin-top: 20px;" onclick="return confirm('Remove permanently?')">Delete from library</a>
                </div>
            </div>

        <?php else: ?>
            <h1>My Media Library</h1>
            <form class="search-bar" method="POST">
                <input type="text" name="title" placeholder="Add new (e.g. One Piece, Batman...)" style="flex:1" required>
                <select name="category">
                    <?php foreach($cats as $c) echo "<option value='$c'>$c</option>"; ?>
                </select>
                <button type="submit" name="add_item">Add to Library</button>
            </form>

            <?php foreach($data as $cat => $items): if(!empty($items)): ?>
                <h2 class="cat-title"><?= $cat ?>s</h2>
                <div class="grid">
                    <?php foreach($items as $item): ?>
                        <a href="?view=<?= $item['id'] ?>" class="card">
                            <img src="<?= $item['poster_url'] ?: 'https://via.placeholder.com/240x350' ?>">
                            <div class="card-info">
                                <strong><?= htmlspecialchars($item['title']) ?></strong><br>
                                <small style="color: var(--accent)">Ep: <?= $item['current_ep'] ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; endforeach; ?>
        <?php endif; ?>

    </div>
</body>
</html>