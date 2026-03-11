<?php
include "../config/database.php";

$total_user = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM radcheck"));

$user_online = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM radacct WHERE acctstoptime IS NULL
"));

$total_nas = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM nas"));

$session = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM radacct
"));

?>

<h2>Dashboard Radius</h2>

<p>Total User : <?php echo $total_user; ?></p>
<p>User Online : <?php echo $user_online; ?></p>
<p>Total NAS : <?php echo $total_nas; ?></p>
<p>Total Session : <?php echo $session; ?></p>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<canvas id="traffic"></canvas>

<script>

var ctx = document.getElementById('traffic');

new Chart(ctx,{
type:'line',
data:{
labels:["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],
datasets:[{
label:'Upload',
data:[10,20,15,30,25,40,35]
},{
label:'Download',
data:[20,10,25,15,35,30,50]
}]
}
});

</script>