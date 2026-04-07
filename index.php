<?php
session_start();

/* cek login */
if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

/* menentukan halaman */
$page = isset($_GET['page']) ? strtolower($_GET['page']) : 'dashboard';
?>

<!DOCTYPE html>
<html>
<head>
<title>Web RADIUS</title>

<style>

body{
margin:0;
font-family:Arial;
background:#0f1e2d;
}

/* navbar */

.navbar{
background:#1a2a3a;
color:white;
padding:15px;
font-size:20px;
border-bottom:1px solid #2a3f55;
}

/* layout */

.container{
display:flex;
}

/* sidebar */

.sidebar{
width:200px;
background:#1a2a3a;
min-height:100vh;
padding-top:20px;
border-right:1px solid #2a3f55;
flex-shrink:0;
}

.sidebar a{
display:block;
padding:12px 20px;
color:#7f8ea3;
text-decoration:none;
}

.sidebar a:hover{
background:#2a3f55;
color:white;
}

.sidebar a.active{
background:#2a3f55;
color:#3498db;
border-left:3px solid #3498db;
}

/* content */

.content{
flex:1;
padding:20px;
background:#0f1e2d;
min-height:100vh;
}

</style>

</head>

<body>

<div class="navbar">
Web Radius Admin
</div>

<div class="container">


<div class="content">

<?php

/* load halaman */

if($page == "dashboard"){
    include "dashboard/dashboard.php";
}

if($page == "users"){
    include "dashboard/users.php";
}

if($page == "nas"){
    include "nas.php";
}

if($page == "accounting"){
    include "accounting.php";
}

if($page == "active_session"){
    include "active_session.php";
}

if($page == "voucher"){
    include "voucher.php";
}

if($page == "laporan"){
    include "laporan.php";
}

?>

</div>

</div>

</body>
</html>