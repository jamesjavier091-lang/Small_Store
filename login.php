
<?php
session_start();
include "config.php";

if(isset($_POST['login'])){
    $username=$_POST['username'];
    $password=$_POST['password'];

    if(trim($username)==""){
        $message="Username is required!";
        $color="danger";
    }elseif(trim($password)==""){
        $message="Password is required!";
        $color="danger";
    }else{
        $stmt=mysqli_prepare($conn,"SELECT * FROM users WHERE username=?");
        mysqli_stmt_bind_param($stmt,"s",$username);
        mysqli_stmt_execute($stmt);
        $result=mysqli_stmt_get_result($stmt);

        if(mysqli_num_rows($result)>0){
            $row=mysqli_fetch_assoc($result);

            if(password_verify($password,$row['password'])){
                $_SESSION['username']=$row['username'];
                $_SESSION['role']=$row['role'];
                header("Location: dashboard.php");
                exit();
            }else{
                $message="Invalid username or password!";
                $color="danger";
            }
        }else{
            $message="Invalid username or password!";
            $color="danger";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Small Store POS</title>

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

        .login-wrapper {
            width: 100%;
            max-width: 430px;
        }

        .login-card {
            background: white;
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 35px rgba(11, 31, 58, 0.12);
        }

        .login-header {
            background: #0b1f3a;
            color: white;
            text-align: center;
            padding: 35px 25px;
        }

        .store-icon {
            width: 75px;
            height: 75px;
            background: #24466f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 35px;
        }

        .login-header h3 {
            font-weight: bold;
            margin-bottom: 7px;
        }

        .login-header p {
            color: #dbe4f0;
            margin-bottom: 0;
            font-size: 14px;
        }

        .login-body {
            padding: 30px;
        }

        .form-label {
            font-size: 14px;
            font-weight: bold;
            color: #0b1f3a;
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

        .btn-login {
            background: #0b1f3a;
            color: white;
            border: none;
            padding: 12px;
            font-weight: bold;
            border-radius: 8px;
            transition: 0.2s;
        }

        .btn-login:hover {
            background: #24466f;
            color: white;
        }

        .btn-register {
            background: white;
            color: #0b1f3a;
            border: 1px solid #0b1f3a;
            padding: 11px;
            font-weight: bold;
            border-radius: 8px;
            transition: 0.2s;
        }

        .btn-register:hover {
            background: #f0f3f7;
            color: #0b1f3a;
        }

        .login-footer {
            text-align: center;
            color: #7a8695;
            font-size: 12px;
            padding: 0 20px 25px;
        }

        @media (max-width: 480px) {
            .login-body {
                padding: 25px 20px;
            }

            .login-header {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">

        <div class="login-card">

            <div class="login-header">
                <div class="store-icon">
                    <i class="bi bi-shop"></i>
                </div>

                <h3>Small Store POS</h3>
                <p>Point of Sale Management System</p>
            </div>

            <div class="login-body">

                <h5 class="fw-bold mb-1" style="color: #0b1f3a;">
                    Welcome!
                </h5>

                <p class="text-muted small mb-4">
                    Please log in to access your account.
                </p>

                <?php if(isset($message)){ ?>
                    <div class="alert alert-<?php echo $color; ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php } ?>

                <form method="POST">

                    <label class="form-label" for="username">
                        Username
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>

                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control"
                            placeholder="Enter your username"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <label class="form-label" for="password">
                        Password
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="btn password-toggle"
                            onclick="showPassword()"
                            aria-label="Show or hide password"
                        >
                            <i class="bi bi-eye" id="eye"></i>
                        </button>
                    </div>

                    <button
                        type="submit"
                        name="login"
                        class="btn btn-login w-100 mb-3"
                    >
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Login
                    </button>

                    <a href="register.php" class="btn btn-register w-100">
                        <i class="bi bi-person-plus me-2"></i>
                        Create User
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
