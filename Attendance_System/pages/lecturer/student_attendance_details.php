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

$course_id = $lecturer['course_id'];

$courseInfo = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT courses.course_name, courses.semester_id
    FROM courses
    WHERE id='$course_id'
"));

$semester_id = $courseInfo['semester_id'];

$selected_student = isset($_GET['student_id']) ? $_GET['student_id'] : null;

$students = mysqli_query($conn,"
    SELECT student_id, first_name, last_name
    FROM students
    WHERE semester_id='$semester_id'
");

if ($selected_student) {

    $studentInfo = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT first_name, last_name
        FROM students
        WHERE student_id='$selected_student'
    "));

    $attendanceHistory = mysqli_query($conn,"
        SELECT status, date
        FROM attendance
        WHERE student_id='$selected_student'
        AND course_id='$course_id'
        ORDER BY date DESC
    ");

    $present_count = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT COUNT(*) total
        FROM attendance
        WHERE student_id='$selected_student'
        AND course_id='$course_id'
        AND status='Present'
    "))['total'];

    $absent_count = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT COUNT(*) total
        FROM attendance
        WHERE student_id='$selected_student'
        AND course_id='$course_id'
        AND status='Absent'
    "))['total'];

    $late_count = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT COUNT(*) total
        FROM attendance
        WHERE student_id='$selected_student'
        AND course_id='$course_id'
        AND status='Late'
    "))['total'];

    $total = $present_count + $absent_count + $late_count;

    $rate = $total > 0 ? round((($present_count + $late_count) / $total) * 100) : 0;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Student Attendance Details</title>

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

.section{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    margin-bottom:25px;
}

.student-table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
    background:white;
    border-radius:16px;
    overflow:hidden;
}

.student-table th{
    background:#eef4ff;
    color:#1a237e;
    padding:16px;
    text-align:left;
    font-weight:bold;
}

.student-table td{
    padding:16px;
    border-bottom:1px solid #e5e7eb;
}

.student-table tr:hover{
    background:#f8fbff;
}

.view-btn{
    text-decoration:none;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:white;
    padding:9px 16px;
    border-radius:10px;
    font-size:14px;
    font-weight:bold;
    transition:0.3s;
}

.view-btn:hover{
    opacity:0.9;
}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
    margin-bottom:20px;
}

.card{
    padding:20px;
    border-radius:16px;
    color:white;
}

.green{
    background:#2e7d32;
}

.red{
    background:#c62828;
}

.orange{
    background:#ef6c00;
}

.blue{
    background:#1565c0;
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

.present{
    color:green;
    font-weight:bold;
}

.absent{
    color:red;
    font-weight:bold;
}

.late{
    color:orange;
    font-weight:bold;
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

<div class="section">
    <h2>Students - <?php echo $courseInfo['course_name']; ?></h2>

    <table class="student-table">
        <tr>
            <th>#</th>
            <th>Student ID</th>
            <th>Full Name</th>
            <th>Action</th>
        </tr>

        <?php
        $count = 1;
        while($student = mysqli_fetch_assoc($students)){
        ?>
        <tr>
            <td><?php echo $count++; ?></td>
            <td><?php echo $student['student_id']; ?></td>
            <td><?php echo $student['first_name']." ".$student['last_name']; ?></td>
            <td>
                <a class="view-btn"
                   href="?student_id=<?php echo $student['student_id']; ?>">
                   View Details
                </a>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

<?php if($selected_student){ ?>

<div class="section">
    <h2>
        <?php echo $studentInfo['first_name']." ".$studentInfo['last_name']; ?>
    </h2>
</div>

<div class="cards">
    <div class="card green">
        <h3>Present</h3>
        <p><?php echo $present_count; ?></p>
    </div>

    <div class="card red">
        <h3>Absent</h3>
        <p><?php echo $absent_count; ?></p>
    </div>

    <div class="card orange">
        <h3>Late</h3>
        <p><?php echo $late_count; ?></p>
    </div>

    <div class="card blue">
        <h3>Attendance Rate</h3>
        <p><?php echo $rate; ?>%</p>
    </div>
</div>

<div class="section">
    <h2>Attendance History</h2>
    <br>

    <table>
        <tr>
            <th>Date</th>
            <th>Status</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($attendanceHistory)){ ?>
        <tr>
            <td><?php echo $row['date']; ?></td>
            <td class="<?php echo strtolower($row['status']); ?>">
                <?php echo $row['status']; ?>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

<?php } ?>

</div>
</body>
</html>