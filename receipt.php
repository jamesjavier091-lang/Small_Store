
<?php
session_start();
include "config.php";

if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id']) || (int)$_GET['id'] <= 0) {
    header("Location: cashier.php");
    exit();
}

$id = (int)$_GET['id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM sales WHERE sale_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$sale = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($sale);
mysqli_stmt_close($stmt);

if (!$data) {
    header("Location: cashier.php");
    exit();
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT sale_items.*, products.product_name
     FROM sale_items
     INNER JOIN products ON sale_items.product_id = products.product_id
     WHERE sale_items.sale_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
$total = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
    $total += (float)$row['subtotal'];
}

mysqli_stmt_close($stmt);

$cash = (float)$data['cash'];
$change = $cash - $total;

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Receipt #<?php echo e($data['sale_id']); ?> | Small Store POS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <style>
            body {
                background: #e3f2fd;
                font-family: Arial, sans-serif;
                color: #333;
            }
            .topbar {
                background: #0b1f3a;
                color: white;
                padding: 18px 25px;
            }
            .receipt-wrapper {
                max-width: 750px;
                margin: 35px auto;
                padding: 0 15px;
            }
            .receipt {
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            }
            .receipt-header {
                background: #0b1f3a;
                color: white;
                text-align: center;
                padding: 30px 20px;
            }
            .receipt-header .store-icon {
                font-size: 38px;
                margin-bottom: 8px;
            }
            .receipt-header h2 {
                font-weight: bold;
                margin-bottom: 5px;
            }
            .receipt-header p {
                margin: 0;
                color: #dbe4f0;
            }
            .receipt-body {
                padding: 30px;
            }
            .receipt-info {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 18px;
                margin-bottom: 25px;
            }
            .info-label {
                color: #6c757d;
                font-size: 13px;
                margin-bottom: 4px;
            }
            .info-value {
                font-weight: bold;
                overflow-wrap: anywhere;
            }
            .table thead th {
                background: #f0f3f7;
                color: #0b1f3a;
                font-size: 13px;
                white-space: nowrap;
            }
            .table td {
                vertical-align: middle;
                font-size: 14px;
            }
            .amount-section {
                max-width: 330px;
                margin-left: auto;
                margin-top: 25px;
            }
            .amount-row {
                display: flex;
                justify-content: space-between;
                gap: 15px;
                padding: 7px 0;
            }
            .amount-total {
                border-top: 2px solid #0b1f3a;
                border-bottom: 1px solid #dee2e6;
                padding: 13px 0;
                margin-bottom: 8px;
                font-size: 21px;
                font-weight: bold;
                color: #0b1f3a;
            }
            .thank-you {
                text-align: center;
                border-top: 1px dashed #adb5bd;
                margin-top: 30px;
                padding-top: 22px;
                color: #555;
            }
            .thank-you i {
                font-size: 25px;
                color: #198754;
            }
            .action-buttons {
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-top: 25px;
                flex-wrap: wrap;
            }
            .btn-navy {
                background: #0b1f3a;
                color: white;
                border: none;
            }
            .btn-navy:hover {
                background: #24466f;
                color: white;
            }
            @media (max-width: 576px) {
                .receipt-wrapper {
                    margin: 20px auto;
                }
                .receipt-body {
                    padding: 18px;
                }
                .receipt-info {
                    gap: 12px;
                }
                .table td,
                .table th {
                    padding: 8px 5px;
                    font-size: 12px;
                }
                .amount-section {
                    max-width: 100%;
                }
            }
            @media print {
                @page {
                    margin: 10mm;
                }
                body {
                    background: white;
                    color: black;
                    font-size: 12px;
                }
                .topbar,
                .action-buttons {
                    display: none !important;
                }
                .receipt-wrapper {
                    max-width: 100%;
                    margin: 0;
                    padding: 0;
                }
                .receipt {
                    box-shadow: none;
                    border: 1px solid #ddd;
                    border-radius: 0;
                }
                .receipt-header {
                    background: white !important;
                    color: black !important;
                    border-bottom: 2px solid black;
                    padding: 18px;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .receipt-header p {
                    color: black !important;
                }
                .receipt-header .store-icon {
                    display: none;
                }
                .receipt-body {
                    padding: 18px;
                }
                .table thead th {
                    background: white !important;
                    color: black !important;
                }
                .thank-you i {
                    color: black !important;
                }
                tr {
                    page-break-inside: avoid;
                }
            }
        </style>
    </head>
    <body>
        <div class="topbar">
            <div class="container d-flex align-items-center">
                <i class="bi bi-shop me-2 fs-4"></i>
                <div>
                    <strong>Small Store POS</strong>
                    <div class="small text-light">Sales Receipt</div>
                </div>
            </div>
        </div>
        <div class="receipt-wrapper">
            <div class="receipt">
                <div class="receipt-header">
                    <div class="store-icon">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h2>Small Store POS</h2>
                    <p>Official Sales Receipt</p>
                    <p class="mt-2">Thank you for shopping with us!</p>
                </div>
                <div class="receipt-body">
                    <div class="receipt-info">
                        <div>
                            <div class="info-label">RECEIPT NUMBER</div>
                            <div class="info-value">
                                #<?php echo e($data['sale_id']); ?>
                            </div>
                        </div>
                        <div>
                            <div class="info-label">DATE AND TIME</div>
                            <div class="info-value">
                                <?php
                                echo e(date(
                                    'M d, Y h:i A',
                                    strtotime($data['sale_date'])
                                ));
                                ?>
                            </div>
                        </div>
                        <div>
                            <div class="info-label">CASHIER</div>
                            <div class="info-value">
                                <?php echo e($data['cashier']); ?>
                            </div>
                        </div>
                        <div>
                            <div class="info-label">PAYMENT METHOD</div>
                            <div class="info-value">Cash</div>
                        </div>
                    </div>
                    <hr>
                    <h5 class="fw-bold mb-3">Order Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($items) > 0) { ?>
                                    <?php foreach ($items as $row) { ?>
                                        <tr>
                                            <td><?php echo e($row['product_name']); ?></td>
                                            <td class="text-center">
                                                <?php echo e($row['quantity']); ?>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                ₱<?php echo number_format((float)$row['price'], 2); ?>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                ₱<?php echo number_format((float)$row['subtotal'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            No items found for this receipt.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="amount-section">
                        <div class="amount-row amount-total">
                            <span>Total</span>
                            <span>₱<?php echo number_format($total, 2); ?></span>
                        </div>

                        <div class="amount-row">
                            <span>Cash Received</span>
                            <strong>₱<?php echo number_format($cash, 2); ?></strong>
                        </div>

                        <div class="amount-row">
                            <span>Change</span>
                            <strong>₱<?php echo number_format($change, 2); ?></strong>
                        </div>
                    </div>
                    <div class="thank-you">
                        <i class="bi bi-check-circle-fill"></i>
                        <h5 class="fw-bold mt-2">Thank You!</h5>
                        <p class="mb-1">We appreciate your purchase.</p>
                        <p class="small mb-0">Please come again.</p>
                    </div>
                </div>
            </div>
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-navy px-4">
                    <i class="bi bi-printer me-1"></i> Print Receipt
                </button>
                <a href="cashier.php" class="btn btn-secondary px-4">
                    <i class="bi bi-arrow-left me-1"></i> Back to Cashier
                </a>
            </div>
        </div>
    </body>
</html>
