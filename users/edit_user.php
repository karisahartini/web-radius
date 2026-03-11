<?php

include "../config/database.php";

if(isset($_POST['update'])){

$username = $_POST['username'];
$password = $_POST['password'];

mysqli_query($conn,"
UPDATE radcheck 
SET value='$password' 
WHERE username='$username'
");

echo "Password berhasil diubah";

}

?>

<form method="POST">

Username
<input type="text" name="username">

Password Baru
<input type="text" name="password">

<button name="update">Update</button>

</form>