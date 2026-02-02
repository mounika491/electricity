<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$sql = "SELECT * FROM customer ORDER BY reg_date DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Customers</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>👤 Customer Directory</h2>
            <div>
                <a href="admin.php" class="btn">Dashboard</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Service No.</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['number']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td><span class="badge"><?php echo ucfirst($row['category']); ?></span></td>
                    <td><?php echo $row['phone']; ?></td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <a href="manage_bills.php?customer=<?php echo $row['number']; ?>" class="btn-sm btn-primary">Manage Bills</a>
                            <a href="edit_customer.php?id=<?php echo $row['number']; ?>" class="btn-sm">Edit</a>

                        </div>
                    </td>
                </tr>
                <tr>
                    <td><?php echo $row['number']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td><span class="badge"><?php echo ucfirst($row['category']); ?></span></td>
                    <td><?php echo $row['phone']; ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                        <a href="edit_customer.php?id=<?php echo $row['number']; ?>" class="btn-sm">Edit</a>
                        
                        <a href="delete_customer.php?id=<?php echo $row['number']; ?>" 
                        class="btn-sm" 
                        style="background: #e74c3c; color: white;"
                        onclick="return confirm('⚠️ This will delete ALL history and bills for this customer. Proceed?');">
                        Delete
                        </a>
                    </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>