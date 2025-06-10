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

$comment = trim($_POST['comment'] ?? '');
if ($comment === '' || mb_strlen($comment) > 500) {
    echo json_encode(['success' => false, 'error' => 'Το σχόλιο πρέπει να είναι 1-500 χαρακτήρες.']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO comments (user_id, recipe_id, comment, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$user_id, $recipe_id, $comment]);
    // Επιστροφή νέου αριθμού σχολίων
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE recipe_id = ?');
    $countStmt->execute([$recipe_id]);
    $commentCount = $countStmt->fetchColumn();
    echo json_encode(['success' => true, 'commentCount' => $commentCount]);
} catch (PDOException $e) {
    error_log('Comment AJAX error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Σφάλμα κατά την αποθήκευση σχολίου.']);
}
