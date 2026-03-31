<?php
if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}
 
include __DIR__ . '/../config/database.php';
 
/* total user */
$user = mysqli_query($conn,"SELECT COUNT(*) as total FROM users");
$dataUser = mysqli_fetch_assoc($user);
 
/* user online */
$online = mysqli_query($conn,"SELECT COUNT(*) as total FROM radacct WHERE acctstoptime IS NULL");
$dataOnline = mysqli_fetch_assoc($online);
 
/* total NAS */
$nas = mysqli_query($conn,"SELECT COUNT(*) as total FROM nas");
$dataNas = mysqli_fetch_assoc($nas);
 
/* login hari ini */
$login = mysqli_query($conn,"SELECT COUNT(*) as total FROM radacct WHERE DATE(acctstarttime)=CURDATE()");
$dataLogin = mysqli_fetch_assoc($login);
 
/* statistik login 7 hari terakhir */
$stat = mysqli_query($conn,"
    SELECT DATE(acctstarttime) as tgl, COUNT(*) as total
    FROM radacct
    WHERE acctstarttime >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(acctstarttime)
    ORDER BY tgl ASC
");
$statLabels = [];
$statData = [];
while($row = mysqli_fetch_assoc($stat)){
    $statLabels[] = date('d M', strtotime($row['tgl']));
    $statData[] = $row['total'];
}
 
/* top 5 user paling aktif */
$topUsers = mysqli_query($conn,"
    SELECT username, COUNT(*) as sesi, SUM(acctinputoctets+acctoutputoctets) as traffic
    FROM radacct
    WHERE acctstarttime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY username
    ORDER BY sesi DESC
    LIMIT 5
");
 
/* sesi aktif terbaru */
$activeSessions = mysqli_query($conn,"
    SELECT username, nasipaddress, acctstarttime,
           TIMESTAMPDIFF(MINUTE, acctstarttime, NOW()) as durasi_menit
    FROM radacct
    WHERE acctstoptime IS NULL
    ORDER BY acctstarttime DESC
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RADIUS Panel — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
    --bg: #0b0f1a;
    --sidebar-bg: #0f1629;
    --card-bg: #131b2e;
    --card-border: #1e2d4a;
    --accent: #3b82f6;
    --accent2: #06b6d4;
    --accent3: #10b981;
    --accent4: #f59e0b;
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
    display: flex;
    min-height: 100vh;
    overflow-x: hidden;
}
 
/* ── SIDEBAR ── */
.sidebar {
    width: 230px;
    min-height: 100vh;
    background: var(--sidebar-bg);
    border-right: 1px solid var(--card-border);
    position: fixed;
    top: 0; left: 0;
    display: flex;
    flex-direction: column;
    padding: 0;
    z-index: 100;
}
 
.sidebar-brand {
    padding: 28px 24px 20px;
    border-bottom: 1px solid var(--card-border);
}
 
.brand-label {
    font-family: var(--font-mono);
    font-size: 11px;
    letter-spacing: 3px;
    color: var(--accent);
    text-transform: uppercase;
    margin-bottom: 4px;
}
 
.brand-name {
    font-family: var(--font-mono);
    font-size: 18px;
    font-weight: 700;
    color: var(--text);
}
 
.sidebar-nav {
    padding: 20px 14px;
    flex: 1;
}
 
.nav-section {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
    padding: 0 10px;
    margin: 18px 0 8px;
}
 
.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: var(--muted);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all .18s ease;
    margin-bottom: 2px;
}
 
.sidebar-nav a i {
    font-size: 16px;
    width: 20px;
    text-align: center;
}
 
.sidebar-nav a:hover,
.sidebar-nav a.active {
    background: rgba(59,130,246,.12);
    color: var(--accent);
}
 
.sidebar-nav a.active {
    border-left: 3px solid var(--accent);
    padding-left: 9px;
}
 
.sidebar-footer {
    padding: 16px 14px;
    border-top: 1px solid var(--card-border);
}
 
.sidebar-footer a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: #ef4444;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: background .18s;
}
 
.sidebar-footer a:hover { background: rgba(239,68,68,.1); }
 
