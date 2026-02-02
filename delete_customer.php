<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Unauthorized access.");
}

$id = $_GET['id'];

if ($id > 0) {
    mysqli_begin_transaction($conn);

    try {
        // Order matters! Delete children first, then the parent.
        mysqli_query($conn, "DELETE FROM payments WHERE customer_number = '$id'");
        mysqli_query($conn, "DELETE FROM payment_history WHERE customer_number = '$id'");
        mysqli_query($conn, "DELETE FROM bill WHERE number = '$id'");
        mysqli_query($conn, "DELETE FROM readings WHERE number = '$id'");
        mysqli_query($conn, "DELETE FROM users WHERE number = '$id'");
        
        // Now we can safely delete the customer
        mysqli_query($conn, "DELETE FROM customer WHERE number = '$id'");

        mysqli_commit($conn);
        header("Location: admin.php?msg=Customer deleted");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "Error: " . $e->getMessage();
    }
}
?>