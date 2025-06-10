<?php
require 'header.php';
require 'session_check.php';

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;

// Φόρτωση στοιχείων χρήστη
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error in profile: " . $e->getMessage());
    die("Σφάλμα συστήματος.");
}

// Στατιστικά χρήστη
try {
    $recipesStmt = $pdo->prepare("SELECT COUNT(*) FROM recipes WHERE user_id = ?");
    $recipesStmt->execute([$_SESSION['user_id']]);
    $totalRecipes = $recipesStmt->fetchColumn();
    
    $likesStmt = $pdo->prepare("SELECT COUNT(*) FROM likes JOIN recipes ON likes.recipe_id = recipes.id WHERE recipes.user_id = ?");
    $likesStmt->execute([$_SESSION['user_id']]);
    $totalLikes = $likesStmt->fetchColumn();
    
    $commentsStmt = $pdo->prepare("SELECT COUNT(*) FROM comments JOIN recipes ON comments.recipe_id = recipes.id WHERE recipes.user_id = ?");
    $commentsStmt->execute([$_SESSION['user_id']]);
    $totalComments = $commentsStmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Database error getting user stats: " . $e->getMessage());
    $totalRecipes = $totalLikes = $totalComments = 0;
}

// Αλλαγή κωδικού
if ($_POST && isset($_POST['change_password'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "Μη έγκυρη αίτηση.";
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($currentPassword)) {
            $errors[] = "Εισάγετε τον τρέχοντα κωδικό.";
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors[] = "Λάθος τρέχων κωδικός.";
        }
        
        if (empty($newPassword)) {
            $errors[] = "Εισάγετε νέο κωδικό.";
        } elseif (strlen($newPassword) < 8) {
            $errors[] = "Ο νέος κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPassword)) {
            $errors[] = "Ο νέος κωδικός πρέπει να περιέχει κεφαλαίο, μικρό γράμμα και αριθμό.";
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = "Οι νέοι κωδικοί δεν ταιριάζουν.";
        }
        
        if (empty($errors)) {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                $success = "Ο κωδικός άλλαξε με επιτυχία!";
            } catch (PDOException $e) {
                error_log("Database error changing password: " . $e->getMessage());
                $errors[] = "Σφάλμα στην αλλαγή κωδικού.";
            }
        }
    }
}

// Εμφάνιση συνταγών χρήστη
try {
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $userRecipes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error getting user recipes: " . $e->getMessage());
    $userRecipes = [];
}
?>

<div class="profile-container">
    <div class="profile-header">
        <h1>Το Προφίλ μου</h1>
        <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
        <p class="profile-email">Email: <?php echo htmlspecialchars($user['email']); ?></p>
    </div>
    
    <!-- Στατιστικά -->
    <div class="profile-stats">
        <div class="stat-item">
            <span class="stat-number"><?php echo (int)$totalRecipes; ?></span>
            <span class="stat-label">Συνταγές</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo (int)$totalLikes; ?></span>
            <span class="stat-label">Likes</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo (int)$totalComments; ?></span>
            <span class="stat-label">Σχόλια</span>
        </div>
    </div>
    
    <!-- Αλλαγή Κωδικού -->
    <div class="profile-section">
        <h2>Αλλαγή Κωδικού</h2>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="password-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="change_password" value="1">
            
            <div class="form-group">
                <label for="current_password">Τρέχων Κωδικός:</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">Νέος Κωδικός:</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Επιβεβαίωση Νέου Κωδικού:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Αλλαγή Κωδικού</button>
        </form>
    </div>
    
    <!-- Οι Συνταγές μου -->
    <div class="profile-section">
        <div class="section-header">
            <h2>Οι Συνταγές μου</h2>
            <a href="upload.php" class="btn btn-secondary">Νέα Συνταγή</a>
        </div>
        
        <?php if (empty($userRecipes)): ?>
            <div class="no-posts">
                <p>Δεν έχετε ανεβάσει συνταγές ακόμα.</p>
                <a href="upload.php" class="btn btn-primary">Ανεβάστε την Πρώτη σας!</a>
            </div>
        <?php else: ?>
            <div class="profile-gallery">
                <?php foreach ($userRecipes as $recipe): ?>
                    <div class="profile-post">
                        <a href="recipe.php?id=<?php echo (int)$recipe['id']; ?>">
                            <img src="uploads/<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($recipe['title']); ?>"
                                 loading="lazy">
                        </a>
                        <div class="post-info">
                            <span class="post-date"><?php echo date('d/m/Y', strtotime($recipe['created_at'])); ?></span>
                            <?php
                            // Likes και σχόλια για κάθε συνταγή
                            $recipeLikes = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipe_id = ?");
                            $recipeLikes->execute([$recipe['id']]);
                            $likes = $recipeLikes->fetchColumn();
                            
                            $recipeComments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE recipe_id = ?");
                            $recipeComments->execute([$recipe['id']]);
                            $comments = $recipeComments->fetchColumn();
                            ?>
                            <span class="post-stats">❤️ <?php echo (int)$likes; ?> 💬 <?php echo (int)$comments; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($userRecipes) >= 12): ?>
                <div class="view-all">
                    <a href="gallery.php?user=<?php echo $_SESSION['user_id']; ?>" class="btn btn-outline">
                        Δες Όλες τις Συνταγές
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Επικίνδυνη Ζώνη -->
    <div class="profile-section danger-zone">
        <h2>Επικίνδυνη Ζώνη</h2>
        <p>Οι παρακάτω ενέργειες είναι μη αναστρέψιμες.</p>
        <div class="danger-actions">
            <button class="btn btn-danger" onclick="confirmDeleteAccount()">
                Διαγραφή Λογαριασμού
            </button>
        </div>
    </div>
</div>

<script>
function confirmDeleteAccount() {
    if (confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε τον λογαριασμό σας?\n\nΑυτή η ενέργεια θα διαγράψει όλες τις φωτογραφίες και τα δεδομένα σας και δεν μπορεί να αναιρεθεί.')) {
        if (confirm('Τελευταία επιβεβαίωση: Διαγραφή λογαριασμού;')) {
            // Εδώ θα μπορούσες να προσθέσεις τη λειτουργία διαγραφής
            alert('Η λειτουργία διαγραφής δεν έχει υλοποιηθεί ακόμα.');
        }
    }
}

// Password confirmation validation
const confirmPasswordInput = document.getElementById('confirm_password');
const newPasswordInput = document.getElementById('new_password');
if (confirmPasswordInput && newPasswordInput) {
    confirmPasswordInput.addEventListener('input', function() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = this.value;
        if (newPassword !== confirmPassword) {
            this.setCustomValidity('Οι κωδικοί δεν ταιριάζουν');
        } else {
            this.setCustomValidity('');
        }
    });
}
</script>

<?php require 'footer.php'; ?>