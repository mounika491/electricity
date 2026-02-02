<?php
session_start();
include "config.php";

// Check if user is admin or has permission
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'worker')) {
    header("Location: login.php");
    exit;
}

$bill_id = $_GET['id'] ?? 0;

// Get bill details with Task 1 specific information
$sql = "SELECT b.*, c.address, c.phone, c.category 
        FROM bill b 
        JOIN customer c ON b.number = c.number 
        WHERE b.id = '$bill_id' AND b.billing_type = 'task1'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Bill not found!'); window.location.href='admin.php';</script>";
    exit;
}

$bill = mysqli_fetch_assoc($result);

// Calculate breakdown for Task 1
$units = $bill['units'];
$breakdown = [];

if ($units == 0) {
    $breakdown[] = [
        'description' => 'Minimum charge (0 units)',
        'units' => 0,
        'rate' => 'N/A',
        'amount' => 25.00
    ];
} else {
    // Calculate slab-wise breakdown
    $remaining_units = $units;
    
    if ($remaining_units > 150) {
        $slab_units = $remaining_units - 150;
        $breakdown[] = [
            'description' => 'Above 150 units',
            'units' => $slab_units,
            'rate' => 4.50,
            'amount' => $slab_units * 4.50
        ];
        $remaining_units = 150;
    }
    
    if ($remaining_units > 100) {
        $slab_units = $remaining_units - 100;
        $breakdown[] = [
            'description' => '101-150 units',
            'units' => $slab_units,
            'rate' => 3.50,
            'amount' => $slab_units * 3.50
        ];
        $remaining_units = 100;
    }
    
    if ($remaining_units > 50) {
        $slab_units = $remaining_units - 50;
        $breakdown[] = [
            'description' => '51-100 units',
            'units' => $slab_units,
            'rate' => 2.50,
            'amount' => $slab_units * 2.50
        ];
        $remaining_units = 50;
    }
    
    if ($remaining_units > 0) {
        $breakdown[] = [
            'description' => 'First 50 units',
            'units' => $remaining_units,
            'rate' => 1.50,
            'amount' => $remaining_units * 1.50
        ];
    }
}

// Reverse for correct display order
$breakdown = array_reverse($breakdown);

// Get payment history
$payment_sql = "SELECT * FROM payments WHERE bill_id = '$bill_id' ORDER BY payment_date DESC";
$payment_result = mysqli_query($conn, $payment_sql);
$payments = [];
$total_paid = 0;

