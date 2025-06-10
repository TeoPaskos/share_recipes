<?php
// ΑΦΑΙΡΕΣΗ DEBUG ΕΚΤΥΠΩΣΗΣ SESSION
// ΜΗΝ ΕΚΤΥΠΩΝΕΤΕ ΠΟΤΕ ΠΡΙΝ ΤΟ HEADER/SESSION_START
?>
<nav>
    <div class="container">
        <div class="nav-brand">
            <a href="index.php" class="brand-link">
                 <strong>RecipeSocial</strong>
            </a>
        </div>
        
        <div class="nav-links">
            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                🏠 Αρχική
            </a>
            <a href="recipes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'recipes.php' ? 'active' : ''; ?>">
                📖 Συνταγές
            </a>
            
            <?php if (isLoggedIn()): ?>
                <a href="add_recipe.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_recipe.php' ? 'active' : ''; ?>">
                    ➕ Νέα Συνταγή
                </a>
                <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    👤 Προφίλ
                </a>
                <a href="logout.php" class="nav-link logout-link">
                    🚪 Έξοδος (<?php echo htmlspecialchars($_SESSION['username']); ?>)
                </a>
            <?php else: ?>
                <a href="register.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>">
                    ✨ Εγγραφή
                </a>
                <a href="login.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">
                    🔑 Σύνδεση
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Mobile menu toggle -->
        <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    const nav = document.querySelector('nav');
    nav.classList.toggle('mobile-menu-open');
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
    const nav = document.querySelector('nav');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (!nav.contains(e.target) && nav.classList.contains('mobile-menu-open')) {
        nav.classList.remove('mobile-menu-open');
    }
});
</script>

<style>
/* Enhanced Navigation Styles */
nav .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
}

.nav-brand {
    flex-shrink: 0;
}

.brand-link {
    font-size: 1.5rem;
    font-weight: bold;
    color: white !important;
    text-decoration: none;
    margin-right: 0 !important;
    padding: 10px 0;
}

.brand-link:hover {
    background: none !important;
    transform: scale(1.05);
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}

.nav-link {
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
    white-space: nowrap;
    margin-right: 0 !important;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.3);
    font-weight: 600;
}

.logout-link {
    background: rgba(220, 53, 69, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.logout-link:hover {
    background: rgba(220, 53, 69, 0.5);
}

.mobile-menu-toggle {
    display: none;
    flex-direction: column;
    cursor: pointer;
    padding: 5px;
}

.mobile-menu-toggle span {
    width: 25px;
    height: 3px;
    background: white;
    margin: 3px 0;
    transition: 0.3s;
    border-radius: 2px;
}

/* Mobile styles */
@media (max-width: 768px) {
    nav .container {
        flex-wrap: wrap;
        position: relative;
    }
    
    .mobile-menu-toggle {
        display: flex;
    }
    
    .nav-links {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: rgba(102, 126, 234, 0.95);
        backdrop-filter: blur(10px);
        flex-direction: column;
        padding: 20px;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        z-index: 1000;
    }
    
    .mobile-menu-open .nav-links {
        display: flex;
    }
    
    .mobile-menu-open .mobile-menu-toggle span:nth-child(1) {
        transform: rotate(-45deg) translate(-5px, 6px);
    }
    
    .mobile-menu-open .mobile-menu-toggle span:nth-child(2) {
        opacity: 0;
    }
    
    .mobile-menu-open .mobile-menu-toggle span:nth-child(3) {
        transform: rotate(45deg) translate(-5px, -6px);
    }
    
    .nav-link {
        width: 100%;
        text-align: center;
        margin: 5px 0;
        padding: 15px;
        border-radius: 10px;
    }
}

@media (max-width: 480px) {
    .brand-link {
        font-size: 1.2rem;
    }
    
    nav .container {
        padding: 0 15px;
    }
}
</style>