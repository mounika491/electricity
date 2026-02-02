<?php
session_start();

// 1. INCLUDE CONFIG: This is the critical fix. 
// This loads the database connection ($conn) AND the validation functions.
include "config.php";

// 2. AUTH CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// REMOVE the manual mysqli_connect lines that were here!
// $conn is now provided by config.php

$success = "";
$error = "";

// Handle customer registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $number = mysqli_real_escape_string($conn, $_POST['number']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $category = $_POST['category'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $errors = [];
    
    // These functions now work because we included config.php above
    $res1 = validateConsumerNumber($number, $conn);
    if ($res1 !== true) $errors[] = $res1;
    
    $res2 = validateName($name);
    if ($res2 !== true) $errors[] = $res2;
    
    $res3 = validatePhone($phone);
    if ($res3 !== true) $errors[] = $res3;
    
    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            $sql1 = "INSERT INTO customer (number, name, phone, address, category, reg_date) 
                     VALUES ('$number', '$name', '$phone', '$address', '$category', CURDATE())";
            
            $sql2 = "INSERT INTO users (username, password, role, number) 
                     VALUES ('$username', '$password', 'customer', '$number')";
            
            if (mysqli_query($conn, $sql1) && mysqli_query($conn, $sql2)) {
                mysqli_commit($conn);
                $success = "Customer #$number added successfully!";
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Fetch customers
$sql = "SELECT * FROM customer ORDER BY reg_date DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="menu">
        <div class="container" style="background:none; box-shadow:none; margin:0;">
            <a href="admin.php"><strong>⚡ Admin Panel</strong></a>
            <a href="generate_bill.php">Generate Bill</a>
            <a href="view_bills.php">Manage Bills</a>
            <a href="logout.php" style="float:right">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Register New Customer</h2>
        
        <?php if($success) echo "<p style='color:green'>$success</p>"; ?>
        <?php if($error) echo "<p style='color:red'>$error</p>"; ?>

        <form method="POST">
            <input type="number" name="number" placeholder="Consumer Number" required>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <select name="category">
                <option value="household">Household</option>
                <option value="commercial">Commercial</option>
            </select>
            <textarea name="address" placeholder="Address" required></textarea>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register" class="btn">Add Customer</button>
        </form>

        <hr>

        <h2>Customer List</h2>
        <table>
            <tr>
                <th>No.</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td>#<?php echo $row['number']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo formatPhone($row['phone']); ?></td>
                <td>
                    <a href="edit_customer.php?id=<?php echo $row['number']; ?>" class="btn-sm">Edit</a>
                    <a href="delete_customer.php?id=<?php echo $row['number']; ?>" 
                       style="color:red; margin-left:10px;"
                       onclick="return confirm('Delete this customer and ALL their records?');">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>