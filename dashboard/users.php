<?php
include "config/database.php";

$data = mysqli_query($conn,"SELECT * FROM radcheck");
?>

<h2>User Management</h2>

<table>

<tr>
<th>Username</th>
<th>Password</th>
<th>Action</th>
</tr>

<?php while($d=mysqli_fetch_array($data)){ ?>

<tr>

<td><?php echo $d['username']; ?></td>
<td><?php echo $d['value']; ?></td>

<td>

<a href="#">Edit</a> |
<a href="#">Delete</a>

</td>

</tr>

<?php } ?>

</table>