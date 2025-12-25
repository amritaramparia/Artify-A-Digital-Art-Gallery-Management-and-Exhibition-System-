<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "art";

$conn = new mysqli($servername, $username, $password, $dbname);

$cart_count = 0;
$wishlist_count = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get cart count
    $cart_sql = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($cart_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_result = $stmt->get_result();
    $cart_data = $cart_result->fetch_assoc();
    $cart_count = $cart_data['count'];
    $stmt->close();
    
    // Get wishlist count
    $wishlist_sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
    $stmt = $conn->prepare($wishlist_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $wishlist_result = $stmt->get_result();
    $wishlist_data = $wishlist_result->fetch_assoc();
    $wishlist_count = $wishlist_data['count'];
    $stmt->close();
}
// Check if user is logged in and get user type
$isArtist = false;
$isAdmin = false;
$userName = "Guest";
$userInitial = "G";

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Check if user is an approved artist
    $artistCheckSql = "SELECT * FROM artists WHERE user_id = ? AND approval_status = 'approved'";
    $stmt = $conn->prepare($artistCheckSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $artistResult = $stmt->get_result();
    
    if ($artistResult->num_rows > 0) {
        $isArtist = true;
        $artistData = $artistResult->fetch_assoc();
        $userName = $artistData['artist_name'];
        $userInitial = strtoupper(substr($userName, 0, 1));
    } else {
        // Get regular user name
        $userSql = "SELECT first_name, last_name FROM register_data WHERE id = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows > 0) {
            $userData = $userResult->fetch_assoc();
            $userName = $userData['first_name'] . ' ' . $userData['last_name'];
            $userInitial = strtoupper(substr($userName, 0, 1));
        }
    }
}

