<?php
session_start();

$username = $_POST['username'];
$password = $_POST['password'];

/* contoh login manual (nanti bisa pakai database) */
if ($username == "admin" && $password == "admin123") {
    $_SESSION['login'] = true;
    $_SESSION['username'] = $username;

    header("Location: dashboard.php");
} else {
    echo "<script>alert('Login gagal!');window.location='login.php';</script>";
}