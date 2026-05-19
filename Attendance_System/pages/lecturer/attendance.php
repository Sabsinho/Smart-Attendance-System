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

$currentPage = 'attendance';
$user_id = $_SESSION['user_id'];

/* LECTURER */
$lecturerQuery = mysqli_query($conn,"
    SELECT lecturers.*
    FROM lecturers
    JOIN users ON users.lecturer_id = lecturers.id
    WHERE users.id='$user_id'
");

$lecturer = mysqli_fetch_assoc($lecturerQuery);

if (!$lecturer) {
    exit("Lecturer not found");
}

$course_id = $lecturer['course_id'];
$semester_id = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT semester_id FROM courses WHERE id='$course_id'
"))['semester_id'];

/* 9 CLASSES (FACULTY × SHIFT × SEMESTER) */
$classes = mysqli_query($conn,"
    SELECT 
        f.id as faculty_id,
        f.faculty_name,
        s.id as shift_id,
        s.shift_name,
        sem.semester_name
    FROM faculties f
    CROSS JOIN shifts s
    CROSS JOIN semesters sem
    WHERE sem.id='$semester_id'
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Attendance</title>
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

/* SIDEBAR (EXACT SAME STYLE AS REPORT) */
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

.menu a,
.logout a{
    display:block;
    color:white;
    text-decoration:none;
    padding:16px 28px;
    border-left:4px solid transparent;
}

.menu a.active{
    background:rgba(255,255,255,0.12);
    border-left:4px solid #4dd0e1;
    font-weight:bold;
}

/* MAIN */
.main{
    margin-left:270px;
    width:calc(100% - 270px);
    padding:35px;
}

.topbar{
    display:flex;
    justify-content:space-between;
    margin-bottom:30px;
}

.admin-box{
    background:white;
    padding:14px 22px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    font-weight:bold;
    color:#283593;
}

/* SECTION (SAME AS REPORT) */
.section{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    margin-bottom:25px;
}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}

.card{
    padding:20px;
    border-radius:16px;
    background:white;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.card h3{
    margin-bottom:10px;
}

.btn{
    padding:10px 14px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    margin-top:10px;
    width:100%;
}

.manual{background:#2196f3;color:white;}
.camera{background:#2e7d32;color:white;}
</style>
</head>

<body>

<div class="sidebar">

    <div class="logo-section">
        <div class="logo-circle">
            <img src="../../assets/GU.jpg" style="width:100%;height:100%;object-fit:cover;">
        </div>
        <div class="logo-title">Gollis University</div>
    </div>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="attendance.php" class="active">Attendance</a>
        <a href="report.php">Report</a>
        <a href="ai_analysis.php">AI Analysis</a>
    </div>

    <div class="logout">
        <a href="../../includes/logout.php">Logout</a>
    </div>

</div>

<div class="main">

<div class="topbar">
    <h1>Take Attendance</h1>
    <div class="admin-box"><?php echo $_SESSION['username']; ?></div>
</div>

<div class="section">
    <h2>Available Classes</h2>

    <div class="cards">

        <?php while($class = mysqli_fetch_assoc($classes)){ ?>

        <div class="card">

            <h3><?php echo $class['faculty_name']; ?></h3>

            <p><b>Shift:</b> <?php echo $class['shift_name']; ?></p>
            <p><b>Semester:</b> <?php echo $class['semester_name']; ?></p>

            <form method="GET" action="./manual_attendance.php">
    <input type="hidden" name="faculty_id" value="<?php echo $class['faculty_id']; ?>">
    <input type="hidden" name="shift_id" value="<?php echo $class['shift_id']; ?>">
    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
    <button type="submit" class="btn manual">Manual Attendance</button>
</form>

<form method="GET" action="./camera_attendance.php">
    <input type="hidden" name="faculty_id" value="<?php echo $class['faculty_id']; ?>">
    <input type="hidden" name="shift_id" value="<?php echo $class['shift_id']; ?>">
    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
    <button type="submit" class="btn camera">Camera Attendance</button>
</form>

        </div>

        <?php } ?>

    </div>

</div>

</div>

</body>
</html>