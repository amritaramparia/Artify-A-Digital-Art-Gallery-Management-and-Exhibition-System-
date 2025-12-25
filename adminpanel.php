<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "art";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get total user count
$user_count_sql = "SELECT COUNT(*) as total_users FROM register_data";
$user_count_result = $conn->query($user_count_sql);
$user_count = 0;

if ($user_count_result && $user_count_result->num_rows > 0) {
    $row = $user_count_result->fetch_assoc();
    $user_count = $row['total_users'];
}

// Get other counts
$artist_count_sql= "SELECT COUNT(*) as total_artists FROM artists";
$artist_count_result = $conn->query($artist_count_sql);
$artist_count = 0;
if ($artist_count_result && $artist_count_result->num_rows > 0) {
    $row = $artist_count_result->fetch_assoc();
    $artist_count = $row['total_artists'];
}

$artwork_count_sql= "SELECT COUNT(*) as total_artworks FROM artworks";
$artwork_count_result = $conn->query($artwork_count_sql);
$artwork_count = 0;
if ($artwork_count_result && $artwork_count_result->num_rows > 0) {
    $row = $artwork_count_result->fetch_assoc();
    $artwork_count = $row['total_artworks'];
}


$conn->close();
?>
                   
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artify Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --orange1: #f43a09;
            --orange2: #ffb766;
            --bluegreen: #c2edda;
            --green: #68d388;
            --dark: #2c2c54;
            --light: #f5f5f5;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
            display: flex;
            min-height: 100vh;
        }
        
        /* 3D Font Effects */
        .text-3d {
            text-shadow: 0 1px 0 #ccc, 
                         0 2px 0 #c9c9c9,
                         0 3px 0 #bbb,
                         0 4px 0 #b9b9b9,
                         0 5px 0 #aaa,
                         0 6px 1px rgba(0,0,0,.1),
                         0 0 5px rgba(0,0,0,.1),
                         0 1px 3px rgba(0,0,0,.3),
                         0 3px 5px rgba(0,0,0,.2),
                         0 5px 10px rgba(0,0,0,.25),
                         0 10px 10px rgba(0,0,0,.2),
                         0 20px 20px rgba(0,0,0,.15);
        }
        
        .text-3d-light {
            text-shadow: 0 1px 0 #e6e6e6, 
                         0 2px 0 #d9d9d9,
                         0 3px 0 #ccc,
                         0 4px 0 #bfbfbf,
                         0 5px 0 #b3b3b3,
                         0 6px 1px rgba(0,0,0,.1),
                         0 0 5px rgba(0,0,0,.1),
                         0 1px 3px rgba(0,0,0,.2),
                         0 3px 5px rgba(0,0,0,.15);
        }
        
        .text-3d-orange {
            color: var(--orange1);
            text-shadow: 0 1px 0 #d33106, 
                         0 2px 0 #c22e06,
                         0 3px 0 #b02a05,
                         0 4px 0 #9e2605,
                         0 5px 0 #8c2104,
                         0 6px 1px rgba(0,0,0,.2),
                         0 0 5px rgba(0,0,0,.1),
                         0 1px 3px rgba(0,0,0,.3),
                         0 3px 5px rgba(0,0,0,.25),
                         0 5px 10px rgba(0,0,0,.3);
        }
        
        .text-3d-dark {
            color: var(--dark);
            text-shadow: 0 1px 0 #252548, 
                         0 2px 0 #212142,
                         0 3px 0 #1d1d3c,
                         0 4px 0 #191936,
                         0 5px 0 #151530,
                         0 6px 1px rgba(0,0,0,.2),
                         0 0 5px rgba(0,0,0,.1),
                         0 1px 3px rgba(0,0,0,.3),
                         0 3px 5px rgba(0,0,0,.25);
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: linear-gradient(135deg, var(--dark) 0%, #3d3d72 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            margin-bottom: 25px;
            border-radius: 10px;
            color: white;
        }
        
        .header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.15);
            border-radius: 25px;
            padding: 8px 18px;
            width: 300px;
            backdrop-filter: blur(5px);
        }
        
        .search-box input {
            border: none;
            background: transparent;
            padding: 8px;
            width: 100%;
            outline: none;
            color: white;
        }
        
        .search-box input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid var(--orange2);
            box-shadow: 0 0 10px rgba(255, 183, 102, 0.5);
        }
        
        .notification {
            position: relative;
            margin-right: 20px;
        }
        
        .notification i {
            font-size: 1.2rem;
            color: white;
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--orange1);
            color: white;
            font-size: 0.7rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-card {
            display: flex;
            align-items: center;
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.8rem;
            margin-right: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .artwork-icon {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .artist-icon {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .auction-icon {
            background: linear-gradient(135deg, var(--orange2) 0%, #ffca93 100%);
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .user-icon {
            background: linear-gradient(135deg, var(--bluegreen) 0%, #d6f5e8 100%);
            color: var(--dark);
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .section-title {
            margin: 30px 0 20px;
            color: var(--dark);
            padding-bottom: 12px;
            border-bottom: 2px solid var(--orange2);
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 3px rgba(0,0,0,0.1);
        }
        
        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .data-table th, .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
        }
        
        .data-table th {
            background: linear-gradient(135deg, var(--dark) 0%, #3d3d72 100%);
            color: white;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .data-table tr {
            transition: background-color 0.2s;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background-color: rgba(194, 237, 218, 0.2);
        }
        
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .published {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
        }
        
        .pending {
            background: linear-gradient(135deg, var(--orange2) 0%, #ffca93 100%);
            color: var(--dark);
        }
        
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .edit-btn {
            background: linear-gradient(135deg, var(--bluegreen) 0%, #d6f5e8 100%);
            color: var(--dark);
        }
        
        .delete-btn {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--light);
            transition: all 0.3s;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--orange2);
            box-shadow: 0 0 0 3px rgba(255, 183, 102, 0.3), inset 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #e03507 0%, #f55f2f 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            transition: all 0.3s;
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .tab.active {
            border-bottom: 3px solid var(--orange1);
            color: var(--orange1);
            text-shadow: 0 1px 2px rgba(244, 58, 9, 0.2);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .main-content.active {
                margin-left: 250px;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .search-box {
                width: 200px;
            }
        }
        
        @media (max-width: 576px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-box {
                width: 100%;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <h2 class="text-3d-light">Dashboard Overview</h2>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search...">
            </div>
            <div class="user-info">
               
                <img src="https://ui-avatars.com/api/?name=Admin+User&background=f43a09&color=fff" alt="Admin User">
                <span>Admin User</span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="dashboard-cards">
            <div class="card stat-card">
                <div class="card-icon artwork-icon">
                    <i class="fas fa-palette"></i>
                </div>
                <div>
                    <div class="number text-3d-dark"><?php echo $artwork_count;?></div>
                    <div class="label">Total Artworks</div>
                </div>
            </div>
            <div class="card stat-card">
                <div class="card-icon artist-icon">
                    <i class="fas fa-user-alt"></i>
                </div>
                <div>
                    <div class="number text-3d-dark"><?php echo $artist_count; ?></div>
                    <div class="label">Registered Artists</div>
                </div>
            </div>
            
            <div class="card stat-card">
                <div class="card-icon user-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="number text-3d-dark"><?php echo $user_count; ?></div>
                    <div class="label">Total Users</div>
                </div>
            </div>
        </div>

        <!-- Recent Artworks -->
        <h3 class="section-title text-3d-dark">Recent Artworks</h3>
        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Artist</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>ART001</td>
                        <td>Sunset Dreams</td>
                        <td>Taranjeet Kaur</td>
                        <td>Rs. 45,500.00</td>
                        <td><span class="status published">Published</span></td>
                        <td>
                            <button class="action-btn edit-btn"><i class="fas fa-edit"></i> Edit</button>
                            <button class="action-btn delete-btn"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>ART002</td>
                        <td>Mountain Serenity</td>
                        <td>Arpita Biswas</td>
                        <td>Rs. 75,000.00</td>
                        <td><span class="status published">Published</span></td>
                        <td>
                            <button class="action-btn edit-btn"><i class="fas fa-edit"></i> Edit</button>
                            <button class="action-btn delete-btn"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>ART003</td>
                        <td>Urban Life</td>
                        <td>Ananya Roy</td>
                        <td>Rs. 100,000.00</td>
                        <td><span class="status pending">Pending</span></td>
                        <td>
                            <button class="action-btn edit-btn"><i class="fas fa-edit"></i> Edit</button>
                            <button class="action-btn delete-btn"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

       

    <script>
        // Simple tab functionality for potential future expansion
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    tab.classList.add('active');
                    
                    // Show corresponding content
                    const contentId = tab.getAttribute('data-target');
                    document.getElementById(contentId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>