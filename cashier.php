<?php
    session_start();
    include "config.php";

    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }

    $message = "";
    $color = "";

    if (isset($_POST['add'])) {
        $product = $_POST['product_id'] ?? "";
        $qty = $_POST['qty'] ?? "";

        if (!is_numeric($product) || !is_numeric($qty) || (int)$product <= 0 || (int)$qty <= 0) {
            $message = "Invalid product or quantity!";
            $color = "danger";
        } else {
            $product = (int)$product;
            $qty = (int)$qty;

            $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id=?");
            mysqli_stmt_bind_param($stmt, "i", $product);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $product_row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$product_row) {
                $message = "Product not found!";
                $color = "danger";
            } elseif ((int)$product_row['stock'] <= 0 || $qty > (int)$product_row['stock']) {
                $message = "Not enough stock! Available stock: " . $product_row['stock'];
                $color = "danger";
            } else {
                $stmt = mysqli_prepare($conn, "SELECT * FROM cart WHERE product_id=?");
                mysqli_stmt_bind_param($stmt, "i", $product);
                mysqli_stmt_execute($stmt);
                $check = mysqli_stmt_get_result($stmt);
                $cart_row = mysqli_fetch_assoc($check);
                mysqli_stmt_close($stmt);

                if ($cart_row) {
                    $new_qty = (int)$cart_row['quantity'] + $qty;

                    if ($new_qty > (int)$product_row['stock']) {
                        $message = "Not enough stock! Available stock: " . $product_row['stock'];
                        $color = "danger";
                    } else {
                        $stmt = mysqli_prepare($conn, "UPDATE cart SET quantity=? WHERE product_id=?");
                        mysqli_stmt_bind_param($stmt, "ii", $new_qty, $product);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        header("Location: cashier.php");
                        exit();
                    }
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO cart(product_id, quantity) VALUES(?, ?)");
                    mysqli_stmt_bind_param($stmt, "ii", $product, $qty);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    header("Location: cashier.php");
                    exit();
                }
            }
        }
    }

    if (isset($_GET['remove'])) {
        $id = $_GET['remove'];

        if (ctype_digit((string)$id) && (int)$id > 0) {
            $id = (int)$id;
            $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header("Location: cashier.php");
        exit();
    }

    if (isset($_POST['update'])) {
        $id = $_POST['cart_id'] ?? "";
        $qty = $_POST['quantity'] ?? "";

        if (!is_numeric($id) || !is_numeric($qty) || (int)$id <= 0 || (int)$qty <= 0) {
            $message = "Invalid cart quantity!";
            $color = "danger";
        } else {
            $id = (int)$id;
            $qty = (int)$qty;

            $stmt = mysqli_prepare($conn, "SELECT cart.product_id, products.stock FROM cart INNER JOIN products ON cart.product_id=products.product_id WHERE cart.cart_id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $cart_row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$cart_row) {
                $message = "Cart item not found!";
                $color = "danger";
            } elseif ($qty > (int)$cart_row['stock']) {
                $message = "Not enough stock! Available stock: " . $cart_row['stock'];
                $color = "danger";
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE cart SET quantity=? WHERE cart_id=?");
                mysqli_stmt_bind_param($stmt, "ii", $qty, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                header("Location: cashier.php");
                exit();
            }
        }
    }

    if (isset($_POST['complete'])) {
        $cash = $_POST['cash'] ?? "";

        if (!is_numeric($cash) || (float)$cash <= 0) {
            $message = "Invalid cash amount!";
            $color = "danger";
        } else {
            $cash = (float)$cash;
            $total_query = mysqli_query($conn, "SELECT COALESCE(SUM(products.price * cart.quantity),0) AS grand_total, COUNT(*) AS item_count FROM cart INNER JOIN products ON cart.product_id=products.product_id");

            if (!$total_query) {
                die("Cart Total Error: " . mysqli_error($conn));
            }

            $total_row = mysqli_fetch_assoc($total_query);
            $grand_total = (float)$total_row['grand_total'];
            $item_count = (int)$total_row['item_count'];

            if ($item_count <= 0) {
                $message = "Cart is empty!";
                $color = "danger";
            } elseif ($grand_total <= 0) {
                $message = "Invalid cart total!";
                $color = "danger";
            } else {
                $stock_error = "";
                $cart_check = mysqli_query($conn, "SELECT cart.product_id, cart.quantity, products.product_name, products.stock FROM cart INNER JOIN products ON cart.product_id=products.product_id");

                while ($row = mysqli_fetch_assoc($cart_check)) {
                    if ((int)$row['quantity'] > (int)$row['stock']) {
                        $stock_error = "Not enough stock for " . $row['product_name'] . "! Available stock: " . $row['stock'];
                        break;
                    }
                }

                if ($stock_error != "") {
                    $message = $stock_error;
                    $color = "danger";
                } elseif ($cash < $grand_total) {
                    $message = "Insufficient Cash!";
                    $color = "danger";
                } else {
                    $change = $cash - $grand_total;
                    $cashier = $_SESSION['username'];

                    $stmt = mysqli_prepare($conn, "INSERT INTO sales (sale_date, cashier, total, cash, change_amount) VALUES (NOW(), ?, ?, ?, ?)");
                    if (!$stmt) {
                        die("Sale Prepare Error: " . mysqli_error($conn));
                    }

                    mysqli_stmt_bind_param($stmt, "sddd", $cashier, $grand_total, $cash, $change);

                    if (!mysqli_stmt_execute($stmt)) {
                        die("Sale Insert Error: " . mysqli_stmt_error($stmt));
                    }

                    $sale_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);

                    $cart_items = mysqli_query($conn, "SELECT cart.product_id, cart.quantity, products.price, products.stock FROM cart INNER JOIN products ON cart.product_id=products.product_id");

                    while ($row = mysqli_fetch_assoc($cart_items)) {
                        $product_id = (int)$row['product_id'];
                        $quantity = (int)$row['quantity'];
                        $price = (float)$row['price'];
                        $subtotal = $price * $quantity;

                        $stmt = mysqli_prepare($conn, "INSERT INTO sale_items (sale_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
                        mysqli_stmt_bind_param($stmt, "iiidd", $sale_id, $product_id, $quantity, $price, $subtotal);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        $newstock = (int)$row['stock'] - $quantity;
                        $stmt = mysqli_prepare($conn, "UPDATE products SET stock=? WHERE product_id=?");
                        mysqli_stmt_bind_param($stmt, "ii", $newstock, $product_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }

                    mysqli_query($conn, "DELETE FROM cart");
                    header("Location: receipt.php?id=" . $sale_id);
                    exit();
                }
            }
        }
    }
    $products = mysqli_query($conn, "SELECT * FROM products ORDER BY product_name");
    $cart = mysqli_query($conn, "SELECT cart.*, products.product_name, products.price, products.stock FROM cart INNER JOIN products ON cart.product_id=products.product_id ORDER BY products.product_name");

    $cartItems = [];
    $grand = 0;

    while ($item = mysqli_fetch_assoc($cart)) {
        $item['line_total'] = (float)$item['price'] * (int)$item['quantity'];
        $grand += $item['line_total'];
        $cartItems[] = $item;
    }
    $productCount = mysqli_num_rows($products);
    $cartCount = count($cartItems);
    ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cashier - Small Store POS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            body { background: #e3f2fd; font-family: Arial, sans-serif; }
            .topbar { background: #0b1f3a; min-height: 65px; }
            .sidebar { background: #0b1f3a; min-height: calc(100vh - 65px); padding: 20px 12px; }
            .sidebar a { display: block; color: #dbe4f0; text-decoration: none; padding: 12px 15px; margin-bottom: 7px; border-radius: 8px; transition: .2s; }
            .sidebar a:hover, .sidebar a.active { background: #24466f; color: white; }
            .sidebar a i { margin-right: 9px; }
            .main-content { padding: 25px; }
            .page-title { color: #172b4d; font-weight: bold; }
            .summary-card, .content-card { background: white; border: none; border-radius: 12px; box-shadow: 0 3px 12px rgba(0,0,0,.05); }
            .summary-card { padding: 18px 20px; height: 100%; }
            .summary-icon { width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 22px; }
            .content-card .card-header { background: white; border-bottom: 1px solid #edf0f4; padding: 17px 20px; font-weight: bold; color: #172b4d; }
            .content-card .card-body { padding: 20px; }
            .form-control, .form-select { border-radius: 8px; padding: 9px 11px; }
            .btn { border-radius: 8px; }
            .table th { background: #0b1f3a; color: white; white-space: nowrap; font-weight: 500; }
            .table td { vertical-align: middle; }
            .table-product { min-width: 520px; }
            .table-cart { min-width: 580px; }
            .cash-input { height: 58px; font-size: 24px; font-weight: bold; }
            .grand-total { font-size: 28px; font-weight: bold; color: #198754; }
            @media (max-width: 767px) {
                .sidebar { min-height: auto; }
                .main-content { padding: 15px; }
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-dark topbar shadow-sm">
            <div class="container-fluid px-4">
                <a href="dashboard.php" class="navbar-brand fw-bold"><i class="bi bi-shop me-2"></i>Small Store POS</a>
                <div class="text-white">Welcome | <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> <i class="bi bi-person-circle ms-2"></i></div>
            </div>
        </nav>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-2 p-0">
                    <div class="sidebar">
                        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        <?php if ($_SESSION['role'] == "Admin") { ?>
                            <a href="categories.php"><i class="bi bi-tags"></i> Categories</a>
                            <a href="products.php"><i class="bi bi-box-seam"></i> Products</a>
                            <a href="users.php"><i class="bi bi-people"></i> Users</a>
                        <?php } ?>
                        <a href="cashier.php" class="active"><i class="bi bi-cart4"></i> Cashier</a>
                        <a href="sales.php"><i class="bi bi-receipt"></i> Sales</a>
                        <?php if ($_SESSION['role'] == "Admin") { ?>
                            <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
                        <?php } ?>
                        <a href="logout.php" class="text-warning mt-4"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
                <div class="col-md-10 main-content">
                    <div class="mb-4">
                        <h2 class="page-title mb-1"><i class="bi bi-cart-check me-2"></i>Cashier</h2>
                        <p class="text-secondary mb-0">Select products, manage the cart, and complete customer payments.</p>
                    </div>
                    <?php if ($message != "") { ?>
                        <div class="alert alert-<?php echo $color; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="summary-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div><p class="text-secondary mb-1">Available Products</p><h3 class="fw-bold mb-0"><?php echo $productCount; ?></h3></div>
                                    <div class="summary-icon bg-primary-subtle text-primary"><i class="bi bi-box-seam"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div><p class="text-secondary mb-1">Items in Cart</p><h3 class="fw-bold mb-0"><?php echo $cartCount; ?></h3></div>
                                    <div class="summary-icon bg-info-subtle text-info"><i class="bi bi-cart3"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div><p class="text-secondary mb-1">Current Total</p><h3 class="fw-bold mb-0 text-success">₱<?php echo number_format($grand, 2); ?></h3></div>
                                    <div class="summary-icon bg-success-subtle text-success"><i class="bi bi-cash-coin"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <!-- PRODUCT LIST -->
                        <div class="col-xl-6">
                            <div class="card content-card h-100">
                                <div class="card-header"><i class="bi bi-search me-2"></i>Search Products</div>
                                <div class="card-body">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                        <input type="text" id="search" class="form-control" placeholder="Search product name...">
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-hover table-product">
                                            <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th style="min-width:150px;">Quantity / Add</th></tr></thead>
                                            <tbody id="productTable">
                                            <?php while ($row = mysqli_fetch_assoc($products)) { ?>
                                                <tr>
                                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                                    <td>₱<?php echo number_format($row['price'], 2); ?></td>
                                                    <td>
                                                        <?php if ((int)$row['stock'] <= 0) { ?>
                                                            <span class="badge text-bg-danger">Out of Stock</span>
                                                        <?php } elseif ((int)$row['stock'] <= 10) { ?>
                                                            <span class="badge text-bg-danger"><?php echo (int)$row['stock']; ?> left</span>
                                                        <?php } else { ?>
                                                            <span class="badge text-bg-success"><?php echo (int)$row['stock']; ?></span>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" class="d-flex gap-2">
                                                            <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                                            <input type="number" name="qty" value="1" min="1" max="<?php echo (int)$row['stock']; ?>" class="form-control" style="width:75px;" required <?php echo ((int)$row['stock'] <= 0) ? 'disabled' : ''; ?>>
                                                            <button type="submit" name="add" class="btn btn-success btn-sm" <?php echo ((int)$row['stock'] <= 0) ? 'disabled' : ''; ?>>
                                                                <i class="bi bi-plus-lg"></i> Add
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                        <p id="noProducts" class="text-center text-secondary py-3 d-none">No matching products found.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card content-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-cart3 me-2"></i>Shopping Cart</span>
                                    <span class="badge text-bg-primary"><?php echo $cartCount; ?> items</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-cart">
                                            <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th>Action</th></tr></thead>
                                            <tbody>
                                            <?php if (count($cartItems) == 0) { ?>
                                                <tr><td colspan="5" class="text-center text-secondary py-4"><i class="bi bi-cart-x fs-3 d-block mb-2"></i>Your cart is empty.</td></tr>
                                            <?php } else { ?>
                                                <?php foreach ($cartItems as $item) { ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                                        <td>
                                                            <form method="POST" class="d-flex gap-1">
                                                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                                                <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="1" max="<?php echo (int)$item['stock']; ?>" class="form-control" style="width:75px;" required>
                                                        </td>
                                                        <td>₱<?php echo number_format($item['line_total'], 2); ?></td>
                                                        <td class="text-nowrap">
                                                                <button type="submit" name="update" class="btn btn-warning btn-sm" title="Update quantity"><i class="bi bi-check-lg"></i></button>
                                                                <a href="cashier.php?remove=<?php echo $item['cart_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this product?')" title="Remove item"><i class="bi bi-trash"></i></a>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="border rounded-3 p-3 bg-light mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-semibold text-secondary"><i class="bi bi-calculator me-2"></i>Grand Total</span>
                                            <span class="grand-total">₱<span id="grand"><?php echo number_format($grand, 2, '.', ''); ?></span></span>
                                        </div>
                                        <form method="POST" id="paymentForm">
                                            <label for="cash" class="form-label fw-bold"><i class="bi bi-cash me-1"></i>Cash Received (₱)</label>
                                            <input type="number" step="0.01" min="0.01" name="cash" id="cash" class="form-control cash-input" placeholder="0.00" required>

                                            <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                                                <span class="fw-semibold">Change</span>
                                                <span class="fs-4 fw-bold text-primary">₱<span id="change">0.00</span></span>
                                            </div>
                                            <button type="submit" name="complete" class="btn btn-success btn-lg w-100" <?php echo ($cartCount == 0) ? 'disabled' : ''; ?>>
                                                <i class="bi bi-check-circle me-2"></i>Complete Sale
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.getElementById("search").addEventListener("input", function () {
            let value = this.value.toLowerCase();
            let rows = document.querySelectorAll("#productTable tr");
            let visibleCount = 0;

            rows.forEach(function (row) {
                let product = row.cells[0].innerText.toLowerCase();

                if (product.includes(value)) {
                    row.style.display = "";
                    visibleCount++;
                } else {
                    row.style.display = "none";
                }
            });

            document.getElementById("noProducts").classList.toggle("d-none", visibleCount > 0);
        });

        let grand = <?php echo json_encode((float)$grand); ?>;

        document.getElementById("cash").addEventListener("input", function () {
            let cash = parseFloat(this.value) || 0;
            let change = cash - grand;

            if (change < 0) {
                change = 0;
            }

            document.getElementById("change").textContent = change.toFixed(2);
        });
        </script>
    </body>
</html>
