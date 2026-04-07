<?php
include "koneksi.php";

// Ambil data user yang masih online
$query = "SELECT username, nasipaddress, framedipaddress,
          acctstarttime, acctsessiontime,
          acctinputoctets, acctoutputoctets
          FROM radacct
          WHERE acctstoptime IS NULL
          ORDER BY acctstarttime DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Active Sessions</title>
    <style>
        body { font-family: Arial; background:#f4f4f4; }
        table { border-collapse: collapse; width: 95%; margin:auto; background:white;}
        th, td { border:1px solid #ccc; padding:8px; text-align:center; }
        th { background:#28a745; color:white; }
        h2 { text-align:center; }
    </style>
</head>
<body>

<h2>USER YANG SEDANG ONLINE</h2>

<table>
<tr>
    <th>Username</th>
    <th>NAS IP</th>
    <th>User IP</th>
    <th>Start Time</th>
    <th>Session (Detik)</th>
    <th>Download (MB)</th>
    <th>Upload (MB)</th>
</tr>

<?php
if(mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){

        $download = round($row['acctinputoctets']/1024/1024,2);
        $upload   = round($row['acctoutputoctets']/1024/1024,2);

        echo "<tr>
            <td>{$row['username']}</td>
            <td>{$row['nasipaddress']}</td>
            <td>{$row['framedipaddress']}</td>
            <td>{$row['acctstarttime']}</td>
            <td>{$row['acctsessiontime']}</td>
            <td>{$download} MB</td>
            <td>{$upload} MB</td>
        </tr>";
    }
}else{
    echo "<tr><td colspan='7'>Tidak ada user online</td></tr>";
}
?>

</table>

</body>
</html>