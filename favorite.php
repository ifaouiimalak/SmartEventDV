<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND event_id = ?");
$stmt->execute([$userId, $id]);

if ($stmt->fetch()) {
    $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND event_id = ?")->execute([$userId, $id]);
} else {
    $pdo->prepare("INSERT IGNORE INTO favorites(user_id, event_id) VALUES(?, ?)")->execute([$userId, $id]);
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
