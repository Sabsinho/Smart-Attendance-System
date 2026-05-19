# Attendance Management System (Face Recognition Based)

## Project Overview
This is a web-based Attendance Management System developed for lecturers and students.  
It allows lecturers to take attendance manually or using **facial recognition through a camera system**.

The system is built using:
- PHP (Frontend + Backend)
- MySQL (Database)
- Python (Face Recognition API)
- HTML, CSS, JavaScript

It supports:
- Lecturer login system
- Course-based attendance tracking
- Manual attendance marking
- AI-based facial recognition attendance
- Student attendance history and analytics

---

## ⚙️ Features

### 👨‍🏫 Lecturer Side
- Login authentication
- View assigned courses
- Take attendance manually
- Take attendance using camera (AI recognition)
- View reports per course
- View student attendance history

### 🎓 Student Side
- Stored in database per semester/faculty/shift
- Attendance tracked per course
- Individual attendance history

### 🤖 AI Face Recognition
- Uses Python `face_recognition`
- Matches student face with stored images
- Automatically marks attendance in database

---

## 🧠 System Architecture


Frontend (PHP + HTML + CSS)
↓
MySQL Database
↓
Python Face Recognition API (Flask)
↓
Camera Input → Face Detection → Attendance Marking


---

## 🚀 How to Run the Project

### 1. Clone or extract project
Place it inside:

C:\xampp\htdocs\Attendance_System


---

### 2. Start XAMPP
- Start **Apache**
- Start **MySQL**

---

### 3. Setup Database
- Open phpMyAdmin
- Import the project database file:

attendance_system.sql


---

### 4. Configure Database Connection
Edit:

config/db.php


Make sure:
```php
$conn = mysqli_connect("localhost", "root", "", "attendance_system");
5. Run Python Face Recognition API

Go to:

Attendance_System/python

Install dependencies:

pip install flask numpy face_recognition cmake dlib
pip install face-recognition-models

Run server:

python face_recognition_api.py

It should run on:

http://127.0.0.1:5000
6. Run Website

Open in browser:

http://localhost/Attendance_System
📷 Face Recognition Setup
Go to:
python/known_faces/
Add student images like:
CST001.jpg
CST002.jpg
CST003.jpg

⚠️ The filename MUST match the student ID in the database.

👥 Group Members
1. Mustafe Hussein Addour
2. Ahmed-Shakir Khadar Abdi
3. Khadar Mohamed Abdi
4. Samatar Mohamed Ahmed
5. Abdirahman Bashir Ibrahim
6. Sabir Abdirahman Osman
7. Hamse Mohamed Ali
