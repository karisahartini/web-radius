<?php
include "../config/database.php";

if(isset($_POST['submit'])){

$username = $_POST['username'];
$password = $_POST['password'];

mysqli_query($conn,"
INSERT INTO radcheck(username,attribute,op,value)
VALUES('$username','Cleartext-Password',':=','$password')
");

echo "User berhasil ditambahkan";

}

?>

<form method="POST">

Username
<input type="text" name="username">

Password
<input type="password" name="password">

<button name="submit">Tambah</button>

</form>