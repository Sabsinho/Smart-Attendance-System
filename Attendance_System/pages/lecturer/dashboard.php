<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'lecturer') {
    exit("Access denied");
}

$currentPage = 'dashboard';

$lecturer_id = $_SESSION['lecturer_id'];

$total_classes = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM courses")
)['total'];

$today_attendance = mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(*) as total
        FROM attendance
        WHERE attendance_date = CURDATE()
    ")
)['total'];

$present_today = mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(*) as total
        FROM attendance
        WHERE attendance_date = CURDATE()
        AND status='Present'
    ")
)['total'];

$late_today = mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(*) as total
        FROM attendance
        WHERE attendance_date = CURDATE()
        AND status='Late'
    ")
)['total'];

$absent_today = mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(*) as total
        FROM attendance
        WHERE attendance_date = CURDATE()
        AND status='Absent'
    ")
)['total'];

$attendance_rate = $today_attendance > 0
? round((($present_today + $late_today) / $today_attendance) * 100)
: 0;

$recent_logs = mysqli_query($conn, "
    SELECT action, created_at
    FROM system_logs
    WHERE user_id='$lecturer_id'
    ORDER BY created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lecturer Dashboard</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif;}
body{display:flex;background:#f6f9fc;}

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
    width:90px;height:90px;margin:0 auto 15px;
    border-radius:50%;background:white;overflow:hidden;
}

.logo-circle img{width:100%;height:100%;object-fit:cover;}

.logo-title{font-size:20px;font-weight:bold;}
.logo-subtitle{font-size:13px;opacity:0.85;margin-top:5px;}

.menu a{
    display:block;
    color:white;
    text-decoration:none;
    padding:16px 28px;
    border-left:4px solid transparent;
}

.menu a:hover{background:rgba(255,255,255,0.08);}

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

.logout a{
    display:block;
    color:white;
    padding:16px 28px;
    text-decoration:none;
}

.main{
    margin-left:270px;
    width:calc(100% - 270px);
    padding:35px;
}

.topbar{
    display:flex;
    justify-content:space-between;
    margin-bottom:35px;
}

.topbar h1{font-size:30px;color:#1f2937;}

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
}

.card{
    padding:28px;
    border-radius:18px;
    color:white;
    box-shadow:0 8px 22px rgba(0,0,0,0.08);
}

.card h3{font-size:15px;margin-bottom:12px;}
.card p{font-size:32px;font-weight:bold;}

.blue{background:linear-gradient(135deg,#2196f3,#1565c0);}
.purple{background:linear-gradient(135deg,#8e24aa,#5e35b1);}
.orange{background:linear-gradient(135deg,#fb8c00,#ef6c00);}
.green{background:linear-gradient(135deg,#43a047,#2e7d32);}
.cyan{background:linear-gradient(135deg,#00acc1,#00838f);}

.content-grid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:25px;
    margin-top:30px;
}

.panel{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
}

.activity-item{
    padding:12px 0;
    border-bottom:1px solid #eee;
    font-size:14px;
}
</style>
</head>

<body>

<div class="sidebar">

    <div class="logo-section">
        <div class="logo-circle">
            <img src="../../assets/GU.jpg">
        </div>
        <div class="logo-title">Gollis University</div>
        <div class="logo-subtitle">Lecturer Panel</div>
    </div>

    <div class="menu">
        <a href="dashboard.php" class="<?php if($currentPage=='dashboard') echo 'active'; ?>">Dashboard</a>
        <a href="attendance.php">Attendance</a>
        <a href="report.php">Report</a>
        <a href="ai_analysis.php">AI Analysis</a>
    </div>

    <div class="logout">
        <a href="../../includes/logout.php">Logout</a>
    </div>

</div>

<div class="main">

    <div class="topbar">
        <h1>Lecturer Dashboard</h1>
        <div class="admin-box">
            <?php echo $_SESSION['username']; ?>
        </div>
    </div>

    <div class="cards">
        <div class="card blue">
            <h3>Total Classes</h3>
            <p><?php echo $total_classes; ?></p>
        </div>

        <div class="card purple">
            <h3>Today Attendance</h3>
            <p><?php echo $today_attendance; ?></p>
        </div>

        <div class="card green">
            <h3>Present</h3>
            <p><?php echo $present_today; ?></p>
        </div>

        <div class="card orange">
            <h3>Absent</h3>
            <p><?php echo $absent_today; ?></p>
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
                    <?php echo $log['action']; ?>
                    <br>
                    <small><?php echo $log['created_at']; ?></small>
                </div>
            <?php } ?>

        </div>

        <div class="panel">
            <h2>Quick Info</h2>
            <p>Use Attendance page to mark students.</p>
            <p>Use Report page to view class stats.</p>
            <p>AI Analysis helps detect weak students.</p>
        </div>

    </div>

</div>

</body>
</html>