/* ── MAIN CONTENT ── */
.main {
    margin-left: 230px;
    flex: 1;
    padding: 36px 36px 60px;
    max-width: calc(100% - 230px);
}
 
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 36px;
}
 
.topbar-title {
    font-size: 22px;
    font-weight: 600;
    color: var(--text);
}
 
.topbar-sub {
    font-size: 13px;
    color: var(--muted);
    margin-top: 2px;
}
 
.badge-live {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(16,185,129,.1);
    border: 1px solid rgba(16,185,129,.25);
    color: var(--accent3);
    font-size: 12px;
    font-family: var(--font-mono);
    padding: 5px 12px;
    border-radius: 20px;
}
 
.badge-live::before {
    content: '';
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--accent3);
    animation: pulse 1.6s infinite;
}
 
@keyframes pulse {
    0%,100%{opacity:1;transform:scale(1)}
    50%{opacity:.4;transform:scale(.8)}
}
 
/* ── STAT CARDS ── */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 28px;
}
 
.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 14px;
    padding: 22px 22px 18px;
    position: relative;
    overflow: hidden;
    transition: transform .2s, border-color .2s;
}
 
.stat-card:hover {
    transform: translateY(-3px);
    border-color: var(--c, var(--accent));
}
 
.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--c, var(--accent));
    border-radius: 14px 14px 0 0;
}
 
.stat-card::after {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 100px; height: 100px;
    border-radius: 50%;
    background: radial-gradient(circle, var(--c, var(--accent)) 0%, transparent 70%);
    opacity: .07;
}
 
.stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: rgba(255,255,255,.05);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    color: var(--c, var(--accent));
    margin-bottom: 14px;
}
 
.stat-label {
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 6px;
}
 
.stat-value {
    font-family: var(--font-mono);
    font-size: 34px;
    font-weight: 700;
    color: var(--text);
    line-height: 1;
}
 
.stat-sub {
    font-size: 12px;
    color: var(--muted);
    margin-top: 8px;
}
 
/* ── GRID ROW ── */
.row-grid {
    display: grid;
    gap: 22px;
    margin-bottom: 22px;
}
 
.row-grid.cols-6-4 { grid-template-columns: 1.5fr 1fr; }
.row-grid.cols-1 { grid-template-columns: 1fr; }
 
/* ── CARD ── */
.card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 14px;
    overflow: hidden;
}
 
.card-header {
    padding: 18px 22px;
    border-bottom: 1px solid var(--card-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
 
.card-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
}
 
.card-title i { color: var(--accent); }
 
.card-badge {
    font-family: var(--font-mono);
    font-size: 11px;
    padding: 3px 9px;
    border-radius: 20px;
    background: rgba(59,130,246,.12);
    color: var(--accent);
    border: 1px solid rgba(59,130,246,.2);
}
 
.card-body { padding: 22px; }
 
/* ── CHART ── */
.chart-wrap {
    position: relative;
    height: 220px;
}
 
/* ── TABLE ── */
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
 
.data-table th {
    font-family: var(--font-mono);
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--muted);
    padding: 0 12px 12px;
    text-align: left;
    border-bottom: 1px solid var(--card-border);
}
 
.data-table td {
    padding: 11px 12px;
    border-bottom: 1px solid rgba(30,45,74,.5);
    color: var(--text);
    vertical-align: middle;
}
 
.data-table tr:last-child td { border-bottom: none; }
 
.data-table tr:hover td { background: rgba(59,130,246,.04); }
 
.user-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}
 
.user-avatar {
    width: 28px; height: 28px;
    border-radius: 7px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: flex; align-items: center; justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: white;
    font-family: var(--font-mono);
    flex-shrink: 0;
}
 
.tag {
    display: inline-block;
    font-family: var(--font-mono);
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 5px;
    font-weight: 700;
}
 
.tag-green { background: rgba(16,185,129,.12); color: var(--accent3); border: 1px solid rgba(16,185,129,.2); }
.tag-blue  { background: rgba(59,130,246,.12);  color: var(--accent);  border: 1px solid rgba(59,130,246,.2); }
.tag-amber { background: rgba(245,158,11,.12); color: var(--accent4); border: 1px solid rgba(245,158,11,.2); }
 
