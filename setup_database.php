
<?php
// Database setup for Electricity Bill System
$host = "localhost";
$user = "root";
$pass = "";
$db = "electricity";

$conn = mysqli_connect($host, $user, $pass);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $db";
if (mysqli_query($conn, $sql)) {
    echo "✅ Database '$db' created or already exists<br>";
} else {
    die("❌ Error creating database: " . mysqli_error($conn));
}

mysqli_select_db($conn, $db);

// Tables creation
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE,
        password VARCHAR(255),
        role ENUM('admin', 'worker', 'customer'),
        number INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "customer" => "CREATE TABLE IF NOT EXISTS customer (
        number INT PRIMARY KEY,
        name VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        category ENUM('household', 'commercial', 'industrial'),
        email VARCHAR(100),
        reg_date DATE
    )",
    
    "readings" => "CREATE TABLE IF NOT EXISTS readings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        number INT,
        month INT,
        year INT,
        reading DECIMAL(10,2),
        read_date DATE,
        UNIQUE KEY unique_reading (number, month, year)
    )",
    
    "bill" => "CREATE TABLE IF NOT EXISTS bill (
        id INT AUTO_INCREMENT PRIMARY KEY,
        number INT,
        month INT,
        year INT,
        units DECIMAL(10,2),
        amount DECIMAL(10,2),
        gst DECIMAL(10,2) DEFAULT 0,
        fine DECIMAL(10,2) DEFAULT 0,
        prev_due DECIMAL(10,2) DEFAULT 0,
        total DECIMAL(10,2),
        due_date DATE,
        service_number VARCHAR(50),
        status ENUM('pending', 'partially_paid', 'paid', 'overdue') DEFAULT 'pending',
        paid_amount DECIMAL(10,2) DEFAULT 0,
        remaining_due DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_payment_date DATE
    )",
    
    "payments" => "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bill_id INT,
        customer_number INT,
        amount_paid DECIMAL(10,2),
        payment_date DATE,
        payment_method VARCHAR(50),
        transaction_id VARCHAR(100),
        status ENUM('completed', 'cancelled', 'pending') DEFAULT 'completed'
    )",
    
    "minimum_charges" => "CREATE TABLE IF NOT EXISTS minimum_charges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(20),
        min_charge DECIMAL(10,2),
        effective_from DATE
    )"
];

foreach ($tables as $name => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ Table '$name' created successfully<br>";
    } else {
        echo "❌ Error creating table '$name': " . mysqli_error($conn) . "<br>";
    }
}

// Insert default data
$default_data = [
    "INSERT INTO users (username, password, role) VALUES 
    ('admin', 'admin123', 'admin'),
    ('worker', 'worker123', 'worker')",
    
    "INSERT INTO minimum_charges (category, min_charge, effective_from) VALUES 
    ('household', 50.00, '2020-01-01'),
    ('commercial', 100.00, '2020-01-01'),
    ('industrial', 200.00, '2020-01-01')"
];

foreach ($default_data as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ Default data inserted<br>";
    }
}

echo "<h2>✅ Database Setup Complete!</h2>";
echo "<p>The electricity billing system database has been set up successfully.</p>";
echo "<p><a href='login.php' style='display:inline-block; padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>Go to Login Page</a></p>";

mysqli_close($conn);
?>