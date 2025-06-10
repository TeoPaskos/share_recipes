<?php
require 'config.php';
require 'session_check.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Μη έγκυρη μέθοδος.']);
    exit;
}

if (!isset($_POST['recipe_id']) || !is_numeric($_POST['recipe_id'])) {
    echo json_encode(['success' => false, 'error' => 'Μη έγκυρο αίτημα.']);
    exit;
}

$recipe_id = (int)$_POST['recipe_id'];
$user_id = $_SESSION['user_id'];

// Έλεγχος αν έχει ήδη κάνει like
$stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id = ? AND recipe_id = ?');
$stmt->execute([$user_id, $recipe_id]);
$alreadyLiked = $stmt->fetch();

if ($alreadyLiked) {
    // Αν υπάρχει, κάνε unlike
    $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND recipe_id = ?')->execute([$user_id, $recipe_id]);
    $action = 'unliked';
} else {
    // Αν δεν υπάρχει, κάνε like
    $pdo->prepare('INSERT INTO likes (user_id, recipe_id) VALUES (?, ?)')->execute([$user_id, $recipe_id]);
    $action = 'liked';
}

// Επιστροφή νέου αριθμού likes
$stmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE recipe_id = ?');
$stmt->execute([$recipe_id]);
$likeCount = $stmt->fetchColumn();

echo json_encode(['success' => true, 'action' => $action, 'likeCount' => $likeCount]);
