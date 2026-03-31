<?php
include __DIR__ . "/../config/database.php";

$msg = "";
$msgType = "";

/* tambah user */
if(isset($_POST['tambah'])){
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    if($username && $password){
        $cek = mysqli_query($conn,"SELECT id FROM users WHERE username='$username'");
        if(mysqli_num_rows($cek) > 0){
            $msg = "Username <strong>$username</strong> sudah digunakan.";
            $msgType = "error";
        } else {
            mysqli_query($conn,"INSERT INTO users(username,password) VALUES('$username','$password')");
            $msg = "User <strong>$username</strong> berhasil ditambahkan.";
            $msgType = "success";
        }
    }
}

/* hapus user */
if(isset($_GET['hapus'])){
    $id = (int)$_GET['hapus'];
    mysqli_query($conn,"DELETE FROM users WHERE id='$id'");
    $msg = "User berhasil dihapus.";
    $msgType = "success";
}

/* search & list */
$search = "";
if(isset($_GET['search'])){
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $query = mysqli_query($conn,"SELECT * FROM users WHERE username LIKE '%$search%' ORDER BY id DESC");
} else {
    $query = mysqli_query($conn,"SELECT * FROM users ORDER BY id DESC");
}
$totalUsers = mysqli_num_rows($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management — RADIUS</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root {
    --bg: #0b0f1a;
    --card-bg: #131b2e;
    --card-border: #1e2d4a;
    --accent: #3b82f6;
    --accent2: #06b6d4;
    --accent3: #10b981;
    --danger: #ef4444;
    --text: #e2e8f0;
    --muted: #64748b;
    --font-mono: 'Space Mono', monospace;
    --font-body: 'DM Sans', sans-serif;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--font-body);
    padding: 32px 36px 60px;
    min-height: 100vh;
}

/* ── PAGE HEADER ── */
.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 28px;
}

.page-title {
    font-size: 22px;
    font-weight: 600;
}

.page-sub {
    font-size: 13px;
    color: var(--muted);
    margin-top: 3px;
}

.total-badge {
    font-family: var(--font-mono);
    font-size: 12px;
    padding: 5px 14px;
    border-radius: 20px;
    background: rgba(59,130,246,.1);
    border: 1px solid rgba(59,130,246,.25);
    color: var(--accent);
}

/* ── ALERT ── */
.alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 18px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 22px;
    animation: fadeIn .3s ease;
}

.alert-success {
    background: rgba(16,185,129,.1);
    border: 1px solid rgba(16,185,129,.25);
    color: var(--accent3);
}

.alert-error {
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.25);
    color: var(--danger);
}

@keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:none} }

/* ── TOP ROW ── */
.top-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
}

/* ── CARD ── */
.card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 14px;
    overflow: hidden;
}

.card-header {
    padding: 16px 22px;
    border-bottom: 1px solid var(--card-border);
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 14px;
    font-weight: 600;
}

.card-header i { color: var(--accent); font-size: 16px; }

.card-body { padding: 22px; }

/* ── FORM ── */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.form-label {
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--muted);
    font-weight: 500;
}

.form-input {
    background: rgba(255,255,255,.05);
    border: 1px solid var(--card-border);
    border-radius: 9px;
    padding: 11px 14px;
    color: var(--text);
    font-family: var(--font-body);
    font-size: 14px;
    width: 100%;
    transition: border-color .2s, background .2s;
    outline: none;
}

.form-input:focus {
    border-color: var(--accent);
    background: rgba(59,130,246,.06);
}

.form-input::placeholder { color: var(--muted); }

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

/* ── BUTTONS ── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 20px;
    border-radius: 9px;
    font-family: var(--font-body);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all .18s;
}

.btn-primary {
    background: var(--accent);
    color: white;
    width: 100%;
    justify-content: center;
}

.btn-primary:hover { background: #2563eb; transform: translateY(-1px); }

.btn-search {
    background: rgba(6,182,212,.12);
    border: 1px solid rgba(6,182,212,.25);
    color: var(--accent2);
}

.btn-search:hover { background: rgba(6,182,212,.2); }

.btn-clear {
    background: rgba(100,116,139,.1);
    border: 1px solid rgba(100,116,139,.2);
    color: var(--muted);
    text-decoration: none;
}

.btn-clear:hover { background: rgba(100,116,139,.18); color: var(--text); }

.search-row {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.search-row .form-group { flex: 1; margin-bottom: 0; }

/* ── TABLE ── */
.table-wrap { overflow-x: auto; }

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}

.data-table thead th {
    font-family: var(--font-mono);
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--muted);
    padding: 0 16px 14px;
    text-align: left;
    border-bottom: 1px solid var(--card-border);
    white-space: nowrap;
}

.data-table tbody td {
    padding: 13px 16px;
    border-bottom: 1px solid rgba(30,45,74,.5);
    vertical-align: middle;
    color: var(--text);
}

.data-table tbody tr:last-child td { border-bottom: none; }

.data-table tbody tr {
    transition: background .15s;
}

.data-table tbody tr:hover td {
    background: rgba(59,130,246,.04);
}

