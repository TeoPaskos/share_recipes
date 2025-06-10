<?php
require 'config.php';
require 'session_check.php';
require 'nav.php';

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
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        // Validation τίτλου
        if (empty($title)) {
            $errors[] = "Ο τίτλος είναι υποχρεωτικός.";
        } elseif (mb_strlen($title) > 100) {
            $errors[] = "Ο τίτλος δεν μπορεί να υπερβαίνει τους 100 χαρακτήρες.";
        }
        // Validation περιγραφής
        if (mb_strlen($description) > 1000) {
            $errors[] = "Η περιγραφή δεν μπορεί να υπερβαίνει τους 1000 χαρακτήρες.";
        }
        // File validation
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Σφάλμα στο ανέβασμα της εικόνας.";
        } else {
            $file = $_FILES['image'];
            // Έλεγχος μεγέθους (μέγιστο 6MB)
            if ($file['size'] > 6 * 1024 * 1024) {
                $errors[] = "Η εικόνα δεν μπορεί να υπερβαίνει τα 6MB.";
            }
            // Έλεγχος τύπου αρχείου
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = "Επιτρέπονται μόνο αρχεία εικόνας (JPEG, PNG, GIF, WebP).";
            }
            // Έλεγχος διαστάσεων εικόνας
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $errors[] = "Μη έγκυρο αρχείο εικόνας.";
            } elseif ($imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
                $errors[] = "Οι διαστάσεις της εικόνας δεν μπορούν να υπερβαίνουν τα 4000x4000 pixels.";
            }
        }
        // Αν δεν υπάρχουν σφάλματα, προχωράμε στο upload
        if (empty($errors)) {
            // Δημιουργία ασφαλούς ονόματος αρχείου
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('img_', true) . '.' . strtolower($extension);
            $uploadPath = __DIR__ . "/uploads/" . $filename;
            // Δημιουργία φακέλου uploads αν δεν υπάρχει
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            // Μετακίνηση αρχείου
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Εισαγωγή στη βάση
                try {
                    $stmt = $pdo->prepare("INSERT INTO recipes (user_id, title, description, image_path) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $title, $description, $filename]);
                    $success = true;
                } catch (PDOException $e) {
                    // Διαγραφή αρχείου αν αποτύχει η εισαγωγή στη βάση
                    unlink($uploadPath);
                    $errors[] = "Σφάλμα στην αποθήκευση. Δοκιμάστε ξανά.";
                }
            } else {
                $errors[] = "Σφάλμα στο ανέβασμα του αρχείου.";
            }
        }
    }
}
?>

<h2>Ανέβασε Συνταγή</h2>

<?php if ($success): ?>
    <div style="color: green; background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin-bottom: 20px;">
        <strong>Επιτυχία!</strong> Η συνταγή ανέβηκε με επιτυχία! 
        <a href="recipes.php">Δες τις συνταγές</a>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div style="color: #721c24; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin-bottom: 20px;">
        <strong>Σφάλματα:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <label for="title">Τίτλος συνταγής:</label><br>
    <input type="text" name="title" id="title" maxlength="100" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"><br>
    <small>Μέγιστο 100 χαρακτήρες</small><br><br>
    <label for="description">Περιγραφή:</label><br>
    <textarea name="description" id="description" maxlength="1000" placeholder="Προαιρετική περιγραφή της συνταγής..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea><br>
    <small>Μέγιστο 1000 χαρακτήρες</small><br><br>
    <label for="image">Εικόνα συνταγής:</label><br>
    <input type="file" name="image" id="image" accept="image/*" required><br>
    <small>Επιτρέπονται: JPEG, PNG, GIF, WebP (μέγιστο 5MB, 4000x4000px)</small><br><br>
    <input type="submit" value="Ανέβασμα">
</form>

<script>
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('Η εικόνα είναι πολύ μεγάλη (>5MB)');
            e.target.value = '';
        }
    }
});
</script>

</main>
</body>
</html>