while ($payment = mysqli_fetch_assoc($payment_result)) {
    $payments[] = $payment;
    $total_paid += $payment['amount_paid'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Bill (Task 1) - #<?php echo $bill['id']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @media print {
            body {
                background: white !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            .container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .header {
                border-bottom: 2px solid #333 !important;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #17a2b8;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #17a2b8;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .bill-title {
            font-size: 24px;
            color: #333;
            text-transform: uppercase;
            margin-top: 10px;
        }
        
        .task1-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .bill-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .meta-item {
            text-align: center;
            flex: 1;
        }
        
        .meta-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .customer-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #17a2b8;
            border-radius: 5px;
            background: #f8fdff;
        }
        
        .info-item h4 {
            color: #17a2b8;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .info-item p {
            font-size: 16px;
            color: #333;
        }
        
        .bill-details {
            margin-bottom: 30px;
        }
        
        .details-header {
            background: #17a2b8;
            color: white;
            padding: 12px 15px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 5px 5px 0 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .amount-breakdown {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .breakdown-title {
            color: #856404;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .total-section {
            text-align: right;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }
        
        .total-row.final {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
            border-bottom: 2px solid #28a745;
        }
        
        .payment-info {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .payment-table th {
            background: #c3e6cb;
        }
        
        .due-amount {
            text-align: center;
            padding: 25px;
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .due-amount h3 {
            color: #721c24;
            margin-bottom: 10px;
        }
        
        .due-amount .amount {
            font-size: 36px;
            font-weight: bold;
            color: #dc3545;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        
        .notice {
            background: #e8f4fc;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        
        .print-buttons {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 0 10px;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn:hover {
            background: #138496;
        }
        
        .btn-print {
            background: #28a745;
        }
        
        .btn-print:hover {
            background: #218838;
        }
        
        .btn-back {
            background: #6c757d;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="print-buttons no-print">
        <button onclick="window.print()" class="btn btn-print">🖨️ Print Bill</button>
        <a href="admin.php" class="btn btn-back">← Back to Dashboard</a>
        <a href="generate_bill_task1.php" class="btn">Generate Another Bill</a>
    </div>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">ELECTRICITY BOARD</div>
            <div class="company-tagline">Government of India - Powering the Nation</div>
            <span class="task1-badge">Task 1 Billing System</span>
            <div class="bill-title">ELECTRICITY CONSUMPTION BILL</div>
        </div>
        
        <!-- Bill Meta Information -->
        <div class="bill-meta">
            <div class="meta-item">
                <div class="meta-label">Bill Number</div>
                <div class="meta-value">#<?php echo str_pad($bill['id'], 6, '0', STR_PAD_LEFT); ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Billing Period</div>
                <div class="meta-value">
                    <?php 
                    echo date('F', mktime(0,0,0,$bill['month'],1)) . ' ' . $bill['year'];
                    ?>
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Due Date</div>
                <div class="meta-value" style="color: <?php 
                    echo (strtotime($bill['due_date']) < time()) ? '#dc3545' : '#28a745';
                ?>;">
                    <?php echo date('d M Y', strtotime($bill['due_date'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="customer-info">
            <div class="info-item">
                <h4>Consumer Details</h4>
                <p style="font-size: 20px; font-weight: bold; text-transform: uppercase;">
                    <?php echo htmlspecialchars($bill['name']); ?>
                </p>
                <p><strong>Service No:</strong> <?php echo $bill['number']; ?></p>
                <p><strong>Category:</strong> <?php echo strtoupper($bill['category']); ?></p>
            </div>
            <div class="info-item">
                <h4>Contact Information</h4>
                <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($bill['address'])); ?></p>
                <p><strong>Phone:</strong> <?php echo formatPhone($bill['phone']); ?></p>
            </div>
        </div>
        
        <!-- Bill Details -->
        <div class="bill-details">
            <div class="details-header">Consumption Details</div>
            <table>
                <tr>
                    <td><strong>Meter Reading Date:</strong></td>
                    <td><?php echo date('d M Y', strtotime($bill['reading_date'] ?? date('Y-m-d'))); ?></td>
                    <td><strong>Bill Generation Date:</strong></td>
                    <td><?php echo date('d M Y', strtotime($bill['created_at'] ?? date('Y-m-d'))); ?></td>
                </tr>
                <tr>
                    <td><strong>Previous Reading:</strong></td>
                    <td><?php echo number_format($bill['previous_reading'] ?? 0); ?> kWh</td>
                    <td><strong>Current Reading:</strong></td>
                    <td><?php echo number_format(($bill['previous_reading'] ?? 0) + $bill['units']); ?> kWh</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Units Consumed:</strong></td>
                    <td colspan="2" style="font-size: 18px; font-weight: bold; color: #17a2b8;">
                        <?php echo number_format($bill['units']); ?> kWh
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Amount Breakdown -->
        <div class="amount-breakdown">
            <div class="breakdown-title">Task 1 - Slab Rate Calculation</div>
            <table>
                <thead>
                    <tr>
                        <th>Consumption Slab</th>
                        <th>Units</th>
                        <th>Rate (₹ per kWh)</th>
                        <th>Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    foreach ($breakdown as $item): 
                        $subtotal += $item['amount'];
                    ?>
                    <tr>
                        <td><?php echo $item['description']; ?></td>
                        <td><?php echo number_format($item['units'], 2); ?></td>
                        <td>₹<?php echo number_format($item['rate'], 2); ?></td>
                        <td>₹<?php echo number_format($item['amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- Fine if applicable -->
                    <?php if ($bill['fine_amount'] > 0): ?>
                    <tr style="background: #f8d7da;">
                        <td><strong>Late Payment Fine</strong></td>
                        <td>-</td>
                        <td>-</td>
                        <td style="color: #dc3545; font-weight: bold;">
                            ₹<?php echo number_format($bill['fine_amount'], 2); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Total Amount -->
        <div class="total-section">
            <div class="total-row">
                <span>Electricity Charges:</span>
                <span>₹<?php echo number_format($bill['billed_amount'], 2); ?></span>
            </div>
            
            <?php if ($bill['fine_amount'] > 0): ?>
            <div class="total-row">
                <span>Late Payment Fine:</span>
                <span style="color: #dc3545;">+ ₹<?php echo number_format($bill['fine_amount'], 2); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="total-row final">
                <span>TOTAL AMOUNT PAYABLE:</span>
                <span>₹<?php echo number_format($bill['total'], 2); ?></span>
            </div>
        </div>
        
        <!-- Payment Status -->
        <div class="due-amount">
            <h3>AMOUNT <?php echo ($bill['remaining_due'] > 0) ? 'DUE' : 'PAID'; ?></h3>
            <div class="amount">
                ₹<?php echo number_format($bill['remaining_due'] ?? $bill['total'], 2); ?>
            </div>
            <p style="margin-top: 10px; color: #666;">
                Status: <strong><?php echo strtoupper($bill['status']); ?></strong>
                <?php if ($bill['paid_amount'] > 0): ?>
                | Paid: ₹<?php echo number_format($bill['paid_amount'], 2); ?>
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Payment History (if any) -->
        <?php if (!empty($payments)): ?>
        <div class="payment-info">
            <h3 style="color: #155724; margin-bottom: 15px;">Payment History</h3>
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Payment Date</th>
                        <th>Amount Paid</th>
                        <th>Method</th>
                        <th>Transaction ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                        <td>₹<?php echo number_format($payment['amount_paid'], 2); ?></td>
                        <td><?php echo strtoupper($payment['payment_method']); ?></td>
                        <td><?php echo $payment['transaction_id'] ?: 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Important Notices -->
        <div class="notice">
            <h4 style="color: #17a2b8; margin-bottom: 10px;">IMPORTANT NOTICES:</h4>
            <ol style="margin-left: 20px; color: #666;">
                <li>This bill is generated using <strong>Task 1 slab rates</strong> as specified in requirements.</li>
                <li>Minimum charge of ₹25.00 applies for zero consumption.</li>
                <li>Late payment fine of ₹150.00 applies if previous dues exist.</li>
                <li>Payments can be made online, at authorized centers, or via bank transfer.</li>
                <li>Disconnection will occur if bill is not paid by due date.</li>
                <li>For complaints: Call 1912 or email eb-support@gov.in</li>
            </ol>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated bill. No signature required.</p>
            <p>Visit our website: www.electricityboard.gov.in | Helpline: 1912 (24x7)</p>
            <p style="margin-top: 10px; font-size: 12px; color: #999;">
                Bill Generated On: <?php echo date('d M Y h:i A'); ?> | 
                Generated By: <?php echo $_SESSION['username']; ?>
            </p>
        </div>
    </div>
    <div class="print-container">
        <center>
            <h1>Electricity Board</h1>
            <p>Official Billing Statement</p>
        </center>
        <hr>
        <table width="100%">
            <tr>
                <td><strong>Consumer Name:</strong> <?php echo $bill['name']; ?></td>
                <td><strong>Date:</strong> <?php echo date('d-m-Y'); ?></td>
            </tr>
            <tr>
                <td><strong>Service Number:</strong> <?php echo $bill['service_number']; ?></td>
                <td><strong>Units Consumed:</strong> <?php echo $bill['units']; ?></td>
            </tr>
        </table>
        <hr>
        <div class="computation-box">
            <p>Current Charges: ₹<?php echo $bill['amount']; ?></p>
            <p>Previous Pending: ₹<?php echo $bill['prev_due']; ?></p>
            <p>Fixed Minimum Charge: ₹25.00 (included if 0 units)</p>
        </div>
        <div class="final-total">
            <h4>Amount Payable by Due Date: ₹<?php echo $bill['total']; ?></h4>
            <h4 style="color:red;">Amount Payable after Due Date: ₹<?php echo $bill['total'] + 150; ?></h4>
        </div>
    </div>
    <script>
        // Auto-print if coming from generation
        <?php if (isset($_SESSION['new_bill_id']) && $_SESSION['new_bill_id'] == $bill_id): ?>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                    <?php unset($_SESSION['new_bill_id']); ?>
                }, 1000);
            };
        <?php endif; ?>
    </script>
</body>
</html>