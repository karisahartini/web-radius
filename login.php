<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Login daloRADIUS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-box">
    <img src="seamolec.jpeg" class="logo" alt="SEAMOLEC">
    <h2>Admin Login Page</h2>

    <form action="proses_login.php" method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>


        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>