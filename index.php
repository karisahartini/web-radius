<?php
session_start();

/* cek login */
if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

/* menentukan halaman */
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html>
<head>
<title>Web RADIUS</title>

<style>

body{
margin:0;
font-family:Arial;
background:#f5f9ff;
}

/* navbar */

.navbar{
background:#3498db;
color:white;
padding:15px;
font-size:20px;
}

/* layout */

.container{
display:flex;
}

/* sidebar */

.sidebar{
width:200px;
background:#eaf6ff;
height:100vh;
padding-top:20px;
}

.sidebar a{
display:block;
padding:12px 20px;
color:#2c3e50;
text-decoration:none;
}

.sidebar a:hover{
background:#d6ecff;
}

/* content */

.content{
flex:1;
padding:20px;
}

</style>

</head>

<body>

<div class="navbar">
Web Radius Admin
</div>

<div class="container">

<div class="sidebar">

<a href="index.php?page=dashboard">Dashboard</a>
<a href="users.php">Users</a>
<a href="#">NAS</a>
<a href="#">Reports</a>
<a href="logout.php">Logout</a>

</div>

<div class="content">

<?php

/* load halaman */

if($page == "dashboard"){
include "dashboard/dashboard.php";
}

?>

</div>

</div>

</body>
</html>