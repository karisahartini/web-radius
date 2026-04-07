<?php
/*
 * voucher.php
 * Dipanggil via include dari index.php
 */

$conn = new mysqli("localhost", "root", "", "radius");
$conn->set_charset("utf8mb4");

// ===================== HANDLE AKSI =====================

$pesan = '';
$pesan_type = '';

// Tambah voucher
if (isset($_POST['aksi']) && $_POST['aksi'] == 'tambah') {
    $username = strtoupper(trim($_POST['username']));
    $password = trim($_POST['password']);
    $paket    = $_POST['paket'];

    if (empty($username) || empty($password)) {
        $pesan = 'Username dan password tidak boleh kosong!';
        $pesan_type = 'error';
    } else {
        $cek = $conn->prepare("SELECT id FROM vouchers WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $pesan = 'Username sudah ada, gunakan username lain!';
            $pesan_type = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO vouchers (username, password, paket, status) VALUES (?, ?, ?, 'aktif')");
            $stmt->bind_param("sss", $username, $password, $paket);
            $stmt->execute();
            $pesan = 'Voucher berhasil ditambahkan!';
            $pesan_type = 'sukses';
        }
    }
}

// Generate massal otomatis
if (isset($_POST['aksi']) && $_POST['aksi'] == 'generate') {
    $jumlah = intval($_POST['jumlah']);
    $paket  = $_POST['paket_generate'];
    $jumlah = max(1, min($jumlah, 100));

    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $berhasil = 0;
    for ($i = 0; $i < $jumlah; $i++) {
        do {
            $suffix = '';
            for ($j = 0; $j < 4; $j++) $suffix .= $chars[rand(0, strlen($chars)-1)];
            $uname = 'WIFI' . str_pad(rand(1,9), 2, '0', STR_PAD_LEFT) . '-' . $suffix;
            $cek = $conn->prepare("SELECT id FROM vouchers WHERE username = ?");
            $cek->bind_param("s", $uname);
            $cek->execute();
        } while ($cek->get_result()->num_rows > 0);

        $pass = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 4);
        $stmt = $conn->prepare("INSERT INTO vouchers (username, password, paket, status) VALUES (?, ?, ?, 'aktif')");
        $stmt->bind_param("sss", $uname, $pass, $paket);
        if ($stmt->execute()) $berhasil++;
    }
    $pesan = "$berhasil voucher berhasil digenerate!";
    $pesan_type = 'sukses';
}

// Hapus voucher
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $stmt = $conn->prepare("DELETE FROM vouchers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $pesan = 'Voucher berhasil dihapus!';
    $pesan_type = 'sukses';
}

// ===================== AMBIL DATA =====================

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$cari   = isset($_GET['cari'])   ? trim($_GET['cari']) : '';

$sql = "SELECT * FROM vouchers WHERE 1=1";
$params = [];
$types = '';

if (in_array($filter, ['aktif', 'digunakan', 'expired'])) {
    $sql .= " AND status = ?";
    $types .= 's';
    $params[] = $filter;
}

if (!empty($cari)) {
    $sql .= " AND (username LIKE ? OR password LIKE ?)";
    $types .= 'ss';
    $like = "%$cari%";
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) die("Query error: " . $conn->error);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
    if ($result === false) die("Query error: " . $conn->error);
}

// Statistik
$stat = $conn->query("SELECT status, COUNT(*) as total FROM vouchers GROUP BY status");
$counts = ['aktif' => 0, 'digunakan' => 0, 'expired' => 0];
while ($s = $stat->fetch_assoc()) $counts[$s['status']] = $s['total'];
$total = array_sum($counts);
?>

<!-- ===================== CSS ===================== -->
<style>
.vc-wrap {
    font-family: Arial, sans-serif;
    color: #e0e6f0;
}

