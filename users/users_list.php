<?php
include "../config/database.php";

$data = mysqli_query($conn,"SELECT * FROM radcheck");

?>

<h2>User Management</h2>

<a href="add_user.php">Tambah User</a>

<table border="1">

<tr>
<th>Username</th>
<th>Action</th>
</tr>

<?php while($d = mysqli_fetch_array($data)){ ?>

<tr>
<td><?php echo $d['username']; ?></td>

<td>
<a href="edit_user.php?id=<?php echo $d['id']; ?>">Edit</a>
<a href="delete_user.php?id=<?php echo $d['id']; ?>">Hapus</a>
</td>

</tr>

<?php } ?>

</table>