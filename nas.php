<?php
include __DIR__ . "/config/database.php";

$msg = "";
$msgType = "";

/* tambah NAS */
if(isset($_POST['tambah'])){
    $nasname    = mysqli_real_escape_string($conn, trim($_POST['nasname']));
    $shortname  = mysqli_real_escape_string($conn, trim($_POST['shortname']));
    $type       = mysqli_real_escape_string($conn, trim($_POST['type']));
    $secret     = mysqli_real_escape_string($conn, trim($_POST['secret']));
    $description= mysqli_real_escape_string($conn, trim($_POST['description']));

    if($nasname && $secret){
        $cek = mysqli_query($conn,"SELECT id FROM nas WHERE nasname='$nasname'");
        if(mysqli_num_rows($cek) > 0){
            $msg = "IP Address <strong>$nasname</strong> sudah terdaftar.";
            $msgType = "error";
        } else {
            mysqli_query($conn,"INSERT INTO nas(nasname,shortname,type,secret,description) VALUES('$nasname','$shortname','$type','$secret','$description')");
            $msg = "NAS <strong>$shortname</strong> berhasil ditambahkan.";
            $msgType = "success";
        }
    } else {
        $msg = "IP Address dan Secret Key wajib diisi.";
        $msgType = "error";
    }
}

/* hapus NAS */
if(isset($_GET['hapus'])){
    $id = (int)$_GET['hapus'];
    $hapus = mysqli_query($conn,"DELETE FROM nas WHERE id='$id'");
    if($hapus && mysqli_affected_rows($conn) > 0){
        $msg = "NAS berhasil dihapus.";
        $msgType = "success";
    } else {
        $msg = "Gagal menghapus NAS.";
        $msgType = "error";
    }
}

/* edit NAS */
$editData = null;
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $res = mysqli_query($conn,"SELECT * FROM nas WHERE id='$id'");
    $editData = mysqli_fetch_assoc($res);
}

/* simpan edit */
if(isset($_POST['simpan_edit'])){
    $id         = (int)$_POST['edit_id'];
    $nasname    = mysqli_real_escape_string($conn, trim($_POST['nasname']));
    $shortname  = mysqli_real_escape_string($conn, trim($_POST['shortname']));
    $type       = mysqli_real_escape_string($conn, trim($_POST['type']));
    $secret     = mysqli_real_escape_string($conn, trim($_POST['secret']));
    $description= mysqli_real_escape_string($conn, trim($_POST['description']));

    mysqli_query($conn,"UPDATE nas SET nasname='$nasname', shortname='$shortname', type='$type', secret='$secret', description='$description' WHERE id='$id'");
    $msg = "NAS <strong>$shortname</strong> berhasil diperbarui.";
    $msgType = "success";
}

/* list NAS */
$search = "";
if(isset($_GET['search'])){
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $query = mysqli_query($conn,"SELECT * FROM nas WHERE nasname LIKE '%$search%' OR shortname LIKE '%$search%' ORDER BY id DESC");
} else {
    $query = mysqli_query($conn,"SELECT * FROM nas ORDER BY id DESC");
}
$totalNas = mysqli_num_rows($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NAS Management — RADIUS</title>
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
    --warning: #f59e0b;
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

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 28px;
}

.page-title { font-size: 22px; font-weight: 600; }
.page-sub { font-size: 13px; color: var(--muted); margin-top: 3px; }

.total-badge {
    font-family: var(--font-mono);
    font-size: 12px;
    padding: 5px 14px;
    border-radius: 20px;
    background: rgba(59,130,246,.1);
    border: 1px solid rgba(59,130,246,.25);
    color: var(--accent);
}

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

.alert-success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.25); color: var(--accent3); }
.alert-error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25); color: var(--danger); }

@keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:none} }

.top-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }

.card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 14px; overflow: hidden; margin-bottom: 24px; }

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

.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }

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

.form-input:focus { border-color: var(--accent); background: rgba(59,130,246,.06); }
.form-input::placeholder { color: var(--muted); }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

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
    text-decoration: none;
}

.btn-primary { background: var(--accent); color: white; width: 100%; justify-content: center; }
.btn-primary:hover { background: #2563eb; transform: translateY(-1px); }

.btn-warning { background: rgba(245,158,11,.15); border: 1px solid rgba(245,158,11,.3); color: var(--warning); }
.btn-warning:hover { background: rgba(245,158,11,.25); }

.btn-search { background: rgba(6,182,212,.12); border: 1px solid rgba(6,182,212,.25); color: var(--accent2); }
.btn-search:hover { background: rgba(6,182,212,.2); }

.btn-clear { background: rgba(100,116,139,.1); border: 1px solid rgba(100,116,139,.2); color: var(--muted); }
.btn-clear:hover { background: rgba(100,116,139,.18); color: var(--text); }

.search-row { display: flex; gap: 10px; align-items: flex-end; }
.search-row .form-group { flex: 1; margin-bottom: 0; }

.table-wrap { overflow-x: auto; }

.data-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }

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
.data-table tbody tr:hover td { background: rgba(59,130,246,.04); }

