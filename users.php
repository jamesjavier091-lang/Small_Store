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

if (isset($_POST['add'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($username == "") {
        $message = "Username is required!";
        $color = "danger";
    } elseif (trim($password) == "") {
        $message = "Password is required!";
        $color = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters!";
        $color = "danger";
    } elseif ($role != "Admin" && $role != "Cashier") {
        $message = "Please select a valid role!";
        $color = "danger";
    } else {
        $check = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users WHERE username=?"
        );
        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {
            $message = "Username already exists!";
            $color = "danger";
        } else {
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users(username,password,role) VALUES(?,?,?)"
            );
            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $username,
                $hashed_password,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "User Added Successfully!";
                $color = "success";
            } else {
                $message = "User Creation Failed!";
                $color = "danger";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    if (!ctype_digit((string)$id)) {
        $message = "Invalid user ID!";
        $color = "danger";
    } else {
        $check = mysqli_prepare(
            $conn,
            "SELECT username FROM users WHERE user_id=?"
        );
        mysqli_stmt_bind_param($check, "i", $id);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        $delete_user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($check);

        if (!$delete_user) {
            $message = "User not found!";
            $color = "danger";
        } elseif ($delete_user['username'] == $_SESSION['username']) {
            $message = "You cannot delete your own account while logged in!";
            $color = "danger";
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM users WHERE user_id=?"
            );
            mysqli_stmt_bind_param($stmt, "i", $id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: users.php");
                exit();
            } else {
                $message = "Unable to delete this user. The account may be linked to existing records.";
                $color = "danger";
                mysqli_stmt_close($stmt);
            }
        }
    }
}

if (isset($_POST['update'])) {
    $id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $role = $_POST['role'];

    if (!ctype_digit((string)$id)) {
        $message = "Invalid user ID!";
        $color = "danger";
    } elseif ($username == "") {
        $message = "Username is required!";
        $color = "danger";
    } elseif ($role != "Admin" && $role != "Cashier") {
        $message = "Please select a valid role!";
        $color = "danger";
    } else {
        $check = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users WHERE username=? AND user_id<>?"
        );
        mysqli_stmt_bind_param($check, "si", $username, $id);
        mysqli_stmt_execute($check);
        $duplicate = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($duplicate) > 0) {
            $message = "Username already exists!";
            $color = "danger";
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE users SET username=?, role=? WHERE user_id=?"
            );
            mysqli_stmt_bind_param(
                $stmt,
                "ssi",
                $username,
                $role,
                $id
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "User Updated Successfully!";
                $color = "success";

                if ($username == $_SESSION['username'] ||
                    (isset($_SESSION['user_id']) &&
                    (int)$id == (int)$_SESSION['user_id'])) {
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                }
            } else {
                $message = "User Update Failed!";
                $color = "danger";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}

$users = mysqli_query(
    $conn,
    "SELECT * FROM users ORDER BY user_id DESC"
);

$total_users = mysqli_num_rows($users);

$total_admins_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role='Admin'"
);
$total_admins = mysqli_fetch_assoc($total_admins_result)['total'];

