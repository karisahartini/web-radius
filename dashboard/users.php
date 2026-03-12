<?php
include __DIR__ . "/../config/database.php";

/* tambah user */

if(isset($_POST['tambah'])){

$username = $_POST['username'];
$password = $_POST['password'];

mysqli_query($conn,"INSERT INTO users(username,password)
VALUES('$username','$password')");

}

/* hapus user */

if(isset($_GET['hapus'])){

$id = $_GET['hapus'];

mysqli_query($conn,"DELETE FROM users WHERE id='$id'");

}

/* search user */

$search = "";

if(isset($_GET['search'])){
$search = $_GET['search'];
$query = mysqli_query($conn,"SELECT * FROM users WHERE username LIKE '%$search%'");
}else{
$query = mysqli_query($conn,"SELECT * FROM users");
}

?>

<style>

h2{
color:#3498db;
}

.box{
background:#eaf6ff;
padding:20px;
border-radius:8px;
margin-bottom:20px;
}

input{
padding:8px;
margin:5px;
border:1px solid #ccc;
border-radius:5px;
}

button{
background:#5dade2;
color:white;
border:none;
padding:8px 15px;
border-radius:5px;
cursor:pointer;
}

button:hover{
background:#3498db;
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

.hapus{
color:red;
text-decoration:none;
}

</style>

<h2>User Management</h2>

<div class="box">

<h3>Tambah User</h3>

<form method="POST">

<input type="text" name="username" placeholder="Username" required>

<input type="text" name="password" placeholder="Password" required>

<button type="submit" name="tambah">Tambah User</button>

</form>

</div>

<div class="box">

<h3>Cari User</h3>

<form method="GET">

<input type="hidden" name="page" value="users">

<input type="text" name="search" placeholder="Cari username">

<button type="submit">Search</button>

</form>

</div>

<div class="box">

<h3>Daftar User</h3>

<table>

<tr>
<th>ID</th>
<th>Username</th>
<th>Password</th>
<th>Aksi</th>
</tr>

<?php while($data=mysqli_fetch_assoc($query)){ ?>

<tr>

<td><?php echo $data['id']; ?></td>

<td><?php echo $data['username']; ?></td>

<td><?php echo $data['password']; ?></td>

<td>

<a class="hapus" href="index.php?page=users&hapus=<?php echo $data['id']; ?>">
Hapus
</a>

</td>

</tr>

<?php } ?>

</table>

</div>