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
        if ($type === 'anime' || $type === 'series') {
            $stmt = $pdo->prepare("UPDATE $table SET current_season = ?, current_ep = ? WHERE id = ?");
            $stmt->execute([$_POST['season'], $_POST['ep'], $id]);
        } elseif ($type === 'movies') {
            $pdo->prepare("UPDATE table_movies SET status = 'watched' WHERE id = ?")->execute([$id]);
        }
        header("Location: index.php");
        exit;
    }

    if (isset($_GET['delete'])) {

        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
        header("Location: index.php");
        exit;
    }

    $item = $pdo->query("SELECT * FROM $table WHERE id = $id")->fetch();
} catch (Exception $e) {
    die($e->getMessage());
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Details</title>
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Dashboard</a>

        <div class="hero">
            <img src="<?= $item['poster_url'] ?>">

            <div class="hero-content">
                <h1><?= htmlspecialchars($item['title']) ?></h1>
                <p><?= htmlspecialchars($item['summary']) ?></p>

                <div class="controls-box">
                    <h3>Update Progress</h3>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">

                        <?php if ($type !== 'movies'): ?>
                            <label>Season:</label>
                            <input type="number" name="season" class="ep-input" value="<?= $item['current_season'] ?>">

                            <label>Episode:</label>
                            <input type="number" name="ep" class="ep-input" value="<?= $item['current_ep'] ?>">
                        <?php else: ?>
                            <p>Status: Still on the watchlist.</p>
                        <?php endif; ?>

                        <button type="submit" name="update">Save Changes</button>
                    </form>
                </div>

                <a href="?type=<?= $type ?>&id=<?= $id ?>&delete=1" class="del-btn" onclick="return confirm('Remove permanently?')">Delete from Library</a>
            </div>
        </div>
    </div>
</body>

</html>