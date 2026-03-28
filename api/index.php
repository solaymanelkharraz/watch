<?php
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    
    // Handle "Mark as Watched"
    if (isset($_GET['watch'])) {
        $stmt = $pdo->prepare("UPDATE watchlist SET status='watched', watched_at=NOW() WHERE id=?");
        $stmt->execute([$_GET['watch']]);
    }
    
    // Fetch Data
    $to_watch = $pdo->query("SELECT * FROM watchlist WHERE status='to-watch'")->fetchAll();
    $history = $pdo->query("SELECT * FROM watchlist WHERE status='watched' ORDER BY watched_at DESC")->fetchAll();

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>