<?php
include "config.php";

echo "<h2>System Database Update</h2>";

// 1. Create Payment-related Tables
$tables = [
    "payments" => "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bill_id INT,
        customer_number INT,
        amount_paid DECIMAL(10,2),
        payment_date DATE,
        payment_method VARCHAR(50) DEFAULT 'cash',
        status VARCHAR(20) DEFAULT 'completed'
    )",
    "payment_history" => "CREATE TABLE IF NOT EXISTS payment_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bill_id INT,
        paid_amount DECIMAL(10,2),
        remaining_due DECIMAL(10,2),
        payment_date DATE
    )"
];

foreach ($tables as $name => $sql) {
    if (mysqli_query($conn, $sql)) echo "✅ Table '$name' ready.<br>";
}

// 2. SAFE ALTER: Add columns to 'bill' table individually
$new_columns = [
    'paid_amount' => "DECIMAL(10,2) DEFAULT 0",
    'remaining_due' => "DECIMAL(10,2) DEFAULT 0",
    'last_payment_date' => "DATE"
];

foreach ($new_columns as $col => $definition) {
    // Version-safe check: check if column exists before adding
    $check = mysqli_query($conn, "SHOW COLUMNS FROM bill LIKE '$col'");
    if (mysqli_num_rows($check) == 0) {
        if (mysqli_query($conn, "ALTER TABLE bill ADD $col $definition")) {
            echo "✅ Column '$col' added.<br>";
        }
    } else {
        echo "ℹ️ Column '$col' already exists.<br>";
    }
}

// 3. Sync existing data (If bill was unpaid, set remaining_due to total)
mysqli_query($conn, "UPDATE bill SET remaining_due = total WHERE remaining_due = 0 AND status = 'pending'");

echo "<h3>Update Complete! You can now use the Payment System.</h3>";
?>