.id-cell {
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--muted);
}

.user-chip {
    display: inline-flex;
    align-items: center;
    gap: 9px;
}

.user-avatar {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: flex; align-items: center; justify-content: center;
    font-family: var(--font-mono);
    font-size: 11px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}

.pw-mask {
    font-family: var(--font-mono);
    font-size: 13px;
    letter-spacing: 2px;
    color: var(--muted);
    cursor: pointer;
    user-select: none;
    transition: color .15s;
}

.pw-mask.shown { color: var(--accent2); letter-spacing: 0; }

.pw-toggle {
    margin-left: 6px;
    font-size: 13px;
    color: var(--muted);
    cursor: pointer;
    transition: color .15s;
}

.pw-toggle:hover { color: var(--accent); }

.btn-hapus {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 7px;
    background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.2);
    color: var(--danger);
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all .18s;
}

.btn-hapus:hover {
    background: rgba(239,68,68,.2);
    transform: translateY(-1px);
}

/* ── EMPTY STATE ── */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--muted);
}

.empty-state i {
    font-size: 36px;
    display: block;
    margin-bottom: 10px;
    opacity: .4;
}

.empty-state p { font-size: 14px; }

/* ── RESPONSIVE ── */
@media(max-width:768px){
    body { padding: 20px 16px 40px; }
    .top-row { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- Header -->
<div class="page-header">
    <div>
        <div class="page-title"><i class="bi bi-people-fill" style="color:var(--accent);margin-right:8px"></i>User Management</div>
        <div class="page-sub">Kelola akun pengguna RADIUS</div>
    </div>
    <div class="total-badge"><?= $totalUsers ?> User Terdaftar</div>
</div>

<!-- Alert -->
<?php if($msg): ?>
<div class="alert alert-<?= $msgType ?>">
    <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
    <?= $msg ?>
</div>
<?php endif; ?>

<!-- Top Row: Tambah + Search -->
<div class="top-row">

    <!-- Form Tambah User -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-person-plus-fill"></i>
            Tambah User Baru
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input class="form-input" type="text" name="username" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input class="form-input" type="text" name="password" placeholder="Masukkan password" required autocomplete="off">
                    </div>
                </div>
                <button class="btn btn-primary" type="submit" name="tambah">
                    <i class="bi bi-plus-lg"></i> Tambah User
                </button>
            </form>
        </div>
    </div>

    <!-- Form Search -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-search"></i>
            Cari User
        </div>
        <div class="card-body">
            <form method="GET">
                <input type="hidden" name="page" value="users">
                <div class="search-row">
                    <div class="form-group">
                        <label class="form-label">Keyword</label>
                        <input class="form-input" type="text" name="search"
                               placeholder="Cari berdasarkan username..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button class="btn btn-search" type="submit">
                        <i class="bi bi-search"></i> Cari
                    </button>
                    <?php if($search): ?>
                    <a class="btn btn-clear" href="index.php?page=users">
                        <i class="bi bi-x"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php if($search): ?>
                <div style="margin-top:12px;font-size:13px;color:var(--muted)">
                    Menampilkan hasil untuk: <strong style="color:var(--accent2)">"<?= htmlspecialchars($search) ?>"</strong>
                    &mdash; <?= $totalUsers ?> ditemukan
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

</div>

<!-- Tabel User -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-table"></i>
        Daftar User
    </div>
    <div class="card-body" style="padding:0 22px 22px">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $count = 0;
                while($data = mysqli_fetch_assoc($query)):
                    $count++;
                    $initials = strtoupper(substr($data['username'], 0, 2));
                    $pwId = 'pw_'.$data['id'];
                ?>
                <tr>
                    <td class="id-cell">#<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <div class="user-chip">
                            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                            <span><?= htmlspecialchars($data['username']) ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="pw-mask" id="<?= $pwId ?>">••••••••</span>
                        <i class="bi bi-eye pw-toggle"
                           data-id="<?= $pwId ?>"
                           data-pw="<?= htmlspecialchars($data['password']) ?>"
                           title="Tampilkan password"></i>
                    </td>
                    <td>
                        <a class="btn-hapus"
                           href="index.php?page=users&hapus=<?= $data['id'] ?>"
                           onclick="return confirm('Hapus user <?= htmlspecialchars($data['username']) ?>?')">
                            <i class="bi bi-trash3"></i> Hapus
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if($count === 0): ?>
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <i class="bi bi-person-x"></i>
                            <p><?= $search ? 'Tidak ada user dengan username "'.$search.'"' : 'Belum ada user terdaftar.' ?></p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Toggle tampilkan/sembunyikan password
document.querySelectorAll('.pw-toggle').forEach(btn => {
    btn.addEventListener('click', function(){
        const id  = this.dataset.id;
        const pw  = this.dataset.pw;
        const el  = document.getElementById(id);
        const shown = el.classList.toggle('shown');
        el.textContent = shown ? pw : '••••••••';
        this.className = shown
            ? 'bi bi-eye-slash pw-toggle'
            : 'bi bi-eye pw-toggle';
    });
});
</script>
</body>
</html>