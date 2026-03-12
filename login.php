<?php
session_start();

if(isset($_POST['login'])){

$username = $_POST['username'];
$password = $_POST['password'];

if($username=="admin" && $password=="12345"){

$_SESSION['login'] = true;

header("Location: index.php?page=dashboard");
exit();

}else{
$error = "Username atau Password salah!";
}

}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login web Radius</title>
</head>

<body>

<h2>Login Page</h2>

<?php if(isset($error)){ echo $error; } ?>

<form method="post">

<input type="text" name="username" placeholder="Username" required>
<br><br>

<input type="password" name="password" placeholder="Password" required>
<br><br>

<button type="submit" name="login">Login</button>

</form>

</body>
</html>