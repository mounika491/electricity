
<?php
// config.php - Database connection and core functions
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/se/electric/');
define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/se/electric/');

$host = "localhost";
$user = "root";
$pass = "";
$db = "electricity";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// MODULE: VALIDATION FUNCTIONS (Task 1: Section 2)
// ============================================

/**
 * Requirement 1.d & 2.b: Validate name (alphabets only)
 * @param string $name - Customer name
 * @return mixed - True if valid, error message if invalid
 */
function validateName($name) {
    if (empty(trim($name))) {
        return "Error: Name cannot be empty.";
    }
    
    // Alphabets and spaces only
    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        return "Error: Name must contain only alphabets and spaces. No numbers or special characters allowed.";
    }
    
    return true;
}

/**
 * Requirement 1.e & 2.c: Validate phone number (exactly 10 digits)
 * @param string $phone - Phone number
 * @return mixed - True if valid, error message if invalid
 */
function validatePhone($phone) {
    // Remove any non-numeric characters
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if exactly 10 digits
    if (strlen($cleaned) !== 10) {
        return "Error: Phone number must be exactly 10 digits.";
    }
    
    // Check if all digits are numeric
    if (!is_numeric($cleaned)) {
        return "Error: Phone number must contain only numbers.";
    }
    
    return true;
}

/**
 * Requirement 1.c & 2.a: Check if service number is unique
 * @param int $number - Service/Consumer number
 * @param mysqli $conn - Database connection
 * @return mixed - True if unique, error message if duplicate
 */
function validateConsumerNumber($number, $conn) {
    if (empty($number)) {
        return "Error: Consumer number cannot be empty.";
    }
    
    if ($number < 1000 || $number > 99999) {
        return "Error: Consumer number must be between 1000 and 99999.";
    }
    
    // Check if number already exists
    $sql = "SELECT number FROM customer WHERE number = '" . mysqli_real_escape_string($conn, $number) . "'";
    $res = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($res) > 0) {
        return "Error: Consumer number $number already exists. Please use a unique number.";
    }
    
    return true;
}

// ============================================
// MODULE: COMPUTATION (Task 1: Section 3)
// ============================================

/**
 * Requirement 3.a & 3.b: Calculate electricity bill using slab rates
 * @param float $units - Units consumed
 * @return float - Calculated bill amount
 */
function calculateTask1Bill($units) {
    $amount = 0;
    
    // Requirement 3.b: Zero units = Minimum charge of 25/-
    if ($units <= 0) {
        return 25.00;
    }

    // Requirement 3.a: Slab Rates
    if ($units <= 50) {
        $amount = $units * 1.5;
    } elseif ($units <= 100) {
        $amount = (50 * 1.5) + (($units - 50) * 2.5);
    } elseif ($units <= 150) {
        $amount = (50 * 1.5) + (50 * 2.5) + (($units - 100) * 3.5);
    } else {
        $amount = (50 * 1.5) + (50 * 2.5) + (50 * 3.5) + (($units - 150) * 4.5);
    }
    
    return round($amount, 2);
}

/**
 * Requirement 3.c: Calculate fine amount
 * @param float $previous_due - Previous pending amount
 * @return float - Fine amount (150 if previous due > 0)
 */
function calculateFine($previous_due) {
    return ($previous_due > 0) ? 150.00 : 0.00;
}

/**
 * Calculate total payable amount
 * @param float $units - Current units consumed
 * @param float $previous_due - Previous pending amount
 * @return array - Various calculated amounts
 */
function calculateTotalBill($units, $previous_due = 0) {
    $current_charge = calculateTask1Bill($units);
    $fine = calculateFine($previous_due);
    $total_without_fine = $current_charge + $previous_due;
    $total_with_fine = $total_without_fine + $fine;
    
    return [
        'units' => $units,
        'current_charge' => $current_charge,
        'previous_due' => $previous_due,
        'fine' => $fine,
        'total_without_fine' => $total_without_fine,
        'total_with_fine' => $total_with_fine,
        'due_date' => date('Y-m-d', strtotime('+15 days'))
    ];
}

// ============================================
// MODULE: OUTPUT FORMATTING
// ============================================

/**
 * Format phone number for display
 * @param string $phone - Raw phone number
 * @return string - Formatted phone number
 */
function formatPhone($phone) {
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($cleaned) == 10) {
        return "(" . substr($cleaned, 0, 3) . ") " . 
               substr($cleaned, 3, 3) . "-" . 
               substr($cleaned, 6);
    }
    
    return $phone;
}

/**
 * Format currency for display
 * @param float $amount - Amount to format
 * @return string - Formatted currency
 */
function formatCurrency($amount) {
    return "₹" . number_format($amount, 2);
}

/**
 * Get month name from number
 * @param int $month - Month number (1-12)
 * @return string - Month name
 */
function getMonthName($month) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 
        4 => 'April', 5 => 'May', 6 => 'June', 
        7 => 'July', 8 => 'August', 9 => 'September', 
        10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    return $months[$month] ?? 'Unknown';
}

// ============================================
// MODULE: DATABASE UTILITIES
// ============================================

/**
 * Safe database query execution
 * @param mysqli $conn - Database connection
 * @param string $sql - SQL query
 * @return mysqli_result|bool - Query result
 */
function executeQuery($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        error_log("Database Error: " . mysqli_error($conn) . " - Query: " . $sql);
        return false;
    }
    
    return $result;
}

/**
 * Sanitize user input
 * @param string $input - Raw user input
 * @param mysqli $conn - Database connection
 * @return string - Sanitized input
 */
function sanitizeInput($input, $conn) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    $input = mysqli_real_escape_string($conn, $input);
    
    return $input;
}
?>