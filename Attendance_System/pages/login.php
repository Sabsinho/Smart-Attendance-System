<?php
session_start();
include("../config/db.php");

$error = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['lecturer_id'] = $user['lecturer_id'];

        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
            exit();
        }

        if ($user['role'] == 'lecturer') {
            header("Location: lecturer/dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    height: 100vh;
    display: flex;
    background: #f6f9fc;
}

.left-panel {
    width: 50%;
    background: linear-gradient(135deg, #1a237e, #283593, #1565c0);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 60px;
}

.branding {
    text-align: center;
    max-width: 460px;
}

.logo-circle {
    width: 150px;
    height: 150px;
    margin: 0 auto 25px auto;
    border-radius: 50%;
    background: white;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 12px 30px rgba(0,0,0,0.22);
}

.logo-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.branding h1 {
    font-size: 40px;
    margin-bottom: 12px;
}

.branding p {
    font-size: 17px;
    opacity: 0.92;
    line-height: 1.7;
}

.right-panel {
    width: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
}

.login-box {
    width: 430px;
    background: rgba(255,255,255,0.88);
    backdrop-filter: blur(18px);
    padding: 45px;
    border-radius: 26px;
    box-shadow: 0 20px 45px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.7);
    position: relative;
    animation: floatCard 4s ease-in-out infinite;
}

@keyframes floatCard {
    0% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-6px);
    }
    100% {
        transform: translateY(0px);
    }
}

.top-accent {
    width: 70px;
    height: 6px;
    background: linear-gradient(90deg, #2196f3, #7b1fa2, #00acc1);
    border-radius: 20px;
    margin-bottom: 28px;
}

.login-title {
    font-size: 26px;
    color: #1f2937;
    font-weight: bold;
    margin-bottom: 8px;
}

.login-subtitle {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 30px;
    line-height: 1.5;
}

.input-group {
    margin-bottom: 20px;
}

.input-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: bold;
    color: #374151;
}

.input-group input {
    width: 100%;
    padding: 15px;
    border-radius: 12px;
    border: 1px solid #dbe3ef;
    font-size: 15px;
    transition: 0.3s;
    background: white;
}

.input-group input:focus {
    outline: none;
    border-color: #1565c0;
    box-shadow: 0 0 0 4px rgba(21,101,192,0.08);
}

button {
    width: 100%;
    padding: 16px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #2196f3, #1565c0);
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(33,150,243,0.25);
}

.error {
    margin-top: 18px;
    background: #ffebee;
    color: #c62828;
    padding: 12px;
    border-radius: 10px;
    text-align: center;
    font-size: 14px;
}

.footer {
    text-align: center;
    margin-top: 22px;
    color: #9ca3af;
    font-size: 13px;
}
</style>
</head>
<body>

<div class="left-panel">
    <div class="branding">
        <div class="logo-circle">
            <img src="../assets/GU.jpg" alt="Gollis Logo">
        </div>

        <h1>Gollis University</h1>

        <p>
            Smart Attendance Management System powered by AI,
            Face Recognition, Automated Reports, and Attendance Analytics.
        </p>
    </div>
</div>

<div class="right-panel">

    <div class="login-box">

        <div class="top-accent"></div>

        <div class="login-title">System Access</div>
        <div class="login-subtitle">
            Enter your credentials to continue into the attendance platform.
        </div>

        <form method="POST">

            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <button type="submit" name="login">Login</button>

        </form>

        <?php if($error != "") { ?>
            <div class="error">
                <?php echo $error; ?>
            </div>
        <?php } ?>

        <div class="footer">
            AI Powered Attendance System
        </div>

    </div>

</div>

</body>
</html>