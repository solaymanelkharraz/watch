<?php
// DB Connection
$host = getenv('DB_HOST') ?: 'mysql-jbala.alwaysdata.net';
$db   = getenv('DB_NAME') ?: 'jbala_watch';
$user = getenv('DB_USER') ?: 'jbala';
$pass = getenv('DB_PASS') ?: 'sql@2006';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // API Fetch Logic (Improved for Movies)
    function fetchMedia($title, $type) {
        $q = urlencode($title);
        if ($type === 'anime') {
            $res = @json_decode(file_get_contents("https://api.jikan.moe/v4/anime?q=$q&limit=1"), true);
            $item = $res['data'][0] ?? null;
            return ['img' => $item['images']['jpg']['large_image_url'] ?? '', 'sum' => $item['synopsis'] ?? 'No summary.', 'tot' => $item['episodes'] ?? 0];
        } else {
            // TVmaze for Series and Movies
            $res = @json_decode(file_get_contents("https://api.tvmaze.com/singlesearch/shows?q=$q"), true);
            return ['img' => $res['image']['original'] ?? '', 'sum' => strip_tags($res['summary'] ?? 'No summary.'), 'tot' => 0];
        }
    }

    if (isset($_POST['add'])) {
        $t = $_POST['type'];
        $m = fetchMedia($_POST['title'], $t);
        $table = "table_" . $t;
        
        if ($t === 'movies') {
            $stmt = $pdo->prepare("INSERT INTO $table (title, poster_url, summary) VALUES (?,?,?)");
            $stmt->execute([$_POST['title'], $m['img'], $m['sum']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO $table (title, poster_url, summary, total_eps) VALUES (?,?,?,?)");
            $stmt->execute([$_POST['title'], $m['img'], $m['sum'], $m['tot']]);
        }
        header("Location: index.php"); exit;
    }

    $animes = $pdo->query("SELECT * FROM table_anime ORDER BY id DESC")->fetchAll();
    $series = $pdo->query("SELECT * FROM table_series ORDER BY id DESC")->fetchAll();
    $movies = $pdo->query("SELECT * FROM table_movies WHERE status='to-watch' ORDER BY id DESC")->fetchAll();

} catch (Exception $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Media Library</title>
</head>
<body>
    <form class="add-form" method="POST">
        <input type="text" name="title" placeholder="Enter Title..." style="flex:1" required>
        <select name="type">
            <option value="anime">Anime</option>
            <option value="series">Series</option>
            <option value="movies">Movie</option>
        </select>
        <button type="submit" name="add">Add</button>
    </form>

    <h2>My Anime</h2>
    <div class="grid">
        <?php foreach($animes as $a): ?>
            <a href="details.php?type=anime&id=<?= $a['id'] ?>" class="card">
                <img src="<?= $a['poster_url'] ?>">
                <div><?= $a['title'] ?> <br> <small>Ep: <?= $a['current_ep'] ?></small></div>
            </a>
        <?php endforeach; ?>
    </div>

    <h2>My Series</h2>
    <div class="grid">
        <?php foreach($series as $s): ?>
            <a href="details.php?type=series&id=<?= $s['id'] ?>" class="card">
                <img src="<?= $s['poster_url'] ?>">
                <div><?= $s['title'] ?> <br> <small>S<?= $s['current_season'] ?> E<?= $s['current_ep'] ?></small></div>
            </a>
        <?php endforeach; ?>
    </div>

    <h2>Movies to Watch</h2>
    <div class="grid">
        <?php foreach($movies as $m): ?>
            <a href="details.php?type=movies&id=<?= $m['id'] ?>" class="card">
                <img src="<?= $m['poster_url'] ?>">
                <div><?= $m['title'] ?></div>
            </a>
        <?php endforeach; ?>
    </div>
</body>
</html>