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

$user_id = $_SESSION['user_id'];

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

$courseInfo = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT courses.course_name, courses.course_code, semesters.semester_name, courses.semester_id
    FROM courses
    JOIN semesters ON courses.semester_id = semesters.id
    WHERE courses.id='$course_id'
"));

$semester_id = $courseInfo['semester_id'];

$students = mysqli_query($conn,"
    SELECT student_id, first_name, last_name
    FROM students
    WHERE semester_id='$semester_id'
");

if (isset($_POST['save_attendance'])) {

    $nowDate = date("Y-m-d");
    $nowTime = date("H:i:s");

    foreach ($_POST['status'] as $student_id => $status) {

        $check = mysqli_query($conn,"
            SELECT id
            FROM attendance
            WHERE student_id='$student_id'
            AND course_id='$course_id'
            AND date='$nowDate'
        ");

        if (mysqli_num_rows($check) > 0) {

            mysqli_query($conn,"
                UPDATE attendance
                SET
                    status='$status',
                    date='$nowDate',
                    time='$nowTime',
                    attendance_date='$nowDate',
                    attendance_time='$nowTime'
                WHERE student_id='$student_id'
                AND course_id='$course_id'
                AND date='$nowDate'
            ");

        } else {

            mysqli_query($conn,"
                INSERT INTO attendance
                (
                    student_id,
                    course_id,
                    status,
                    date,
                    time,
                    attendance_date,
                    attendance_time
                )
                VALUES
                (
                    '$student_id',
                    '$course_id',
                    '$status',
                    '$nowDate',
                    '$nowTime',
                    '$nowDate',
                    '$nowTime'
                )
            ");
        }
    }

    echo "<script>
        alert('Attendance saved successfully');
        window.location.href='student_attendance_details.php';
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Manual Attendance</title>

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

select{
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
}

.save-btn{
    background:#2e7d32;
    color:white;
    border:none;
    padding:12px 20px;
    border-radius:10px;
    cursor:pointer;
    font-weight:bold;
    margin-top:20px;
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
    <h1><?php echo $courseInfo['course_name']; ?> Manual Attendance</h1>
    <div class="admin-box"><?php echo $_SESSION['username']; ?></div>
</div>

<div class="section">
    <h2>Course Information</h2>
    <p><strong>Course:</strong> <?php echo $courseInfo['course_name']; ?></p>
    <p><strong>Code:</strong> <?php echo $courseInfo['course_code']; ?></p>
    <p><strong>Semester:</strong> <?php echo $courseInfo['semester_name']; ?></p>
</div>

<div class="section">
    <h2>Take Attendance</h2>

    <form method="POST">
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
            </tr>

            <?php while($row = mysqli_fetch_assoc($students)){ ?>
            <tr>
                <td><?php echo $row['student_id']; ?></td>
                <td><?php echo $row['first_name']." ".$row['last_name']; ?></td>
                <td>
                    <select name="status[<?php echo $row['student_id']; ?>]">
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Late">Late</option>
                    </select>
                </td>
            </tr>
            <?php } ?>
        </table>

        <button type="submit" name="save_attendance" class="save-btn">
            Save Attendance
        </button>
    </form>
</div>

</div>
</body>
</html>