
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

    $message = "";
    $color = "";

    if(isset($_POST['add'])){
        $category = trim($_POST['category']);

        if($category == ""){
            $message = "Category name is required!";
            $color = "danger";
        }
        elseif(strlen($category) < 2){
            $message = "Category name must be at least 2 characters!";
            $color = "danger";
        }
        else{
            $check = mysqli_prepare($conn, "SELECT category_id FROM categories WHERE category_name=?");
            mysqli_stmt_bind_param($check, "s", $category);
            mysqli_stmt_execute($check);
            $result = mysqli_stmt_get_result($check);

            if(mysqli_num_rows($result) > 0){
                $message = "Category already exists!";
                $color = "danger";
            }
            else{
                $stmt = mysqli_prepare($conn, "INSERT INTO categories(category_name) VALUES(?)");
                mysqli_stmt_bind_param($stmt, "s", $category);

                if(mysqli_stmt_execute($stmt)){
                    header("Location: categories.php?status=added");
                    exit();
                }
                else{
                    $message = "Category Addition Failed!";
                    $color = "danger";
                }

                mysqli_stmt_close($stmt);
            }

            mysqli_stmt_close($check);
        }
    }

    if(isset($_GET['status'])){
        if($_GET['status'] == "added"){
            $message = "Category Added Successfully!";
            $color = "success";
        }
        elseif($_GET['status'] == "updated"){
            $message = "Category Updated Successfully!";
            $color = "success";
        }
        elseif($_GET['status'] == "deleted"){
            $message = "Category Deleted Successfully!";
            $color = "success";
        }
    }

    if(isset($_POST['delete_category'])){
        $id = $_POST['id'];

        if(!is_numeric($id) || $id <= 0){
            $message = "Invalid category ID!";
            $color = "danger";
        }
        else{
            $stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE category_id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);

            if(mysqli_stmt_execute($stmt)){
                if(mysqli_stmt_affected_rows($stmt) > 0){
                    mysqli_stmt_close($stmt);
                    header("Location: categories.php?status=deleted");
                    exit();
                }
                else{
                    $message = "Category not found!";
                    $color = "danger";
                }
            }
            else{
                $message = "Cannot delete this category because it may be linked to existing products.";
                $color = "danger";
            }

            mysqli_stmt_close($stmt);
        }
    }

    if(isset($_POST['update'])){
        $id = $_POST['id'];
        $category = trim($_POST['category']);

        if(!is_numeric($id) || $id <= 0){
            $message = "Invalid category ID!";
            $color = "danger";
        }
        elseif($category == ""){
            $message = "Category name is required!";
            $color = "danger";
        }
        elseif(strlen($category) < 2){
            $message = "Category name must be at least 2 characters!";
            $color = "danger";
        }
        else{
            $check = mysqli_prepare(
                $conn,
                "SELECT category_id FROM categories WHERE category_name=? AND category_id!=?"
            );

            mysqli_stmt_bind_param($check, "si", $category, $id);
            mysqli_stmt_execute($check);
            $result = mysqli_stmt_get_result($check);

            if(mysqli_num_rows($result) > 0){
                $message = "Category already exists!";
                $color = "danger";
            }
            else{
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE categories SET category_name=? WHERE category_id=?"
                );

                mysqli_stmt_bind_param($stmt, "si", $category, $id);

                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_close($stmt);
                    mysqli_stmt_close($check);

                    header("Location: categories.php?status=updated");
                    exit();
                }
                else{
                    $message = "Category Update Failed!";
                    $color = "danger";
                }

                mysqli_stmt_close($stmt);
            }

            mysqli_stmt_close($check);
        }
    }

    $list = mysqli_query(
        $conn,
        "SELECT * FROM categories ORDER BY category_id DESC"
    );

    $data = null;

    if(isset($_GET['edit'])){
        $id = $_GET['edit'];

        if(is_numeric($id) && $id > 0){
            $stmt = mysqli_prepare(
                $conn,
                "SELECT * FROM categories WHERE category_id=?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $edit = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($edit);

            mysqli_stmt_close($stmt);
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Categories - Small Store POS</title>
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
            .category-icon {
                width: 42px;
                height: 42px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                background: #e8f0ff;
                color: #2563eb;
                font-size: 20px;
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
                        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        <a href="categories.php" class="active"><i class="bi bi-tags"></i> Categories</a>
                        <a href="products.php"><i class="bi bi-box-seam"></i> Products</a>
                        <a href="users.php"><i class="bi bi-people"></i> Users</a>
                        <a href="cashier.php"><i class="bi bi-cart4"></i> Cashier</a>
                        <a href="sales.php"><i class="bi bi-receipt"></i> Sales</a>
                        <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
                        <a href="logout.php" class="text-warning mt-4"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
                <div class="col-md-10 main-content">

                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                        <div>
                            <h2 class="fw-bold mb-1">
                                <i class="bi bi-tags text-primary"></i>
                                Category Management
                            </h2>
                            <p class="page-subtitle mb-0">
                                Organize and manage your store's product categories.
                            </p>
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
                        <div class="col-12 col-sm-6 col-xl-4">
                            <div class="card content-card">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="category-icon">
                                        <i class="bi bi-tags"></i>
                                    </div>
                                    <div>
                                        <p class="text-secondary mb-1">Total Categories</p>
                                        <h3 class="fw-bold mb-0">
                                            <?php echo mysqli_num_rows($list); ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card content-card mb-4">
                        <div class="card-header">
                            <i class="bi bi-plus-circle text-primary"></i>
                            Add New Category
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="row g-3 align-items-end">

                                    <div class="col-12 col-md-9">
                                        <label class="form-label fw-semibold">Category Name</label>

                                        <input type="text" name="category" class="form-control" placeholder="Enter category name" minlength="2" required>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <button type="submit" name="add" class="btn btn-primary w-100">
                                            <i class="bi bi-plus-lg"></i> Add Category
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card content-card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span><i class="bi bi-list-ul text-primary"></i> Category List</span>
                            <span class="badge text-bg-primary"> <?php echo mysqli_num_rows($list); ?> categories</span>
                        </div>
                        <div class="card-body">
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" id="search" class="form-control" placeholder="Search category name...">
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 100px;">ID</th>
                                            <th>Category Name</th>
                                            <th style="width: 190px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="categoryTable">
                                        <?php
                                        mysqli_data_seek($list, 0);

                                        while($row = mysqli_fetch_assoc($list)){
                                        ?>
                                            <tr>
                                                <td class="text-secondary">
                                                    #<?php echo (int)$row['category_id']; ?>
                                                </td>
                                                <td class="fw-semibold">
                                                    <i class="bi bi-tag text-primary me-2"></i>
                                                    <?php echo htmlspecialchars($row['category_name']); ?>
                                                </td>
                                                <td>
                                                    <a
                                                        href="?edit=<?php echo (int)$row['category_id']; ?>"
                                                        class="btn btn-warning btn-sm me-1"
                                                    >
                                                        <i class="bi bi-pencil-square"></i> Edit
                                                    </a>

                                                    <form
                                                        method="POST"
                                                        class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to delete this category?');"
                                                    >
                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?php echo (int)$row['category_id']; ?>"
                                                        >
                                                        <button
                                                            type="submit"
                                                            name="delete_category"
                                                            class="btn btn-danger btn-sm"
                                                        >
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                        <tr id="noResults" style="display:none;">
                                            <td colspan="3" class="text-center text-secondary py-4">
                                                No matching categories found.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php if(isset($_GET['edit'])){ ?>
                        <div class="card content-card mb-4" id="editCategory">
                            <div class="card-header">
                                <i class="bi bi-pencil-square text-primary"></i>
                                Edit Category
                            </div>
                            <div class="card-body p-4">
                                <?php if($data){ ?>
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?php echo (int)$data['category_id']; ?>">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-12 col-md-9">
                                                <label class="form-label fw-semibold">Category Name</label>
                                                <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($data['category_name']); ?>" minlength="2" required>
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <button type="submit" name="update" class="btn btn-success w-100">
                                                    <i class="bi bi-check-lg"></i> Update Category
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                <?php } else { ?>
                                    <div class="alert alert-warning mb-0">
                                        Category not found.
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.getElementById("search").addEventListener("keyup", function(){
            let value = this.value.toLowerCase();
            let rows = document.querySelectorAll("#categoryTable tr:not(#noResults)");
            let found = 0;

            rows.forEach(function(row){
                let category = row.cells[1].innerText.toLowerCase();

                if(category.includes(value)){
                    row.style.display = "";
                    found++;
                }
                else{
                    row.style.display = "none";
                }
            });

            document.getElementById("noResults").style.display =
                found === 0 ? "" : "none";
        });
        </script>
    </body>
</html>
