<?php
session_start();
include "config.php";

$cust_id = $_GET['customer'] ?? 0;
$bills_sql = "SELECT * FROM bill WHERE number = '$cust_id' ORDER BY year DESC, month DESC";
$bills_res = mysqli_query($conn, $bills_sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Customer Bills</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h3>Bills for Customer #<?php echo $cust_id; ?></h3>
        <table>
            <thead>
                <tr>
                    <th>Bill ID</th>
                    <th>Month/Year</th>
                    <th>Total</th>
                    <th>Remaining</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($bill = mysqli_fetch_assoc($bills_res)): ?>
                <tr>
                    <td>#<?php echo $bill['id']; ?></td>
                    <td><?php echo $bill['month']."/".$bill['year']; ?></td>
                    <td>₹<?php echo $bill['total']; ?></td>
                    <td style="color: red;">₹<?php echo $bill['remaining_due']; ?></td>
                    <td><span class="status-badge <?php echo $bill['status']; ?>"><?php echo $bill['status']; ?></span></td>
                    <td>
                        <?php if($bill['remaining_due'] > 0): ?>
                            <a href="make_payment.php?id=<?php echo $bill['id']; ?>" class="btn-sm">Pay Now</a>
                        <?php else: ?>
                            <span style="color: green;">✔ Paid</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="view_customers.php" class="btn" style="margin-top: 20px;">Back to Customers</a>
    </div>
</body>
</html>