.id-cell { font-family: var(--font-mono); font-size: 12px; color: var(--muted); }

.ip-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.ip-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #3b82f6, #06b6d4);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    color: white;
    flex-shrink: 0;
}

.secret-mask {
    font-family: var(--font-mono);
    font-size: 13px;
    letter-spacing: 2px;
    color: var(--muted);
    cursor: pointer;
    user-select: none;
    transition: color .15s;
}

.secret-mask.shown { color: var(--accent2); letter-spacing: 0; }

.secret-toggle { margin-left: 6px; font-size: 13px; color: var(--muted); cursor: pointer; transition: color .15s; }
.secret-toggle:hover { color: var(--accent); }

.type-badge {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 20px;
    background: rgba(59,130,246,.1);
    border: 1px solid rgba(59,130,246,.2);
    color: var(--accent);
    font-family: var(--font-mono);
}

.action-group { display: flex; gap: 8px; align-items: center; }

.btn-edit {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 7px;
    background: rgba(245,158,11,.1);
    border: 1px solid rgba(245,158,11,.2);
    color: var(--warning);
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all .18s;
}

.btn-edit:hover { background: rgba(245,158,11,.2); transform: translateY(-1px); }

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

.btn-hapus:hover { background: rgba(239,68,68,.2); transform: translateY(-1px); }

.empty-state { text-align: center; padding: 50px 20px; color: var(--muted); }
.empty-state i { font-size: 36px; display: block; margin-bottom: 10px; opacity: .4; }
.empty-state p { font-size: 14px; }

/* Modal Edit */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 999;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active { display: flex; }

.modal {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    width: 100%;
    max-width: 560px;
    padding: 28px;
    animation: fadeIn .25s ease;
}

.modal-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-title i { color: var(--warning); }

