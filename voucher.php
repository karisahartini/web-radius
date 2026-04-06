<?php
// DATA DUMMY (nanti bisa dari database)
$vouchers = [
    ["username" => "WIFI01-RBF4", "password" => "RBF4"],
    ["username" => "WIFI01-JIWF", "password" => "JIWF"],
    ["username" => "WIFI01-ABCD", "password" => "1234"],
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Voucher WiFi</title>
    <style>
        body {
            font-family: Arial;
        }

        .voucher {
            border: 2px dashed black;
            width: 220px;
            padding: 10px;
            text-align: center;
            float: left;
            margin: 10px;
        }

        .voucher h3 {
            margin: 5px 0;
        }

        .qr {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<?php foreach($vouchers as $v) { ?>
    <div class="voucher">
        <h3>HOTSPOT WIFI</h3>
        <p>Username: <b><?= $v['username'] ?></b></p>
        <p>Password: <b><?= $v['password'] ?></b></p>

        <div class="qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= $v['username'] ?>">
        </div>

        <p>Scan QR untuk login</p>
    </div>
<?php } ?>

</body>
</html>