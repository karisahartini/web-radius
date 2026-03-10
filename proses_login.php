<?php
session_start();

/* CEK APAKAH FORM DISUBMIT */
if (!isset($_POST['username'], $_POST['password'])) {
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);

/* LOGIN SEDERHANA */
if ($username === "admin" && $password === "admin") {

    $_SESSION['login']    = true;
    $_SESSION['username'] = $username;

    header("Location: dashboard.php");
    exit;

} else {
    echo "<script>
        alert('Username atau Password salah!');
        window.location.href = 'login.php';
    </script>";
    exit;
}