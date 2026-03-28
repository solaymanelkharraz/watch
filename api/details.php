<?php
$host = getenv('DB_HOST') ?: 'mysql-jbala.alwaysdata.net';
$db   = getenv('DB_NAME') ?: 'jbala_watch';
$user = getenv('DB_USER') ?: 'jbala';
$pass = getenv('DB_PASS') ?: 'sql@2006';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    
    $type = $_GET['type'];
    $id = (int)$_GET['id'];
    $table = "table_" . $type;

    if (isset($_POST['update'])) {
        if ($type === 'anime') {
            $pdo->prepare("UPDATE table_anime SET current_ep = ? WHERE id = ?")->execute([$_POST['ep'], $id]);
        } elseif ($type === 'series') {
            $pdo->prepare("UPDATE table_series SET current_season = ?, current_ep = ? WHERE id = ?")->execute([$_POST['season'], $_POST['ep'], $id]);
        } elseif ($type === 'movies') {
            $pdo->prepare("UPDATE table_movies SET status = 'watched' WHERE id = ?")->execute([$id]);
        }
        header("Location: index.php"); exit;
    }

    if (isset($_GET['delete'])) {
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
        header("Location: index.php"); exit;
    }

    $item = $pdo->query("SELECT * FROM $table WHERE id = $id")->fetch();

} catch (Exception $e) { die($e->getMessage()); }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Details</title>
    <style>
        body { background: #0a0a0a; color: #fff; font-family: sans-serif; padding: 40px; }
        .flex { display: flex; gap: 40px; }
        img { width: 300px; border-radius: 10px; }
        .btn { padding: 10px 20px; background: #00d1b2; border: none; color: #000; font-weight: bold; cursor: pointer; }
        .del { color: #ff3860; text-decoration: none; margin-top: 20px; display: block; }
    </style>
</head>
<body>
    <a href="index.php" style="color:#aaa;">← Back</a>
    <div class="flex">
        <img src="<?= $item['poster_url'] ?>">
        <div>
            <h1><?= $item['title'] ?></h1>
            <p><?= $item['summary'] ?></p>
            
            <form method="POST">
                <?php if ($type === 'anime'): ?>
                    <label>Current Episode:</label><br>
                    <input type="number" name="ep" value="<?= $item['current_ep'] ?>" style="width:60px; padding:10px;">
                <?php elseif ($type === 'series'): ?>
                    <label>Season:</label> <input type="number" name="season" value="<?= $item['current_season'] ?>" style="width:40px;">
                    <label>Episode:</label> <input type="number" name="ep" value="<?= $item['current_ep'] ?>" style="width:40px;">
                <?php elseif ($type === 'movies'): ?>
                    <p>Status: To Watch</p>
                <?php endif; ?>
                
                <br><br>
                <button type="submit" name="update" class="btn">
                    <?= ($type === 'movies') ? 'Mark as Watched' : 'Save Progress' ?>
                </button>
            </form>
            
            <a href="?type=<?= $type ?>&id=<?= $id ?>&delete=1" class="del" onclick="return confirm('Delete?')">Remove from library</a>
        </div>
    </div>
</body>
</html>