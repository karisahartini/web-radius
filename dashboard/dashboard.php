<?php
session_start();
if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

include "../config/database.php";

/* total user */
$user = mysqli_query($conn,"SELECT COUNT(*) as total FROM users");
$dataUser = mysqli_fetch_assoc($user);

/* user online */
$online = mysqli_query($conn,"SELECT COUNT(*) as total FROM radacct WHERE acctstoptime IS NULL");
$dataOnline = mysqli_fetch_assoc($online);

/* total NAS */
$nas = mysqli_query($conn,"SELECT COUNT(*) as total FROM nas");
$dataNas = mysqli_fetch_assoc($nas);

/* login hari ini */
$login = mysqli_query($conn,"SELECT COUNT(*) as total FROM radacct WHERE DATE(acctstarttime)=CURDATE()");
$dataLogin = mysqli_fetch_assoc($login);
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard RADIUS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:#e9f5ff;
}

.sidebar{
width:220px;
height:100vh;
position:fixed;
background:#0d6efd;
color:white;
padding:20px;
}

.sidebar a{
color:white;
display:block;
margin:15px 0;
text-decoration:none;
}

.sidebar a:hover{
background:rgba(255,255,255,0.2);
padding:8px;
border-radius:5px;
}

.content{
margin-left:240px;
padding:30px;
}

.card{
border:none;
border-radius:10px;
box-shadow:0 2px 10px rgba(0,0,0,0.1);
}

</style>

</head>

<body>

<div class="sidebar">
<h4>RADIUS PANEL</h4>
<hr>

<a href="dashboard.php">Dashboard</a>
<a href="users.php">User Management</a>
<a href="#">NAS</a>
<a href="#">Accounting</a>
<a href="logout.php">Logout</a>

</div>


<div class="content">

<h2 class="mb-4">Dashboard</h2>

<div class="row">

<div class="col-md-3">
<div class="card bg-primary text-white">
<div class="card-body">
<h5>Total User</h5>
<h2><?php echo $dataUser['total']; ?></h2>
</div>
</div>
</div>


<div class="col-md-3">
<div class="card bg-success text-white">
<div class="card-body">
<h5>User Online</h5>
<h2><?php echo $dataOnline['total']; ?></h2>
</div>
</div>
</div>


<div class="col-md-3">
<div class="card bg-warning text-white">
<div class="card-body">
<h5>Total NAS</h5>
<h2><?php echo $dataNas['total']; ?></h2>
</div>
</div>
</div>


<div class="col-md-3">
<div class="card bg-info text-white">
<div class="card-body">
<h5>Login Hari Ini</h5>
<h2><?php echo $dataLogin['total']; ?></h2>
</div>
</div>
</div>

</div>


<br><br>

<div class="card">
<div class="card-header">
Statistik Login
</div>

<div class="card-body">
<p>Statistik login user akan muncul disini.</p>
</div>

</div>


</div>

</body>
</html>