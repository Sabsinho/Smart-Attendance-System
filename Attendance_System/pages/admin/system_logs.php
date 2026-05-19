<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'admin') {
    exit("Access denied");
}

$currentPage = 'logs';

$logs = mysqli_query($conn,"
    SELECT system_logs.*, users.username, users.role
    FROM system_logs
    JOIN users ON system_logs.user_id = users.id
    ORDER BY system_logs.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Logs</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial,sans-serif;
}

body{
    display:flex;
    background:#f6f9fc;
}

.sidebar{
    width:270px;
    height:100vh;
    position:fixed;
    background:linear-gradient(180deg,#1a237e,#283593);
    color:white;
    padding-top:25px;
}

.logo-section{
    text-align:center;
    padding:0 20px 30px;
    border-bottom:1px solid rgba(255,255,255,0.15);
    margin-bottom:20px;
}

.logo-circle{
    width:90px;
    height:90px;
    margin:0 auto 15px;
    border-radius:50%;
    background:white;
    overflow:hidden;
}

.logo-circle img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.logo-title{
    font-size:20px;
    font-weight:bold;
}

.logo-subtitle{
    font-size:13px;
    opacity:0.85;
    margin-top:4px;
}

.menu a{
    display:block;
    color:white;
    text-decoration:none;
    padding:16px 28px;
    border-left:4px solid transparent;
    transition:0.3s;
}

.menu a:hover{
    background:rgba(255,255,255,0.08);
}

.menu a.active{
    background:rgba(255,255,255,0.12);
    border-left:4px solid #4dd0e1;
    font-weight:bold;
}

.main{
    margin-left:270px;
    width:calc(100% - 270px);
    padding:35px;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.topbar h1{
    color:#1f2937;
    font-size:30px;
}

.admin-box{
    background:white;
    padding:14px 22px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    font-weight:bold;
    color:#283593;
}

.logs-container{
    background:white;
    border-radius:18px;
    padding:25px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#eef4ff;
    color:#1f2937;
    padding:16px;
    text-align:left;
    font-size:14px;
}

td{
    padding:16px;
    border-bottom:1px solid #edf2f7;
    font-size:14px;
    color:#4b5563;
}

tr:hover{
    background:#fafcff;
}

.badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
    display:inline-block;
}

.admin{
    background:#dbeafe;
    color:#1d4ed8;
}

.lecturer{
    background:#f3e8ff;
    color:#7e22ce;
}
</style>
</head>
<body>

<div class="sidebar">

    <div class="logo-section">
        <div class="logo-circle">
            <img src="../../assets/GU.jpg" alt="Logo">
        </div>
        <div class="logo-title">Gollis University</div>
        <div class="logo-subtitle">Attendance Management</div>
    </div>

    <div class="menu">
        <a href="dashboard.php" class="<?php if($currentPage == 'dashboard') echo 'active'; ?>">Dashboard</a>

        <a href="students.php" class="<?php if($currentPage == 'students') echo 'active'; ?>">Students</a>

        <a href="lecturers.php" class="<?php if($currentPage == 'lecturers') echo 'active'; ?>">Lecturers</a>

        <a href="courses.php" class="<?php if($currentPage == 'courses') echo 'active'; ?>">Courses</a>

        <a href="reports.php" class="<?php if($currentPage == 'reports') echo 'active'; ?>">Reports</a>

        <a href="ai_analysis.php" class="<?php if($currentPage == 'ai') echo 'active'; ?>">AI Analysis</a>

        <a href="system_logs.php" class="<?php if($currentPage == 'logs') echo 'active'; ?>">System Logs</a> <br> <br>

        <a href="../../includes/logout.php">Logout</a>
    </div>

</div>

<div class="main">

    <div class="topbar">
        <h1>System Logs</h1>

        <div class="admin-box">
            <?php echo $_SESSION['username']; ?>
        </div>
    </div>

    <div class="logs-container">
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Action</th>
                <th>Date & Time</th>
            </tr>

            <?php while($row = mysqli_fetch_assoc($logs)) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td>
                    <span class="badge <?php echo strtolower($row['role']); ?>">
                        <?php echo ucfirst($row['role']); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($row['action']); ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>

</div>

</body>
</html>