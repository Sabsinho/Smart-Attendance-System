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

$currentPage = 'dashboard';

$total_students = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM students")
)['total'];

$total_lecturers = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM lecturers")
)['total'];

$total_courses = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM courses")
)['total'];

$present_today = mysqli_fetch_assoc(
    mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM attendance
        WHERE attendance_date = CURDATE()
        AND status='Present'
    ")
)['total'];

$late_today = mysqli_fetch_assoc(
    mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM attendance
        WHERE attendance_date = CURDATE()
        AND status='Late'
    ")
)['total'];

$absent_today = $total_students - ($present_today + $late_today);

$attendance_rate = $total_students > 0
? round((($present_today + $late_today) / $total_students) * 100)
: 0;

$recent_logs = mysqli_query($conn,"
    SELECT action, created_at
    FROM system_logs
    ORDER BY created_at DESC
    LIMIT 5
");

$alerts = mysqli_query($conn,"
    SELECT
        students.first_name,
        students.last_name,
        COUNT(attendance.id) as total_absent
    FROM students
    JOIN attendance ON students.student_id = attendance.student_id
    WHERE attendance.status='Absent'
    GROUP BY students.student_id
    HAVING total_absent >= 3
    ORDER BY total_absent DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

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
    margin-top:5px;
}

.menu a,
.logout a{
    display:block;
    color:white;
    text-decoration:none;
    padding:16px 28px;
    border-left:4px solid transparent;
    transition:0.3s;
}

.menu a:hover,
.logout a:hover{
    background:rgba(255,255,255,0.08);
}

.menu a.active{
    background:rgba(255,255,255,0.12);
    border-left:4px solid #4dd0e1;
    font-weight:bold;
}

.logout{
    position:absolute;
    bottom:20px;
    width:100%;
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
    margin-bottom:35px;
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

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:22px;
    margin-bottom:35px;
}

.card{
    padding:28px;
    border-radius:18px;
    color:white;
    box-shadow:0 8px 22px rgba(0,0,0,0.08);
}

.card h3{
    font-size:15px;
    margin-bottom:12px;
}

.card p{
    font-size:32px;
    font-weight:bold;
}

.blue{
    background:linear-gradient(135deg,#2196f3,#1565c0);
}

.purple{
    background:linear-gradient(135deg,#8e24aa,#5e35b1);
}

.orange{
    background:linear-gradient(135deg,#fb8c00,#ef6c00);
}

.cyan{
    background:linear-gradient(135deg,#00acc1,#00838f);
}

.green{
    background:linear-gradient(135deg,#43a047,#2e7d32);
}

.content-grid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:25px;
}

.panel{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
}

.panel h2{
    margin-bottom:20px;
    color:#1f2937;
}

.activity-item{
    padding:14px 0;
    border-bottom:1px solid #eef2f7;
    font-size:14px;
    color:#555;
}

.alert{
    background:#fff3e0;
    color:#e65100;
    padding:14px;
    border-radius:12px;
    margin-bottom:12px;
    font-size:14px;
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
        <a href="dashboard.php" class="<?php if($currentPage=='dashboard') echo 'active'; ?>">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="lecturers.php">Lecturers</a>
        <a href="courses.php">Courses</a>
        <a href="reports.php">Reports</a>
        <a href="ai_analysis.php">AI Analysis</a>
        <a href="system_logs.php">System Logs</a>
    </div>

    <div class="logout">
        <a href="../../includes/logout.php">Logout</a>
    </div>

</div>

<div class="main">

    <div class="topbar">
        <h1>Admin Dashboard</h1>
        <div class="admin-box">
            <?php echo $_SESSION['username']; ?>
        </div>
    </div>

    <div class="cards">
        <div class="card blue">
            <h3>Total Students</h3>
            <p><?php echo $total_students; ?></p>
        </div>

        <div class="card purple">
            <h3>Total Lecturers</h3>
            <p><?php echo $total_lecturers; ?></p>
        </div>

        <div class="card green">
            <h3>Total Courses</h3>
            <p><?php echo $total_courses; ?></p>
        </div>

        <div class="card orange">
            <h3>Present Today</h3>
            <p><?php echo $present_today + $late_today; ?></p>
        </div>

        <div class="card cyan">
            <h3>Attendance Rate</h3>
            <p><?php echo $attendance_rate; ?>%</p>
        </div>
    </div>

    <div class="content-grid">

        <div class="panel">
            <h2>Recent Activity</h2>

            <?php while($log = mysqli_fetch_assoc($recent_logs)) { ?>
                <div class="activity-item">
                    <?php echo htmlspecialchars($log['action']); ?>
                    <br>
                    <small><?php echo $log['created_at']; ?></small>
                </div>
            <?php } ?>
        </div>

        <div class="panel">
            <h2>Absentee Alerts</h2>

            <?php while($alert = mysqli_fetch_assoc($alerts)) { ?>
                <div class="alert">
                    <?php echo $alert['first_name'] . " " . $alert['last_name']; ?>
                    has <?php echo $alert['total_absent']; ?> absences
                </div>
            <?php } ?>
        </div>

    </div>

</div>

</body>
</html>