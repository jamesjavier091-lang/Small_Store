
<?php
session_start();
include "config.php";

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

if($_SESSION['role'] != "Admin"){
    header("Location: dashboard.php");
    exit();
}

$date = "";
$message = "";
$color = "";

if(isset($_POST['search'])){
    $date = trim($_POST['date'] ?? "");

    if($date == ""){
        $message = "Please select a date.";
        $color = "danger";
    }
    elseif(!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)){
        $message = "Invalid date format.";
        $color = "danger";
    }
    else{
        $parts = explode("-", $date);

        if(!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])){
            $message = "Please select a valid date.";
            $color = "danger";
        }
    }

    if($message == ""){
        $stmt = mysqli_prepare($conn, "
            SELECT sales.sale_id, sales.sale_date, sales.cashier,
            COALESCE(SUM(sale_items.subtotal),0) AS total
            FROM sales
            LEFT JOIN sale_items ON sales.sale_id = sale_items.sale_id
            WHERE DATE(sales.sale_date) = ?
            GROUP BY sales.sale_id, sales.sale_date, sales.cashier
            ORDER BY sales.sale_date DESC
        ");

        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        $daily = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
    else{
        $daily = mysqli_query($conn, "
            SELECT sales.sale_id, sales.sale_date, sales.cashier,
            COALESCE(SUM(sale_items.subtotal),0) AS total
            FROM sales
            LEFT JOIN sale_items ON sales.sale_id = sale_items.sale_id
            GROUP BY sales.sale_id, sales.sale_date, sales.cashier
            ORDER BY sales.sale_date DESC
        ");
    }
}
else{
    $daily = mysqli_query($conn, "
        SELECT sales.sale_id, sales.sale_date, sales.cashier,
        COALESCE(SUM(sale_items.subtotal),0) AS total
        FROM sales
        LEFT JOIN sale_items ON sales.sale_id = sale_items.sale_id
        GROUP BY sales.sale_id, sales.sale_date, sales.cashier
        ORDER BY sales.sale_date DESC
    ");
}

$monthly = mysqli_query($conn, "
    SELECT
        DATE_FORMAT(sales.sale_date, '%m') AS month,
        YEAR(sales.sale_date) AS year,
        COALESCE(SUM(sale_items.subtotal),0) AS income
    FROM sales
    LEFT JOIN sale_items ON sales.sale_id = sale_items.sale_id
    GROUP BY YEAR(sales.sale_date), MONTH(sales.sale_date)
    ORDER BY YEAR(sales.sale_date) DESC, MONTH(sales.sale_date) DESC
");

$best = mysqli_query($conn, "
    SELECT products.product_name,
    SUM(sale_items.quantity) AS total_qty
    FROM sale_items
    INNER JOIN products ON sale_items.product_id = products.product_id
    GROUP BY sale_items.product_id, products.product_name
    ORDER BY total_qty DESC
");

$low = mysqli_query($conn, "
    SELECT * FROM products
    WHERE stock <= 10
    ORDER BY stock ASC
");
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Reports - Small Store POS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="css/style.css">
        <style>
            body {
                background: #e3f2fd;
                font-family: Arial, sans-serif;
                color: #243247;
            }
            .topbar {
                background: #0b1f3a;
                min-height: 65px;
            }
            .sidebar {
                background: #0b1f3a;
                min-height: calc(100vh - 65px);
                padding: 20px 12px;
            }
            .sidebar a {
                display: block;
                color: #dbe4f0;
                text-decoration: none;
                padding: 12px 15px;
                margin-bottom: 7px;
                border-radius: 8px;
                transition: .2s;
            }
            .sidebar a:hover,
            .sidebar a.active {
                background: #24466f;
                color: white;
            }
            .sidebar a i {
                margin-right: 9px;
            }
            .main-content {
                padding: 25px;
            }
            .content-card {
                border: 1px solid #e7edf5;
                border-radius: 12px;
                box-shadow: 0 4px 14px rgba(20, 43, 73, 0.04);
                overflow: hidden;
            }
            .content-card .card-header {
                background: white;
                color: #172e4f;
                font-weight: bold;
                padding: 15px;
                border-bottom: 1px solid #edf1f6;
            }
            .form-control {
                border: 1px solid #d8e1ed;
                border-radius: 8px;
                padding: 10px 12px;
            }
            .form-control:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            }
            .table {
                vertical-align: middle;
                margin-bottom: 0;
            }
            .table thead th {
                background: #edf3fa;
                color: #243b5a;
                font-size: 13px;
                padding: 14px;
                white-space: nowrap;
            }
            .table tbody td {
                padding: 13px;
            }
            .table tbody tr:hover {
                background: #f5f9ff;
            }
            .page-subtitle {
                color: #77859a;
                font-size: 14px;
            }
            .report-icon {
                color: #2563eb;
                margin-right: 7px;
            }
            .btn {
                border-radius: 7px;
            }
            @media (max-width: 767px) {
                .sidebar {
                    min-height: auto;
                }
                .main-content {
                    padding: 15px 10px;
                }
            }
            @media print {
                .topbar,
                .sidebar,
                .no-print,
                .btn,
                form {
                    display: none !important;
                }
                body {
                    background: white !important;
                    color: black !important;
                }
                .col-md-10 {
                    width: 100% !important;
                }
                .main-content {
                    padding: 0 !important;
                }
                .content-card {
                    box-shadow: none !important;
                    border: 1px solid #ccc !important;
                    break-inside: avoid;
                    margin-bottom: 15px !important;
                }
                .table thead th {
                    background: #eee !important;
                    color: black !important;
                }
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-dark topbar shadow-sm no-print">
            <div class="container-fluid px-4">
                <a href="dashboard.php" class="navbar-brand fw-bold">
                    <i class="bi bi-shop me-2"></i>Small Store POS
                </a>
                <div class="text-white">
                    Welcome |
                    <b><?php echo htmlspecialchars($_SESSION['username']); ?></b>
                    <i class="bi bi-person-circle ms-1"></i>
                </div>
            </div>
        </nav>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-2 p-0 no-print">
                    <div class="sidebar">
                        <a href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="categories.php">
                            <i class="bi bi-tags"></i> Categories
                        </a>
                        <a href="products.php">
                            <i class="bi bi-box-seam"></i> Products
                        </a>
                        <a href="users.php">
                            <i class="bi bi-people"></i> Users
                        </a>
                        <a href="cashier.php">
                            <i class="bi bi-cart4"></i> Cashier
                        </a>
                        <a href="sales.php">
                            <i class="bi bi-receipt"></i> Sales
                        </a>
                        <a href="reports.php" class="active">
                            <i class="bi bi-bar-chart"></i> Reports
                        </a>
                        <a href="logout.php" class="text-warning mt-4">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
                <div class="col-md-10 main-content">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                        <div>
                            <h2 class="fw-bold mb-1">
                                <i class="bi bi-bar-chart text-primary"></i>
                                Reports
                            </h2>
                            <p class="page-subtitle mb-0">Review daily sales, monthly income, best-selling products, and low stock.</p>
                        </div>
                        <button onclick="window.print()" class="btn btn-primary no-print mt-2">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                    </div>
                    <?php if($message != ""){ ?>
                        <div class="alert alert-<?php echo $color; ?> alert-dismissible fade show no-print" role="alert">
                            <i class="bi bi-info-circle me-1"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>
                    <div class="card content-card mb-4">
                        <div class="card-header">
                            <i class="bi bi-calendar-day report-icon"></i>
                            Daily Sales
                        </div>
                        <div class="card-body">
                            <form method="POST" class="mb-3 no-print">
                                <div class="row g-2">
                                    <div class="col-12 col-md-9">
                                        <label class="form-label fw-semibold">Select Date</label>
                                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
                                    </div>
                                    <div class="col-6 col-md-2 d-flex align-items-end">
                                        <button type="submit" name="search" class="btn btn-success w-100">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                    <div class="col-6 col-md-1 d-flex align-items-end">
                                        <a href="reports.php" class="btn btn-outline-secondary w-100">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </a>
                                    </div>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Receipt</th>
                                            <th>Date</th>
                                            <th>Cashier</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($daily) > 0){ ?>
                                            <?php while($row = mysqli_fetch_assoc($daily)){ ?>
                                                <tr>
                                                    <td>
                                                        <i class="bi bi-receipt text-primary me-1"></i>
                                                        #<?php echo (int)$row['sale_id']; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($row['sale_date']); ?>
                                                    </td>
                                                    <td>
                                                        <i class="bi bi-person text-secondary me-1"></i>
                                                        <?php echo htmlspecialchars($row['cashier']); ?>
                                                    </td>
                                                    <td class="fw-bold">
                                                        ₱<?php echo number_format((float)$row['total'], 2); ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-secondary py-4">
                                                    No sales transactions found for this date.
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card content-card mb-4">
                        <div class="card-header">
                            <i class="bi bi-calendar-month report-icon"></i>
                            Monthly Sales
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Year</th>
                                            <th>Total Income</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($monthly) > 0){ ?>
                                            <?php while($row = mysqli_fetch_assoc($monthly)){ ?>
                                                <tr>
                                                    <td>
                                                        <?php
                                                        echo date("F", mktime(0, 0, 0, (int)$row['month'], 1));
                                                        ?>
                                                    </td>

                                                    <td><?php echo (int)$row['year']; ?></td>

                                                    <td class="fw-bold">
                                                        ₱<?php echo number_format((float)$row['income'], 2); ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-secondary py-4">
                                                    No monthly sales records found.
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card content-card mb-4">
                        <div class="card-header">
                            <i class="bi bi-trophy report-icon"></i>
                            Best-Selling Products
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Total Sold</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($best) > 0){ ?>
                                            <?php while($row = mysqli_fetch_assoc($best)){ ?>
                                                <tr>
                                                    <td>
                                                        <i class="bi bi-box-seam text-primary me-1"></i>
                                                        <?php echo htmlspecialchars($row['product_name']); ?>
                                                    </td>

                                                    <td class="fw-bold">
                                                        <?php echo (int)$row['total_qty']; ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="2" class="text-center text-secondary py-4">
                                                    No product sales found.
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card content-card mb-4">
                        <div class="card-header">
                            <i class="bi bi-exclamation-triangle report-icon"></i>
                            Low Stock Products
                            <span class="badge text-bg-danger ms-2">10 or less</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($low) > 0){ ?>
                                            <?php while($row = mysqli_fetch_assoc($low)){ ?>
                                                <tr>
                                                    <td>
                                                        <i class="bi bi-box text-primary me-1"></i>
                                                        <?php echo htmlspecialchars($row['product_name']); ?>
                                                    </td>

                                                    <td>
                                                        <span class="badge bg-danger">
                                                            <i class="bi bi-exclamation-circle"></i>
                                                            <?php echo (int)$row['stock']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="2" class="text-center text-secondary py-4">
                                                    No low-stock products found.
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
