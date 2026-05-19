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

$currentPage = 'reports';

$total_students = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as total FROM students")
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

$student_stats = mysqli_query($conn,"
SELECT 
    students.student_id,
    students.first_name,
    students.last_name,
    COALESCE(SUM(attendance.status='Present'),0) as presents,
    COALESCE(SUM(attendance.status='Absent'),0) as absents,
    COALESCE(SUM(attendance.status='Late'),0) as lates
FROM students
LEFT JOIN attendance
ON students.student_id = attendance.student_id
GROUP BY students.student_id
ORDER BY students.student_id
LIMIT 20
");

$frequent_absentees = mysqli_query($conn,"
SELECT
    students.student_id,
    students.first_name,
    students.last_name,
    COALESCE(SUM(attendance.status='Absent'),0) as total_absent
FROM students
LEFT JOIN attendance
ON students.student_id = attendance.student_id
GROUP BY students.student_id
HAVING total_absent >= 3
ORDER BY total_absent DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    box-shadow:4px 0 18px rgba(0,0,0,0.08);
}

.logo-section{
    text-align:center;
    padding:0 20px 30px 20px;
    border-bottom:1px solid rgba(255,255,255,0.15);
    margin-bottom:20px;
}

.logo-circle{
    width:90px;
    height:90px;
    margin:0 auto 15px auto;
    border-radius:50%;
    background:white;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
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

.menu a{
    display:block;
    color:white;
    text-decoration:none;
    padding:16px 28px;
    font-size:15px;
    transition:0.3s;
    border-left:4px solid transparent;
    opacity:0.9;
}

.menu a:hover{
    background:rgba(255,255,255,0.08);
    border-left:4px solid #4dd0e1;
    opacity:1;
}

.menu a.active{
    background:rgba(255,255,255,0.12);
    border-left:4px solid #4dd0e1;
    font-weight:bold;
    opacity:1;
}

.logout{
    position:absolute;
    bottom:20px;
    width:100%;
}

.logout a{
    display:block;
    color:white;
    text-decoration:none;
    padding:16px 28px;
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

.print-btn{
    background:#2e7d32;
    color:white;
    border:none;
    padding:12px 18px;
    border-radius:10px;
    cursor:pointer;
    font-weight:bold;
}

.filters{
    background:white;
    padding:20px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    margin-bottom:25px;
}

input,select{
    padding:12px;
    border:1px solid #ddd;
    border-radius:10px;
    margin:6px;
}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-bottom:25px;
}

.card{
    padding:25px;
    border-radius:16px;
    color:white;
    box-shadow:0 8px 22px rgba(0,0,0,0.08);
}

.card h3{
    font-size:15px;
    margin-bottom:10px;
}

.card p{
    font-size:30px;
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

.red{
    background:linear-gradient(135deg,#ef5350,#c62828);
}

.cyan{
    background:linear-gradient(135deg,#00acc1,#00838f);
}

.section{
    background:white;
    padding:25px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    margin-bottom:25px;
}

.section h2{
    margin-bottom:20px;
    color:#1f2937;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#eef4ff;
    padding:14px;
    text-align:left;
}

td{
    padding:14px;
    border-bottom:1px solid #eee;
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
        <a href="students.php" class="<?php if($currentPage=='students') echo 'active'; ?>">Students</a>
        <a href="lecturers.php" class="<?php if($currentPage=='lecturers') echo 'active'; ?>">Lecturers</a>
        <a href="courses.php" class="<?php if($currentPage=='courses') echo 'active'; ?>">Courses</a>
        <a href="reports.php" class="<?php if($currentPage=='reports') echo 'active'; ?>">Reports</a>
        <a href="ai_analysis.php" class="<?php if($currentPage=='ai') echo 'active'; ?>">AI Analysis</a>
        <a href="system_logs.php" class="<?php if($currentPage=='logs') echo 'active'; ?>">System Logs</a>
    </div>

    <div class="logout">
        <a href="../../includes/logout.php">Logout</a>
    </div>
</div>

<div class="main">

<div class="topbar">
    <h1>Attendance Reports & Analytics</h1>

    <div style="display:flex;gap:15px;align-items:center;">
        <button class="print-btn" onclick="window.print()">Print All</button>
        <div class="admin-box"><?php echo $_SESSION['username']; ?></div>
    </div>
</div>

<div class="filters">
    <input type="date">
    <input type="date">

    <select>
        <option>Daily</option>
        <option>Weekly</option>
        <option>Monthly</option>
        <option>Yearly</option>
    </select>
</div>

<div class="cards">
    <div class="card blue">
        <h3>Total Students</h3>
        <p><?php echo $total_students; ?></p>
    </div>

    <div class="card purple">
        <h3>Present Today</h3>
        <p><?php echo $present_today; ?></p>
    </div>

    <div class="card orange">
        <h3>Late Today</h3>
        <p><?php echo $late_today; ?></p>
    </div>

    <div class="card red">
        <h3>Absent Today</h3>
        <p><?php echo $absent_today; ?></p>
    </div>

    <div class="card cyan">
        <h3>Attendance Rate</h3>
        <p><?php echo $attendance_rate; ?>%</p>
    </div>
</div>

<div class="section">
    <h2>Attendance Trend</h2>
    <canvas id="attendanceChart"></canvas>
</div>

<div class="section">
    <h2>Student Attendance Details</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Late</th>
        </tr>

        <?php while($row=mysqli_fetch_assoc($student_stats)){ ?>
        <tr>
            <td><?php echo $row['student_id']; ?></td>
            <td><?php echo $row['first_name']." ".$row['last_name']; ?></td>
            <td><?php echo $row['presents']; ?></td>
            <td><?php echo $row['absents']; ?></td>
            <td><?php echo $row['lates']; ?></td>
        </tr>
        <?php } ?>
    </table>
</div>

<div class="section">
    <h2>Frequent Absentees</h2>

    <table>
        <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Total Absences</th>
        </tr>

        <?php while($row=mysqli_fetch_assoc($frequent_absentees)){ ?>
        <tr>
            <td><?php echo $row['student_id']; ?></td>
            <td><?php echo $row['first_name']." ".$row['last_name']; ?></td>
            <td><?php echo $row['total_absent']; ?></td>
        </tr>
        <?php } ?>
    </table>
</div>

<div class="section">
    <h2>AI Insights</h2>

    <?php
    $insights = [];

    if ($attendance_rate < 70) {
        $insights[] = "Attendance is critically low and requires immediate attention.";
    } elseif ($attendance_rate < 85) {
        $insights[] = "Attendance is moderate but could be improved.";
    } else {
        $insights[] = "Attendance levels are generally healthy.";
    }

    $worst = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT students.first_name, students.last_name,
        COALESCE(SUM(attendance.status='Absent'),0) as absents
        FROM students
        LEFT JOIN attendance ON students.student_id = attendance.student_id
        GROUP BY students.student_id
        ORDER BY absents DESC
        LIMIT 1
    "));

    if ($worst && $worst['absents'] > 0) {
        $insights[] = "Highest absenteeism: {$worst['first_name']} {$worst['last_name']}.";
    }

    $best = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT students.first_name, students.last_name,
        COALESCE(SUM(attendance.status='Present'),0) as presents
        FROM students
        LEFT JOIN attendance ON students.student_id = attendance.student_id
        GROUP BY students.student_id
        ORDER BY presents DESC
        LIMIT 1
    "));

    if ($best && $best['presents'] > 0) {
        $insights[] = "Best attendance: {$best['first_name']} {$best['last_name']}.";
    }

    $trend = mysqli_query($conn,"
        SELECT attendance_date,
        SUM(status='Present') as present_count
        FROM attendance
        WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 5 DAY)
        GROUP BY attendance_date
        ORDER BY attendance_date ASC
    ");

    $values = [];
    while ($t = mysqli_fetch_assoc($trend)) {
        $values[] = $t['present_count'];
    }

    if (count($values) > 2 && end($values) < $values[0]) {
        $insights[] = "Attendance is showing a declining trend over recent days.";
    }

    foreach ($insights as $i) {
        echo "<p>$i</p>";
    }
    ?>
</div>

</div>

<script>
new Chart(document.getElementById('attendanceChart'), {
    type:'bar',
    data:{
        labels:['Mon','Tue','Wed','Thu','Fri'],
        datasets:[{
            label:'Attendance',
            data:[120,190,150,180,170]
        }]
    }
});
</script>

</body>
</html>