/* ── SESSION CARD ── */
.session-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 0;
    border-bottom: 1px solid rgba(30,45,74,.5);
}
 
.session-item:last-child { border-bottom: none; }
 
.session-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--accent3);
    flex-shrink: 0;
    animation: pulse 2s infinite;
}
 
.session-user { font-size: 13px; font-weight: 500; flex: 1; }
.session-ip   { font-family: var(--font-mono); font-size: 11px; color: var(--muted); }
.session-dur  { font-family: var(--font-mono); font-size: 12px; color: var(--accent2); }
 
/* ── RESPONSIVE ── */
@media(max-width:1100px){
    .stat-grid{ grid-template-columns: repeat(2,1fr); }
    .row-grid.cols-6-4{ grid-template-columns: 1fr; }
}
 
@media(max-width:700px){
    .sidebar{ transform: translateX(-100%); }
    .main{ margin-left:0; max-width:100%; padding:20px; }
    .stat-grid{ grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>
 
<!-- ── SIDEBAR ── -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-label">System</div>
        <div class="brand-name">RADIUS<span style="color:var(--accent)">_</span></div>
    </div>
 
    <nav class="sidebar-nav">
        <div class="nav-section">Menu Utama</div>
        <a href="index.php?page=dashboard" class="active">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <a href="dashboard/users.php">
            <i class="bi bi-people-fill"></i> User Management
        </a>
        <a href="#">
            <i class="bi bi-hdd-network-fill"></i> NAS
        </a>
        <a href="#">
            <i class="bi bi-journal-text"></i> Accounting
        </a>
 
        <div class="nav-section">Monitoring</div>
        <a href="#">
            <i class="bi bi-activity"></i> Active Session
        </a>
        <a href="#">
            <i class="bi bi-ticket-perforated-fill"></i> Voucher
        </a>
        <a href="#">
            <i class="bi bi-bar-chart-fill"></i> Laporan
        </a>
    </nav>
 
    <div class="sidebar-footer">
        <a href="logout.php">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </div>
</aside>
 
<!-- ── MAIN ── -->
<main class="main">
 
    <!-- Topbar -->
    <div class="topbar">
        <div>
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-sub"><?= date('l, d F Y') ?></div>
        </div>
        <div class="badge-live">Live Monitor</div>
    </div>
 
    <!-- Stat Cards -->
    <div class="stat-grid">
 
        <div class="stat-card" style="--c:#3b82f6">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-label">Total User</div>
            <div class="stat-value"><?= $dataUser['total'] ?></div>
            <div class="stat-sub">Terdaftar di sistem</div>
        </div>
 
        <div class="stat-card" style="--c:#10b981">
            <div class="stat-icon"><i class="bi bi-wifi"></i></div>
            <div class="stat-label">User Online</div>
            <div class="stat-value"><?= $dataOnline['total'] ?></div>
            <div class="stat-sub">Sesi aktif saat ini</div>
        </div>
 
        <div class="stat-card" style="--c:#f59e0b">
            <div class="stat-icon"><i class="bi bi-hdd-network"></i></div>
            <div class="stat-label">Total NAS</div>
            <div class="stat-value"><?= $dataNas['total'] ?></div>
            <div class="stat-sub">Perangkat terdaftar</div>
        </div>
 
        <div class="stat-card" style="--c:#06b6d4">
            <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-label">Login Hari Ini</div>
            <div class="stat-value"><?= $dataLogin['total'] ?></div>
            <div class="stat-sub">Sesi dibuka hari ini</div>
        </div>
 
    </div>
 
    <!-- Chart & Active Sessions -->
    <div class="row-grid cols-6-4">
 
        <!-- Chart Login 7 Hari -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    Statistik Login (7 Hari Terakhir)
                </div>
                <span class="card-badge">Weekly</span>
            </div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="loginChart"></canvas>
                </div>
            </div>
        </div>
 
        <!-- Sesi Aktif -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="bi bi-broadcast"></i>
                    Sesi Aktif
                </div>
                <span class="card-badge"><?= $dataOnline['total'] ?> Online</span>
            </div>
            <div class="card-body">
                <?php
                $hasSessions = false;
                if($activeSessions):
                    while($s = mysqli_fetch_assoc($activeSessions)):
                        $hasSessions = true;
                        $dur = $s['durasi_menit'];
                        $durStr = $dur < 60 ? $dur.'m' : floor($dur/60).'h '.($dur%60).'m';
                ?>
                <div class="session-item">
                    <div class="session-dot"></div>
                    <div>
                        <div class="session-user"><?= htmlspecialchars($s['username']) ?></div>
                        <div class="session-ip"><?= htmlspecialchars($s['nasipaddress']) ?></div>
                    </div>
                    <div class="session-dur"><?= $durStr ?></div>
                </div>
                <?php endwhile; endif;
                if(!$hasSessions): ?>
                <div style="text-align:center;padding:40px 0;color:var(--muted);font-size:13px;">
                    <i class="bi bi-slash-circle" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                    Tidak ada sesi aktif
                </div>
                <?php endif; ?>
            </div>
        </div>
 
    </div>
 
    <!-- Top Users Table -->
    <div class="row-grid cols-1">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="bi bi-trophy-fill"></i>
                    Top 5 User Aktif (30 Hari)
                </div>
                <span class="card-badge">Monthly</span>
            </div>
            <div class="card-body" style="padding:0 22px 22px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Total Sesi</th>
                            <th>Traffic</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if($topUsers):
                        $rank = 1;
                        while($u = mysqli_fetch_assoc($topUsers)):
                            $traffic = $u['traffic'];
                            if($traffic > 1073741824)      $trafficStr = round($traffic/1073741824,2).' GB';
                            elseif($traffic > 1048576)     $trafficStr = round($traffic/1048576,2).' MB';
                            else                           $trafficStr = round($traffic/1024,1).' KB';
                            $initials = strtoupper(substr($u['username'],0,2));
                    ?>
                        <tr>
                            <td><span style="font-family:var(--font-mono);font-size:12px;color:var(--muted)"><?= $rank ?></span></td>
                            <td>
                                <div class="user-chip">
                                    <div class="user-avatar"><?= $initials ?></div>
                                    <?= htmlspecialchars($u['username']) ?>
                                </div>
                            </td>
                            <td><span class="tag tag-blue"><?= $u['sesi'] ?> sesi</span></td>
                            <td><span style="font-family:var(--font-mono);font-size:12px"><?= $trafficStr ?></span></td>
                            <td><span class="tag tag-green">Aktif</span></td>
                        </tr>
                    <?php $rank++; endwhile;
                    else: ?>
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--muted)">Belum ada data</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
 
</main>
 
<script>
// Chart Data dari PHP
const labels = <?= json_encode($statLabels) ?>;
const data   = <?= json_encode($statData) ?>;
 
const ctx = document.getElementById('loginChart').getContext('2d');
 
const gradient = ctx.createLinearGradient(0, 0, 0, 220);
gradient.addColorStop(0, 'rgba(59,130,246,0.35)');
gradient.addColorStop(1, 'rgba(59,130,246,0)');
 
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels.length ? labels : ['Sen','Sel','Rab','Kam','Jum','Sab','Min'],
        datasets: [{
            label: 'Login',
            data: data.length ? data : [0,0,0,0,0,0,0],
            borderColor: '#3b82f6',
            backgroundColor: gradient,
            borderWidth: 2.5,
            pointBackgroundColor: '#3b82f6',
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#131b2e',
                borderColor: '#1e2d4a',
                borderWidth: 1,
                titleColor: '#94a3b8',
                bodyColor: '#e2e8f0',
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y + ' login'
                }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(30,45,74,.5)', drawBorder: false },
                ticks: { color: '#64748b', font: { family: 'Space Mono', size: 11 } }
            },
            y: {
                grid: { color: 'rgba(30,45,74,.5)', drawBorder: false },
                ticks: { color: '#64748b', font: { family: 'Space Mono', size: 11 }, stepSize: 1 },
                beginAtZero: true
            }
        }
    }
});
</script>
 
</body>
</html>