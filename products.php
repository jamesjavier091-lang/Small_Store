
<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] != "Admin") {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$color = "";

if (isset($_POST['add'])) {
    $product = trim($_POST['product']);
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    if ($product == "") {
        $message = "Product name is required!";
        $color = "danger";
    } elseif (!is_numeric($category) || $category <= 0) {
        $message = "Please select a valid category!";
        $color = "danger";
    } elseif ($price == "" || !is_numeric($price) || $price < 0) {
        $message = "Please enter a valid price!";
        $color = "danger";
    } elseif ($stock == "" || !ctype_digit((string)$stock)) {
        $message = "Please enter a valid stock quantity!";
        $color = "danger";
    } else {
        $check = mysqli_prepare($conn, "SELECT product_id FROM products WHERE product_name=?");
        mysqli_stmt_bind_param($check, "s", $product);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {
            $message = "Product already exists!";
            $color = "danger";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO products (product_name, category_id, price, stock) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sidi", $product, $category, $price, $stock);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Product added successfully!";
                $color = "success";
            } else {
                $message = "Product addition failed!";
                $color = "danger";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    if (!ctype_digit((string)$id) || $id <= 0) {
        $message = "Invalid product ID!";
        $color = "danger";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: products.php");
            exit();
        } else {
            $message = "Unable to delete product. It may already be used in a sale.";
            $color = "danger";
        }

        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $product = trim($_POST['product']);
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    if (!ctype_digit((string)$id) || $id <= 0) {
        $message = "Invalid product ID!";
        $color = "danger";
    } elseif ($product == "") {
        $message = "Product name is required!";
        $color = "danger";
    } elseif (!is_numeric($category) || $category <= 0) {
        $message = "Please select a valid category!";
        $color = "danger";
    } elseif ($price == "" || !is_numeric($price) || $price < 0) {
        $message = "Please enter a valid price!";
        $color = "danger";
    } elseif ($stock == "" || !ctype_digit((string)$stock)) {
        $message = "Please enter a valid stock quantity!";
        $color = "danger";
    } else {
        $check = mysqli_prepare($conn, "SELECT product_id FROM products WHERE product_name=? AND product_id!=?");
        mysqli_stmt_bind_param($check, "si", $product, $id);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {
            $message = "Another product already uses this name!";
            $color = "danger";
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE products SET product_name=?, category_id=?, price=?, stock=? WHERE product_id=?");
            mysqli_stmt_bind_param($stmt, "sidii", $product, $category, $price, $stock, $id);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Product updated successfully!";
                $color = "success";
            } else {
                $message = "Product update failed!";
                $color = "danger";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}

$products = mysqli_query($conn, "SELECT products.*, categories.category_name
    FROM products
    INNER JOIN categories ON products.category_id = categories.category_id
    ORDER BY products.product_name ASC");

$categoryList = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");

$editData = null;

if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];

    if (ctype_digit((string)$editId) && $editId > 0) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id=?");
        mysqli_stmt_bind_param($stmt, "i", $editId);
        mysqli_stmt_execute($stmt);
        $editResult = mysqli_stmt_get_result($stmt);
        $editData = mysqli_fetch_assoc($editResult);
        mysqli_stmt_close($stmt);
    }

    if (!$editData) {
        $message = "Product not found!";
        $color = "danger";
    }
}

$totalProducts = mysqli_num_rows($products);
$lowStockCount = 0;
$outOfStockCount = 0;

mysqli_data_seek($products, 0);

while ($item = mysqli_fetch_assoc($products)) {
    if ($item['stock'] <= 10) {
        $lowStockCount++;
    }

    if ($item['stock'] == 0) {
        $outOfStockCount++;
    }
}

mysqli_data_seek($products, 0);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Products - Small Store POS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            body {
                background: #e3f2fd;
                font-family: Arial, sans-serif;
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
                transition: 0.2s;
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
            .page-title {
                color: #172b4d;
                font-weight: bold;
            }
            .summary-card,
            .content-card {
                background: white;
                border: none;
                border-radius: 12px;
                box-shadow: 0 3px 12px rgba(0,0,0,0.05);
            }
            .summary-card {
                padding: 20px;
                height: 100%;
            }
            .summary-icon {
                width: 48px;
                height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                font-size: 23px;
            }
            .content-card .card-header {
                background: white;
                border-bottom: 1px solid #edf0f4;
                padding: 17px 20px;
                font-weight: bold;
                color: #172b4d;
            }
            .content-card .card-body {
                padding: 20px;
            }
            .form-control,
            .form-select {
                border-radius: 8px;
                padding: 10px 12px;
            }
            .btn {
                border-radius: 8px;
            }
            .table th {
                background: #0b1f3a;
                color: white;
                white-space: nowrap;
                font-weight: 500;
            }
            .table td {
                vertical-align: middle;
            }
            .stock-badge {
                display: inline-block;
                padding: 6px 9px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: bold;
            }
            @media (max-width: 767px) {
                .sidebar {
                    min-height: auto;
                }
                .main-content {
                    padding: 15px;
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
            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <i class="bi bi-person-circle ms-2"></i>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-2 p-0">
            <div class="sidebar">
                <a href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>

                <a href="categories.php">
                    <i class="bi bi-tags"></i> Categories
                </a>

                <a href="products.php" class="active">
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

                <a href="reports.php">
                    <i class="bi bi-bar-chart"></i> Reports
                </a>

                <a href="logout.php" class="text-warning mt-4">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-10 main-content">

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <div>
                    <h2 class="page-title mb-1">
                        <i class="bi bi-box-seam me-2"></i>Product Management
                    </h2>
                    <p class="text-secondary mb-0">Manage products, prices, categories, and stock.</p>
                </div>
            </div>

            <?php if ($message != "") { ?>
                <div class="alert alert-<?php echo $color; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php } ?>

            <!-- SUMMARY CARDS -->
            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="summary-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-secondary mb-1">Total Products</p>
                                <h3 class="fw-bold mb-0"><?php echo $totalProducts; ?></h3>
                            </div>
                            <div class="summary-icon bg-primary-subtle text-primary">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="summary-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-secondary mb-1">Low Stock (10 or below)</p>
                                <h3 class="fw-bold mb-0 text-warning"><?php echo $lowStockCount; ?></h3>
                            </div>
                            <div class="summary-icon bg-warning-subtle text-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="summary-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-secondary mb-1">Out of Stock</p>
                                <h3 class="fw-bold mb-0 text-danger"><?php echo $outOfStockCount; ?></h3>
                            </div>
                            <div class="summary-icon bg-danger-subtle text-danger">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ADD PRODUCT -->
            <div class="card content-card mb-4">
                <div class="card-header">
                    <i class="bi bi-plus-circle me-2"></i>Add New Product
                </div>

                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">

                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-semibold">Product Name</label>
                                <input type="text" name="product" class="form-control"
                                       placeholder="Enter product name" required>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-semibold">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>

                                    <?php
                                    mysqli_data_seek($categoryList, 0);
                                    while ($row = mysqli_fetch_assoc($categoryList)) {
                                    ?>
                                        <option value="<?php echo $row['category_id']; ?>">
                                            <?php echo htmlspecialchars($row['category_name']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-semibold">Price (₱)</label>
                                <input type="number" step="0.01" min="0" name="price"
                                       class="form-control" placeholder="0.00" required>
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <label class="form-label fw-semibold">Stock</label>
                                <input type="number" min="0" step="1" name="stock"
                                       class="form-control" placeholder="0" required>
                            </div>

                            <div class="col-lg-2 col-md-12 d-flex align-items-end">
                                <button type="submit" name="add" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Add Product
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <!-- PRODUCT LIST -->
            <div class="card content-card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-list-ul me-2"></i>Product List</span>
                    <span class="badge text-bg-primary"><?php echo $totalProducts; ?> Products</span>
                </div>

                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-lg-5 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" id="search" class="form-control"
                                       placeholder="Search product or category...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th style="min-width: 145px;">Action</th>
                                </tr>
                            </thead>

                            <tbody id="productTable">
                                <?php while ($row = mysqli_fetch_assoc($products)) { ?>
                                    <tr>
                                        <td><?php echo $row['product_id']; ?></td>

                                        <td class="fw-semibold">
                                            <?php echo htmlspecialchars($row['product_name']); ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?php echo htmlspecialchars($row['category_name']); ?>
                                            </span>
                                        </td>

                                        <td class="fw-semibold">
                                            ₱<?php echo number_format($row['price'], 2); ?>
                                        </td>

                                        <td>
                                            <?php if ($row['stock'] == 0) { ?>
                                                <span class="stock-badge bg-danger text-white">
                                                    Out of Stock
                                                </span>
                                            <?php } elseif ($row['stock'] <= 10) { ?>
                                                <span class="stock-badge bg-danger text-white">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    Low: <?php echo $row['stock']; ?>
                                                </span>
                                            <?php } elseif ($row['stock'] <= 20) { ?>
                                                <span class="stock-badge bg-warning text-dark">
                                                    <?php echo $row['stock']; ?> left
                                                </span>
                                            <?php } else { ?>
                                                <span class="stock-badge bg-success text-white">
                                                    <?php echo $row['stock']; ?> in stock
                                                </span>
                                            <?php } ?>
                                        </td>

                                        <td>
                                            <a href="?edit=<?php echo $row['product_id']; ?>"
                                               class="btn btn-warning btn-sm">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>

                                            <a href="?delete=<?php echo $row['product_id']; ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                        <p id="noResults" class="text-center text-secondary py-3 d-none">
                            <i class="bi bi-search me-1"></i>No matching products found.
                        </p>

                    </div>
                </div>
            </div>

            <!-- EDIT PRODUCT -->
            <?php if ($editData) { ?>
                <div class="card content-card mt-4" id="editProduct">
                    <div class="card-header">
                        <i class="bi bi-pencil-square me-2"></i>Edit Product
                    </div>

                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="id"
                                   value="<?php echo $editData['product_id']; ?>">

                            <div class="row g-3">

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label fw-semibold">Product Name</label>
                                    <input type="text" name="product" class="form-control"
                                           value="<?php echo htmlspecialchars($editData['product_name']); ?>"
                                           required>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label fw-semibold">Category</label>
                                    <select name="category" class="form-select" required>
                                        <?php
                                        mysqli_data_seek($categoryList, 0);
                                        while ($cat = mysqli_fetch_assoc($categoryList)) {
                                        ?>
                                            <option value="<?php echo $cat['category_id']; ?>"
                                                <?php echo ($cat['category_id'] == $editData['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['category_name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label fw-semibold">Price (₱)</label>
                                    <input type="number" step="0.01" min="0" name="price"
                                           class="form-control"
                                           value="<?php echo htmlspecialchars((string)$editData['price']); ?>"
                                           required>
                                </div>

                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label fw-semibold">Stock</label>
                                    <input type="number" min="0" step="1" name="stock"
                                           class="form-control"
                                           value="<?php echo $editData['stock']; ?>"
                                           required>
                                </div>

                                <div class="col-lg-2 col-md-12 d-flex align-items-end gap-2">
                                    <button type="submit" name="update" class="btn btn-success flex-grow-1">
                                        <i class="bi bi-check-lg me-1"></i>Update
                                    </button>

                                    <a href="products.php" class="btn btn-secondary">
                                        Cancel
                                    </a>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById("search").addEventListener("keyup", function () {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#productTable tr");
    let visibleCount = 0;

    rows.forEach(function (row) {
        let product = row.cells[1].innerText.toLowerCase();
        let category = row.cells[2].innerText.toLowerCase();

        if (product.includes(value) || category.includes(value)) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });

    document.getElementById("noResults").classList.toggle("d-none", visibleCount > 0);
});
</script>

</body>
</html>
