<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html>
<head>
<title>Web Radius</title>

<style>

body{
margin:0;
font-family:Arial;
background:#f4f6f9;
}

/* sidebar */

.sidebar{
width:230px;
background:#2f4050;
position:fixed;
height:100%;
color:white;
}

.sidebar h2{
text-align:center;
padding:20px;
}

.sidebar a{
display:block;
padding:15px;
color:white;
text-decoration:none;
}

.sidebar a:hover{
background:#1c2833;
}

/* topbar */

.topbar{
margin-left:230px;
background:white;
padding:15px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

/* content */

.content{
margin-left:230px;
padding:20px;
}

/* cards */

.card{
display:inline-block;
background:white;
padding:20px;
margin:10px;
width:200px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
border-radius:5px;
}

/* table */

table{
width:100%;
background:white;
border-collapse:collapse;
}

table th, table td{
padding:10px;
border:1px solid #ddd;
}

table th{
background:#34495e;
color:white;
}

</style>

</head>

<body>

<div class="sidebar">

<h2>Web Radius</h2>

<a href="index.php?page=dashboard">Dashboard</a>
<a href="index.php?page=users">User Management</a>

</div>


<div class="topbar">

<b>Radius Admin Panel</b>

</div>


<div class="content">

<?php

if($page=="dashboard"){
include "dashboard/dashboard.php";
}

if($page=="users"){
include "dashboard/users.php";
}

?>

</div>

</body>
</html>