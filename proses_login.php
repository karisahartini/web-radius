<?php
session_start();

$username = $_POST['username'];
$password = $_POST['password'];

if ($username === "admin" && $password === "admin") {

    $_SESSION['login'] = true;
    $_SESSION['username'] = $username;

    header("Location: index.php");
    exit;

} else {
    echo "<script>
        alert('Username atau Password salah!');
        window.location='login.php';
    </script>";
}
?>