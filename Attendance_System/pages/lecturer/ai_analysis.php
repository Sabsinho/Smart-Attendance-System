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

$currentPage = 'ai';

$responseText = "";
$userMessage = "";
$user_id = $_SESSION['user_id'];

$lecturerQuery = mysqli_query($conn,"
    SELECT lecturers.*
    FROM lecturers
    JOIN users ON users.lecturer_id = lecturers.id
    WHERE users.id='$user_id'
");

$lecturer = mysqli_fetch_assoc($lecturerQuery);

$faculty_id = $lecturer['faculty_id'];
$shift_id = $lecturer['shift_id'];
$course_id = $lecturer['course_id'];

$courseInfo = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT courses.course_name, courses.course_code
    FROM courses
    WHERE id='$course_id'
"));

if (isset($_POST['send'])) {

    $userMessage = mysqli_real_escape_string($conn, $_POST['message']);

    $totalStudents = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM students
        WHERE faculty_id='$faculty_id'
        AND shift_id='$shift_id'
    "))['total'];

    $presentToday = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM attendance
        JOIN students ON attendance.student_id = students.student_id
        WHERE attendance.course_id='$course_id'
        AND attendance.status='Present'
        AND students.faculty_id='$faculty_id'
        AND students.shift_id='$shift_id'
        AND attendance.attendance_date = CURDATE()
    "))['total'];

    $lateToday = mysqli_fetch_assoc(mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM attendance
        JOIN students ON attendance.student_id = students.student_id
        WHERE attendance.course_id='$course_id'
        AND attendance.status='Late'
        AND students.faculty_id='$faculty_id'
        AND students.shift_id='$shift_id'
        AND attendance.attendance_date = CURDATE()
    "))['total'];

    $absentToday = $totalStudents - ($presentToday + $lateToday);

    $attendanceSummary = "
Course Statistics:
Course: {$courseInfo['course_name']}
Course Code: {$courseInfo['course_code']}
Total Students: $totalStudents
Present Today: $presentToday
Late Today: $lateToday
Absent Today: $absentToday
";

    $studentQuery = mysqli_query($conn,"
        SELECT student_id, first_name, last_name
        FROM students
        WHERE faculty_id='$faculty_id'
        AND shift_id='$shift_id'
        LIMIT 50
    ");

    $studentList = "";

    while ($row = mysqli_fetch_assoc($studentQuery)) {
        $studentList .= $row['student_id'] . " - " .
                        $row['first_name'] . " " .
                        $row['last_name'] . "\n";
    }

    $attendanceQuery = mysqli_query($conn,"
        SELECT students.first_name,
               students.last_name,
               attendance.status,
               attendance.attendance_date
        FROM attendance
        JOIN students ON attendance.student_id = students.student_id
        WHERE attendance.course_id='$course_id'
        AND students.faculty_id='$faculty_id'
        AND students.shift_id='$shift_id'
        ORDER BY attendance.attendance_date DESC
        LIMIT 100
    ");

    $attendanceData = "";

    while ($row = mysqli_fetch_assoc($attendanceQuery)) {
        $attendanceData .= $row['first_name'] . " " .
                           $row['last_name'] . " | " .
                           $row['status'] . " | " .
                           $row['attendance_date'] . "\n";
    }

    $prompt = "
You are an AI assistant for Gollis University Smart Attendance System.

Only answer using this data.

$attendanceSummary

Student List:
$studentList

Attendance Records:
$attendanceData

User Question:
$userMessage
";

    $apiKey = "API KEY";

    $data = [
        "model" => "openai/gpt-4o-mini",
        "messages" => [
            [
                "role" => "user",
                "content" => $prompt
            ]
        ]
    ];

    $ch = curl_init("https://openrouter.ai/api/v1/chat/completions");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $apiKey,
        "Content-Type: application/json",
        "HTTP-Referer: http://localhost",
        "X-Title: Lecturer Attendance AI"
    ]);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($result, true);

    $responseText = $decoded['choices'][0]['message']['content'] ?? "AI failed to respond.";

    mysqli_query($conn,"
        INSERT INTO system_logs (user_id, action)
        VALUES ('{$_SESSION['user_id']}', 'Used Lecturer AI Analysis')
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lecturer AI Analysis</title>

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

.menu a{
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
    display:flex;
    flex-direction:column;
    height:100vh;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.admin-box{
    background:white;
    padding:14px 22px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    font-weight:bold;
    color:#283593;
}

.chat-box{
    flex:1;
    background:white;
    border-radius:18px;
    padding:25px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    overflow-y:auto;
    margin-bottom:20px;
}

.message{
    margin-bottom:20px;
}

.user-message{
    background:#e3f2fd;
    padding:15px;
    border-radius:14px;
    max-width:70%;
    margin-left:auto;
}

.bot-message{
    background:#f8fafc;
    padding:18px;
    border-radius:14px;
    line-height:1.7;
    white-space:pre-wrap;
    border:1px solid #e5e7eb;
    max-width:85%;
}

form{
    display:flex;
    gap:15px;
}

input[type="text"]{
    flex:1;
    padding:16px;
    border:1px solid #d1d5db;
    border-radius:14px;
    outline:none;
}

button{
    background:linear-gradient(135deg,#2196f3,#1565c0);
    color:white;
    border:none;
    padding:16px 24px;
    border-radius:14px;
    cursor:pointer;
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
        <a href="report.php">Report</a>
        <a href="ai_analysis.php" class="active">AI Analysis</a>
    </div>

    <div class="logout">
        <a href="../../includes/logout.php">Logout</a>
    </div>
</div>

<div class="main">

    <div class="topbar">
        <h1>AI Analysis</h1>
        <div class="admin-box"><?php echo $_SESSION['username']; ?></div>
    </div>

    <div class="chat-box">

        <?php if(!empty($userMessage)){ ?>

            <div class="message">
                <div class="user-message">
                    <?php echo htmlspecialchars($userMessage); ?>
                </div>
            </div>

            <div class="message">
                <div class="bot-message">
                    <?php echo htmlspecialchars($responseText); ?>
                </div>
            </div>

        <?php } else { ?>

            <div class="bot-message">
                Ask about your students, attendance trends, absentees, attendance percentages, or course statistics.
            </div>

        <?php } ?>

    </div>

    <form method="POST">
        <input type="text" name="message" placeholder="Ask anything about your class..." required>
        <button type="submit" name="send">Send</button>
    </form>

</div>

</body>
</html>