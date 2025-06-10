<?php
require 'config.php';
require 'nav.php';

// Έλεγχος και validation του ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Μη έγκυρο ID συνταγής");
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT recipes.*, users.username FROM recipes JOIN users ON recipes.user_id = users.id WHERE recipes.id = ?");
$stmt->execute([$id]);
$recipe = $stmt->fetch();

if (!$recipe) die("Συνταγή δεν βρέθηκε");

// Ασφαλής εκτύπωση με htmlspecialchars
echo "<h2>".htmlspecialchars($recipe['title'])."</h2>";
echo "<h4>Από: ".htmlspecialchars($recipe['username'])."</h4>";
echo "<img src='uploads/".htmlspecialchars($recipe['image_path'])."' width='400' alt='Εικόνα συνταγής'>";
echo "<p>".htmlspecialchars($recipe['description'])."</p>";

// Like count
$count = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipe_id=?");
$count->execute([$id]);
echo "<p>Likes: ".(int)$count->fetchColumn()."</p>";

// Like κουμπί με CSRF protection
if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $checkLike = $pdo->prepare("SELECT * FROM likes WHERE user_id=? AND recipe_id=?");
    $checkLike->execute([$_SESSION['user_id'], $id]);
    if (!$checkLike->fetch()) {
        echo "<form method='POST'>";
        echo "<input type='hidden' name='csrf_token' value='".$_SESSION['csrf_token']."'>";
        echo "<button name='like'>Like</button></form>";
    }
    if (isset($_POST['like'])) {
        if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            $checkDuplicate = $pdo->prepare("SELECT * FROM likes WHERE user_id=? AND recipe_id=?");
            $checkDuplicate->execute([$_SESSION['user_id'], $id]);
            if (!$checkDuplicate->fetch()) {
                $pdo->prepare("INSERT INTO likes (user_id, recipe_id) VALUES (?, ?)")
                   ->execute([$_SESSION['user_id'], $id]);
            }
            header("Location: recipe.php?id=".$id);
            exit();
        }
    }
    // Σχόλια με ασφαλή εκτύπωση
    echo "<h4>Σχόλια</h4>";
    $comments = $pdo->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE recipe_id=? ORDER BY created_at DESC");
    $comments->execute([$id]);
    foreach ($comments as $comment) {
        echo "<p><strong>".htmlspecialchars($comment['username'])."</strong>: ".htmlspecialchars($comment['comment'])."</p>";
    }
    // Φόρμα σχολίου με CSRF
    echo "<form method='POST'>";
    echo "<input type='hidden' name='csrf_token' value='".$_SESSION['csrf_token']."'>";
    echo "<textarea name='comment' required maxlength='500' placeholder='Γράψε ένα σχόλιο...'></textarea><br>";
    echo "<button>Σχόλιο</button>";
    echo "</form>";
    if (isset($_POST['comment'])) {
        if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            $comment = trim($_POST['comment']);
            if (!empty($comment) && strlen($comment) <= 500) {
                $pdo->prepare("INSERT INTO comments (user_id, recipe_id, comment) VALUES (?, ?, ?)")
                   ->execute([$_SESSION['user_id'], $id, $comment]);
                header("Location: recipe.php?id=".$id);
                exit();
            }
        }
    }
} else {
    echo "<p><a href='login.php'>Συνδέσου</a> για να κάνεις like ή να σχολιάσεις.</p>";
}
// ...υπόλοιπο html/footer...