.modal-footer {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-cancel {
    flex: 1;
    background: rgba(100,116,139,.1);
    border: 1px solid rgba(100,116,139,.2);
    color: var(--muted);
    justify-content: center;
}

.btn-cancel:hover { background: rgba(100,116,139,.2); color: var(--text); }

.btn-save {
    flex: 2;
    background: var(--warning);
    color: #0b0f1a;
    justify-content: center;
}

.btn-save:hover { background: #d97706; transform: translateY(-1px); }

@media(max-width:768px){
    body { padding: 20px 16px 40px; }
    .top-row { grid-template-columns: 1fr; }
    .form-row, .form-row-3 { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- Header -->
<div class="page-header">
    <div>
        <div class="page-title"><i class="bi bi-router-fill" style="color:var(--accent);margin-right:8px"></i>NAS Management</div>
        <div class="page-sub">Kelola Network Access Server (NAS) RADIUS</div>
    </div>
    <div class="total-badge"><?= $totalNas ?> NAS Terdaftar</div>
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

    <!-- Form Tambah NAS -->
    <div class="card" style="margin-bottom:0">
        <div class="card-header">
            <i class="bi bi-plus-circle-fill"></i>
            Tambah NAS Baru
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-hdd-network"></i> IP Address</label>
                        <input class="form-input" type="text" name="nasname" placeholder="192.168.1.1" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-tag"></i> Short Name</label>
                        <input class="form-input" type="text" name="shortname" placeholder="Router-01" autocomplete="off">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-diagram-3"></i> Type</label>
                        <input class="form-input" type="text" name="type" placeholder="other" value="other" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="bi bi-key-fill"></i> Secret Key</label>
                        <input class="form-input" type="text" name="secret" placeholder="secret123" required autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-card-text"></i> Description</label>
                    <input class="form-input" type="text" name="description" placeholder="Keterangan NAS..." autocomplete="off">
                </div>
                <button class="btn btn-primary" type="submit" name="tambah">
                    <i class="bi bi-plus-lg"></i> Tambah NAS
                </button>
            </form>
        </div>
    </div>

    <!-- Search -->
    <div class="card" style="margin-bottom:0">
        <div class="card-header">
            <i class="bi bi-search"></i>
            Cari NAS
        </div>
        <div class="card-body">
            <form method="GET">
                <input type="hidden" name="page" value="nas">
                <div class="search-row">
                    <div class="form-group">
                        <label class="form-label">Keyword</label>
                        <input class="form-input" type="text" name="search"
                               placeholder="Cari IP atau nama NAS..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button class="btn btn-search" type="submit">
                        <i class="bi bi-search"></i> Cari
                    </button>
                    <?php if($search): ?>
                    <a class="btn btn-clear" href="/web-radius/index.php?page=nas">
                        <i class="bi bi-x"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php if($search): ?>
                <div style="margin-top:12px;font-size:13px;color:var(--muted)">
                    Hasil pencarian: <strong style="color:var(--accent2)">"<?= htmlspecialchars($search) ?>"</strong>
                    &mdash; <?= $totalNas ?> ditemukan
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

</div>

<!-- Tabel NAS -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-table"></i>
        Daftar NAS
    </div>
    <div class="card-body" style="padding:0 22px 22px">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>IP Address</th>
                        <th>Short Name</th>
                        <th>Type</th>
                        <th>Secret Key</th>
                        <th>Description</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $count = 0;
                while($data = mysqli_fetch_assoc($query)):
                    $count++;
                    $secId = 'sec_'.$data['id'];
                ?>
                <tr>
                    <td class="id-cell">#<?= str_pad($data['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <div class="ip-chip">
                            <div class="ip-icon"><i class="bi bi-router"></i></div>
                            <span style="font-family:var(--font-mono);font-size:13px"><?= htmlspecialchars($data['nasname']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($data['shortname'] ?? '-') ?></td>
                    <td><span class="type-badge"><?= htmlspecialchars($data['type'] ?? 'other') ?></span></td>
                    <td>
                        <span class="secret-mask" id="<?= $secId ?>">••••••••</span>
                        <i class="bi bi-eye secret-toggle"
                           data-id="<?= $secId ?>"
                           data-secret="<?= htmlspecialchars($data['secret']) ?>"
                           title="Tampilkan secret"></i>
                    </td>
                    <td style="color:var(--muted);font-size:13px"><?= htmlspecialchars($data['description'] ?? '-') ?></td>
                    <td>
                        <div class="action-group">
                            <a class="btn-edit"
                               href="#"
                               onclick="openEdit(<?= $data['id'] ?>, '<?= addslashes($data['nasname']) ?>', '<?= addslashes($data['shortname']) ?>', '<?= addslashes($data['type']) ?>', '<?= addslashes($data['secret']) ?>', '<?= addslashes($data['description']) ?>')">
                                <i class="bi bi-pencil-fill"></i> Edit
                            </a>
                            <a class="btn-hapus"
                               href="/web-radius/index.php?page=nas&hapus=<?= $data['id'] ?>"
                               onclick="return confirm('Hapus NAS <?= htmlspecialchars($data['nasname']) ?>?')">
                                <i class="bi bi-trash3"></i> Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if($count === 0): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="bi bi-router"></i>
                            <p><?= $search ? 'Tidak ada NAS dengan keyword "'.$search.'"' : 'Belum ada NAS terdaftar.' ?></p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal">
        <div class="modal-title">
            <i class="bi bi-pencil-square"></i> Edit NAS
        </div>
        <form method="POST">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-hdd-network"></i> IP Address</label>
                    <input class="form-input" type="text" name="nasname" id="edit_nasname" placeholder="192.168.1.1" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-tag"></i> Short Name</label>
                    <input class="form-input" type="text" name="shortname" id="edit_shortname" placeholder="Router-01">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-diagram-3"></i> Type</label>
                    <input class="form-input" type="text" name="type" id="edit_type" placeholder="other">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-key-fill"></i> Secret Key</label>
                    <input class="form-input" type="text" name="secret" id="edit_secret" placeholder="secret123" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><i class="bi bi-card-text"></i> Description</label>
                <input class="form-input" type="text" name="description" id="edit_description" placeholder="Keterangan NAS...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeEdit()">
                    <i class="bi bi-x-lg"></i> Batal
                </button>
                <button type="submit" name="simpan_edit" class="btn btn-save">
                    <i class="bi bi-check-lg"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle secret key
document.querySelectorAll('.secret-toggle').forEach(btn => {
    btn.addEventListener('click', function(){
        const id     = this.dataset.id;
        const secret = this.dataset.secret;
        const el     = document.getElementById(id);
        const shown  = el.classList.toggle('shown');
        el.textContent = shown ? secret : '••••••••';
        this.className = shown
            ? 'bi bi-eye-slash secret-toggle'
            : 'bi bi-eye secret-toggle';
    });
});

// Modal Edit
function openEdit(id, nasname, shortname, type, secret, description){
    document.getElementById('edit_id').value          = id;
    document.getElementById('edit_nasname').value     = nasname;
    document.getElementById('edit_shortname').value   = shortname;
    document.getElementById('edit_type').value        = type;
    document.getElementById('edit_secret').value      = secret;
    document.getElementById('edit_description').value = description;
    document.getElementById('modalEdit').classList.add('active');
}

function closeEdit(){
    document.getElementById('modalEdit').classList.remove('active');
}

// Tutup modal kalau klik di luar
document.getElementById('modalEdit').addEventListener('click', function(e){
    if(e.target === this) closeEdit();
});
</script>
</body>
</html>