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

$currentPage = 'report';
$user_id = $_SESSION['user_id'];

/* GET LECTURER */
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

$faculty_id = $lecturer['faculty_id'];
$shift_id = $lecturer['shift_id'];
$course_id = $lecturer['course_id'];

/* COURSE INFO */
$courseInfo = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT courses.course_name, courses.course_code, semesters.semester_name
    FROM courses
    JOIN semesters ON courses.semester_id = semesters.id
    WHERE courses.id='$course_id'
"));

/* GET SEMESTER OF THIS COURSE */
$course_semester = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT semester_id FROM courses WHERE id='$course_id'
"))['semester_id'];

/* TOTAL STUDENTS (FIXED LOGIC) */
$total_students = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM students
    WHERE semester_id='$course_semester'
"))['total'];

/* PRESENT */
$present_count = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM attendance
    JOIN students ON attendance.student_id = students.student_id
    WHERE attendance.course_id='$course_id'
    AND attendance.status='Present'
    AND students.semester_id='$course_semester'
"))['total'];

/* ABSENT */
$absent_count = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM attendance
    JOIN students ON attendance.student_id = students.student_id
    WHERE attendance.course_id='$course_id'
    AND attendance.status='Absent'
    AND students.semester_id='$course_semester'
"))['total'];

/* LATE */
$late_count = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) as total
    FROM attendance
    WHERE course_id='$course_id'
    AND status='Late'
"))['total'];

/* ATTENDANCE RATE */
$attendance_rate = ($total_students > 0)
    ? round((($present_count + $late_count) / $total_students) * 100)
    : 0;

/* STUDENTS LIST */
$students = mysqli_query($conn,"
    SELECT
        students.student_id,
        students.first_name,
        students.last_name,

        COUNT(CASE WHEN attendance.status='Present' THEN 1 END) as presents,
        COUNT(CASE WHEN attendance.status='Absent' THEN 1 END) as absents,
        COUNT(CASE WHEN attendance.status='Late' THEN 1 END) as lates

    FROM students
    LEFT JOIN attendance 
        ON students.student_id = attendance.student_id
        AND attendance.course_id='$course_id'

    WHERE students.semester_id='$course_semester'

    GROUP BY students.student_id
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Lecturer Report</title>
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

.print-btn{
    background:#2e7d32;
    color:white;
    border:none;
    padding:12px 20px;
    border-radius:10px;
    cursor:pointer;
    font-weight:bold;
}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin:25px 0;
}

.card{
    padding:25px;
    border-radius:18px;
    color:white;
}

.blue{background:linear-gradient(135deg,#2196f3,#1565c0);}
.orange{background:linear-gradient(135deg,#fb8c00,#ef6c00);}
.red{background:linear-gradient(135deg,#ef5350,#c62828);}
.purple{background:linear-gradient(135deg,#8e24aa,#5e35b1);}

.card p{
    font-size:30px;
    font-weight:bold;
    margin-top:10px;
}

.section{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    margin-bottom:25px;
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

@media print{
    body *{ visibility:hidden; }
    #printArea, #printArea *{ visibility:visible; }
    #printArea{
        position:absolute;
        left:0;
        top:0;
        width:100%;
    }
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
        <div class="logo-subtitle">Attendance Management</div>
    </div>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="attendance.php">Attendance</a>
        <a href="report.php" class="active">Report</a>
        <a href="ai_analysis.php">AI Analysis</a>
    </div>

    <div class="logout">
        <a href="../../includes/logout.php">Logout</a>
    </div>
</div>

<div class="main">

<div class="topbar">
    <h1><?php echo $courseInfo['course_name']; ?> Report</h1>

    <div style="display:flex;gap:15px;">
        <button class="print-btn" onclick="window.print()">Print Report</button>
        <div class="admin-box"><?php echo $_SESSION['username']; ?></div>
    </div>
</div>

<div id="printArea">

<div class="section">
    <h2>Course Information</h2>
    <p><strong>Course:</strong> <?php echo $courseInfo['course_name']; ?></p>
    <p><strong>Code:</strong> <?php echo $courseInfo['course_code']; ?></p>
    <p><strong>Semester:</strong> <?php echo $courseInfo['semester_name']; ?></p>
</div>

<div class="cards">
    <div class="card blue">
        <h3>Total Students</h3>
        <p><?php echo $total_students; ?></p>
    </div>

    <div class="card orange">
        <h3>Present</h3>
        <p><?php echo $present_count; ?></p>
    </div>

    <div class="card red">
        <h3>Absent</h3>
        <p><?php echo $absent_count; ?></p>
    </div>

    <div class="card purple">
        <h3>Attendance Rate</h3>
        <p><?php echo $attendance_rate; ?>%</p>
    </div>
</div>

<div class="section">
    <h2>Attendance Graph</h2>
    <canvas id="chart"></canvas>
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

        <?php while($student=mysqli_fetch_assoc($students)){ ?>
        <tr>
            <td><?php echo $student['student_id']; ?></td>
            <td><?php echo $student['first_name']." ".$student['last_name']; ?></td>
            <td><?php echo $student['presents']; ?></td>
            <td><?php echo $student['absents']; ?></td>
            <td><?php echo $student['lates']; ?></td>
        </tr>
        <?php } ?>
    </table>
</div>

</div>
</div>

<script>
new Chart(document.getElementById('chart'),{
    type:'bar',
    data:{
        labels:['Present','Absent','Late'],
        datasets:[{
            label:'Attendance Statistics',
            data:[
                <?php echo $present_count; ?>,
                <?php echo $absent_count; ?>,
                <?php echo $late_count; ?>
            ]
        }]
    }
});
</script>

</body>
</html>