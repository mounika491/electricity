
<?php
session_start();
include "config.php";

// Check if user is admin or worker
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'worker')) {
    header("Location: login.php");
    exit;
}

$errors = [];
$bill_output = null;
$customer_info = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $name = sanitizeInput(trim($_POST['name']), $conn);
    $service_number = sanitizeInput(trim($_POST['service_number']), $conn);
    $phone = sanitizeInput(trim($_POST['phone']), $conn);
    $units = floatval($_POST['units']);
    $previous_due = floatval($_POST['prev_due'] ?? 0);
    
    // Validate inputs (Task 1 Requirements)
    $vName = validateName($name);
    $vPhone = validatePhone($phone);
    $vNumber = validateConsumerNumber($service_number, $conn);
    
    if ($vName !== true) $errors[] = $vName;
    if ($vPhone !== true) $errors[] = $vPhone;
    if ($vNumber !== true) $errors[] = $vNumber;
    
    // Validate units
    if ($units < 0) {
        $errors[] = "Error: Units consumed cannot be negative.";
    }
    
    // Validate previous due
    if ($previous_due < 0) {
        $errors[] = "Error: Previous due cannot be negative.";
    }
    
    // If no errors, calculate bill
    if (empty($errors)) {
        $customer_info = [
            'name' => $name,
            'service_number' => $service_number,
            'phone' => $phone,
            'units' => $units,
            'previous_due' => $previous_due
        ];
        
        $bill_output = calculateTotalBill($units, $previous_due);
        $bill_output['name'] = $name;
        $bill_output['service_number'] = $service_number;
        $bill_output['phone'] = $phone;
        $bill_output['bill_date'] = date('d-m-Y');
        $bill_output['due_date'] = date('d-m-Y', strtotime('+15 days'));
        
        // Save to database if confirmed
        if (isset($_POST['confirm_bill'])) {
            // Check if customer exists
            $check_customer = "SELECT id FROM customer WHERE number = '$service_number'";
            $customer_exists = mysqli_query($conn, $check_customer);
            
            if (mysqli_num_rows($customer_exists) == 0) {
                // Insert new customer
                $insert_customer = "INSERT INTO customer (number, name, phone, address, category, reg_date) 
                                   VALUES ('$service_number', '$name', '$phone', 'Task 1 Customer', 'household', CURDATE())";
                
                if (!mysqli_query($conn, $insert_customer)) {
                    $errors[] = "Error saving customer: " . mysqli_error($conn);
                }
            }
            
            if (empty($errors)) {
                // Insert reading
                $current_month = date('m');
                $current_year = date('Y');
                $estimated_reading = $units; // For Task 1, we use units directly
                
                $insert_reading = "INSERT INTO readings (number, month, year, reading, read_date) 
                                  VALUES ('$service_number', '$current_month', '$current_year', '$estimated_reading', CURDATE())";
                
                // Insert bill
                $bill_id = uniqid('TASK1_');
                $insert_bill = "INSERT INTO bill (number, month, year, units, amount, fine, prev_due, total, due_date, service_number, status) 
                               VALUES ('$service_number', '$current_month', '$current_year', 
                               '$units', '{$bill_output['current_charge']}', '{$bill_output['fine']}', 
                               '{$bill_output['previous_due']}', '{$bill_output['total_without_fine']}', 
                               '{$bill_output['due_date']}', '$bill_id', 'pending')";
                
                if (mysqli_query($conn, $insert_reading) && mysqli_query($conn, $insert_bill)) {
                    $saved_bill_id = mysqli_insert_id($conn);
                    
                    echo "<script>
                        alert('✅ Task 1 Bill generated successfully!\\nBill ID: $bill_id\\nAmount: ₹" . number_format($bill_output['total_without_fine'], 2) . "');
                        window.location.href = 'print_bill_task1.php?id=$saved_bill_id&task1=1';
                    </script>";
                } else {
                    $errors[] = "Error saving bill: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Task 1 Electricity Bill Generator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px 30px;
            text-align: center;
        }
        
        .header h1 {
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header .subtitle {
            color: #95a5a6;
            font-size: 14px;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .task1-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        h2, h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
            font-size: 14px;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn {
            background: #3498db;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-right: 10px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-success:hover {
            background: #219653;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #dc3545;
            margin-bottom: 20px;
        }
        
        .error-box ul {
            margin-left: 20px;
        }
        
        .success-box {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #28a745;
            margin-bottom: 20px;
        }
        
        .bill-preview {
            border: 2px solid #2c3e50;
            padding: 25px;
            margin: 20px 0;
            border-radius: 8px;
            background: #f8fdff;
        }
        
        .bill-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
        }
        
        .bill-header h2 {
            color: #2c3e50;
            border: none;
        }
        
        .bill-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .detail-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dotted #dee2e6;
        }
        
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        
        .detail-value {
            color: #212529;
        }
        
        .calculation-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .calculation-table th,
        .calculation-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        
        .calculation-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .total-row {
            background: #2c3e50;
            color: white;
            font-weight: bold;
        }
        
        .rate-info {
            background: #e8f4fc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        
        .rate-info h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .rate-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .rate-table th,
        .rate-table td {
            padding: 10px;
            border: 1px solid #cce5ff;
            text-align: center;
        }
        
        .rate-table th {
            background: #cce5ff;
            color: #004085;
        }
        
        .navigation {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚡ Electricity Bill System - Task 1</h1>
        <div class="subtitle">Software Engineering Lab - I.M. Tech VI – Jan – May 2026</div>
    </div>
    
    <div class="container">
        <div class="card">
            <span class="task1-badge">Task 1 Implementation</span>
            <h2>Generate Electricity Bill (Task 1 Requirements)</h2>
            
            <?php if(!empty($errors)): ?>
                <div class="error-box">
                    <strong>⚠️ Validation Errors:</strong>
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Rate Information -->
            <div class="rate-info">
                <h4>📊 Task 1 Slab Rates (Requirement 3.a):</h4>
                <table class="rate-table">
                    <thead>
                        <tr>
                            <th>Consumption Slab</th>
                            <th>Rate per Unit (₹)</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>First 50 units</td>
                            <td>1.50</td>
                            <td>For consumption up to 50 kWh</td>
                        </tr>
                        <tr>
                            <td>Next 50 units (51-100)</td>
                            <td>2.50</td>
                            <td>For consumption between 51-100 kWh</td>
                        </tr>
                        <tr>
                            <td>Next 50 units (101-150)</td>
                            <td>3.50</td>
                            <td>For consumption between 101-150 kWh</td>
                        </tr>
                        <tr>
                            <td>Above 150 units</td>
                            <td>4.50</td>
                            <td>For consumption above 150 kWh</td>
                        </tr>
                        <tr style="background: #fff3cd;">
                            <td colspan="3">
                                <strong>Note:</strong> Minimum charge of ₹25.00 applies if units consumed = 0 (Requirement 3.b)
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Input Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label>Consumer Name * (Requirement 1.d)</label>
                    <input type="text" name="name" 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                           placeholder="Enter full name (alphabets only)" required
                           pattern="[A-Za-z\s]+" title="Only alphabets and spaces allowed">
                    <small style="color: #666; font-size: 12px;">Only alphabets and spaces allowed. No numbers or special characters.</small>
                </div>
                
                <div class="form-group">
                    <label>Service/Consumer Number * (Requirement 1.c)</label>
                    <input type="number" name="service_number" 
                           value="<?php echo isset($_POST['service_number']) ? htmlspecialchars($_POST['service_number']) : ''; ?>" 
                           placeholder="Enter unique consumer number" required
                           min="1000" max="99999">
                    <small style="color: #666; font-size: 12px;">Must be unique number between 1000 and 99999</small>
                </div>
                
                <div class="form-group">
                    <label>Phone Number * (Requirement 1.e)</label>
                    <input type="tel" name="phone" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                           placeholder="Enter 10-digit phone number" required
                           pattern="[0-9]{10}" title="Exactly 10 digits required">
                    <small style="color: #666; font-size: 12px;">Exactly 10 digits required</small>
                </div>
                
                <div class="form-group">
                    <label>Units Consumed (kWh) *</label>
                    <input type="number" step="0.01" name="units" 
                           value="<?php echo isset($_POST['units']) ? htmlspecialchars($_POST['units']) : ''; ?>" 
                           placeholder="Enter units consumed" required min="0">
                </div>
                
                <div class="form-group">
                    <label>Previous Pending Due (if any)</label>
                    <input type="number" step="0.01" name="prev_due" 
                           value="<?php echo isset($_POST['prev_due']) ? htmlspecialchars($_POST['prev_due']) : '0'; ?>" 
                           placeholder="Enter previous due amount" min="0">
                </div>
                
                <button type="submit" class="btn btn-success">Calculate Bill</button>
                <button type="reset" class="btn btn-danger">Reset Form</button>
                <a href="generate_bill.php" class="btn">Switch to Regular Billing</a>
            </form>
        </div>
        
        <?php if($bill_output): ?>
        <div class="card">
            <div class="bill-preview">
                <!-- Bill Header -->
                <div class="bill-header">
                    <h2>ELECTRICITY BILL - TASK 1</h2>
                    <p><strong>Bill Date:</strong> <?php echo $bill_output['bill_date']; ?> | 
                    <strong>Due Date:</strong> <?php echo $bill_output['due_date']; ?></p>
                </div>
                
                <!-- Customer Information -->
                <h3>Consumer Information</h3>
                <div class="bill-details">
                    <div class="detail-box">
                        <div class="detail-row">
                            <span class="detail-label">Consumer Name:</span>
                            <span class="detail-value" style="text-transform: uppercase; font-weight: bold;">
                                <?php echo htmlspecialchars(strtoupper($bill_output['name'])); ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Service Number:</span>
                            <span class="detail-value"><?php echo $bill_output['service_number']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Phone Number:</span>
                            <span class="detail-value"><?php echo formatPhone($bill_output['phone']); ?></span>
                        </div>
                    </div>
                    
                    <div class="detail-box">
                        <div class="detail-row">
                            <span class="detail-label">Units Consumed:</span>
                            <span class="detail-value"><?php echo number_format($bill_output['units'], 2); ?> kWh</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Previous Pending Due:</span>
                            <span class="detail-value">₹<?php echo number_format($bill_output['previous_due'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Bill Status:</span>
                            <span class="detail-value" style="color: #dc3545; font-weight: bold;">PENDING</span>
                        </div>
                    </div>
                </div>
                
                <!-- Bill Calculation -->
                <h3>Bill Calculation (Requirement 3)</h3>
                <table class="calculation-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Calculation</th>
                            <th>Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Current Electricity Charges</td>
                            <td>
                                <?php 
                                if ($bill_output['units'] == 0) {
                                    echo "Minimum charge (0 units)";
                                } else {
                                    echo number_format($bill_output['units'], 2) . " kWh @ slab rates";
                                }
                                ?>
                            </td>
                            <td><?php echo number_format($bill_output['current_charge'], 2); ?></td>
                        </tr>
                        
                        <?php if($bill_output['previous_due'] > 0): ?>
                        <tr style="background: #f8f9fa;">
                            <td>Previous Pending Due</td>
                            <td>Carry forward from previous bill</td>
                            <td><?php echo number_format($bill_output['previous_due'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <tr class="total-row">
                            <td colspan="2"><strong>AMOUNT PAYABLE BY DUE DATE</strong></td>
                            <td><strong>₹<?php echo number_format($bill_output['total_without_fine'], 2); ?></strong></td>
                        </tr>
                        
                        <?php if($bill_output['previous_due'] > 0): ?>
                        <tr style="background: #f8d7da;">
                            <td>Late Payment Fine (Requirement 3.c)</td>
                            <td>Fixed fine for previous due</td>
                            <td style="color: #dc3545; font-weight: bold;">
                                + ₹<?php echo number_format($bill_output['fine'], 2); ?>
                            </td>
                        </tr>
                        
                        <tr class="total-row" style="background: #dc3545;">
                            <td colspan="2"><strong>AMOUNT PAYABLE AFTER DUE DATE (WITH FINE)</strong></td>
                            <td><strong>₹<?php echo number_format($bill_output['total_with_fine'], 2); ?></strong></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Important Notice -->
                <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #ffc107;">
                    <h4 style="color: #856404; margin-top: 0;">⚠️ IMPORTANT NOTICE (Requirement 4)</h4>
                    <p><strong>Pay before due date (<?php echo $bill_output['due_date']; ?>) to avoid fine of ₹150.00</strong></p>
                    <p>Payment after due date: ₹<?php echo number_format($bill_output['total_with_fine'], 2); ?></p>
                </div>
                
                <!-- Action Buttons -->
                <div style="text-align: center; margin-top: 30px;">
                    <form method="POST" action="">
                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($customer_info['name']); ?>">
                        <input type="hidden" name="service_number" value="<?php echo htmlspecialchars($customer_info['service_number']); ?>">
                        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($customer_info['phone']); ?>">
                        <input type="hidden" name="units" value="<?php echo htmlspecialchars($customer_info['units']); ?>">
                        <input type="hidden" name="prev_due" value="<?php echo htmlspecialchars($customer_info['previous_due']); ?>">
                        <input type="hidden" name="confirm_bill" value="1">
                        
                        <button type="submit" class="btn btn-success" style="padding: 15px 40px; font-size: 18px;">
                            ✅ Confirm & Save This Bill
                        </button>
                        <button type="button" class="btn" onclick="window.print()">🖨️ Print Bill</button>
                        <a href="generate_bill_task1.php" class="btn btn-danger">✗ Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Requirements Summary -->
        <div class="card">
            <h3>📋 Task 1 Requirements Summary</h3>
            <div style="color: #666; line-height: 1.8;">
                <p><strong>✅ Implemented Requirements:</strong></p>
                <ol style="margin-left: 20px; margin-bottom: 15px;">
                    <li><strong>Input Module:</strong>
                        <ul style="margin-left: 20px;">
                            <li>Accept required consumer details</li>
                            <li>Unique consumer/service number validation</li>
                            <li>Alphabets-only name validation</li>
                            <li>10-character phone number validation</li>
                        </ul>
                    </li>
                    <li><strong>Validation Module:</strong>
                        <ul style="margin-left: 20px;">
                            <li>Reject duplicate service numbers</li>
                            <li>Reject names with numbers/special characters</li>
                            <li>Reject invalid phone numbers</li>
                            <li>Proper error handling with re-prompting</li>
                        </ul>
                    </li>
                    <li><strong>Computation Module:</strong>
                        <ul style="margin-left: 20px;">
                            <li>Slab rate calculation (1.5, 2.5, 3.5, 4.5)</li>
                            <li>Minimum charge of ₹25 for 0 units</li>
                            <li>Fixed fine of ₹150 for previous dues</li>
                        </ul>
                    </li>
                    <li><strong>Output Module:</strong>
                        <ul style="margin-left: 20px;">
                            <li>Display consumer details</li>
                            <li>Show units consumed</li>
                            <li>Show previous pending dues</li>
                            <li>Show due dates with/without fine</li>
                        </ul>
                    </li>
                    <li><strong>Additional Requirements:</strong>
                        <ul style="margin-left: 20px;">
                            <li>Modular programming with functions</li>
                            <li>Proper error handling</li>
                            <li>Reusable modules</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="navigation">
            <a href="admin.php" class="btn">← Admin Dashboard</a>
            <a href="generate_bill.php" class="btn">Switch to Regular Billing</a>
            <a href="login.php" class="btn">Logout</a>
        </div>
    </div>
    
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                let valid = true;
                let errors = [];
                
                // Name validation
                const nameInput = document.querySelector('input[name="name"]');
                if (!/^[A-Za-z\s]+$/.test(nameInput.value.trim())) {
                    errors.push('Name must contain only alphabets and spaces');
                    nameInput.style.borderColor = '#dc3545';
                    valid = false;
                } else {
                    nameInput.style.borderColor = '#28a745';
                }
                
                // Phone validation
                const phoneInput = document.querySelector('input[name="phone"]');
                if (!/^\d{10}$/.test(phoneInput.value.trim())) {
                    errors.push('Phone number must be exactly 10 digits');
                    phoneInput.style.borderColor = '#dc3545';
                    valid = false;
                } else {
                    phoneInput.style.borderColor = '#28a745';
                }
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fix the following errors:\n\n' + errors.join('\n'));
                }
            });
            
            // Real-time validation
            document.querySelectorAll('input').forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.checkValidity()) {
                        this.style.borderColor = '#28a745';
                    } else {
                        this.style.borderColor = '#dc3545';
                    }
                });
            });
        });
    </script>
</body>
</html>