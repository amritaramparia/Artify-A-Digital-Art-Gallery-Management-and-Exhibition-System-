<?php
// sidebar.php - Reusable sidebar component
?>

<!-- Sidebar Styles -->
<style>
    /* Sidebar Styles */
    .sidebar {
        width: 250px;
        background-color: var(--dark);
        color: white;
        padding: 20px 0;
        height: 100vh;
        position: fixed;
        overflow-y: auto;
        transition: all 0.3s ease;
    }
    
    .logo {
        text-align: center;
        padding: 20px 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 20px;
    }
    
    .logo h2 {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        font-weight: 600;
        background: linear-gradient(45deg, #f43a09, #ffb766);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        letter-spacing: 1px;
    }
    
    .nav-menu {
        list-style: none;
    }
    
    .nav-menu li {
        margin-bottom: 5px;
    }
    
    .nav-menu a {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: white;
        text-decoration: none;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .nav-menu a:hover, .nav-menu a.active {
        background-color: rgba(255,255,255,0.1);
        border-left: 4px solid var(--orange1);
        text-shadow: 0 0 8px rgba(255, 183, 102, 0.7);
    }
    
    .nav-menu i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
    
    /* Toggle Button for Responsive */
    .toggle-btn {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1000;
        background: var(--orange1);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 15px;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-250px);
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .toggle-btn {
            display: block;
        }
    }
</style>

<!-- Sidebar HTML -->
<button class="toggle-btn" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="logo">
        <h2>Artify</h2>
    </div>
    <ul class="nav-menu">
        <li><a href="adminpanel.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adminpanel.php' ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
        <li><a href='artwork_adminpanel.php'><i class="fas fa-palette"></i> <span>Artworks</span></a></li>
        <li><a href="adminartist_approval.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adminartist_approval.php' ? 'active' : ''; ?>"><i class="fas fa-user-alt"></i> <span>Artists</span></a></li>
        <li><a href="orders.php"><i class="fas fa-gavel"></i> <span>Orders</span></a></li>
        <li><a href="category_adminpanel.php"><i class="fas fa-tags"></i> <span>Categories</span></a></li>
        <li><a href="admin_user.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
        <li><a href="index.php"><i class="fas fa-arrow-left"></i> <span>Back to Site</span></a></li>
        <!--<li><a href="#"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>-->
    </ul>
</div>

<script>
    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('mainContent').classList.toggle('active');
    });
</script>
