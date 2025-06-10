<?php
require 'header.php';

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;

if ($_POST) {
    // CSRF έλεγχος
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "Μη έγκυρη αίτηση. Δοκιμάστε ξανά.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation του username
        if (empty($username)) {
            $errors[] = "Το όνομα χρήστη είναι υποχρεωτικό.";
        } elseif (strlen($username) < 3) {
            $errors[] = "Το όνομα χρήστη πρέπει να έχει τουλάχιστον 3 χαρακτήρες.";
        } elseif (strlen($username) > 50) {
            $errors[] = "Το όνομα χρήστη δεν μπορεί να υπερβαίνει τους 50 χαρακτήρες.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Το όνομα χρήστη μπορεί να περιέχει μόνο γράμματα, αριθμούς και κάτω παύλα.";
        }

        // Validation email
        if (empty($email)) {
            $errors[] = "Το email είναι υποχρεωτικό.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Το email δεν είναι έγκυρο.";
        } elseif (strlen($email) > 100) {
            $errors[] = "Το email δεν μπορεί να υπερβαίνει τους 100 χαρακτήρες.";
        }

        // Validation του κωδικού
        if (empty($password)) {
            $errors[] = "Ο κωδικός είναι υποχρεωτικός.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $errors[] = "Ο κωδικός πρέπει να περιέχει τουλάχιστον ένα μικρό γράμμα, ένα κεφαλαίο και έναν αριθμό.";
        }

        // Επιβεβαίωση κωδικού
        if ($password !== $confirmPassword) {
            $errors[] = "Οι κωδικοί δεν ταιριάζουν.";
        }

        // Έλεγχος αν υπάρχει ήδη το username ή το email
        if (empty($errors)) {
            try {
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check->execute([$username, $email]);
                if ($check->fetch()) {
                    $errors[] = "Το όνομα χρήστη ή το email χρησιμοποιείται ήδη.";
                }
            } catch (PDOException $e) {
                error_log("Database error checking username/email: " . $e->getMessage());
                $errors[] = "Σφάλμα συστήματος. Δοκιμάστε ξανά αργότερα.";
            }
        }

        // Εγγραφή χρήστη
        if (empty($errors)) {
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword]);
                // Επιτυχής εγγραφή - redirect στο login
                header("Location: login.php?registered=1");
                exit();
            } catch (PDOException $e) {
                error_log("Database error in registration: " . $e->getMessage());
                $errors[] = "Σφάλμα στην εγγραφή. Δοκιμάστε ξανά αργότερα.";
            }
        }
    }
}
?>

<div class="auth-container">
    <h2>Εγγραφή</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="register-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="form-group">
            <label for="username">Όνομα Χρήστη:</label>
            <input type="text" 
                   id="username" 
                   name="username" 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                   required 
                   maxlength="50"
                   pattern="[a-zA-Z0-9_]+"
                   autocomplete="username">
            <small>3-50 χαρακτήρες, μόνο γράμματα, αριθμοί και κάτω παύλα</small>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                   required 
                   maxlength="100"
                   autocomplete="email">
            <small>Έγκυρη διεύθυνση email, μέγιστο 100 χαρακτήρες</small>
        </div>
        
        <div class="form-group">
            <label for="password">Κωδικός:</label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   required
                   minlength="8"
                   autocomplete="new-password">
            <small>Τουλάχιστον 8 χαρακτήρες με κεφαλαίο, μικρό γράμμα και αριθμό</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Επιβεβαίωση Κωδικού:</label>
            <input type="password" 
                   id="confirm_password" 
                   name="confirm_password" 
                   required
                   autocomplete="new-password">
        </div>
        
        <div class="form-group">
            <input type="submit" value="Εγγραφή" class="btn btn-primary">
        </div>
        
        <div class="auth-links">
            <p>Έχετε ήδη λογαριασμό; <a href="login.php">Σύνδεση εδώ</a></p>
        </div>
    </form>
</div>

<script>
// Έλεγχος αν υπάρχουν τα στοιχεία πριν το event listener (register.php)
const confirmPasswordInput = document.getElementById('confirm_password');
const passwordInput = document.getElementById('password');
if (confirmPasswordInput && passwordInput) {
    confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        if (password !== confirmPassword) {
            this.setCustomValidity('Οι κωδικοί δεν ταιριάζουν');
        } else {
            this.setCustomValidity('');
        }
    });
    passwordInput.addEventListener('input', function() {
        // Εδώ μπορείς να προσθέσεις ένδειξη ισχύος κωδικού
    });
}
</script>

<?php require 'footer.php'; ?>
