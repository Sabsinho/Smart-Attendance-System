<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id'])) {
    exit("Not logged in");
}

$student_id = $_POST['student_id'];
$course_id = $_POST['course_id'];

$date = date("Y-m-d");
$time = date("H:i:s");

$check = mysqli_query($conn, "
    SELECT id FROM attendance
    WHERE student_id='$student_id'
    AND course_id='$course_id'
    AND date='$date'
");

if (mysqli_num_rows($check) > 0) {

    mysqli_query($conn, "
        UPDATE attendance
        SET status='Present',
            date='$date',
            time='$time',
            attendance_date='$date',
            attendance_time='$time'
        WHERE student_id='$student_id'
        AND course_id='$course_id'
        AND date='$date'
    ");

    echo "Updated attendance";
} else {

    mysqli_query($conn, "
        INSERT INTO attendance
        (student_id, course_id, status, date, time, attendance_date, attendance_time)
        VALUES
        ('$student_id', '$course_id', 'Present', '$date', '$time', '$date', '$time')
    ");

    echo "Attendance saved";
}
?>