// Check if admin is logged in
if (isset($_SESSION['admin_id'])) {
    $isAdmin = true;
    $userName = "Admin";
    $userInitial = "A";
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Artify - Compact Art Menu</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Import the same fonts as the footer */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Montserrat:wght@300;400;500&display=swap');
    
    :root {
      --orange1: #f43a09;
      --orange2: #ffb766;
      --bluegreen: #c2edda;
      --green: #68d388;
      --dark-bg: #1a1a1a;
      --dark-secondary: #2a2a2a;
      --text-light: #ccc;
    }
    
    body {
      margin: 0;
      font-family: 'Montserrat', sans-serif;
      background-color: #f8f9fa;
      padding-top: 85px;
    }
    
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 15px 40px;
      background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-secondary) 50%, var(--dark-bg) 100%);
      border-bottom: none;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.5);
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      overflow: visible;
      z-index: 1000;
      box-sizing: border-box;
    }
    
    header::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
          radial-gradient(circle at 20% 80%, 
              rgba(255, 183, 102, 0.15) 0%, 
              transparent 20%),
          radial-gradient(circle at 80% 20%, 
              rgba(104, 211, 136, 0.15) 0%, 
              transparent 20%);
      z-index: 1;
      pointer-events: none; /* FIX: Allow clicks to pass through */
    }
    
    /* Logo left */
    .logo {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      font-weight: 600;
      background: linear-gradient(45deg, #f43a09, #ffb766);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
      letter-spacing: 1px;
      white-space: nowrap;
      position: relative;
      z-index: 3; /* FIX: Increased z-index */
    }
    
    /* Navigation menu with integrated search */
    nav {
      position: relative;
      z-index: 3; /* FIX: Increased z-index */
      display: flex;
      align-items: center;
      flex: 1;
      justify-content: center;
    }
    
    nav ul {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      gap: 28px;
      align-items: center;
    }
    
    nav ul li {
      position: relative;
    }
    
    nav ul li a {
      text-decoration: none;
      font-size: 1.1rem;
      color: var(--text-light);
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
      padding: 5px 0;
      font-family: 'Montserrat', sans-serif;
    }
    
    nav ul li a::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0;
      height: 3px;
      background: linear-gradient(90deg, #f43a09, #ffb766);
      transition: width 0.3s ease;
      border-radius: 3px;
    }
    
    nav ul li a:hover {
      color: #ffb766;
    }
    
    nav ul li a:hover::after {
      width: 100%;
    }
    
    .art-dropdown {
      position: absolute;
      top: 100%;
      left: 0;
      background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-secondary) 100%);
      border-radius: 8px;
      width: 400px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      opacity: 0;
      visibility: hidden;
      transform: translateY(10px);
      transition: all 0.3s ease;
      z-index: 1001;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 15px;
    }
    
    nav ul li:hover .art-dropdown {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    
    .dropdown-section h3 {
      color: var(--orange2);
      font-size: 0.9rem;
      margin: 15px 0 10px 0;
      padding-bottom: 6px;
      border-bottom: 1px solid rgba(255, 183, 102, 0.3);
      font-family: 'Montserrat', sans-serif;
      letter-spacing: 0.5px;
      text-align: left;
    }
    
    .dropdown-section:first-child h3 {
      margin-top: 0;
    }
    
    .dropdown-section ul {
      display: flex;
      flex-direction: column;
      gap: 8px;
      align-items: flex-start;
      text-align: left;
    }
    
    .dropdown-section ul li {
      padding: 0;
      width:100%;
    }
    
    .dropdown-section ul li a {
      font-size: 0.85rem;
      color: var(--text-light);
      transition: all 0.2s ease;
      padding: 3px 0;
      display: block;
      text-align: left;
      position:relative;
    }
    
    .dropdown-section ul li a:hover {
      color: var(--orange2);
      transform: translateX(5px);
    }
    
    .dropdown-section ul li a::after {
      display: none;
    }
    
    .dropdown-columns {
      display: flex;
      gap: 15px;
      align-items: left;
    }
    
    .dropdown-column {
      flex: 1;
    }
    
    /* Search box integrated in navigation */
    .nav-search-container {
      position: relative;
      margin-left: 20px;
      z-index: 3; /* FIX: Added z-index */
    }
    
    .nav-search-box {
      display: flex;
      width: 220px;
      position: relative;
    }
    
    .nav-search-input {
      width: 100%;
      padding: 10px 40px 10px 15px;
      border: 2px solid rgba(255, 255, 255, 0.1);
      border-radius: 30px;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      outline: none;
      background: rgba(255, 255, 255, 0.1);
      color: var(--text-light);
      backdrop-filter: blur(5px);
      box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.3),
                 -3px -3px 8px rgba(255, 255, 255, 0.05);
    }
    
    .nav-search-input:focus {
      border-color: var(--orange2);
      box-shadow: 0 0 0 2px rgba(255, 183, 102, 0.3),
                  5px 5px 10px rgba(0, 0, 0, 0.3),
                 -3px -3px 8px rgba(255, 255, 255, 0.05);
    }
    
    .nav-search-input::placeholder {
      color: #999;
    }
    
    .nav-search-button {
      position: absolute;
      right: 5px;
      top: 5px;
      width: 30px;
      height: 30px;
      background: linear-gradient(45deg, #f43a09, #ffb766);
      border: none;
      border-radius: 50%;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(244, 58, 9, 0.4);
      font-size: 0.9rem;
    }
    
    .nav-search-button:hover {
      background: linear-gradient(45deg, #ff6b3d, #ffc58c);
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(244, 58, 9, 0.5);
    }
    
    /* Header right section */
    .header-right {
      display: flex;
      align-items: center;
      gap: 15px;
      position: relative;
      z-index: 3; /* FIX: Increased z-index */
    }
    
    /* Circular Icons Common Styles */
    .icon-container {
      position: relative;
      display: flex;
      align-items: center;
      z-index: 4; /* FIX: Added higher z-index */
    }
    
    .circular-icon {
      width: 45px;
      height: 45px;
      background: linear-gradient(145deg, #2a2a2a, #232323);
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 18px;
      color: var(--text-light);
      box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.3),
                 -3px -3px 8px rgba(255, 255, 255, 0.05);
      text-decoration: none;
      position: relative;
      z-index: 5; /* FIX: Added higher z-index */
    }
    
    .circular-icon:hover {
      background: linear-gradient(145deg, #f43a09, #ff6b3d);
      color: white;
      transform: translateY(-5px);
      box-shadow: 8px 8px 15px rgba(0, 0, 0, 0.4),
                 -5px -5px 10px rgba(255, 255, 255, 0.08);
    }
    
    .icon-count {
      position: absolute;
      top: -5px;
      right: -5px;
      background: var(--green);
      color: white;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 12px;
      font-weight: bold;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      z-index: 6; /* FIX: Added higher z-index */
    }
    
    .icon-text {
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%);
      background: #333;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 12px;
      white-space: nowrap;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      margin-top: 8px;
      font-family: 'Montserrat', sans-serif;
      z-index: 6; /* FIX: Added higher z-index */
    }
    
    .icon-text::before {
      content: "";
      position: absolute;
      bottom: 100%;
      left: 50%;
      transform: translateX(-50%);
      border-width: 5px;
      border-style: solid;
      border-color: transparent transparent #333 transparent;
    }
    
    .icon-container:hover .icon-text {
      opacity: 1;
      visibility: visible;
    }
    
    /* Profile Icon Styles */
    .profile-container {
      position: relative;
      z-index: 4; /* FIX: Added higher z-index */
    }
    
    .profile-icon {
      width: 45px;
      height: 45px;
      background: linear-gradient(145deg, #2a2a2a, #232323);
      color: var(--text-light);
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: bold;
      font-size: 16px;
      box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.3),
                 -3px -3px 8px rgba(255, 255, 255, 0.05);
      position: relative;
      z-index: 5; /* FIX: Added higher z-index */
    }
    
    .profile-icon:hover {
      background: linear-gradient(145deg, #f43a09, #ff6b3d);
      color: white;
      transform: translateY(-5px);
      box-shadow: 8px 8px 15px rgba(0, 0, 0, 0.4),
                 -5px -5px 10px rgba(255, 255, 255, 0.08);
    }
    
    .dropdown-menu {
      position: absolute;
      top: 55px;
      right: 0;
      background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-secondary) 100%);
      border-radius: 12px;
      width: 250px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1001;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 10px 0;
    }
    
    .dropdown-menu.active {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    
    .menu-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 20px;
      color: var(--text-light);
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 14px;
      font-family: 'Montserrat', sans-serif;
      border: none;
      background: none;
      width: 100%;
      text-align: left;
      cursor: pointer;
    }
    
    .menu-item:hover {
      background: rgba(255, 255, 255, 0.1);
      color: var(--orange2);
      transform: translateX(5px);
    }
    
    .menu-item i {
      width: 20px;
      text-align: center;
      font-size: 16px;
    }
    
    .artist-section {
      border-top: 1px solid rgba(255, 183, 102, 0.3);
      border-bottom: 1px solid rgba(255, 183, 102, 0.3);
      margin: 8px 0;
      padding: 8px 0;
    }
    
    .artist-section-title {
      padding: 8px 20px;
      font-size: 12px;
      color: var(--orange2);
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .menu-divider {
      height: 1px;
      background: rgba(255, 255, 255, 0.1);
      margin: 5px 0;
    }
    
    .profile-text {
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%);
      background: #333;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 12px;
      white-space: nowrap;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      margin-top: 8px;
      font-family: 'Montserrat', sans-serif;
      z-index: 6; /* FIX: Added higher z-index */
    }
    
    .profile-text::before {
      content: "";
      position: absolute;
      bottom: 100%;
      left: 50%;
      transform: translateX(-50%);
      border-width: 5px;
      border-style: solid;
      border-color: transparent transparent #333 transparent;
    }
    
    .profile-container:hover .profile-text {
      opacity: 1;
      visibility: visible;
    }
    
    .admin-section {
      border-top: 1px solid rgba(255, 183, 102, 0.3);
      border-bottom: 1px solid rgba(255, 183, 102, 0.3);
      margin: 8px 0;
      padding: 8px 0;
      background: rgba(244, 58, 9, 0.1);
    }
    
    .admin-section-title {
      padding: 8px 20px;
      font-size: 12px;
      color: var(--orange1);
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    /* Responsive Design */
    @media (max-width: 1100px) {
      nav ul {
        gap: 15px;
      }
      
      .nav-search-box {
        width: 180px;
      }
    }
    
    @media (max-width: 992px) {
      header {
        padding: 15px 20px;
        flex-wrap: wrap;
      }
      
      nav {
        order: 3;
        width: 100%;
        margin-top: 15px;
      }
      
      nav ul {
        justify-content: center;
        flex-wrap: wrap;
      }
      
      .nav-search-container {
        margin: 10px 0 0 0;
        width: 100%;
        display: flex;
        justify-content: center;
      }
      
      .nav-search-box {
        width: 80%;
        max-width: 400px;
      }
      
      .header-right {
        order: 2;
      }
      
      body {
        padding-top: 120px;
      }
    }
    
    @media (max-width: 768px) {
      nav ul li a {
        font-size: 1rem;
      }
      
      .logo {
        font-size: 2rem;
      }
      
      body {
        padding-top: 110px;
      }
    }
    
    @media (max-width: 576px) {
      nav ul {
        gap: 10px;
      }
      
      .header-right {
        gap: 10px;
      }
      
      .circular-icon,
      .profile-icon {
        width: 40px;
        height: 40px;
        font-size: 16px;
      }
      
      body {
        padding-top: 100px;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">Artify</div>
    
    <nav>
      <ul>
        <li>
          <a href="#">Art</a>
          <div class="art-dropdown">
            <div class="dropdown-columns">
              <div class="dropdown-column">
                <div class="dropdown-section">
                  <h3>ALL PAINTINGS</h3>
                  <ul>
                    <li><a href="artgallary.php?subcategory=1">Abstract Painting</a></li>
                    <li><a href="artgallary.php?subcategory=5">Acrylic Painting</a></li>
                    <li><a href="artgallary.php?subcategory=2">Fresco</a></li>
                    <li><a href="artgallary.php?subcategory=4">Ink Wash Painting</a></li>
                    <li><a href="artgallary.php?subcategory=3">Modern Art Paintings</a></li>
                    <li><a href="artgallary.php?subcategory=6">Nature | Scenery</a></li>
                    <li><a href="artgallary.php?subcategory=7">Cityscape Painting</a></li>
                    <li><a href="artgallary.php?subcategory=8">Figurative Painting</a></li>
                    <li><a href="artgallary.php?subcategory=9">Flower Painting</a></li>
                    <li><a href="artgallary.php?subcategory=10">Hindu God-Goddess</a></li>
                  </ul>
                </div>
              </div>
              
              <div class="dropdown-column">
                <div class="dropdown-section">
                  <h3>ALL SCULPTURE</h3>
                  <ul>
                    <li><a href="artgallary.php?subcategory=11">Free-standing Sculpture</a></li>
                    <li><a href="artgallary.php?subcategory=12">Relief Sculpture</a></li>
                    <li><a href="artgallary.php?subcategory=13">Carving</a></li>
                    <li><a href="artgallary.php?subcategory=14">Kinetic Sculpture</a></li>
                  </ul>
                </div>
                
                <div class="dropdown-section">
                  <h3>SHOWPIECE & DECORATIVE</h3>
                  <ul>
                    <li><a href="artgallary.php?subcategory=15">Pottery & Ceramics</a></li>
                    <li><a href="artgallary.php?subcategory=16">Glass Art</a></li>
                    <li><a href="artgallary.php?subcategory=17">Calligraphy</a></li>
                    <li><a href="artgallary.php?subcategory=18">Mosaic Art</a></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </li>
       
        <li><a href="artgallary.php">Gallery</a></li>
        <li><a href="artists.php">Artists</a></li>
        <li><a href="aboutus.php">About</a></li>
        <li><a href="login.php">login</a></li>
  
        <?php if ($isArtist): ?>
          <!-- ARTIST-SPECIFIC NAVIGATION ITEMS -->
          <li><a href="artist_dashboard.php">Artist Dashboard</a></li>
          <li><a href="upload_artwork.php">Upload Art</a></li>
        <?php endif; ?>
        
        
      </ul>
    </nav>
  
    <div class="header-right">
      <div class="icon-container">
        <a href="wishlist.php" class="circular-icon">
          <i class="fa-solid fa-heart"></i>
          <?php if ($wishlist_count > 0): ?>
                <span class="count-badge wishlist-badge"><?php echo $wishlist_count; ?></span>
            <?php endif; ?>
        </a>
        <div class="icon-text">My Wishlist</div>
      </div>

      <div class="icon-container">
        <a href="cart.php" class="circular-icon">
          <i class="fa-solid fa-cart-shopping"></i>
           <?php if ($cart_count > 0): ?>
                <span class="count-badge cart-badge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>
        <div class="icon-text">Shopping Cart</div>
      </div>
      
      <!-- User Profile -->
      <div class="profile-container">
        <div class="profile-icon" onclick="toggleMenu()">
          <?php echo $userInitial; ?>
        </div>
        <div class="profile-text"><?php echo htmlspecialchars($userName); ?></div>
        <div class="dropdown-menu" id="dropdownMenu">
          <!-- User Info -->
          <div class="menu-item" style="color: var(--green); font-weight: bold;">
            <i class="fas fa-user"></i>
            <span><?php echo htmlspecialchars($userName); ?></span>
            <?php if ($isAdmin): ?>
              <span style="color: var(--orange1); margin-left: 5px;">(Admin)</span>
            <?php elseif ($isArtist): ?>
              <span style="color: var(--orange2); margin-left: 5px;">(Artist)</span>
            <?php endif; ?>
          </div>
          
          <div class="menu-divider"></div>
          
          <!-- Regular User Options -->
          <a href="profile.php" class="menu-item">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
          </a>
          
          
          <?php if ($isArtist): ?>
            <!-- Artist Section -->
            <div class="artist-section">
              <div class="artist-section-title">
                <i class="fas fa-palette"></i>
                Artist Tools
              </div>
              <a href="artist_dashboard.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
              </a>
             
              <a href="artist_artworks.php" class="menu-item">
                <i class="fas fa-palette"></i>
                <span>My Artworks</span>
              </a>
              <a href="my_orders.php" class="menu-item">
              <i class="fas fa-shopping-bag"></i>
              <span>My Orders</span>
            </a>
             
            </div>

          <?php elseif ($isAdmin): ?>
            <!-- Admin Section -->
            <div class="admin-section">
              <div class="admin-section-title">
                <i class="fas fa-crown"></i>
                Admin Tools
              </div>
              <a href="adminpanel.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Admin Dashboard</span>
              </a>
              <a href="admin_user.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
              </a>
              <a href="adminartist_approval.php" class="menu-item">
                <i class="fas fa-paint-brush"></i>
                <span>Manage Artists</span>
              </a>
              <a href="artwork_adminpanel.php" class="menu-item">
                <i class="fas fa-image"></i>
                <span>Manage Artworks</span>
              </a>
              <a href="orders.php" class="menu-item">
                <i class="fas fa-image"></i>
                <span>Manage Orders</span>
              </a>
            </div>
          <?php else: ?>
            <!-- Become Artist Option for Regular Users -->
            <a href="artist_registration.php" class="menu-item">
              <i class="fas fa-palette"></i>
              <span>Become an Artist</span>
            </a>
             <a href="my_orders.php" class="menu-item">
              <i class="fas fa-shopping-bag"></i>
              <span>My Orders</span>
            </a>
          <?php endif; ?>
          
          <div class="menu-divider"></div>
          
          <!-- Logout Options -->
          <?php if ($isAdmin): ?>
            <div class="menu-item" onclick="confirmLogout()" style="color: var(--orange1);">
              <i class="fas fa-sign-out-alt"></i>
              <span>Admin Logout</span>
            </div>
          <?php else: ?>
            <div class="menu-item" onclick="confirmLogout()" style="color: var(--orange1);">
              <i class="fas fa-sign-out-alt"></i>
              <span>Log Out</span>
            </div>
            <div class="menu-item" onclick="confirmDeleteAccount()" style="color: #dc3545;">
              <i class="fas fa-trash-alt"></i>
              <span>Delete Account</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // Menu Toggle
    function toggleMenu() {
      const menu = document.getElementById('dropdownMenu');
      menu.classList.toggle('active');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const menu = document.getElementById('dropdownMenu');
      const icon = document.querySelector('.profile-icon');
      
      if (!menu.contains(event.target) && !icon.contains(event.target)) {
        menu.classList.remove('active');
      }
    });

    function updateCartCount(count) {
      const cartCountElement = document.getElementById('cart-count');
      if (cartCountElement) {
        cartCountElement.textContent = count;
      }
    }

    // Function to update wishlist count in header
    function updateWishlistCount(count) {
      const wishlistCountElement = document.getElementById('wishlist-count');
      if (wishlistCountElement) {
        wishlistCountElement.textContent = count;
      }
    }

    // Initialize counts on page load
    document.addEventListener('DOMContentLoaded', function() {
      const cart = JSON.parse(localStorage.getItem('artCart')) || [];
      const wishlist = JSON.parse(localStorage.getItem('artWishlist')) || [];
      
      updateCartCount(cart.length);
      updateWishlistCount(wishlist.length);
    });
   
    // Search functionality
    document.querySelector('.nav-search-button').addEventListener('click', function() {
      const searchTerm = document.querySelector('.nav-search-input').value;
      if (searchTerm) {
        window.location.href = 'search.php?q=' + encodeURIComponent(searchTerm);
      }
    });

    document.querySelector('.nav-search-input').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        const searchTerm = this.value;
        if (searchTerm) {
          window.location.href = 'search.php?q=' + encodeURIComponent(searchTerm);
        }
      }
    });

    function confirmLogout() {
      Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f43a09',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Logout!'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'logout.php';
        }
      });
    }

    function confirmDeleteAccount() {
      Swal.fire({
        title: 'Delete Account?',
        text: 'This will permanently delete your account and all associated data. This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete My Account',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'logout.php?action=delete';
        }
      });
    }

    // Initialize counts on page load
    function initializeCounts() {
      const cart = JSON.parse(localStorage.getItem('artCart')) || [];
      const wishlist = JSON.parse(localStorage.getItem('artWishlist')) || [];
      
      updateCartCount(cart.length);
      updateWishlistCount(wishlist.length);
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', initializeCounts);
  </script>
</body>
</html>