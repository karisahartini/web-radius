<?php
include __DIR__ . "/../config/database.php";

/* total user */

$queryUser = mysqli_query($conn,"SELECT COUNT(*) AS total FROM users");
$dataUser = mysqli_fetch_assoc($queryUser);

$totalUser = $dataUser['total'];

/* contoh data */

$userOnline = 5;
$totalNAS = 2;
$sessionAktif = 3;

?>

<style>

h2{
color:#3498db;
}

.card-container{
display:flex;
flex-wrap:wrap;
gap:20px;
}

.card{
background:#eaf6ff;
padding:20px;
width:220px;
border-radius:8px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
text-align:center;
}

.card h3{
color:#2c3e50;
}

.card h1{
color:#3498db;
}

.box{
background:#eaf6ff;
padding:20px;
border-radius:8px;
margin-top:20px;
}

table{
width:100%;
border-collapse:collapse;
background:white;
}

table th{
background:#5dade2;
color:white;
padding:10px;
}

table td{
padding:10px;
border-bottom:1px solid #ddd;
}

table tr:hover{
background:#f2f9ff;
}

</style>

<h2>Dashboard</h2>

<div class="card-container">

<div class="card">
<h3>Total User</h3>
<h1><?php echo $totalUser; ?></h1>
</div>

<div class="card">
<h3>User Aktif</h3>
<h1><?php echo $userOnline; ?></h1>
</div>

<div class="card">
<h3>Total NAS</h3>
<h1><?php echo $totalNAS; ?></h1>
</div>

<div class="card">
<h3>Session Aktif</h3>
<h1><?php echo $sessionAktif; ?></h1>
</div>

</div>

<div class="box">

<h3>Statistik Login Harian</h3>

<table>

<tr>
<th>Tanggal</th>
<th>Jumlah Login</th>
</tr>

<tr>
<td>2026-03-10</td>
<td>50</td>
</tr>

<tr>
<td>2026-03-11</td>
<td>65</td>
</tr>

<tr>
<td>2026-03-12</td>
<td>25</td>
</tr>

</table>

</div>

<div class="box">

<h3>Grafik Traffic Upload & Download</h3>

<canvas id="trafficChart"></canvas>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const ctx = document.getElementById('trafficChart');

new Chart(ctx,{

type:'line',

data:{
labels:['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'],

datasets:[

{
label:'Upload',
data:[10,20,15,25,30,28,40],
borderWidth:2
},

{
label:'Download',
data:[30,40,35,50,55,60,70],
borderWidth:2
}

]

},

options:{
responsive:true
}

});

</script>