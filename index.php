<?php require 'header.php'; ?>

<div class="home-container">
    <div class="hero-section">
        <h1>🌟 Καλώς ήρθες στο MyRECIPE! 🌟</h1>
        <p class="hero-description">
            Ανέβασε τις συνταγές σου, δες τις δημιουργίες των άλλων και 
            μοίρασε τις στιγμές που σε εμπνέουν!
        </p>
        
        <div class="hero-buttons">
            <a href="recipes.php" class="btn btn-primary btn-large">
                🍲 Δες τις συνταγές!
            </a>
            
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-outline btn-large">
                    ✨ Ξεκίνα Τώρα
                </a>
            <?php else: ?>
                <a href="add_recipe.php" class="btn btn-secondary btn-large">
                    ⬆️ Ανέβασε Συνταγή
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Στατιστικά (αν υπάρχουν δεδομένα) -->
    <?php
    try {
        $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalRecipes = $pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
        $totalLikes = $pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting homepage stats: " . $e->getMessage());
        $totalUsers = $totalRecipes = $totalLikes = 0;
    }
    
    if ($totalRecipes > 0): ?>
        <div class="stats-section">
            <h2>📊 Η Κοινότητά μας</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-number"><?php echo number_format($totalUsers); ?></span>
                    <span class="stat-label">Χρήστες</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo number_format($totalRecipes); ?></span>
                    <span class="stat-label">Συνταγές</span>
                </div>
                <div class="stat-box">
                    <span class="stat-number"><?php echo number_format($totalLikes); ?></span>
                    <span class="stat-label">Likes</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Πρόσφατες συνταγές -->
    <?php
    try {
        $stmt = $pdo->query("SELECT recipes.*, users.username FROM recipes JOIN users ON recipes.user_id = users.id ORDER BY created_at DESC LIMIT 6");
        $recentRecipes = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting recent recipes: " . $e->getMessage());
        $recentRecipes = [];
    }
    
    if (!empty($recentRecipes)): ?>
        <div class="recent-section">
            <div class="section-header">
                <h2>🔥 Πρόσφατες Συνταγές</h2>
                <a href="recipes.php" class="btn btn-outline">Δες Όλες</a>
            </div>
            
            <div class="recent-grid">
                <?php foreach ($recentRecipes as $recipe): ?>
                    <div class="recent-post">
                        <a href="recipe.php?id=<?php echo (int)$recipe['id']; ?>">
                            <img src="uploads/<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                                 alt="Εικόνα συνταγής από <?php echo htmlspecialchars($recipe['username']); ?>"
                                 loading="lazy">
                            <div class="recent-overlay">
                                <div class="recent-info">
                                    <span class="recent-author">
                                        <?php echo htmlspecialchars($recipe['username']); ?>
                                    </span>
                                    <span class="recent-date">
                                        <?php echo getTimeAgo($recipe['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Features -->
    <div class="features-section">
        <h2>✨ Γιατί MyRECIPE;</h2>
        <div class="features-grid">
            <div class="feature-box">
                <div class="feature-icon">🍕</div>
                <h3>Εύκολη Χρήση</h3>
                <p>Ανέβασε συνταγές με ένα κλικ και μοιράσου τες άμεσα με την κοινότητα.</p>
            </div>
            
            <div class="feature-box">
                <div class="feature-icon">🔒</div>
                <h3>Ασφαλής</h3>
                <p>Οι συνταγές σου είναι ασφαλείς και προστατευμένες με σύγχρονα μέτρα ασφαλείας.</p>
            </div>
            
            <div class="feature-box">
                <div class="feature-icon">📝 ❤️</div>
                <h3>Διαδραστική</h3>
                <p>Κάνε like, σχολίασε και συνδέσου με άλλους μάγειρες.</p>
            </div>
            
            <div class="feature-box">
                <div class="feature-icon">👨‍🍳</div>
                <h3>Δημιουργική</h3>
                <p>Εκφράσου μέσα από τις συνταγές και ανακάλυψε νέα ταλέντα.</p>
            </div>
        </div>
    </div>
    
    <!-- Call to action -->
    <?php if (!isLoggedIn()): ?>
        <div class="cta-section">
            <h2>🔥 Είσαι Έτοιμος να Ξεκινήσεις;</h2>
            <p>Γίνε μέλος της κοινότητάς μας και μοιράσου τη δική σου γεύση!</p>
            
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-primary btn-large">
                    Δωρεάν Εγγραφή
                </a>
                <a href="login.php" class="btn btn-outline btn-large">
                    Έχω ήδη Λογαριασμό
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="cta-section">
            <h2>👋 👨‍🍳 Γεια σου, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>Τι θα έλεγες να μοιραστείς μια νέα συνταγή σήμερα;</p>
            
            <div class="cta-buttons">
                <a href="add_recipe.php" class="btn btn-primary btn-large">
                    🍲 Ανέβασε Συνταγή
                </a>
                <a href="profile.php" class="btn btn-outline btn-large">
                    👤 Το Προφίλ μου
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>