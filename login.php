<?php
session_start();

/* jika sudah login langsung ke dashboard */

if(isset($_SESSION['login'])){
header("Location: index.php?page=dashboard");
exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login daloRADIUS </title>

<style>

body{
font-family:Arial;
background:#2c3e50;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.login-box{
background:white;
padding:40px;
width:300px;
text-align:center;
border-radius:5px;
}

input{
width:100%;
padding:10px;
margin:10px 0;
}

button{
width:100%;
padding:10px;
background:#3498db;
color:white;
border:none;
}

</style>

</head>

<body>

<div class="login-box">

<h2>login page</h2>

<form method="POST">

<input type="text" name="username" placeholder="Username" required>

<input type="password" name="password" placeholder="Password" required>

<button type="submit" name="login">Login</button>

</form>

<?php

if(isset($_POST['login'])){

$username = $_POST['username'];
$password = $_POST['password'];

/* username dan password admin */

if($username=="admin" && $password=="admin123"){

$_SESSION['login']=true;

/* redirect ke dashboard */

header("Location: index.php?page=dashboard");
exit();

}else{

echo "<p style='color:red'>Login gagal</p>";

}

}

?>

</div>

</body>
</html>