<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "radius";

$conn = mysqli_connect($host,$user,$pass,$db);

if(!$conn){
    die("Database gagal koneksi");
}

?>