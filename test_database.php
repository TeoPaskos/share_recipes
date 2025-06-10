<?php
// test_database.php
// Δοκιμή σύνδεσης με τη βάση MySQL

// Εισάγετε εδώ τα στοιχεία της βάσης σας αν δεν κάνετε require το config.php
$host = 'localhost';
$db   = 'aegean_recipes';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$port = 3320; // Αν το XAMPP τρέχει σε άλλο port, αλλάξτε το

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo '<h2 style="color:green">✅ Επιτυχής σύνδεση με τη βάση!</h2>';
} catch (PDOException $e) {
    echo '<h2 style="color:red">❌ Αποτυχία σύνδεσης:</h2>';
    echo '<pre>' . $e->getMessage() . '</pre>';
}
?>
