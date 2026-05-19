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

$currentPage = 'courses';

$selectedFaculty = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$selectedSemester = isset($_GET['semester']) ? $_GET['semester'] : '';

$user_id = $_SESSION['user_id'];

function logAction($conn, $user_id, $action) {
    mysqli_query($conn, "INSERT INTO system_logs (user_id, action)
    VALUES ('$user_id', '$action')");
}

/* ADD COURSE */
if (isset($_POST['add_course'])) {

    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $semester_id = $_POST['semester_id'];
    $faculty_ids = $_POST['faculty_ids'];

    mysqli_query($conn, "INSERT INTO courses (course_name, course_code, semester_id)
    VALUES ('$course_name','TEMP','$semester_id')");

    $course_id = mysqli_insert_id($conn);
    $course_code = "CRS" . str_pad($course_id + 1000, 4, "0", STR_PAD_LEFT);

    mysqli_query($conn, "UPDATE courses 
    SET course_code='$course_code' 
    WHERE id='$course_id'");

    foreach ($faculty_ids as $faculty_id) {
        mysqli_query($conn, "INSERT INTO course_faculties (course_id, faculty_id)
        VALUES ('$course_id','$faculty_id')");
    }

    logAction($conn, $user_id, "Added course: $course_name ($course_code)");

    header("Location: courses.php");
    exit();
}

/* EDIT COURSE */
if (isset($_POST['edit_course'])) {

    $course_id = $_POST['course_id'];
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $semester_id = $_POST['semester_id'];
    $faculty_ids = $_POST['faculty_ids'];

    mysqli_query($conn, "
        UPDATE courses 
        SET course_name='$course_name', semester_id='$semester_id'
        WHERE id='$course_id'
    ");

    mysqli_query($conn, "DELETE FROM course_faculties WHERE course_id='$course_id'");

    foreach ($faculty_ids as $faculty_id) {
        mysqli_query($conn, "
            INSERT INTO course_faculties (course_id, faculty_id)
            VALUES ('$course_id','$faculty_id')
        ");
    }

    logAction($conn, $user_id, "Edited course: $course_name (ID: $course_id)");

    header("Location: courses.php");
    exit();
}

/* DELETE COURSE */
if (isset($_GET['delete'])) {

    $id = mysqli_real_escape_string($conn, $_GET['delete']);

    $course = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT course_name, course_code FROM courses WHERE id='$id'
    "));

    mysqli_query($conn, "UPDATE lecturers SET course_id=NULL WHERE course_id='$id'");
    mysqli_query($conn, "DELETE FROM course_faculties WHERE course_id='$id'");
    mysqli_query($conn, "DELETE FROM courses WHERE id='$id'");

    logAction($conn, $user_id, "Deleted course: {$course['course_name']} ({$course['course_code']})");

    header("Location: courses.php");
    exit();
}

/* FETCH DATA */
$query = "
SELECT DISTINCT courses.*
FROM courses
LEFT JOIN course_faculties ON courses.id = course_faculties.course_id
WHERE 1=1
";

if ($selectedFaculty != '') {
    $query .= " AND course_faculties.faculty_id='$selectedFaculty'";
}

if ($selectedSemester != '') {
    $query .= " AND courses.semester_id='$selectedSemester'";
}

$query .= " ORDER BY courses.id DESC";

$courses = mysqli_query($conn, $query);
$faculties = mysqli_query($conn, "SELECT * FROM faculties");
$semesters = mysqli_query($conn, "SELECT * FROM semesters");

function getCourseFaculties($conn, $courseId) {
    $names = [];

    $result = mysqli_query($conn, "
        SELECT faculties.faculty_name
        FROM course_faculties
        JOIN faculties ON faculties.id = course_faculties.faculty_id
        WHERE course_faculties.course_id='$courseId'
    ");

    while ($row = mysqli_fetch_assoc($result)) {
        $names[] = $row['faculty_name'];
    }

    return implode(", ", $names);
}

function getSemesterName($conn, $semesterId) {
    $result = mysqli_query($conn, "SELECT semester_name FROM semesters WHERE id='$semesterId'");
    $row = mysqli_fetch_assoc($result);
    return $row ? $row['semester_name'] : '';
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Courses Management</title>

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

.add-btn{
    background:linear-gradient(135deg,#2196f3,#1565c0);
    color:white;
    padding:14px 22px;
    border:none;
    border-radius:12px;
    cursor:pointer;
    font-weight:bold;
}

.filters{
    display:flex;
    gap:15px;
    margin-bottom:20px;
    align-items:center;
}

.filters select{
    padding:12px;
    border:1px solid #ddd;
    border-radius:10px;
    min-width:200px;
}

.table-container{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
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
    border:none;
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
    margin:50px auto;
}

input,select{
    width:100%;
    padding:12px;
    margin:8px 0;
    border:1px solid #ddd;
    border-radius:10px;
}

.multi-select{
    height:120px;
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
    margin-top:12px;
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
        <h1>Courses Management</h1>
        <div class="admin-box"><?php echo $_SESSION['username']; ?></div>
    </div>

    <div style="margin-bottom:20px;">
        <button class="add-btn" onclick="openModal()">+ Add Course</button>
    </div>

    <form method="GET" class="filters">
        <select name="faculty">
            <option value="">All Faculties</option>
            <?php mysqli_data_seek($faculties,0); while($f=mysqli_fetch_assoc($faculties)){ ?>
                <option value="<?php echo $f['id']; ?>" <?php if($selectedFaculty==$f['id']) echo "selected"; ?>>
                    <?php echo $f['faculty_name']; ?>
                </option>
            <?php } ?>
        </select>

        <select name="semester">
            <option value="">All Semesters</option>
            <?php mysqli_data_seek($semesters,0); while($s=mysqli_fetch_assoc($semesters)){ ?>
                <option value="<?php echo $s['id']; ?>" <?php if($selectedSemester==$s['id']) echo "selected"; ?>>
                    <?php echo $s['semester_name']; ?>
                </option>
            <?php } ?>
        </select>

        <button class="add-btn" type="submit">Search</button>
    </form>

    <div class="table-container">
        <table>
            <tr>
                <th>Course Code</th>
                <th>Course Name</th>
                <th>Faculties</th>
                <th>Semester</th>
                <th>Actions</th>
            </tr>

            <?php while($row=mysqli_fetch_assoc($courses)){ ?>
            <tr>
                <td><?php echo $row['course_code']; ?></td>
                <td><?php echo $row['course_name']; ?></td>
                <td><?php echo getCourseFaculties($conn,$row['id']); ?></td>
                <td><?php echo getSemesterName($conn,$row['semester_id']); ?></td>
                <td>
                    <button class="edit-btn" onclick="openEditModal(
                        '<?php echo $row['id']; ?>',
                        '<?php echo $row['course_name']; ?>',
                        '<?php echo $row['semester_id']; ?>'
                    )">Edit</button>

                    <a class="delete-btn" href="courses.php?delete=<?php echo $row['id']; ?>">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

</div>

<div id="modal" class="modal">
    <div class="modal-content">
        <h2>Add Course</h2>

        <form method="POST">
            <input type="text" name="course_name" placeholder="Course Name" required>

            <select name="semester_id" required>
                <option value="">Select Semester</option>
                <?php mysqli_data_seek($semesters,0); while($s=mysqli_fetch_assoc($semesters)){ ?>
                    <option value="<?php echo $s['id']; ?>">
                        <?php echo $s['semester_name']; ?>
                    </option>
                <?php } ?>
            </select>

            <select class="multi-select" name="faculty_ids[]" multiple required>
                <?php mysqli_data_seek($faculties,0); while($f=mysqli_fetch_assoc($faculties)){ ?>
                    <option value="<?php echo $f['id']; ?>">
                        <?php echo $f['faculty_name']; ?>
                    </option>
                <?php } ?>
            </select>

            <button class="save-btn" type="submit" name="add_course">Save Course</button>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h2>Edit Course</h2>

        <form method="POST">
            <input type="hidden" name="course_id" id="edit_course_id">

            <input type="text" name="course_name" id="edit_course_name" required>

            <select name="semester_id" id="edit_semester_id" required>
                <?php mysqli_data_seek($semesters,0); while($s=mysqli_fetch_assoc($semesters)){ ?>
                    <option value="<?php echo $s['id']; ?>">
                        <?php echo $s['semester_name']; ?>
                    </option>
                <?php } ?>
            </select>

            <select class="multi-select" name="faculty_ids[]" id="edit_faculties" multiple required>
                <?php mysqli_data_seek($faculties,0); while($f=mysqli_fetch_assoc($faculties)){ ?>
                    <option value="<?php echo $f['id']; ?>">
                        <?php echo $f['faculty_name']; ?>
                    </option>
                <?php } ?>
            </select>

            <button class="save-btn" type="submit" name="edit_course">Update Course</button>
        </form>
    </div>
</div>

<script>
function openModal(){
    document.getElementById("modal").style.display = "block";
}

function openEditModal(id, name, semesterId){
    document.getElementById("editModal").style.display = "block";
    document.getElementById("edit_course_id").value = id;
    document.getElementById("edit_course_name").value = name;
    document.getElementById("edit_semester_id").value = semesterId;
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