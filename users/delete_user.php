<?php

include "../config/database.php";

$id = $_GET['id'];

mysqli_query($conn,"DELETE FROM radcheck WHERE id='$id'");

header("location:user_list.php");

?>