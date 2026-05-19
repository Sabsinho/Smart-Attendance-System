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

$faculty_id = $_GET['faculty_id'];
$shift_id = $_GET['shift_id'];
$course_id = $_GET['course_id'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Camera Attendance</title>

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

/* SIDEBAR */
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
    overflow:hidden;
}

.logo-circle img{
    width:100%;
    height:100%;
    object-fit:cover;
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
}

/* MAIN */
.main{
    margin-left:270px;
    width:calc(100% - 270px);
    padding:35px;
}

.section{
    background:white;
    padding:30px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    text-align:center;
}

.camera-btn{
    background:#2e7d32;
    color:white;
    border:none;
    padding:16px 20px;
    width:260px;
    border-radius:12px;
    font-size:16px;
    cursor:pointer;
    margin-top:20px;
}

.camera-btn:hover{
    background:#1b5e20;
}

.preview{
    margin-top:20px;
    width:100%;
    max-width:400px;
    border-radius:12px;
    display:none;
}

.result{
    margin-top:20px;
    font-weight:bold;
    color:#283593;
}

#imageInput{
    display:none;
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

<div class="section">

<h2>Camera Attendance</h2>
<p>Scan student face using phone camera</p>

<input type="file" id="imageInput" accept="image/*" capture="environment">

<button class="camera-btn" onclick="openCamera()">
    📷 Open Camera & Scan Face
</button>

<img id="preview" class="preview">

<div class="result" id="result">Waiting for scan...</div>

</div>

</div>

<script>
function openCamera(){
    document.getElementById("imageInput").click();
}

document.getElementById("imageInput").addEventListener("change", function(){

    let file = this.files[0];
    if(!file) return;

    let reader = new FileReader();

    reader.onloadend = function(){

        let base64 = reader.result.split(",")[1];

        document.getElementById("preview").src = reader.result;
        document.getElementById("preview").style.display = "block";

        document.getElementById("result").innerHTML = "Scanning face...";

        fetch("http://192.168.100.15:5000/recognize", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                image: base64
            })
        })
        .then(res => res.json())
        .then(data => {

            if(data.success && data.student_id && data.student_id !== "Unknown"){

                document.getElementById("result").innerHTML =
                    "Detected: " + data.student_id + " (Saving...)";

                /* AUTO SAVE ATTENDANCE */
                fetch("save_attendance.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "student_id=" + data.student_id +
                          "&course_id=<?php echo $course_id; ?>"
                })
                .then(res => res.text())
                .then(msg => {
                    document.getElementById("result").innerHTML = msg;
                });

            } else {
                document.getElementById("result").innerHTML =
                    "Face not recognized";
            }

        })
        .catch(err => {
            document.getElementById("result").innerHTML =
                "API not running or blocked";
        });

    };

    reader.readAsDataURL(file);
});
</script>

</body>
</html>