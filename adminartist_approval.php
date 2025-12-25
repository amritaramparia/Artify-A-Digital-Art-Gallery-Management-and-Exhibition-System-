<?php
session_start();

// Database connection using MySQLi
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'art';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve'])) {
        $artist_id = intval($_POST['artist_id']);
        $stmt = $conn->prepare("UPDATE artists SET approval_status = 'approved', updated_at = NOW() WHERE artist_id = ?");
        $stmt->bind_param("i", $artist_id);
        if ($stmt->execute()) {
            $message = "Artist application approved successfully!";
        } else {
            $message = "Error approving application: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['reject'])) {
        $artist_id = intval($_POST['artist_id']);
        $stmt = $conn->prepare("UPDATE artists SET approval_status = 'rejected', updated_at = NOW() WHERE artist_id = ?");
        $stmt->bind_param("i", $artist_id);
        if ($stmt->execute()) {
            $message = "Artist application rejected successfully!";
        } else {
            $message = "Error rejecting application: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all artist applications
$artists = [];
$result = $conn->query("SELECT * FROM artists ORDER BY created_at DESC");
if ($result) {
    $artists = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

// Get counts for statistics
$total_artists = 0;
$approved_artists = 0;
$pending_artists = 0;
$rejected_artists = 0;

$count_result = $conn->query("SELECT approval_status, COUNT(*) as count FROM artists GROUP BY approval_status");
if ($count_result) {
    while ($row = $count_result->fetch_assoc()) {
        $total_artists += $row['count'];
        switch ($row['approval_status']) {
            case 'approved':
                $approved_artists = $row['count'];
                break;
            case 'pending':
                $pending_artists = $row['count'];
                break;
            case 'rejected':
                $rejected_artists = $row['count'];
                break;
        }
    }
    $count_result->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Applications Management</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            background: linear-gradient(135deg, var(--dark) 0%, var(--orange1) 100%);
            color: white;
            padding: 30px 0;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        
        .success {
            background-color: var(--bluegreen);
            color: var(--dark);
            border: 1px solid var(--green);
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* New Layout for Stats and Chart */
        .stats-chart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .total-artists .stat-icon {
            background-color: rgba(44, 44, 84, 0.1);
            color: var(--dark);
        }
        
        .approved .stat-icon {
            background-color: rgba(104, 211, 136, 0.1);
            color: var(--green);
        }
        
        .pending .stat-icon {
            background-color: rgba(255, 183, 102, 0.1);
            color: var(--orange2);
        }
        
        .rejected .stat-icon {
            background-color: rgba(244, 58, 9, 0.1);
            color: var(--orange1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        
        /* Compact Chart Container */
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }
        
        .chart-title {
            text-align: center;
            margin-bottom: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .chart-wrapper {
            position: relative;
            height: 200px;
            width: 100%;
        }
        
        .chart-legend {
            display: flex;
            flex-direction: column;
            margin-top: 15px;
            gap: 8px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.8rem;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .legend-approved {
            background-color: var(--green);
        }
        
        .legend-pending {
            background-color: var(--orange2);
        }
        
        .legend-rejected {
            background-color: var(--orange1);
        }
        
        /* Applications Table */
        .applications-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--dark);
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: rgba(194, 237, 218, 0.2);
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background-color: var(--orange2);
            color: var(--dark);
        }
        
        .status-approved {
            background-color: var(--green);
            color: white;
        }
        
        .status-rejected {
            background-color: var(--orange1);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        
        .btn-approve {
            background-color: var(--green);
            color: white;
        }
        
        .btn-reject {
            background-color: var(--orange1);
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .main-content.active {
                margin-left: 250px;
            }
            
            .stats-chart-container {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="container">
            <header>
                <h1>Artist Applications Management</h1>
                <p class="subtitle">Review and manage artist registration requests</p>
            </header>

            <?php if (!empty($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Stats and Chart Section -->
            <div class="stats-chart-container">
                <!-- Left Side - Statistics Cards -->
                <div class="stats-container">
                    <div class="stat-card total-artists">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_artists; ?></div>
                        <div class="stat-label">Total Artists</div>
                    </div>
                    
                    <div class="stat-card approved">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $approved_artists; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $pending_artists; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    
                    <div class="stat-card rejected">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $rejected_artists; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>
                
                <!-- Right Side - Compact Chart -->
                <div class="chart-container">
                    <div class="chart-title">Application Status</div>
                    <div class="chart-wrapper">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color legend-approved"></div>
                            <span>Approved: <?php echo $approved_artists; ?></span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-pending"></div>
                            <span>Pending: <?php echo $pending_artists; ?></span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color legend-rejected"></div>
                            <span>Rejected: <?php echo $rejected_artists; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="applications-table">
                <table>
                    <thead>
                        <tr>
                            <th>Artist Name</th>
                            <th>Email</th>
                            <th>Art Style</th>
                            <th>Experience</th>
                            <th>Status</th>
                            <th>Applied On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($artists)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-palette" style="font-size: 3rem; color: var(--orange2); margin-bottom: 15px; display: block;"></i>
                                    <h3>No Artist Applications Found</h3>
                                    <p>There are currently no artist applications to review.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($artists as $artist): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-user-alt" style="margin-right: 8px; color: var(--orange1);"></i>
                                        <?php echo htmlspecialchars($artist['artist_name']); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-envelope" style="margin-right: 8px; color: var(--orange1);"></i>
                                        <?php echo htmlspecialchars($artist['email']); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-paint-brush" style="margin-right: 8px; color: var(--orange1);"></i>
                                        <?php echo htmlspecialchars($artist['art_style'] ?? 'Not specified'); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-chart-line" style="margin-right: 8px; color: var(--orange1);"></i>
                                        <?php echo ucfirst(htmlspecialchars($artist['experience_level'])); ?>
                                    </td>
                                    <td>
                                        <span class="status status-<?php echo htmlspecialchars($artist['approval_status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($artist['approval_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar-alt" style="margin-right: 8px; color: var(--orange1);"></i>
                                        <?php echo date('M j, Y', strtotime($artist['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($artist['approval_status'] == 'pending'): ?>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="artist_id" value="<?php echo $artist['artist_id']; ?>">
                                                    <button type="submit" name="approve" class="btn btn-approve">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="artist_id" value="<?php echo $artist['artist_id']; ?>">
                                                    <button type="submit" name="reject" class="btn btn-reject">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #6c757d; font-style: italic;">No actions available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Chart.js implementation for status distribution
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Approved', 'Pending', 'Rejected'],
                    datasets: [{
                        data: [
                            <?php echo $approved_artists; ?>,
                            <?php echo $pending_artists; ?>,
                            <?php echo $rejected_artists; ?>
                        ],
                        backgroundColor: [
                            '#68d388', // green
                            '#ffb766', // orange2
                            '#f43a09'  // orange1
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false // Hide default legend as we have a custom one
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.raw;
                                    return label;
                                }
                            }
                        }
                    },
                    cutout: '60%' // Makes it a doughnut chart with a hole in the center
                }
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>