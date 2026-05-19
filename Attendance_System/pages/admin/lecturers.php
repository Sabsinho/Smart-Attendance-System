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

$currentPage = 'lecturers';

function addLog($conn, $action) {
    $user_id = $_SESSION['user_id'];
    $action = mysqli_real_escape_string($conn, $action);

    mysqli_query($conn, "
        INSERT INTO system_logs (user_id, action)
        VALUES ('$user_id', '$action')
    ");
}

if (isset($_POST['add_lecturer'])) {

    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    $upload_dir = "../../uploads/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $portrait_path = "";

    if (!empty($_FILES['portrait']['name'])) {
        $image_name = time() . "_" . basename($_FILES['portrait']['name']);
        $tmp_name = $_FILES['portrait']['tmp_name'];
        $target = $upload_dir . $image_name;

        move_uploaded_file($tmp_name, $target);
        $portrait_path = "../../uploads/" . $image_name;
    }

    mysqli_query($conn, "
        INSERT INTO lecturers
        (first_name,last_name,email,phone,course_id,portrait_path)
        VALUES
        ('$first_name','$last_name','$email','$phone','$course_id','$portrait_path')
    ");

    $lecturer_id = mysqli_insert_id($conn);

    $staff_id = "LEC" . str_pad($lecturer_id + 1000, 4, "0", STR_PAD_LEFT);

    mysqli_query($conn, "
        UPDATE lecturers
        SET staff_id='$staff_id'
        WHERE id='$lecturer_id'
    ");

    mysqli_query($conn, "
        INSERT INTO users
        (username,password,role,lecturer_id)
        VALUES
        ('$username','$password','lecturer','$lecturer_id')
    ");

    addLog($conn, "Added lecturer: $first_name $last_name ($staff_id)");

    header("Location: lecturers.php");
    exit();
}

if (isset($_POST['edit_lecturer'])) {

    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);

    mysqli_query($conn, "
        UPDATE lecturers SET
        first_name='$first_name',
        last_name='$last_name',
        email='$email',
        phone='$phone',
        course_id='$course_id'
        WHERE id='$id'
    ");

    $staff = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT staff_id FROM lecturers WHERE id='$id'
    "));

    addLog($conn, "Edited lecturer: $first_name $last_name ({$staff['staff_id']})");

    header("Location: lecturers.php");
    exit();
}

if (isset($_GET['delete'])) {

    $id = mysqli_real_escape_string($conn, $_GET['delete']);

    $lecturer = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT first_name,last_name,staff_id
        FROM lecturers
        WHERE id='$id'
    "));

    mysqli_query($conn, "DELETE FROM users WHERE lecturer_id='$id'");
    mysqli_query($conn, "DELETE FROM lecturers WHERE id='$id'");

    addLog($conn, "Deleted lecturer: {$lecturer['first_name']} {$lecturer['last_name']} ({$lecturer['staff_id']})");

    header("Location: lecturers.php");
    exit();
}

$lecturers = mysqli_query($conn, "
    SELECT lecturers.*, courses.course_name
    FROM lecturers
    LEFT JOIN courses ON lecturers.course_id = courses.id
    ORDER BY lecturers.id DESC
");

$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY course_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lecturers Management</title>

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
    padding:14px 22px;
    border:none;
    border-radius:12px;
    cursor:pointer;
    font-weight:bold;
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

.lecturer-img{
    width:55px;
    height:55px;
    border-radius:50%;
    object-fit:cover;
}

.edit-btn{
    background:#fb8c00;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:8px;
    cursor:pointer;
    margin-right:6px;
}

.delete-btn{
    background:#e53935;
    color:white;
    padding:8px 14px;
    border-radius:8px;
    text-decoration:none;
}

.modal{
    display:none;
    position:fixed;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.4);
}

.modal-content{
    background:white;
    width:500px;
    padding:25px;
    border-radius:18px;
    margin:40px auto;
}

input,select{
    width:100%;
    padding:12px;
    margin:8px 0;
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
        <h1>Lecturers Management</h1>
        <div style="display:flex;gap:15px;align-items:center;">
            <button class="add-btn" onclick="openModal()">+ Add Lecturer</button>
            <div class="admin-box"><?php echo $_SESSION['username']; ?></div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Staff ID</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Course</th>
                <th>Actions</th>
            </tr>

            <?php while($row=mysqli_fetch_assoc($lecturers)) { ?>
            <tr>
                <td><img class="lecturer-img" src="<?php echo $row['portrait_path']; ?>"></td>
                <td><?php echo $row['first_name']." ".$row['last_name']; ?></td>
                <td><?php echo $row['staff_id']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['phone']; ?></td>
                <td><?php echo $row['course_name']; ?></td>
                <td>
                    <button class="edit-btn" onclick="openEditModal(
                    '<?php echo $row['id']; ?>',
                    '<?php echo $row['first_name']; ?>',
                    '<?php echo $row['last_name']; ?>',
                    '<?php echo $row['email']; ?>',
                    '<?php echo $row['phone']; ?>',
                    '<?php echo $row['course_id']; ?>'
                    )">Edit</button>

                    <a class="delete-btn" href="lecturers.php?delete=<?php echo $row['id']; ?>">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="modal" class="modal">
<div class="modal-content">
<h2>Add Lecturer</h2>

<form method="POST" enctype="multipart/form-data">
<input type="text" name="first_name" placeholder="First Name" required>
<input type="text" name="last_name" placeholder="Last Name" required>
<input type="email" name="email" placeholder="Email">
<input type="text" name="phone" placeholder="Phone">

<select name="course_id" required>
<option value="">Select Course</option>
<?php mysqli_data_seek($courses,0); while($c=mysqli_fetch_assoc($courses)){ ?>
<option value="<?php echo $c['id']; ?>"><?php echo $c['course_name']; ?></option>
<?php } ?>
</select>

<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<input type="file" name="portrait">

<button class="save-btn" type="submit" name="add_lecturer">Save Lecturer</button>
</form>
</div>
</div>

<div id="editModal" class="modal">
<div class="modal-content">
<h2>Edit Lecturer</h2>

<form method="POST">
<input type="hidden" name="id" id="edit_id">
<input type="text" name="first_name" id="edit_first_name" required>
<input type="text" name="last_name" id="edit_last_name" required>
<input type="email" name="email" id="edit_email">
<input type="text" name="phone" id="edit_phone">

<select name="course_id" id="edit_course_id">
<?php mysqli_data_seek($courses,0); while($c=mysqli_fetch_assoc($courses)){ ?>
<option value="<?php echo $c['id']; ?>"><?php echo $c['course_name']; ?></option>
<?php } ?>
</select>

<button class="save-btn" type="submit" name="edit_lecturer">Update Lecturer</button>
</form>
</div>
</div>

<script>
function openModal(){
    document.getElementById("modal").style.display = "block";
}

function openEditModal(id, first, last, email, phone, course){
    document.getElementById("editModal").style.display = "block";
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_first_name").value = first;
    document.getElementById("edit_last_name").value = last;
    document.getElementById("edit_email").value = email;
    document.getElementById("edit_phone").value = phone;
    document.getElementById("edit_course_id").value = course;
}

window.onclick = function(event){
    if(event.target == document.getElementById("modal")){
        document.getElementById("modal").style.display = "none";
    }

    if(event.target == document.getElementById("editModal")){
        document.getElementById("editModal").style.display = "none";
    }
}
</script>

</body>
</html>