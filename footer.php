</main>
    
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>📸 MyRECIPE</h3>
                    <p>Μοιράσου τις στιγμές σου, ανακάλυψε νέες ιστορίες.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Γρήγοροι Σύνδεσμοι</h4>
                    <ul>
                        <li><a href="index.php">Αρχική</a></li>
                        <li><a href="recipes.php">Συνταγές</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="add_recipe.php">Νέα Συνταγή</a></li>
                            <li><a href="profile.php">Προφίλ</a></li>
                        <?php else: ?>
                            <li><a href="register.php">Εγγραφή</a></li>
                            <li><a href="login.php">Σύνδεση</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Πληροφορίες</h4>
                    <ul>
                        <li><a href="#privacy">Πολιτική Απορρήτου</a></li>
                        <li><a href="#terms">Όροι Χρήσης</a></li>
                        <li><a href="#contact">Επικοινωνία</a></li>
                        <li><a href="#help">Βοήθεια</a></li>
                    </ul>
                </div>
                
                <?php if (isLoggedIn()): ?>
                <div class="footer-section">
                    <h4>Γεια σου, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h4>
                    <p>Ευχαριστούμε που είσαι μέλος της κοινότητάς μας!</p>
                    <?php
                    try {
                        $userStats = $pdo->prepare("SELECT COUNT(*) as posts FROM posts WHERE user_id = ?");
                        $userStats->execute([$_SESSION['user_id']]);
                        $userPostCount = $userStats->fetchColumn();
                        
                        if ($userPostCount > 0) {
                            echo "<small>Έχεις μοιραστεί $userPostCount " . ($userPostCount == 1 ? 'φωτογραφία' : 'φωτογραφίες') . "!</small>";
                        }
                    } catch (PDOException $e) {
                        // Silent fail
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; <?php echo date('Y'); ?> MyGallery. Φτιαγμένο με ❤️ για τους λάτρεις της φωτογραφίας.</p>
                    <div class="footer-stats">
                        <?php
                        try {
                            $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                            $totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
                            echo "<small>$totalUsers χρήστες • $totalPosts φωτογραφίες</small>";
                        } catch (PDOException $e) {
                            // Silent fail
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>