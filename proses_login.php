<?php
session_start();

$username = $_POST['username'];
$password = $_POST['password'];

/* CONTOH LOGIN SEDERHANA */
if ($username === "admin" && $password === "admin") {

    $_SESSION['login'] = true;
    $_SESSION['username'] = $username;

    header("Location: dashboard/dashboard.php");
    exit;

} else {
    echo "<script>
        alert('Username atau Password salah!');
        window.location='login.php';
    </script>";
}