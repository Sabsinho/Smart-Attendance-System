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

$currentPage = 'ai';

$responseText = "";
$userMessage = "";

if (isset($_POST['send'])) {

    $userMessage = mysqli_real_escape_string($conn, $_POST['message']);

    $totalStudents = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) as total FROM students")
    )['total'];

    $totalLecturers = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) as total FROM lecturers")
    )['total'];

    $totalCourses = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) as total FROM courses")
    )['total'];

    $presentToday = mysqli_fetch_assoc(
        mysqli_query($conn,"
            SELECT COUNT(*) as total
            FROM attendance
            WHERE attendance_date = CURDATE()
            AND status='Present'
        ")
    )['total'];

    $lateToday = mysqli_fetch_assoc(
        mysqli_query($conn,"
            SELECT COUNT(*) as total
            FROM attendance
            WHERE attendance_date = CURDATE()
            AND status='Late'
        ")
    )['total'];

    $absentToday = $totalStudents - ($presentToday + $lateToday);

    $attendanceSummary = "
System Statistics:
Total Students: $totalStudents
Total Lecturers: $totalLecturers
Total Courses: $totalCourses
Present Today: $presentToday
Late Today: $lateToday
Absent Today: $absentToday
";

    $studentQuery = mysqli_query($conn,"
        SELECT student_id, first_name, last_name
        FROM students
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
        JOIN students
        ON attendance.student_id = students.student_id
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
You are an AI assistant for Gollis University Smart Attendance Management System.

Use ONLY the provided data.

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
        "X-Title: Gollis Attendance AI"
    ]);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($result, true);

    $responseText = $decoded['choices'][0]['message']['content'] ?? "AI failed to respond.";

    mysqli_query($conn,"
        INSERT INTO system_logs (user_id, action)
        VALUES ('{$_SESSION['user_id']}', 'Used AI assistant')
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Analysis</title>

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
    font-size:15px;
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
        <h1>AI Analysis</h1>
        <div class="admin-box">
            <?php echo $_SESSION['username']; ?>
        </div>
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
                Ask anything about students, attendance, absentees, lecturers, courses, or reports.
            </div>

        <?php } ?>

    </div>

    <form method="POST">
        <input type="text" name="message" placeholder="Ask anything about the system..." required>
        <button type="submit" name="send">Send</button>
    </form>

</div>

</body>
</html>