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

$currentPage = 'students';

if (isset($_POST['add_student'])) {

    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $enrollment_year = mysqli_real_escape_string($conn, $_POST['enrollment_year']);
    $semester_id = mysqli_real_escape_string($conn, $_POST['semester_id']);
    $faculty_id = mysqli_real_escape_string($conn, $_POST['faculty_id']);
    $shift_id = mysqli_real_escape_string($conn, $_POST['shift_id']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $portrait_path = "";

    if (!empty($_FILES['portrait']['name'])) {

        $upload_dir = "../../uploads/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $image_name = time() . "_" . basename($_FILES['portrait']['name']);
        $tmp_name = $_FILES['portrait']['tmp_name'];
        $target = $upload_dir . $image_name;

        move_uploaded_file($tmp_name, $target);

        $portrait_path = "../../uploads/" . $image_name;
    }

    mysqli_query($conn, "INSERT INTO students
    (student_id, first_name, last_name, enrollment_year, semester_id, faculty_id, shift_id, phone, address, portrait_path)
    VALUES
    ('$student_id','$first_name','$last_name','$enrollment_year','$semester_id','$faculty_id','$shift_id','$phone','$address','$portrait_path')");

    $user_id = $_SESSION['user_id'];
    $action = "Added student: $first_name $last_name ($student_id)";

    mysqli_query($conn, "INSERT INTO system_logs (user_id, action)
    VALUES ('$user_id','$action')");

    header("Location: students.php");
    exit();
}

if (isset($_GET['delete'])) {

    $id = mysqli_real_escape_string($conn, $_GET['delete']);

    $student = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT first_name, last_name, student_id
        FROM students
        WHERE id='$id'
    "));

    mysqli_query($conn, "DELETE FROM students WHERE id='$id'");

    $user_id = $_SESSION['user_id'];
    $action = "Deleted student: {$student['first_name']} {$student['last_name']} ({$student['student_id']})";

    mysqli_query($conn, "INSERT INTO system_logs (user_id, action)
    VALUES ('$user_id','$action')");

    header("Location: students.php");
    exit();
}

if (isset($_POST['edit_student'])) {

    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $enrollment_year = mysqli_real_escape_string($conn, $_POST['enrollment_year']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    mysqli_query($conn, "UPDATE students SET
        student_id='$student_id',
        first_name='$first_name',
        last_name='$last_name',
        enrollment_year='$enrollment_year',
        phone='$phone',
        address='$address'
        WHERE id='$id'
    ");

    $user_id = $_SESSION['user_id'];
    $action = "Edited student: $first_name $last_name ($student_id)";

    mysqli_query($conn, "INSERT INTO system_logs (user_id, action)
    VALUES ('$user_id','$action')");

    header("Location: students.php");
    exit();
}

$students = mysqli_query($conn, "SELECT * FROM students ORDER BY id DESC");
$faculties = mysqli_query($conn, "SELECT * FROM faculties");
$semesters = mysqli_query($conn, "SELECT * FROM semesters");
$shifts = mysqli_query($conn, "SELECT * FROM shifts");
?>

<!DOCTYPE html>
<html>
<head>
<title>Students Management</title>

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
    opacity:0.9;
    transition:0.3s;
}

.menu a:hover,
.logout a:hover{
    background:rgba(255,255,255,0.08);
    border-left:4px solid #4dd0e1;
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

.main{
    margin-left:270px;
    width:calc(100% - 270px);
    padding:35px;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.topbar h1{
    font-size:30px;
    color:#1f2937;
}

.admin-box{
    background:white;
    padding:14px 22px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    font-weight:bold;
    color:#283593;
}

.add-btn{
    background:linear-gradient(135deg,#2196f3,#1565c0);
    color:white;
    border:none;
    padding:14px 22px;
    border-radius:12px;
    cursor:pointer;
    font-weight:bold;
    margin-bottom:25px;
}

.table-container{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#eef4ff;
    padding:15px;
    text-align:left;
}

td{
    padding:15px;
    border-bottom:1px solid #eee;
}

.student-img{
    width:55px;
    height:55px;
    border-radius:50%;
    object-fit:cover;
}

.action-btn{
    padding:8px 14px;
    border:none;
    border-radius:8px;
    color:white;
    cursor:pointer;
    text-decoration:none;
    font-size:13px;
}

.edit{ background:#fb8c00; }
.delete{ background:#e53935; }

.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.45);
}

.modal-content{
    background:white;
    width:500px;
    padding:25px;
    border-radius:18px;
    margin:50px auto;
    max-height:85vh;
    overflow:auto;
}

input, select, textarea{
    width:100%;
    padding:12px;
    margin:7px 0;
    border:1px solid #ddd;
    border-radius:10px;
}

.save-btn{
    width:100%;
    padding:13px;
    border:none;
    border-radius:10px;
    background:#1565c0;
    color:white;
    font-weight:bold;
    cursor:pointer;
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
        <a href="dashboard.php" class="<?php if($currentPage=='dashboard') echo 'active'; ?>">Dashboard</a>
        <a href="students.php" class="<?php if($currentPage=='students') echo 'active'; ?>">Students</a>
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
        <h1>Students Management</h1>
        <div class="admin-box"><?php echo $_SESSION['username']; ?></div>
    </div>

    <button class="add-btn" onclick="openModal()">+ Add New Student</button>

    <div class="table-container">
        <table>
            <tr>
                <th>Photo</th>
                <th>ID</th>
                <th>Name</th>
                <th>Enrollment</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>

            <?php while($row=mysqli_fetch_assoc($students)){ ?>
            <tr>
                <td><img class="student-img" src="<?php echo $row['portrait_path']; ?>"></td>
                <td><?php echo $row['student_id']; ?></td>
                <td><?php echo $row['first_name']." ".$row['last_name']; ?></td>
                <td><?php echo $row['enrollment_year']; ?></td>
                <td><?php echo $row['phone']; ?></td>
                <td><?php echo $row['address']; ?></td>
                <td>
                    <button class="action-btn edit" onclick="openEditModal(
                    '<?php echo $row['id']; ?>',
                    '<?php echo $row['student_id']; ?>',
                    '<?php echo $row['first_name']; ?>',
                    '<?php echo $row['last_name']; ?>',
                    '<?php echo $row['enrollment_year']; ?>',
                    '<?php echo $row['phone']; ?>',
                    '<?php echo $row['address']; ?>'
                    )">Edit</button>

                    <a class="action-btn delete" href="students.php?delete=<?php echo $row['id']; ?>">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="studentModal" class="modal">
<div class="modal-content">
<form method="POST" enctype="multipart/form-data">
<input type="text" name="student_id" placeholder="Student ID" required>
<input type="text" name="first_name" placeholder="First Name" required>
<input type="text" name="last_name" placeholder="Last Name" required>
<input type="number" name="enrollment_year" placeholder="Enrollment Year">
<select name="semester_id">
<?php mysqli_data_seek($semesters,0); while($s=mysqli_fetch_assoc($semesters)){ ?>
<option value="<?php echo $s['id']; ?>"><?php echo $s['semester_name']; ?></option>
<?php } ?>
</select>
<select name="faculty_id">
<?php mysqli_data_seek($faculties,0); while($f=mysqli_fetch_assoc($faculties)){ ?>
<option value="<?php echo $f['id']; ?>"><?php echo $f['faculty_name']; ?></option>
<?php } ?>
</select>
<select name="shift_id">
<?php mysqli_data_seek($shifts,0); while($sh=mysqli_fetch_assoc($shifts)){ ?>
<option value="<?php echo $sh['id']; ?>"><?php echo $sh['shift_name']; ?></option>
<?php } ?>
</select>
<input type="text" name="phone" placeholder="Phone">
<textarea name="address" placeholder="Address"></textarea>
<input type="file" name="portrait">
<button class="save-btn" type="submit" name="add_student">Save Student</button>
</form>
</div>
</div>

<div id="editModal" class="modal">
<div class="modal-content">
<form method="POST">
<input type="hidden" name="id" id="edit_id">
<input type="text" name="student_id" id="edit_student_id">
<input type="text" name="first_name" id="edit_first_name">
<input type="text" name="last_name" id="edit_last_name">
<input type="number" name="enrollment_year" id="edit_year">
<input type="text" name="phone" id="edit_phone">
<textarea name="address" id="edit_address"></textarea>
<button class="save-btn" type="submit" name="edit_student">Update Student</button>
</form>
</div>
</div>

<script>
function openModal(){
    document.getElementById("studentModal").style.display = "block";
}

function openEditModal(id, studentId, first, last, year, phone, address){
    document.getElementById("editModal").style.display = "block";
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_student_id").value = studentId;
    document.getElementById("edit_first_name").value = first;
    document.getElementById("edit_last_name").value = last;
    document.getElementById("edit_year").value = year;
    document.getElementById("edit_phone").value = phone;
    document.getElementById("edit_address").value = address;
}

window.onclick = function(event){
    if(event.target.classList.contains("modal")){
        event.target.style.display = "none";
    }
}
</script>

</body>
</html>