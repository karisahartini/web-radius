<?php
session_start();

// hapus semua session
$_SESSION = [];
session_unset();
session_destroy();

// redirect
header("Location: /web-radius/login.php");
exit;
?>