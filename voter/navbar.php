<?php
// Common Navigation Bar for all voter pages
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <i class="fas fa-vote-yea"></i>
            <h2>VoteNepal</h2>
        </div>
        <div class="nav-menu">
            <span class="welcome-text">
                <i class="fas fa-user"></i>
                <?php echo htmlspecialchars($_SESSION['name'] ?? 'Voter'); ?>
            </span>
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profile
            </a>
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo i {
    font-size: 30px;
    color: var(--primary);
}

.logo h2 {
    color: var(--dark);
    font-size: 24px;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 20px;
}

.welcome-text {
    color: var(--dark);
    font-weight: 500;
}

.welcome-text i {
    color: var(--primary);
    margin-right: 5px;
}

.nav-link {
    color: var(--gray);
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 8px;
    transition: all 0.3s;
}

.nav-link:hover,
.nav-link.active {
    background: var(--light);
    color: var(--primary);
}

.btn-logout {
    padding: 8px 20px;
    background: var(--danger);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-logout:hover {
    background: #d81b60;
    transform: translateY(-2px);
}
</style>