$total_cashiers_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role='Cashier'"
);
$total_cashiers = mysqli_fetch_assoc($total_cashiers_result)['total'];
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Management - Small Store POS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="css/style.css">
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
                box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            }
            .summary-card {
                padding: 18px 20px;
                height: 100%;
            }
            .summary-icon {
                width: 46px;
                height: 46px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                font-size: 22px;
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
                padding: 9px 11px;
            }
            .btn {
                border-radius: 8px;
            }
            .table thead th {
                background: #0b1f3a;
                color: white;
                font-size: 13px;
                font-weight: 500;
                white-space: nowrap;
            }
            .table td {
                vertical-align: middle;
                padding-top: 13px;
                padding-bottom: 13px;
            }
            .role-admin {
                background: #e5efff;
                color: #164a91;
            }
            .role-cashier {
                background: #e0f7ec;
                color: #13734a;
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
                <a class="navbar-brand fw-bold" href="dashboard.php">
                    <i class="bi bi-shop-window me-2"></i>Small Store POS
                </a>
                <div class="text-white small">
                    Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    <i class="bi bi-person-circle ms-2"></i>
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
                        <a href="categories.php">
                            <i class="bi bi-tags"></i> Categories
                        </a>
                        <a href="products.php">
                            <i class="bi bi-box-seam"></i> Products
                        </a>
                        <a href="users.php" class="active">
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
                <div class="col-md-10 main-content">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h3 class="page-title mb-1">
                                <i class="bi bi-people me-2"></i>User Management
                            </h3>
                            <div class="text-secondary">
                                Manage administrator and cashier accounts.
                            </div>
                        </div>
                        <button class="btn btn-primary px-3" type="button" data-bs-toggle="collapse" data-bs-target="#addUserForm"> <i class="bi bi-person-plus me-1"></i> Add User</button>
                    </div>
                    <?php if (isset($message)) { ?>
                        <div class="alert alert-<?php echo htmlspecialchars($color); ?> alert-dismissible fade show">
                            <i class="bi bi-info-circle me-1"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>
                    <div class="row g-3 mb-4">
                        <div class="col-xl-4 col-sm-6">
                            <div class="summary-card d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-secondary small">Total Users</div>
                                    <h3 class="fw-bold mb-0">
                                        <?php echo $total_users; ?>
                                    </h3>
                                </div>
                                <div class="summary-icon" style="background:#eaf1fb;color:#17477d;">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-sm-6">
                            <div class="summary-card d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-secondary small">Administrators</div>
                                    <h3 class="fw-bold mb-0">
                                        <?php echo $total_admins; ?>
                                    </h3>
                                </div>
                                <div class="summary-icon" style="background:#e5efff;color:#164a91;">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-sm-6">
                            <div class="summary-card d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-secondary small">Cashiers</div>
                                    <h3 class="fw-bold mb-0">
                                        <?php echo $total_cashiers; ?>
                                    </h3>
                                </div>
                                <div class="summary-icon" style="background:#e0f7ec;color:#13734a;">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-card card mb-4 collapse show" id="addUserForm">
                        <div class="card-header">
                            <i class="bi bi-person-plus me-2"></i>Create New User
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label fw-semibold">Username</label>
                                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label fw-semibold">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="At least 6 characters" minlength="6" required>
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label class="form-label fw-semibold">Role</label>
                                    <select name="role" class="form-select" required>
                                        <option value="">Select role</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Cashier">Cashier</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6 d-flex align-items-end">
                                    <button type="submit" name="add" class="btn btn-primary w-100">
                                        <i class="bi bi-plus-circle me-1"></i>Create User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
                    if (isset($_GET['edit'])) {
                        $edit_id = $_GET['edit'];
                        $edit = null;

                        if (ctype_digit((string)$edit_id)) {
                            $edit_stmt = mysqli_prepare(
                                $conn,
                                "SELECT user_id, username, role FROM users WHERE user_id=?"
                            );

                            mysqli_stmt_bind_param($edit_stmt, "i", $edit_id);
                            mysqli_stmt_execute($edit_stmt);

                            $edit_result = mysqli_stmt_get_result($edit_stmt);
                            $edit = mysqli_fetch_assoc($edit_result);

                            mysqli_stmt_close($edit_stmt);
                        }

                        if ($edit) {
                    ?>
                        <div class="content-card card mb-4">
                            <div class="card-header">
                                <i class="bi bi-pencil-square me-2"></i>Edit User
                            </div>
                            <div class="card-body">
                                <form method="POST" class="row g-3">
                                    <input type="hidden" name="user_id" value="<?php echo (int)$edit['user_id']; ?>">
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold">Username</label>
                                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($edit['username']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Role</label>
                                        <select name="role" class="form-select" required>
                                            <option value="Admin"
                                                <?php echo $edit['role'] == "Admin" ? "selected" : ""; ?>>
                                                Admin
                                            </option>
                                            <option value="Cashier"
                                                <?php echo $edit['role'] == "Cashier" ? "selected" : ""; ?>>
                                                Cashier
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end gap-2">
                                        <button type="submit" name="update" class="btn btn-success">
                                            <i class="bi bi-check-circle me-1"></i>Save Changes
                                        </button>
                                        <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php
                        } else {
                            echo '<div class="alert alert-warning">The selected user was not found.</div>';
                        }
                    }
                    ?>
                    <div class="content-card card">
                        <div class="card-header">
                            <i class="bi bi-person-lines-fill me-2"></i>Registered Users
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                <div class="text-secondary small">
                                    View and manage all user accounts.
                                </div>
                                <div class="input-group" style="max-width:330px;">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" id="userSearch" class="form-control" placeholder="Search username or role...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="usersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($users)) { ?>
                                            <tr>
                                                <td class="text-secondary">
                                                    <?php echo (int)$row['user_id']; ?>
                                                </td>
                                                <td>
                                                    <i class="bi bi-person-circle text-secondary me-2"></i>
                                                    <strong>
                                                        <?php echo htmlspecialchars($row['username']); ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <?php if ($row['role'] == "Admin") { ?>
                                                        <span class="badge rounded-pill role-admin">
                                                            <i class="bi bi-shield-check me-1"></i>Admin
                                                        </span>
                                                    <?php } else { ?>
                                                        <span class="badge rounded-pill role-cashier">
                                                            <i class="bi bi-person-badge me-1"></i>Cashier
                                                        </span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <a
                                                        href="users.php?edit=<?php echo (int)$row['user_id']; ?>"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil me-1"></i>Edit
                                                    </a>
                                                    <?php if ($row['username'] != $_SESSION['username']) { ?>
                                                        <a
                                                            href="users.php?delete=<?php echo (int)$row['user_id']; ?>"
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this user?');">
                                                            <i class="bi bi-trash me-1"></i>Delete
                                                        </a>
                                                    <?php } else { ?>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            disabled>
                                                            <i class="bi bi-shield-lock me-1"></i>Current Account
                                                        </button>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <div id="noUsersFound" class="text-center text-secondary py-4 d-none">
                                    <i class="bi bi-search me-1"></i>No matching users found.
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.getElementById('userSearch').addEventListener('input', function () {
            const search = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            let visibleRows = 0;

            rows.forEach(function (row) {
                const match = row.textContent.toLowerCase().includes(search);

                row.style.display = match ? '' : 'none';

                if (match) {
                    visibleRows++;
                }
            });

            document.getElementById('noUsersFound').classList.toggle(
                'd-none',
                visibleRows !== 0
            );
        });
        </script>
    </body>
</html>