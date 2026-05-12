<?php
session_start();

$host = 'localhost';
$dbname = 'smartevent';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:2rem;color:#c00;">
        <h2>Erreur de connexion à la base de données</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Vérifiez <code>config/db.php</code> et que MySQL est démarré.</p>
    </div>');
}

/**
 * Returns a valid image URL for an event.
 * Falls back to a placeholder if the stored value is empty.
 */
function eventImage(string $image = ''): string {
    if ($image && (str_starts_with($image, 'http://') || str_starts_with($image, 'https://'))) {
        return $image;
    }
    if ($image && file_exists(__DIR__ . '/../assets/uploads/' . $image)) {
        return '/smartevent/assets/uploads/' . $image;
    }
    return 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=800&q=70';
}
