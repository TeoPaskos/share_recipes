<?php
// Ρυθμίσεις ασφαλείας
ini_set('display_errors', 0); // Απόκρυψη errors σε production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Ενεργοποίηση μόνο με HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Περιβάλλον (development/production)
define('ENVIRONMENT', 'development'); // Άλλαξε σε 'production' για παραγωγή

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', 3320); // αν το MySQL τρέχει στο 3320
define('DB_NAME', 'aegean_recipes');//onoma db
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Paths
define('UPLOAD_PATH', __DIR__ . '/uploads/'); //images_path
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show user-friendly message
    if (ENVIRONMENT === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Προσωρινό πρόβλημα με τη βάση δεδομένων. Δοκιμάστε ξανά αργότερα.");
    }
}

// Helper Functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url, $permanent = false) {
    $code = $permanent ? 301 : 302;
    header("Location: $url", true, $code);
    exit();
}

function isValidImage($file) {
    // Έλεγχος MIME type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }
    
    // Έλεγχος extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Έλεγχος μεγέθους
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Έλεγχος διαστάσεων
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return false;
    }
    
    // Μέγιστες διαστάσεις
    if ($imageInfo[0] > 4000 || $imageInfo[1] > 4000) {
        return false;
    }
    
    return true;
}

function createSafeFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid('img_', true) . '.' . $extension;
}

function formatDate($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'μόλις τώρα';
    if ($time < 3600) return floor($time/60) . ' λεπτά πριν';
    if ($time < 86400) return floor($time/3600) . ' ώρες πριν';
    if ($time < 2592000) return floor($time/86400) . ' μέρες πριν';
    
    return formatDate($datetime);
}

// Δημιουργία απαραίτητων φακέλων
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Security Headers (μόνο αν δεν έχουν σταλεί ήδη)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    $csp = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline'; ";
    $csp .= "style-src 'self' 'unsafe-inline'; ";
    $csp .= "img-src 'self' data:; ";
    $csp .= "font-src 'self'; ";
    $csp .= "connect-src 'self';";
    
    header("Content-Security-Policy: $csp");
}

// Timezone
date_default_timezone_set('Europe/Athens');
?>