
<?php
    session_start();
    include "config.php";

    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }

    $product = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
    $product_row = mysqli_fetch_assoc($product);
    $total_products = $product_row['total'];

    $sales = mysqli_query($conn, "SELECT COUNT(*) AS total FROM sales");
    $sales_row = mysqli_fetch_assoc($sales);
    $total_sales = $sales_row['total'];

    $income = mysqli_query($conn, "
        SELECT COALESCE(SUM(subtotal), 0) AS income
        FROM sale_items
    ");
    $income_row = mysqli_fetch_assoc($income);
    $total_income = $income_row['income'];

    $today_income = mysqli_query($conn, "
        SELECT COALESCE(SUM(sale_items.subtotal), 0) AS total
        FROM sales
        INNER JOIN sale_items
        ON sales.sale_id = sale_items.sale_id
        WHERE DATE(sales.sale_date) = CURDATE()
    ");
    $today_income_row = mysqli_fetch_assoc($today_income);

    $today = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM sales
        WHERE DATE(sale_date) = CURDATE()
    ");
    $today_sales = mysqli_fetch_assoc($today);

    $categories = mysqli_query($conn, "SELECT COUNT(*) AS total FROM categories");
    $category_row = mysqli_fetch_assoc($categories);

    $users = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
    $user_row = mysqli_fetch_assoc($users);

    $low = mysqli_query($conn, "
        SELECT product_name, stock
        FROM products
        WHERE stock <= 10
        ORDER BY stock ASC
    ");
    $low_stock = mysqli_num_rows($low);

    $recent_sales = mysqli_query($conn, "
        SELECT
            sales.sale_id,
            sales.sale_date,
            sales.cashier,
            COALESCE(SUM(sale_items.subtotal), 0) AS total
        FROM sales
        LEFT JOIN sale_items
        ON sales.sale_id = sale_items.sale_id
        GROUP BY sales.sale_id, sales.sale_date, sales.cashier
        ORDER BY sales.sale_date DESC
        LIMIT 5
    ");

    $best_products = mysqli_query($conn, "
        SELECT
            products.product_name,
            SUM(sale_items.quantity) AS total_qty
        FROM sale_items
        INNER JOIN products
        ON sale_items.product_id = products.product_id
        GROUP BY sale_items.product_id, products.product_name
        ORDER BY total_qty DESC
        LIMIT 5
    ");
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard - Small Store POS</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
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
                min-width: 0;
            }
            .page-subtitle {
                color: #77859a;
                font-size: 14px;
            }
            .dashboard-card {
                border: none;
                border-radius: 12px;
                box-shadow: 0 4px 14px rgba(20, 43, 73, 0.06);
                transition: .2s;
            }
            .dashboard-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(20, 43, 73, 0.12);
            }
            .dashboard-card .card-body {
                padding: 20px;
            }
            .dashboard-card h3 {
                font-size: 25px;
                font-weight: bold;
                color: #172e4f;
                overflow-wrap: anywhere;
            }
            .stat-icon {
                width: 50px;
                height: 50px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 23px;
                flex-shrink: 0;
            }
            .icon-blue {
                background: #2563eb;
            }
            .icon-green {
                background: #16a36a;
            }
            .icon-purple {
                background: #7545d6;
            }
            .icon-orange {
                background: #ed9418;
            }
            .content-card {
                background: white;
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
            .table {
                vertical-align: middle;
            }
            .table thead th {
                background: #edf3fa;
                color: #243b5a;
                font-size: 13px;
                white-space: nowrap;
            }
            .table td,
            .table th {
                padding: 12px;
            }
            @media (max-width: 767px) {
                .sidebar {
                    min-height: auto;
                }
                .main-content {
                    padding: 15px;
                }
                .dashboard-card .card-body {
                    padding: 15px;
                }
                .dashboard-card h3 {
                    font-size: 22px;
                }
                .stat-icon {
                    width: 42px;
                    height: 42px;
                    font-size: 20px;
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
                        <a href="dashboard.php" class="active">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <?php if ($_SESSION['role'] == "Admin") { ?>
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
                        <a href="sales.php">
                            <i class="bi bi-receipt"></i> Sales
                        </a>
                        <?php if ($_SESSION['role'] == "Admin") { ?>
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
                                <i class="bi bi-house-door-fill text-primary"></i>
                                Dashboard
                            </h2>
                            <p class="page-subtitle mb-0">
                                Welcome back! Here's what's happening in your store today.
                            </p>
                        </div>
                        <div class="text-secondary mt-2">
                            <i class="bi bi-calendar3"></i>
                            <?php echo date("F d, Y"); ?>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card dashboard-card h-100">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-2">Today's Sales</p>
                                        <h3>₱ <?php echo number_format($today_income_row['total'], 2); ?></h3>
                                        <small class="text-secondary">Sales amount today</small>
                                    </div>
                                    <div class="stat-icon icon-blue">
                                        <i class="bi bi-cash-stack"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card dashboard-card h-100">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-2">Number of Sales</p>
                                        <h3><?php echo $today_sales['total']; ?></h3>
                                        <small class="text-secondary">Transactions today</small>
                                    </div>
                                    <div class="stat-icon icon-green">
                                        <i class="bi bi-receipt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card dashboard-card h-100">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-2">Total Products</p>
                                        <h3><?php echo $total_products; ?></h3>
                                        <small class="text-secondary">Products recorded</small>
                                    </div>
                                    <div class="stat-icon icon-purple">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-3">
                            <div class="card dashboard-card h-100">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-2">Total Categories</p>
                                        <h3><?php echo $category_row['total']; ?></h3>
                                        <small class="text-secondary">Product categories</small>
                                    </div>
                                    <div class="stat-icon icon-orange">
                                        <i class="bi bi-tags"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <div class="card dashboard-card h-100">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-2">Total Income</p>
                                        <h3>₱ <?php echo number_format($total_income, 2); ?></h3>
                                        <small class="text-secondary">Income from recorded sales</small>
                                    </div>
                                    <div class="stat-icon icon-green">
                                        <i class="bi bi-wallet2"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card dashboard-card h-100">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-secondary mb-2">Low Stock Products</p>
                                        <h3><?php echo $low_stock; ?></h3>
                                        <small class="text-secondary">Products with stock of 10 or less</small>
                                    </div>
                                    <div class="stat-icon icon-orange">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-xl-7">
                            <div class="card content-card h-100">

                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-clock-history text-primary"></i>
                                        Recent Sales
                                    </span>
                                    <a href="sales.php" class="btn btn-sm btn-outline-primary">
                                        View All
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Receipt No.</th>
                                                    <th>Date & Time</th>
                                                    <th>Cashier</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($recent_sales) > 0) { ?>
                                                    <?php while ($row = mysqli_fetch_assoc($recent_sales)) { ?>
                                                        <tr>
                                                            <td>#<?php echo $row['sale_id']; ?></td>
                                                            <td>
                                                                <?php echo date("M d, Y h:i A", strtotime($row['sale_date'])); ?>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($row['cashier']); ?>
                                                            </td>
                                                            <td class="fw-bold">
                                                                ₱ <?php echo number_format($row['total'], 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-secondary py-4">
                                                            <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                                                            No sales records yet.
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col-12 col-xl-5">
                            <div class="card content-card h-100">
                                <div class="card-header">
                                    <i class="bi bi-trophy text-warning"></i>
                                    Best-Selling Products
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th class="text-end">Quantity Sold</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($best_products) > 0) { ?>
                                                    <?php while ($row = mysqli_fetch_assoc($best_products)) { ?>
                                                        <tr>
                                                            <td>
                                                                <?php echo htmlspecialchars($row['product_name']); ?>
                                                            </td>
                                                            <td class="text-end fw-bold">
                                                                <?php echo $row['total_qty']; ?>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="2" class="text-center text-secondary py-4">
                                                            <i class="bi bi-box-seam fs-2 d-block mb-2"></i>
                                                            No sales data yet.
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
                    <div class="card content-card mb-4">
                        <div class="card-header">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            Low Stock Alert
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Available Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($low) > 0) { ?>
                                            <?php mysqli_data_seek($low, 0); ?>
                                            <?php while ($row = mysqli_fetch_assoc($low)) { ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($row['product_name']); ?>
                                                    </td>
                                                    <td><?php echo $row['stock']; ?></td>
                                                    <td>
                                                        <span class="badge bg-danger">Low Stock</span>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-success py-3">
                                                    <i class="bi bi-check-circle"></i>
                                                    All products have sufficient stock.
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card content-card mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Quick Actions</h5>
                            <a href="cashier.php" class="btn btn-primary me-2 mb-2">
                                <i class="bi bi-cart4"></i> New Sale
                            </a>
                            <?php if ($_SESSION['role'] == "Admin") { ?>
                                <a href="products.php" class="btn btn-outline-primary me-2 mb-2">
                                    <i class="bi bi-box-seam"></i> Manage Products
                                </a>
                                <a href="categories.php" class="btn btn-outline-primary me-2 mb-2">
                                    <i class="bi bi-tags"></i> Manage Categories
                                </a>
                                <a href="reports.php" class="btn btn-outline-primary mb-2">
                                    <i class="bi bi-bar-chart"></i> View Reports
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
