
<?php
session_start();
include "config.php";

// Check if user is worker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'worker') {
    header("Location: login.php");
    exit;
}

// Get statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT number) as total_customers,
    COUNT(*) as total_readings,
    MAX(read_date) as last_reading_date
    FROM readings";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent readings
$recent_sql = "SELECT r.*, c.name 
               FROM readings r 
               JOIN customer c ON r.number = c.number 
               ORDER BY r.read_date DESC 
               LIMIT 10";
$recent_result = mysqli_query($conn, $recent_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Worker Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: #3498db;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            padding: 5px 10px;
            border-radius: 3px;
        }
        
        .nav a:hover {
            background: #2980b9;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .card-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .card-label {
            color: #666;
            font-size: 14px;
        }
        
        h2, h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            flex: 1;
            min-width: 200px;
            padding: 20px;
            background: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            cursor: pointer;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .action-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">👷 Worker Dashboard</div>
        <div class="nav">
            <span>Welcome, <?php echo $_SESSION['username']; ?></span>
            <a href="worker.php">Dashboard</a>
            <a href="add_reading.php">Add Reading</a>
            <a href="generate_bill.php">Generate Bill</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Dashboard Stats -->
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-icon">👥</div>
                <div class="card-value"><?php echo $stats['total_customers'] ?? 0; ?></div>
                <div class="card-label">Total Customers</div>
            </div>
            
            <div class="card">
                <div class="card-icon">📊</div>
                <div class="card-value"><?php echo $stats['total_readings'] ?? 0; ?></div>
                <div class="card-label">Total Readings</div>
            </div>
            
            <div class="card">
                <div class="card-icon">📅</div>
                <div class="card-value">
                    <?php 
                    if($stats['last_reading_date']) {
                        echo date('d M', strtotime($stats['last_reading_date']));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
                <div class="card-label">Last Reading Date</div>
            </div>
            
            <div class="card">
                <div class="card-icon">⚡</div>
                <div class="card-value">Today</div>
                <div class="card-label"><?php echo date('d M Y'); ?></div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <h2>Quick Actions</h2>
        <div class="quick-actions">
            <button class="action-btn" onclick="window.location.href='add_reading.php'">
                <div class="action-icon">➕</div>
                <h3>Add New Reading</h3>
                <p>Record meter reading for a customer</p>
            </button>
            
            <button class="action-btn" onclick="window.location.href='generate_bill.php'">
                <div class="action-icon">🧾</div>
                <h3>Generate Bill</h3>
                <p>Create electricity bill for customer</p>
            </button>
            
        </div>
        
        <!-- Recent Readings -->
        <div class="card">
            <h3>Recent Meter Readings</h3>
            
            <?php if(mysqli_num_rows($recent_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Meter Number</th>
                            <th>Month/Year</th>
                            <th>Reading (kWh)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($recent_result)): 
                            $month_name = date('F', mktime(0,0,0,$row['month'],1));
                        ?>
                        <tr>
                            <td><?php echo $row['read_date']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['number']; ?></td>
                            <td><?php echo $month_name . ' ' . $row['year']; ?></td>
                            <td><?php echo number_format($row['reading'], 2); ?> kWh</td>
                            <td>
                                <a href="add_reading.php?number=<?php echo $row['number']; ?>" class="btn">Edit</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 20px;">No readings found. Start by adding a meter reading.</p>
            <?php endif; ?>
        </div>
        
        <!-- Worker Instructions -->
        <div class="card">
            <h3>Worker Instructions</h3>
            <div style="color: #666; line-height: 1.8;">
                <p><strong>Daily Tasks:</strong></p>
                <ol style="margin-left: 20px; margin-bottom: 15px;">
                    <li>Visit assigned customers for meter reading</li>
                    <li>Record readings in the system</li>
                    <li>Generate bills for completed readings</li>
                    <li>Verify customer information</li>
                </ol>
                
                <p><strong>Important Notes:</strong></p>
                <ul style="margin-left: 20px; margin-bottom: 15px;">
                    <li>Always verify meter number before recording reading</li>
                    <li>Check for abnormal consumption patterns</li>
                    <li>Report damaged meters immediately</li>
                    <li>Maintain customer confidentiality</li>
                </ul>
                
                <div style="background: #e8f4fc; padding: 15px; border-radius: 5px; margin-top: 15px;">
                    <strong>📞 Support:</strong> Contact supervisor for assistance<br>
                    <strong>🕒 Work Hours:</strong> 8:00 AM - 5:00 PM<br>
                    <strong>📧 Email:</strong> worker-support@electricityboard.com
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>