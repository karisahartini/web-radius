<?php
include "config/database.php";

$total_user = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM radcheck"));

$user_online = mysqli_num_rows(mysqli_query($conn,"
SELECT * FROM radacct WHERE acctstoptime IS NULL
"));

$total_nas = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM nas"));

$total_session = mysqli_num_rows(mysqli_query($conn,"SELECT * FROM radacct"));
?>

<h2>Dashboard</h2>

<div class="card">
<h3><?php echo $total_user; ?></h3>
<p>Total User</p>
</div>

<div class="card">
<h3><?php echo $user_online; ?></h3>
<p>User Online</p>
</div>

<div class="card">
<h3><?php echo $total_nas; ?></h3>
<p>Total NAS</p>
</div>

<div class="card">
<h3><?php echo $total_session; ?></h3>
<p>Session Aktif</p>
</div>