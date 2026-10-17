
<?php
session_start();
include "config.php";

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

$message = "";
$color = "";

if(isset($_POST['search'])){
    $keyword = trim($_POST['keyword']);

    if($keyword == ""){
        $message = "Please enter a sale ID or date.";
        $color = "danger";

        $sales = mysqli_query($conn, "
            SELECT sales.sale_id, sales.sale_date, sales.cashier,
            COALESCE(SUM(sale_items.subtotal),0) AS total
            FROM sales
            LEFT JOIN sale_items ON sales.sale_id = sale_items.sale_id
            GROUP BY sales.sale_id, sales.sale_date, sales.cashier
            ORDER BY sales.sale_date DESC
        ");
    }
    else{
        $search_keyword = "%" . $keyword . "%";

        $stmt = mysqli_prepare($conn, "
            SELECT sales.sale_id, sales.sale_date, sales.cashier,
            COALESCE(SUM(sale_items.subtotal),0) AS total
            FROM sales
            LEFT JOIN sale_items ON sales.sale_id = sale_items.sale_id
            WHERE sales.sale_id LIKE ?
            OR DATE(sales.sale_date) = ?
            GROUP BY sales.sale_id, sales.sale_date, sales.cashier
            ORDER BY sales.sale_date DESC
        ");

        mysqli_stmt_bind_param($stmt, "ss", $search_keyword, $keyword);
        mysqli_stmt_execute($stmt);
        $sales = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
}
else{
    $sales = mysqli_query($conn, "
        SELECT sales.sale_id, sales.sale_date, sales.cashier,
        COALESCE(SUM(sale_items.subtotal),0) AS total
        FROM sales
        LEFT JOIN sale_items ON sales.sale_id = sale_items.sale_id
        GROUP BY sales.sale_id, sales.sale_date, sales.cashier
        ORDER BY sales.sale_date DESC
    ");
}

$total = mysqli_query($conn, "
    SELECT COALESCE(SUM(subtotal),0) AS total_sales
    FROM sale_items
");

$row = mysqli_fetch_assoc($total);
$grand_total = (float)$row['total_sales'];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sales History - Small Store POS</title>
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
            .summary-icon {
                width: 45px;
                height: 45px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                background: #e8f0ff;
                color: #2563eb;
                font-size: 22px;
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
        </style>
    </head>
    <body>
        <nav class="navbar navbar-dark topbar shadow-sm">
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
                <div class="col-md-2 p-0">
                    <div class="sidebar">
                        <a href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <?php if($_SESSION['role'] == "Admin"){ ?>
                            <a href="categories.php">
                                <i class="bi bi-tags"></i> Categories
                            </a>
                            <a href="products.php">
                                <i class="bi bi-box-seam"></i> Products
                            </a>
                            <a href="users.php">
                                <i class="bi bi-people"></i> Users
                            </a>
                        <?php } ?>
                        <a href="cashier.php">
                            <i class="bi bi-cart4"></i> Cashier
                        </a>
                        <a href="sales.php" class="active">
                            <i class="bi bi-receipt"></i> Sales
                        </a>
                        <?php if($_SESSION['role'] == "Admin"){ ?>
                            <a href="reports.php">
                                <i class="bi bi-bar-chart"></i> Reports
                            </a>
                        <?php } ?>
                        <a href="logout.php" class="text-warning mt-4">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
                <div class="col-md-10 main-content">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                        <div>
                            <h2 class="fw-bold mb-1">
                                <i class="bi bi-receipt text-primary"></i>
                                Sales History
                            </h2>
                            <p class="page-subtitle mb-0">View and search your store's sales transactions.</p>
                        </div>
                        <div class="text-secondary mt-2">
                            <i class="bi bi-calendar3"></i>
                            <?php echo date("F d, Y"); ?>
                        </div>
                    </div>
                    <?php if($message != ""){ ?>
                        <div class="alert alert-<?php echo $color; ?> alert-dismissible fade show" role="alert">
                            <i class="bi bi-info-circle me-1"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-lg-8">
                            <div class="card content-card h-100">
                                <div class="card-header">
                                    <i class="bi bi-search text-primary"></i>
                                    Search Sales
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="input-group">
                                            <input type="text" name="keyword" class="form-control" placeholder="Receipt No. or Date (YYYY-MM-DD)" value="<?php echo isset($_POST['search']) ? htmlspecialchars($_POST['keyword']) : ''; ?>">
                                            <button type="submit" name="search" class="btn btn-primary">
                                                <i class="bi bi-search"></i> Search
                                            </button>
                                            <a href="sales.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-arrow-clockwise"></i> Reset
                                            </a>
                                        </div>
                                    </form>
                                    <small class="text-secondary d-block mt-2">
                                        Search by receipt number or an exact date, such as 2026-10-18.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="card content-card h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="summary-icon">
                                        <i class="bi bi-cash-stack"></i>
                                    </div>

                                    <div>
                                        <p class="text-secondary mb-1">Total Sales</p>
                                        <h3 class="fw-bold mb-0">
                                            ₱<?php echo number_format($grand_total, 2); ?>
                                        </h3>
                                        <small class="text-secondary">All recorded sale items</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card content-card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span>
                                <i class="bi bi-table text-primary"></i>
                                Sales Transactions
                            </span>
                            <span class="badge text-bg-primary">
                                <?php echo mysqli_num_rows($sales); ?> transactions
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Receipt No.</th>
                                            <th>Date</th>
                                            <th>Cashier</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($sales) > 0){ ?>
                                            <?php while($row = mysqli_fetch_assoc($sales)){ ?>
                                                <tr>
                                                    <td class="fw-semibold">
                                                        <i class="bi bi-receipt text-primary me-1"></i>
                                                        #<?php echo (int)$row['sale_id']; ?>
                                                    </td>
                                                    <td>
                                                        <i class="bi bi-calendar3 text-secondary me-1"></i>
                                                        <?php echo htmlspecialchars($row['sale_date']); ?>
                                                    </td>
                                                    <td>
                                                        <i class="bi bi-person text-secondary me-1"></i>
                                                        <?php echo htmlspecialchars($row['cashier']); ?>
                                                    </td>
                                                    <td class="fw-bold">
                                                        ₱<?php echo number_format((float)$row['total'], 2); ?>
                                                    </td>
                                                    <td>
                                                        <a
                                                            href="receipt.php?id=<?php echo (int)$row['sale_id']; ?>"
                                                            class="btn btn-primary btn-sm"
                                                        >
                                                            <i class="bi bi-eye"></i> View Receipt
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-secondary py-4">
                                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                                    No sales transactions found.
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
