<?php
require 'header.php';

// Rate limiting για login attempts
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
}

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;

// Έλεγχος για πολλές προσπάθειες
if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt']) < 300) {
    $remainingTime = 300 - (time() - $_SESSION['last_attempt']);
    $errors[] = "Πολλές αποτυχημένες προσπάθειες. Δοκιμάστε ξανά σε " . ceil($remainingTime/60) . " λεπτά.";
}

if ($_POST && empty($errors)) {
    // CSRF έλεγχος
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "Μη έγκυρη αίτηση. Δοκιμάστε ξανά.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($username)) {
            $errors[] = "Το όνομα χρήστη είναι υποχρεωτικό.";
        }
        if (empty($password)) {
            $errors[] = "Ο κωδικός είναι υποχρεωτικός.";
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Επιτυχής σύνδεση
                    session_regenerate_id(true); // Security: Regenerate session ID
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['login_attempts'] = 0; // Reset attempts
                    
                    // Redirect to intended page or gallery
                    $redirect = $_GET['redirect'] ?? 'recipes.php';
                    $redirect = filter_var($redirect, FILTER_SANITIZE_URL);
                    header("Location: " . $redirect);
                    exit();
                } else {
                    // Αποτυχημένη σύνδεση
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt'] = time();
                    $errors[] = "Λάθος όνομα χρήστη ή κωδικός.";
                    
                    // Log failed attempt
                    error_log("Failed login attempt for username: " . $username . " from IP: " . $_SERVER['REMOTE_ADDR']);
                }
            } catch (PDOException $e) {
                error_log("Database error in login: " . $e->getMessage());
                $errors[] = "Σφάλμα συστήματος. Δοκιμάστε ξανά αργότερα.";
            }
        }
    }
}

// Reset attempts after 5 minutes
if ((time() - $_SESSION['last_attempt']) > 300) {
    $_SESSION['login_attempts'] = 0;
}
?>

<div class="auth-container">
    <h2>Σύνδεση</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <div class="success">
            <p>Η εγγραφή ολοκληρώθηκε με επιτυχία! Μπορείτε να συνδεθείτε τώρα.</p>
        </div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="form-group">
            <label for="username">Όνομα Χρήστη:</label>
            <input type="text" 
                   id="username" 
                   name="username" 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                   required 
                   maxlength="50"
                   autocomplete="username">
        </div>
        
        <div class="form-group">
            <label for="password">Κωδικός:</label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   required
                   autocomplete="current-password">
        </div>
        
        <div class="form-group">
            <input type="submit" value="Σύνδεση" class="btn btn-primary">
        </div>
        
        <div class="auth-links">
            <p>Δεν έχετε λογαριασμό; <a href="register.php">Εγγραφή εδώ</a></p>
        </div>
    </form>
</div>

<?php require 'footer.php'; ?>