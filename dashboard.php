<?php
include "config/koneksi.php";

/* TOTAL USER */
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM radcheck"));

/* USER ONLINE */
$online = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM radacct WHERE acctstoptime IS NULL"));

/* TOTAL NAS */
$nas = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM nas"));

/* SESSION AKTIF */
$session = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM radacct WHERE acctstoptime IS NULL"));

?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard RADIUS</title>

<link rel="stylesheet" href="dashboard.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

<h1>Dashboard RADIUS</h1>

<div class="card-container">

<div class="card">
<h3>Total User</h3>
<p><?php echo $user['total']; ?></p>
</div>

<div class="card">
<h3>User Online</h3>
<p><?php echo $online['total']; ?></p>
</div>

<div class="card">
<h3>Total NAS</h3>
<p><?php echo $nas['total']; ?></p>
</div>

<div class="card">
<h3>Session Aktif</h3>
<p><?php echo $session['total']; ?></p>
</div>

</div>


<h2>Statistik Login</h2>

<canvas id="loginChart"></canvas>

<h2>Traffic Upload / Download</h2>

<canvas id="trafficChart"></canvas>


<script>

const loginChart = new Chart(document.getElementById('loginChart'),{
type:'bar',
data:{
labels:['Sen','Sel','Rab','Kam','Jum','Sab','Min'],
datasets:[{
label:'Login User',
data:[12,19,8,15,10,6,14],
backgroundColor:'rgba(54,162,235,0.6)'
}]
}
});

const trafficChart = new Chart(document.getElementById('trafficChart'),{
type:'line',
data:{
labels:['00','04','08','12','16','20','24'],
datasets:[
{
label:'Upload',
data:[1,3,5,7,6,4,2],
borderColor:'red',
fill:false
},
{
label:'Download',
data:[2,4,7,10,8,5,3],
borderColor:'blue',
fill:false
}
]
}
});

</script>

</body>
</html>