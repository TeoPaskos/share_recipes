<?php
require 'header.php';

try {
    $stmt = $pdo->query("SELECT recipes.*, users.username FROM recipes JOIN users ON recipes.user_id = users.id ORDER BY created_at DESC");
    $recipes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error in recipes: " . $e->getMessage());
    $recipes = [];
    echo "<div class='error'>Σφάλμα στη φόρτωση των συνταγών. Δοκιμάστε ξανά αργότερα.</div>";
}

if (empty($recipes)): ?>
    <div class="no-posts">
        <h2>Δεν υπάρχουν συνταγές ακόμα</h2>
        <p>Γίνε ο πρώτος που θα μοιραστεί μια συνταγή!</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="add_recipe.php" class="btn btn-primary">Ανέβασε Συνταγή</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-primary">Εγγραφή</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <h1>Συνταγές</h1>
    <div class="gallery-grid">
        <?php foreach ($recipes as $recipe): ?>
            <div class="post-card">
                <div class="post-header">
                    <h3 class="post-author"><?php echo htmlspecialchars($recipe['username']); ?></h3>
                    <span class="post-date"><?php echo date('d/m/Y H:i', strtotime($recipe['created_at'])); ?></span>
                </div>
                <h2 class="recipe-title"><?php echo htmlspecialchars($recipe['title']); ?></h2>
                <div class="post-image">
                    <img src="uploads/<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                         alt="Εικόνα συνταγής από <?php echo htmlspecialchars($recipe['username']); ?>"
                         loading="lazy">
                </div>
                <?php if (!empty($recipe['description'])): ?>
                    <div class="post-description">
                        <p><?php echo htmlspecialchars($recipe['description']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="post-actions">
                    <?php
                    // Μέτρηση likes
                    $likeStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipe_id = ?");
                    $likeStmt->execute([$recipe['id']]);
                    $likeCount = $likeStmt->fetchColumn();
                    // Μέτρηση σχολίων
                    $commentStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE recipe_id = ?");
                    $commentStmt->execute([$recipe['id']]);
                    $commentCount = $commentStmt->fetchColumn();
                    // Έλεγχος αν ο χρήστης έχει κάνει like
                    $userLiked = false;
                    if (isset($_SESSION['user_id'])) {
                        $ulStmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND recipe_id = ?");
                        $ulStmt->execute([$_SESSION['user_id'], $recipe['id']]);
                        $userLiked = $ulStmt->fetch() ? true : false;
                    }
                    ?>
                    <div class="post-stats">
                        <button class="like-btn" data-id="<?php echo (int)$recipe['id']; ?>" <?php if (!isset($_SESSION['user_id'])) echo 'disabled'; ?> style="background:none;border:none;cursor:pointer;font-size:1.2em;">
                            <span class="like-icon" style="color:<?php echo $userLiked ? 'red' : '#888'; ?>;">❤️</span> <span class="like-count"><?php echo (int)$likeCount; ?></span>
                        </button>
                        <span class="comments">💬 <?php echo (int)$commentCount; ?></span>
                    </div>
                    <a href="recipe.php?id=<?php echo (int)$recipe['id']; ?>" class="btn btn-view">
                        Δες Λεπτομέρειες
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const recipeId = this.getAttribute('data-id');
        fetch('like_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'recipe_id=' + encodeURIComponent(recipeId)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.querySelector('.like-count').textContent = data.likeCount;
                this.querySelector('.like-icon').style.color = data.action === 'liked' ? 'red' : '#888';
            } else if (data.error) {
                alert(data.error);
            }
        });
    });
});
</script>

<?php require 'footer.php'; ?>
