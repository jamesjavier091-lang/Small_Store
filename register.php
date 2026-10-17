
<?php
include "config.php";

if(isset($_POST['save'])){
    $username=$_POST['username'];
    $password=$_POST['password'];
    $role=$_POST['role'];

    if(trim($username)==""){
        $message="Username is required!";
        $color="danger";
    }elseif(trim($password)==""){
        $message="Password is required!";
        $color="danger";
    }elseif(strlen($password)<6){
        $message="Password must be at least 6 characters!";
        $color="danger";
    }elseif($role!="Admin" && $role!="Cashier"){
        $message="Please select a valid role!";
        $color="danger";
    }else{
        $check=mysqli_prepare($conn,"SELECT * FROM users WHERE username=?");
        mysqli_stmt_bind_param($check,"s",$username);
        mysqli_stmt_execute($check);
        $result=mysqli_stmt_get_result($check);

        if(mysqli_num_rows($result)>0){
            $message="Username already exists!";
            $color="danger";
        }else{
            $hashed_password=password_hash($password,PASSWORD_DEFAULT);

            $stmt=mysqli_prepare($conn,"INSERT INTO users(username,password,role) VALUES(?,?,?)");
            mysqli_stmt_bind_param($stmt,"sss",$username,$hashed_password,$role);

            if(mysqli_stmt_execute($stmt)){
                $message="User Created Successfully!";
                $color="success";
            }else{
                $message="User Creation Failed!";
                $color="danger";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Create User - Small Store POS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="css/style.css">
        <style>
            body {
                background: #f4f6f9;
                font-family: Arial, sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .register-wrapper {
                width: 100%;
                max-width: 450px;
            }
            .register-card {
                background: white;
                border: none;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 10px 35px rgba(11, 31, 58, 0.12);
            }
            .register-header {
                background: #0b1f3a;
                color: white;
                text-align: center;
                padding: 30px 25px;
            }
            .store-icon {
                width: 70px;
                height: 70px;
                background: #24466f;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 15px;
                font-size: 32px;
            }
            .register-header h3 {
                font-weight: bold;
                margin-bottom: 7px;
            }
            .register-header p {
                color: #dbe4f0;
                margin-bottom: 0;
                font-size: 14px;
            }
            .register-body {
                padding: 30px;
            }
            .form-label {
                color: #0b1f3a;
                font-size: 14px;
                font-weight: bold;
                margin-bottom: 8px;
            }
            .input-group {
                margin-bottom: 20px;
            }
            .input-group-text {
                background: #f4f6f9;
                color: #24466f;
                border: 1px solid #dce2e9;
                border-right: none;
            }
            .form-control {
                padding: 12px;
                border: 1px solid #dce2e9;
                font-size: 14px;
            }
            .input-group .form-control {
                border-left: none;
            }
            .form-control:focus {
                border-color: #24466f;
                box-shadow: 0 0 0 0.15rem rgba(36, 70, 111, 0.12);
            }
            .input-group:focus-within .input-group-text {
                border-color: #24466f;
            }
            .password-toggle {
                background: white;
                border: 1px solid #dce2e9;
                border-left: none;
                color: #64748b;
            }
            .password-toggle:hover {
                background: #f4f6f9;
                color: #0b1f3a;
            }
            .btn-create {
                background: #0b1f3a;
                color: white;
                border: none;
                padding: 12px;
                font-weight: bold;
                border-radius: 8px;
            }
            .btn-create:hover {
                background: #24466f;
                color: white;
            }
            .btn-back {
                background: white;
                color: #0b1f3a;
                border: 1px solid #0b1f3a;
                padding: 11px;
                font-weight: bold;
                border-radius: 8px;
            }
            .btn-back:hover {
                background: #f0f3f7;
                color: #0b1f3a;
            }
            .register-footer {
                text-align: center;
                color: #7a8695;
                font-size: 12px;
                padding: 0 20px 25px;
            }
            @media (max-width: 480px) {
                .register-body {
                    padding: 25px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="register-wrapper">
            <div class="register-card">
                <div class="register-header">
                    <div class="store-icon">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <h3>Create User</h3>
                    <p>Small Store POS Management System</p>
                </div>
                <div class="register-body">
                    <h5 class="fw-bold mb-1" style="color: #0b1f3a;">
                        Register a New Account
                    </h5>
                    <p class="text-muted small mb-4">
                        Enter the account details below.
                    </p>
                    <?php if(isset($message)){ ?>
                        <div class="alert alert-<?php echo $color; ?> alert-dismissible fade show" role="alert">
                            <?php if($color=="success"){ ?>
                                <i class="bi bi-check-circle-fill me-2"></i>
                            <?php }else{ ?>
                                <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <?php } ?>
                            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php } ?>
                    <form method="POST">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" autocomplete="username" required>
                        </div>
                        <label for="password" class="form-label"> Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" minlength="6" autocomplete="new-password" required>
                            <button type="button" class="btn password-toggle" onclick="showPassword()" aria-label="Show or hide password">
                                <i class="bi bi-eye" id="eye"></i>
                            </button>
                        </div>
                        <label for="role" class="form-label">User Role</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person-badge"></i>
                            </span>
                            <select name="role" id="role" class="form-control" required>
                                <option value="">Select user role</option>
                                <option value="Admin">Admin</option>
                                <option value="Cashier">Cashier</option>
                            </select>
                        </div>
                        <button type="submit" name="save" class="btn btn-create w-100 mb-3">
                            <i class="bi bi-person-check me-2"></i>
                            Create User
                        </button>
                        <a href="login.php" class="btn btn-back w-100">
                            <i class="bi bi-arrow-left me-2"></i>
                            Back to Login
                        </a>
                    </form>
                </div>
            </div>
        </div>
        <script>
            function showPassword(){
                var x = document.getElementById("password");
                var eye = document.getElementById("eye");

                if(x.type == "password"){
                    x.type = "text";
                    eye.className = "bi bi-eye-slash";
                }else{
                    x.type = "password";
                    eye.className = "bi bi-eye";
                }
            }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