/* Notifikasi */
.vc-pesan {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.vc-pesan.sukses { background: rgba(39,174,96,0.15); color: #2ecc71; border: 1px solid rgba(39,174,96,0.3); }
.vc-pesan.error  { background: rgba(231,76,60,0.15);  color: #e74c3c; border: 1px solid rgba(231,76,60,0.3); }

/* Header halaman */
.vc-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.vc-page-title {
    display: flex;
    align-items: center;
    gap: 10px;
}
.vc-page-title .icon {
    font-size: 24px;
}
.vc-page-title h2 {
    margin: 0;
    font-size: 20px;
    color: #ffffff;
}
.vc-page-title p {
    margin: 2px 0 0;
    font-size: 13px;
    color: #7f8ea3;
}
.vc-total-badge {
    background: rgba(52,152,219,0.2);
    color: #3498db;
    border: 1px solid rgba(52,152,219,0.4);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
}

/* Stat cards */
.vc-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 700px) { .vc-stats { grid-template-columns: repeat(2, 1fr); } }

.vc-stat {
    background: #1a2a3a;
    border: 1px solid #2a3f55;
    border-radius: 10px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.vc-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.vc-stat-icon.biru   { background: rgba(52,152,219,0.15); }
.vc-stat-icon.hijau  { background: rgba(39,174,96,0.15); }
.vc-stat-icon.kuning { background: rgba(243,156,18,0.15); }
.vc-stat-icon.merah  { background: rgba(231,76,60,0.15); }
.vc-stat-val {
    font-size: 24px;
    font-weight: bold;
    color: #ffffff;
    line-height: 1;
}
.vc-stat-lbl {
    font-size: 12px;
    color: #7f8ea3;
    margin-top: 3px;
}

/* Panel */
.vc-panel-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}
@media (max-width: 700px) { .vc-panel-grid { grid-template-columns: 1fr; } }

.vc-panel {
    background: #1a2a3a;
    border: 1px solid #2a3f55;
    border-radius: 10px;
    padding: 20px;
}
.vc-panel-title {
    font-size: 15px;
    font-weight: bold;
    color: #ffffff;
    margin: 0 0 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #2a3f55;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Form */
.vc-form-grid {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: flex-end;
}
.vc-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
    min-width: 100px;
}
.vc-form-group label {
    font-size: 11px;
    color: #7f8ea3;
    font-weight: bold;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.vc-form-group input,
.vc-form-group select {
    padding: 9px 12px;
    background: #0f1e2d;
    border: 1px solid #2a3f55;
    border-radius: 8px;
    color: #e0e6f0;
    font-size: 13px;
    font-family: Arial;
    outline: none;
    width: 100%;
    box-sizing: border-box;
}
.vc-form-group input::placeholder { color: #4a6070; }
.vc-form-group input:focus,
.vc-form-group select:focus { border-color: #3498db; }
.vc-form-group select option { background: #1a2a3a; }

/* Tombol */
.btn {
    padding: 9px 18px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    font-family: Arial;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.btn-biru   { background: #3498db; color: white; }
.btn-hijau  { background: #27ae60; color: white; }
.btn-merah  { background: rgba(231,76,60,0.15); color: #e74c3c; border: 1px solid rgba(231,76,60,0.3); }
.btn-abu    { background: rgba(127,142,163,0.15); color: #7f8ea3; border: 1px solid rgba(127,142,163,0.2); }
.btn-outline { background: transparent; color: #3498db; border: 1px solid rgba(52,152,219,0.4); }
.btn:hover  { opacity: 0.85; }
.btn-sm { padding: 6px 12px; font-size: 12px; }

/* Tabel area */
.vc-table-wrap {
    background: #1a2a3a;
    border: 1px solid #2a3f55;
    border-radius: 10px;
    overflow: hidden;
}
.vc-table-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #2a3f55;
    flex-wrap: wrap;
    gap: 10px;
}
.vc-table-head h3 {
    margin: 0;
    font-size: 15px;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Toolbar filter */
.vc-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-bottom: 1px solid #2a3f55;
    flex-wrap: wrap;
}
.vc-filter-pill {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    border: 1px solid #2a3f55;
    color: #7f8ea3;
    background: transparent;
    transition: all 0.15s;
}
.vc-filter-pill:hover { border-color: #3498db; color: #3498db; }
.vc-filter-pill.active { background: #3498db; color: white; border-color: #3498db; }

.vc-search {
    margin-left: auto;
    display: flex;
    gap: 6px;
}
.vc-search input {
    padding: 7px 12px;
    background: #0f1e2d;
    border: 1px solid #2a3f55;
    border-radius: 8px;
    color: #e0e6f0;
    font-size: 13px;
    font-family: Arial;
    outline: none;
    width: 180px;
}
.vc-search input::placeholder { color: #4a6070; }
.vc-search input:focus { border-color: #3498db; }

/* Tabel */
table.vc-tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
table.vc-tbl th {
    padding: 11px 20px;
    text-align: left;
    color: #7f8ea3;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #2a3f55;
    background: #0f1e2d;
}
table.vc-tbl td {
    padding: 13px 20px;
    border-bottom: 1px solid #1e3045;
    vertical-align: middle;
    color: #c8d6e5;
}
table.vc-tbl tr:last-child td { border-bottom: none; }
table.vc-tbl tr:hover td { background: rgba(52,152,219,0.05); }

.vc-username {
    font-weight: bold;
    color: #ffffff;
    font-size: 14px;
}
.vc-pass {
    background: #0f1e2d;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12px;
    color: #3498db;
    font-family: monospace;
    border: 1px solid #2a3f55;
}

/* Badge status */
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}
.badge-aktif     { background: rgba(39,174,96,0.15);  color: #2ecc71; border: 1px solid rgba(39,174,96,0.3); }
.badge-digunakan { background: rgba(243,156,18,0.15); color: #f39c12; border: 1px solid rgba(243,156,18,0.3); }
.badge-expired   { background: rgba(231,76,60,0.15);  color: #e74c3c; border: 1px solid rgba(231,76,60,0.3); }

.vc-empty {
    text-align: center;
    padding: 50px;
    color: #4a6070;
    font-size: 14px;
}

/* Print */
@media print {
    body * { visibility: hidden; }
    #area-cetak, #area-cetak * { visibility: visible; }
    #area-cetak {
        position: fixed;
        top: 0; left: 0;
        width: 100%;
    }
    .voucher-grid-print {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        padding: 10px;
    }
    .voucher-card-print {
        border: 2px dashed #333;
        border-radius: 8px;
        padding: 10px;
        text-align: center;
        break-inside: avoid;
    }
}
</style>

<!-- ===================== HTML ===================== -->
<div class="vc-wrap">

<!-- Notifikasi -->
<?php if ($pesan): ?>
<div class="vc-pesan <?= $pesan_type ?>">
    <?= $pesan_type == 'sukses' ? '✅' : '⚠️' ?> <?= htmlspecialchars($pesan) ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="vc-page-header">
    <div class="vc-page-title">
        <span class="icon">🎫</span>
        <div>
            <h2>Voucher Management</h2>
            <p>Kelola voucher WiFi hotspot</p>
        </div>
    </div>
    <span class="vc-total-badge"><?= $total ?> Total Voucher</span>
</div>

<!-- Statistik -->
<div class="vc-stats">
    <div class="vc-stat">
        <div class="vc-stat-icon biru">🎫</div>
        <div>
            <div class="vc-stat-val"><?= $total ?></div>
            <div class="vc-stat-lbl">Total Voucher</div>
        </div>
    </div>
    <div class="vc-stat">
        <div class="vc-stat-icon hijau">✅</div>
        <div>
            <div class="vc-stat-val"><?= $counts['aktif'] ?></div>
            <div class="vc-stat-lbl">Aktif</div>
        </div>
    </div>
    <div class="vc-stat">
        <div class="vc-stat-icon kuning">⏳</div>
        <div>
            <div class="vc-stat-val"><?= $counts['digunakan'] ?></div>
            <div class="vc-stat-lbl">Digunakan</div>
        </div>
    </div>
    <div class="vc-stat">
        <div class="vc-stat-icon merah">❌</div>
        <div>
            <div class="vc-stat-val"><?= $counts['expired'] ?></div>
            <div class="vc-stat-lbl">Expired</div>
        </div>
    </div>
</div>

<!-- Form Panel -->
<div class="vc-panel-grid">

    <!-- Tambah Manual -->
    <div class="vc-panel">
        <div class="vc-panel-title">✍️ Tambah Voucher Manual</div>
        <form method="POST">
            <input type="hidden" name="aksi" value="tambah">
            <div class="vc-form-grid">
                <div class="vc-form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="cth: WIFI01-ABCD" required>
                </div>
                <div class="vc-form-group">
                    <label>Password</label>
                    <input type="text" name="password" placeholder="cth: ABCD" required>
                </div>
            </div>
            <div class="vc-form-grid" style="margin-top:10px">
                <div class="vc-form-group">
                    <label>Paket</label>
                    <select name="paket">
                        <option>1 Jam</option>
                        <option>3 Jam</option>
                        <option>1 Hari</option>
                        <option>1 Minggu</option>
                        <option>1 Bulan</option>
                    </select>
                </div>
                <div class="vc-form-group" style="justify-content:flex-end">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-biru" style="width:100%">+ Tambah Voucher</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Generate Massal -->
    <div class="vc-panel">
        <div class="vc-panel-title">⚡ Generate Massal Otomatis</div>
        <p style="font-size:13px;color:#7f8ea3;margin:0 0 14px">Username & password digenerate otomatis secara acak.</p>
        <form method="POST">
            <input type="hidden" name="aksi" value="generate">
            <div class="vc-form-grid">
                <div class="vc-form-group">
                    <label>Jumlah Voucher</label>
                    <input type="number" name="jumlah" value="10" min="1" max="100">
                </div>
                <div class="vc-form-group">
                    <label>Paket</label>
                    <select name="paket_generate">
                        <option>1 Jam</option>
                        <option>3 Jam</option>
                        <option>1 Hari</option>
                        <option>1 Minggu</option>
                        <option>1 Bulan</option>
                    </select>
                </div>
                <div class="vc-form-group" style="justify-content:flex-end">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-hijau" style="width:100%">⚡ Generate</button>
                </div>
            </div>
        </form>
    </div>

</div>

<!-- Tabel Voucher -->
<div class="vc-table-wrap">
    <div class="vc-table-head">
        <h3>📋 Daftar Voucher</h3>
        <button class="btn btn-outline btn-sm" onclick="window.print()">🖨️ Cetak Voucher Aktif</button>
    </div>

    <!-- Filter & Search -->
    <div class="vc-toolbar">
        <?php
        $filters = ['semua' => 'Semua', 'aktif' => 'Aktif', 'digunakan' => 'Digunakan', 'expired' => 'Expired'];
        foreach ($filters as $val => $label):
            $url = '?page=Voucher&filter=' . $val . (!empty($cari) ? '&cari=' . urlencode($cari) : '');
        ?>
        <a href="<?= $url ?>" class="vc-filter-pill <?= $filter === $val ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>

        <form method="GET" class="vc-search">
            <input type="hidden" name="page" value="Voucher">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <input type="text" name="cari" placeholder="Cari username..." value="<?= htmlspecialchars($cari) ?>">
            <button type="submit" class="btn btn-biru btn-sm">Cari</button>
            <?php if ($cari): ?>
            <a href="?page=Voucher&filter=<?= $filter ?>" class="btn btn-abu btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel -->
    <table class="vc-tbl">
        <thead>
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Password</th>
                <th>Paket</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $ada_data = false;
            while ($v = $result->fetch_assoc()):
                $ada_data = true;
            ?>
            <tr>
                <td style="color:#4a6070"><?= $no++ ?></td>
                <td><span class="vc-username"><?= htmlspecialchars($v['username']) ?></span></td>
                <td><span class="vc-pass"><?= htmlspecialchars($v['password']) ?></span></td>
                <td><?= htmlspecialchars($v['paket']) ?></td>
                <td><span class="badge badge-<?= $v['status'] ?>"><?= ucfirst($v['status']) ?></span></td>
                <td style="color:#7f8ea3;font-size:12px"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
                <td>
                    <a href="?page=Voucher&hapus=<?= $v['id'] ?>"
                       class="btn btn-merah btn-sm"
                       onclick="return confirm('Hapus voucher <?= htmlspecialchars($v['username']) ?>?')">
                        🗑️ Hapus
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>

            <?php if (!$ada_data): ?>
            <tr>
                <td colspan="7" class="vc-empty">
                    <div style="font-size:32px;margin-bottom:8px">🎫</div>
                    Tidak ada voucher ditemukan
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Area Cetak -->
<div id="area-cetak" style="display:none">
    <div class="voucher-grid-print">
        <?php
        $cetak = $conn->query("SELECT * FROM vouchers WHERE status='aktif' ORDER BY created_at DESC");
        while ($v = $cetak->fetch_assoc()):
        ?>
        <div class="voucher-card-print">
            <div style="font-weight:bold;font-size:13px;margin-bottom:6px">HOTSPOT WiFi</div>
            <div style="font-size:11px;margin-bottom:3px">User: <b><?= htmlspecialchars($v['username']) ?></b></div>
            <div style="font-size:11px;margin-bottom:3px">Pass: <b><?= htmlspecialchars($v['password']) ?></b></div>
            <div style="font-size:11px;margin-bottom:8px">Paket: <?= htmlspecialchars($v['paket']) ?></div>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=<?= urlencode($v['username'].':'.$v['password']) ?>"
                 width="90" height="90" alt="QR">
            <div style="font-size:10px;margin-top:6px;color:#555">Scan QR untuk login</div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
window.onbeforeprint = function() { document.getElementById('area-cetak').style.display = 'block'; };
window.onafterprint  = function() { document.getElementById('area-cetak').style.display = 'none'; };
</script>

</div>