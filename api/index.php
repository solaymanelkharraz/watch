<?php
// --- DB CONFIG ---
$host = getenv('DB_HOST') ?: 'mysql-jbala.alwaysdata.net';
$db   = getenv('DB_NAME') ?: 'jbala_watch';
$user = getenv('DB_USER') ?: 'jbala';
$pass = getenv('DB_PASS') ?: 'sql@2006';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- IMPROVED FETCH LOGIC ---
    function fetchMedia($title, $type)
    {
        $q = urlencode($title);
        if ($type === 'anime') {
            $res = @json_decode(file_get_contents("https://api.jikan.moe/v4/anime?q=$q&limit=1"), true);
            $item = $res['data'][0] ?? null;
            return [
                'img' => $item['images']['jpg']['large_image_url'] ?? '',
                'sum' => $item['synopsis'] ?? 'No summary.',
                'tot' => $item['episodes'] ?? 0
            ];
        } else {
            // Better Movie/Series search using TVmaze search array
            $res = @json_decode(file_get_contents("https://api.tvmaze.com/search/shows?q=$q"), true);
            $best = null;
            if (!empty($res)) {
                foreach ($res as $entry) {
                    if ($entry['show']['image']) {
                        $best = $entry['show'];
                        break;
                    }
                }
                if (!$best) $best = $res[0]['show'];
            }
            return [
                'img' => $best['image']['original'] ?? $best['image']['medium'] ?? '',
                'sum' => strip_tags($best['summary'] ?? 'No summary.'),
                'tot' => 0
            ];
        }
    }

    // --- ADD LOGIC ---
    if (isset($_POST['add'])) {
        $t = $_POST['type'];
        $title = $_POST['title'];
        $m = fetchMedia($title, $t);
        $table = "table_" . $t;

        if ($t === 'movies') {
            $stmt = $pdo->prepare("INSERT INTO $table (title, poster_url, summary) VALUES (?,?,?)");
            $stmt->execute([$title, $m['img'], $m['sum']]);
        } else {
            // Anime and Series now use the same columns
            $stmt = $pdo->prepare("INSERT INTO $table (title, poster_url, summary, total_eps) VALUES (?,?,?,?)");
            $stmt->execute([$title, $m['img'], $m['sum'], $m['tot']]);
        }
        header("Location: index.php");
        exit;
    }

    $animes = $pdo->query("SELECT * FROM table_anime WHERE status='to-watch' ORDER BY id DESC")->fetchAll();
    $series = $pdo->query("SELECT * FROM table_series WHERE status='to-watch' ORDER BY id DESC")->fetchAll();
    $movies = $pdo->query("SELECT * FROM table_movies WHERE status='to-watch' ORDER BY id DESC")->fetchAll();

    // Get History (Combining all tables for the last 10 things watched)
    $history = $pdo->query("
    (SELECT title, 'anime' as type FROM table_anime WHERE status='watched')
    UNION
    (SELECT title, 'series' as type FROM table_series WHERE status='watched')
    UNION
    (SELECT title, 'movies' as type FROM table_movies WHERE status='watched')
    LIMIT 10
")->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Media Hub</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <form class="add-box" method="POST">
            <input type="text" name="title" placeholder="Search and add..." style="flex:1" required>
            <select name="type">
                <option value="anime">Anime</option>
                <option value="series">Series</option>
                <option value="movies">Movie</option>
            </select>
            <button type="submit" name="add" class="btn-main">Add to Library</button>
        </form>

        <?php $sections = ['Anime' => $animes, 'Series' => $series, 'Movies' => $movies]; ?>
        <?php foreach ($sections as $label => $list): if ($list): ?>
                <h2><?= $label ?></h2>
                <div class="grid">
                    <?php foreach ($list as $r): ?>
                        <a href="details.php?type=<?= strtolower($label) ?>&id=<?= $r['id'] ?>" class="card">
                            <?php if (isset($r['current_ep'])): ?>
                                <div class="ep-tag">S<?= $r['current_season'] ?> E<?= $r['current_ep'] ?></div>
                            <?php endif; ?>
                            <img src="<?= $r['poster_url'] ?: 'https://via.placeholder.com/200x300' ?>">
                            <div class="card-info">
                                <span class="card-title"><?= htmlspecialchars($r['title']) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
        <?php endif;
        endforeach; ?>
        <h2 style="color: #888; border-color: #444;">Recently Finished</h2>
        <div style="background: #141414; padding: 20px; border-radius: 12px; opacity: 0.7;">
            <?php foreach ($history as $h): ?>
                <p style="margin: 5px 0;">✅ <?= htmlspecialchars($h['title']) ?> <small>(<?= ucfirst($h['type']) ?>)</small></p>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>