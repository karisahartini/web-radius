<?php
$conn = new mysqli("localhost", "root", "", "radius");
$conn->set_charset("utf8mb4");

// ─── HELPERS ────────────────────────────────────────────────────────────────
function formatDuration($seconds) {
    if (!$seconds || $seconds < 0) return '-';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}
function isActive($stoptime) {
    return empty($stoptime) || $stoptime === '0000-00-00 00:00:00';
}
function fmtBytes($bytes) {
    if (!$bytes) return '0 MB';
    $mb = $bytes / 1024 / 1024;
    if ($mb >= 1024) return round($mb/1024,2).' GB';
    return round($mb,2).' MB';
}

// ─── INPUT ───────────────────────────────────────────────────────────────────
$search        = isset($_GET['search'])    ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$filter_status = isset($_GET['status'])    ? $_GET['status'] : 'all';
$date_from     = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to       = isset($_GET['date_to'])   ? $_GET['date_to']   : '';
$view_mode     = isset($_GET['view'])      ? $_GET['view'] : 'table';
$export_csv    = isset($_GET['export'])    && $_GET['export'] === 'csv';

// ─── WHERE CLAUSE ─────────────────────────────────────────────────────────────
$where = [];
if (!empty($search))
    $where[] = "(username LIKE '%$search%' OR nasipaddress LIKE '%$search%')";
if ($filter_status === 'active')
    $where[] = "(acctstoptime IS NULL OR acctstoptime='0000-00-00 00:00:00')";
elseif ($filter_status === 'stopped')
    $where[] = "(acctstoptime IS NOT NULL AND acctstoptime!='0000-00-00 00:00:00')";
if (!empty($date_from))
    $where[] = "DATE(acctstarttime) >= '".mysqli_real_escape_string($conn,$date_from)."'";
if (!empty($date_to))
    $where[] = "DATE(acctstarttime) <= '".mysqli_real_escape_string($conn,$date_to)."'";
$wc = count($where) ? "WHERE ".implode(" AND ",$where) : "";

// ─── EXPORT CSV ───────────────────────────────────────────────────────────────
if ($export_csv) {
    $res = mysqli_query($conn,"SELECT * FROM radacct $wc ORDER BY acctstarttime DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="accounting_'.date('Ymd_His').'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['Username','NAS IP','Start Time','Stop Time','Durasi','Download','Upload','Total Traffic','Status','Terminate Cause']);
    while($r = mysqli_fetch_assoc($res)){
        fputcsv($out,[
            $r['username'],
            $r['nasipaddress'],
            $r['acctstarttime'],
            $r['acctstoptime'] ?: '-',
            formatDuration($r['acctsessiontime']),
            fmtBytes($r['acctinputoctets']),
            fmtBytes($r['acctoutputoctets']),
            fmtBytes(($r['acctinputoctets']+$r['acctoutputoctets'])),
            isActive($r['acctstoptime']) ? 'ONLINE' : 'OFFLINE',
            $r['acctterminatecause'] ?: '-'
        ]);
    }
    fclose($out); exit;
}

// ─── STATS ───────────────────────────────────────────────────────────────────
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN acctstoptime IS NULL OR acctstoptime='0000-00-00 00:00:00' THEN 1 ELSE 0 END) as active,
     SUM(acctinputoctets)  as total_dl,
     SUM(acctoutputoctets) as total_ul,
     SUM(acctsessiontime)  as total_sec,
     COUNT(DISTINCT username) as unique_users
     FROM radacct $wc"));

// ─── PAGINATION ───────────────────────────────────────────────────────────────
$per_page   = 20;
$page       = max(1, intval($_GET['page'] ?? 1));
$offset     = ($page-1)*$per_page;
$total_rows = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM radacct $wc"))['c'];
$total_pages= max(1, ceil($total_rows/$per_page));

// ─── MAIN DATA ────────────────────────────────────────────────────────────────
$result = mysqli_query($conn,
    "SELECT * FROM radacct $wc ORDER BY acctstarttime DESC LIMIT $per_page OFFSET $offset");

// ─── HISTORY: per-user summary ────────────────────────────────────────────────
$history_result = mysqli_query($conn,
    "SELECT username,
     COUNT(*) as login_count,
     MIN(acctstarttime) as first_login,
     MAX(acctstarttime) as last_login,
     SUM(acctsessiontime) as total_duration,
     SUM(acctinputoctets)  as total_dl,
     SUM(acctoutputoctets) as total_ul,
     SUM(acctinputoctets+acctoutputoctets) as total_traffic,
     SUM(CASE WHEN acctstoptime IS NULL OR acctstoptime='0000-00-00 00:00:00' THEN 1 ELSE 0 END) as active_now
     FROM radacct $wc
     GROUP BY username ORDER BY last_login DESC");

// ─── URL HELPER ───────────────────────────────────────────────────────────────
function buildUrl($extra=[]) {
    $base = [
        'search'    => $_GET['search']    ?? '',
        'status'    => $_GET['status']    ?? 'all',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'view'      => $_GET['view']      ?? 'table',
        'page'      => $_GET['page']      ?? 1,
    ];
    $merged = array_merge($base, $extra);
    return '?'.http_build_query(array_filter($merged, fn($v)=>$v!==''&&$v!==null&&$v!==0));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RADIUS Accounting</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Sora:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#07090f;--surface:#0f1624;--surface2:#161e30;--border:#1e2d45;
  --accent:#00c8f0;--accent2:#7c3aed;--green:#10b981;--red:#ef4444;
  --yellow:#f59e0b;--purple:#a78bfa;--text:#dde6f5;--muted:#536278;
  --mono:'JetBrains Mono',monospace;--sans:'Sora',sans-serif;
  --r:14px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--sans);background:var(--bg);color:var(--text);min-height:100vh}
body::before{
  content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
  background-image:linear-gradient(var(--border) 1px,transparent 1px),
                   linear-gradient(90deg,var(--border) 1px,transparent 1px);
  background-size:48px 48px;opacity:.3;
}
.wrap{position:relative;z-index:1;max-width:1440px;margin:0 auto;padding:28px 20px}

/* HEADER */
.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:26px;flex-wrap:wrap;gap:12px}
.logo{display:flex;align-items:center;gap:14px}
.logo-icon{width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:22px}
h1{font-size:1.5rem;font-weight:700;letter-spacing:-.5px}
h1 span{color:var(--accent)}
.subtitle{font-family:var(--mono);font-size:.7rem;color:var(--muted);margin-top:2px}
.live-badge{display:flex;align-items:center;gap:6px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:20px;padding:6px 14px;font-size:.7rem;color:var(--green);font-family:var(--mono)}
.dot{width:7px;height:7px;background:var(--green);border-radius:50%;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.75)}}

/* STAT CARDS */
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:13px;margin-bottom:20px}
.sc{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:16px 18px;position:relative;overflow:hidden;transition:border-color .2s}
.sc:hover{border-color:var(--accent)}
.sc::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
.sc.c1::before{background:linear-gradient(90deg,var(--accent),transparent)}
.sc.c2::before{background:linear-gradient(90deg,var(--green),transparent)}
.sc.c3::before{background:linear-gradient(90deg,#f472b6,transparent)}
.sc.c4::before{background:linear-gradient(90deg,#34d399,transparent)}
.sc.c5::before{background:linear-gradient(90deg,var(--yellow),transparent)}
.sc.c6::before{background:linear-gradient(90deg,var(--purple),transparent)}
.sc-icon{font-size:1.2rem;margin-bottom:8px}
.sc-val{font-family:var(--mono);font-size:1.6rem;font-weight:600;line-height:1;margin-bottom:4px}
.sc.c1 .sc-val{color:var(--accent)}.sc.c2 .sc-val{color:var(--green)}
.sc.c3 .sc-val{color:#f472b6}.sc.c4 .sc-val{color:#34d399}
.sc.c5 .sc-val{color:var(--yellow)}.sc.c6 .sc-val{color:var(--purple)}
.sc-lbl{font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.8px}

/* CONTROLS */
.ctrl-box{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 16px;margin-bottom:16px}
.ctrl-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.ctrl-row+.ctrl-row{margin-top:10px;padding-top:10px;border-top:1px solid var(--border)}
.inp{background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:8px 13px;color:var(--text);font-family:var(--mono);font-size:.8rem;outline:none;transition:border-color .2s}
.inp:focus{border-color:var(--accent)}
.inp-s{flex:1;min-width:200px}
.inp-d{width:148px}
.date-lbl{font-size:.73rem;color:var(--muted);display:flex;align-items:center;gap:6px}
.fbtn{padding:7px 14px;border-radius:8px;border:1px solid var(--border);background:var(--surface2);color:var(--muted);font-family:var(--sans);font-size:.76rem;cursor:pointer;text-decoration:none;transition:all .2s;white-space:nowrap}
.fbtn:hover{border-color:var(--accent);color:var(--text)}
.fbtn.on{background:var(--accent);border-color:var(--accent);color:#000;font-weight:700}
.fbtn.on-g{background:rgba(16,185,129,.15);border-color:var(--green);color:var(--green)}
.btn-p{padding:8px 17px;background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;border-radius:8px;color:#fff;font-family:var(--sans);font-weight:600;font-size:.8rem;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;white-space:nowrap;transition:opacity .2s}
.btn-p:hover{opacity:.85}
.btn-csv{padding:8px 17px;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.35);border-radius:8px;color:var(--green);font-family:var(--sans);font-weight:600;font-size:.8rem;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all .2s;white-space:nowrap}
.btn-csv:hover{background:rgba(16,185,129,.22)}
.btn-r{padding:8px 13px;background:transparent;border:1px solid var(--border);border-radius:8px;color:var(--muted);font-size:.76rem;font-family:var(--sans);cursor:pointer;text-decoration:none;transition:all .2s}
.btn-r:hover{border-color:var(--red);color:var(--red)}

/* TABS */
.tabs{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
.tab{padding:8px 18px;border-radius:10px;border:1px solid var(--border);background:var(--surface);color:var(--muted);font-size:.78rem;font-weight:600;text-decoration:none;transition:all .2s;display:flex;align-items:center;gap:5px}
.tab:hover{border-color:var(--accent);color:var(--text)}
.tab.active{background:linear-gradient(135deg,var(--accent),var(--accent2));border-color:transparent;color:#fff}

/* TABLE BOX */
.tbox{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:16px}
.tbox-hdr{padding:13px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.tbox-title{font-size:.75rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:1px}
.row-cnt{font-family:var(--mono);font-size:.73rem;color:var(--muted)}
.scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.79rem}
thead th{padding:10px 13px;text-align:left;background:var(--surface2);color:var(--muted);font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;white-space:nowrap;border-bottom:1px solid var(--border)}
tbody tr{border-bottom:1px solid var(--border);transition:background .15s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:rgba(255,255,255,.025)}
td{padding:11px 13px;white-space:nowrap}
.mono{font-family:var(--mono)}
.uname{font-family:var(--mono);color:var(--accent);font-weight:600}
.sm{font-size:.73rem}
.muted{color:var(--muted)}
.dl{color:#38bdf8;font-family:var(--mono)}
.ul{color:var(--purple);font-family:var(--mono)}
.dur{color:var(--yellow);font-family:var(--mono)}
.traf{color:#34d399;font-family:var(--mono)}
.num{color:var(--muted);font-family:var(--mono);font-size:.7rem}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:.66rem;font-weight:700;font-family:var(--mono)}
.b-on{background:rgba(16,185,129,.12);color:var(--green);border:1px solid rgba(16,185,129,.25)}
.b-on::before{content:'';width:5px;height:5px;background:var(--green);border-radius:50%;animation:pulse 1.5s infinite}
.b-off{background:rgba(83,98,120,.1);color:var(--muted);border:1px solid rgba(83,98,120,.18)}
.term{font-size:.68rem;color:var(--muted);font-family:var(--mono);max-width:120px;overflow:hidden;text-overflow:ellipsis}
.lcnt{background:rgba(0,200,240,.1);color:var(--accent);border:1px solid rgba(0,200,240,.2);padding:2px 9px;border-radius:20px;font-family:var(--mono);font-size:.7rem;font-weight:700}
.pbar{width:100%;height:4px;background:var(--border);border-radius:3px;margin-top:4px}
.pfill{height:4px;border-radius:3px;background:linear-gradient(90deg,var(--accent),var(--accent2))}

/* PAGINATION */
.pagi{display:flex;justify-content:center;align-items:center;gap:7px;flex-wrap:wrap}
.pg{padding:7px 13px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--text);font-family:var(--mono);font-size:.74rem;text-decoration:none;transition:all .2s}
.pg:hover{border-color:var(--accent);color:var(--accent)}
.pg.cur{background:var(--accent);border-color:var(--accent);color:#000;font-weight:700}
.pg.dis{opacity:.3;pointer-events:none}
.pg-info{font-family:var(--mono);font-size:.7rem;color:var(--muted);margin:0 5px}
.empty{text-align:center;padding:55px 20px;color:var(--muted)}
.empty .icon{font-size:2.8rem;margin-bottom:10px}

@media(max-width:600px){h1{font-size:1.1rem}.sc-val{font-size:1.25rem}.inp-d{width:130px}}
</style>
</head>
<body>
<div class="wrap">

<!-- HEADER -->
<div class="header">
  <div class="logo">
    <div class="logo-icon">📡</div>
    <div>
      <h1>RADIUS <span>Accounting</span></h1>
      <div class="subtitle">FreeRADIUS Session Monitor · radacct</div>
    </div>
  </div>
  <div class="live-badge"><div class="dot"></div>LIVE DATA</div>
</div>

<!-- STAT CARDS -->
<div class="stats">
  <div class="sc c1">
    <div class="sc-icon">📋</div>
    <div class="sc-val"><?= number_format($stats['total']) ?></div>
    <div class="sc-lbl">Total Session</div>
  </div>
  <div class="sc c2">
    <div class="sc-icon">🟢</div>
    <div class="sc-val"><?= number_format($stats['active']) ?></div>
    <div class="sc-lbl">Session Aktif</div>
  </div>
  <div class="sc c3">
    <div class="sc-icon">👤</div>
    <div class="sc-val"><?= number_format($stats['unique_users']) ?></div>
    <div class="sc-lbl">Unique User</div>
  </div>
  <div class="sc c4">
    <div class="sc-icon">⏱</div>
    <div class="sc-val"><?= round($stats['total_sec']/3600,1) ?><span style="font-size:.9rem">h</span></div>
    <div class="sc-lbl">Total Durasi</div>
  </div>
  <div class="sc c5">
    <div class="sc-icon">⬇️</div>
    <div class="sc-val"><?= round($stats['total_dl']/1024/1024/1024,2) ?><span style="font-size:.9rem">GB</span></div>
    <div class="sc-lbl">Total Download</div>
  </div>
  <div class="sc c6">
    <div class="sc-icon">⬆️</div>
    <div class="sc-val"><?= round($stats['total_ul']/1024/1024/1024,2) ?><span style="font-size:.9rem">GB</span></div>
    <div class="sc-lbl">Total Upload</div>
  </div>
</div>

<!-- CONTROLS -->
<form method="GET" action="">
<input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">
<div class="ctrl-box">
  <!-- Baris 1: Search + Status -->
  <div class="ctrl-row">
    <input class="inp inp-s" type="text" name="search"
           placeholder="🔍  Cari username / NAS IP..."
           value="<?= htmlspecialchars($search) ?>">
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <a href="<?= buildUrl(['status'=>'all','page'=>1]) ?>"    class="fbtn <?= $filter_status==='all'?'on':'' ?>">Semua</a>
      <a href="<?= buildUrl(['status'=>'active','page'=>1]) ?>"  class="fbtn <?= $filter_status==='active'?'on-g':'' ?>">🟢 Online</a>
      <a href="<?= buildUrl(['status'=>'stopped','page'=>1]) ?>" class="fbtn <?= $filter_status==='stopped'?'on':'' ?>">⚫ Offline</a>
    </div>
    <button type="submit" class="btn-p">🔍 Cari</button>
    <a href="?" class="btn-r">✕ Reset</a>
  </div>
  <!-- Baris 2: Filter Tanggal + Export -->
  <div class="ctrl-row">
    <label class="date-lbl">📅 Dari:
      <input class="inp inp-d" type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
    </label>
    <label class="date-lbl">s/d:
      <input class="inp inp-d" type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
    </label>
    <a href="<?= buildUrl(['date_from'=>date('Y-m-d'),'date_to'=>date('Y-m-d'),'page'=>1]) ?>" class="fbtn">Hari Ini</a>
    <a href="<?= buildUrl(['date_from'=>date('Y-m-d',strtotime('-7 days')),'date_to'=>date('Y-m-d'),'page'=>1]) ?>" class="fbtn">7 Hari</a>
    <a href="<?= buildUrl(['date_from'=>date('Y-m-01'),'date_to'=>date('Y-m-d'),'page'=>1]) ?>" class="fbtn">Bulan Ini</a>
    <a href="<?= buildUrl(['export'=>'csv']) ?>" class="btn-csv">⬇ Export CSV</a>
  </div>
</div>
</form>

<!-- TABS -->
<div class="tabs">
  <a href="<?= buildUrl(['view'=>'table','page'=>1]) ?>"   class="tab <?= $view_mode==='table'?'active':'' ?>">📋 Data Session</a>
  <a href="<?= buildUrl(['view'=>'history','page'=>1]) ?>" class="tab <?= $view_mode==='history'?'active':'' ?>">📜 History Login User</a>
</div>

<?php if ($view_mode === 'history'):
/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TAB: HISTORY LOGIN PER USER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
$hist_rows = [];
$max_traf  = 0;
while ($r = mysqli_fetch_assoc($history_result)) {
    if ($r['total_traffic'] > $max_traf) $max_traf = $r['total_traffic'];
    $hist_rows[] = $r;
}
?>
<div class="tbox">
  <div class="tbox-hdr">
    <span class="tbox-title">📜 History Login per User</span>
    <span class="row-cnt"><?= count($hist_rows) ?> user ditemukan</span>
  </div>
  <div class="scroll">
  <table>
    <thead><tr>
      <th>#</th><th>Username</th><th>Jumlah Login</th>
      <th>Login Pertama</th><th>Login Terakhir</th>
      <th>Total Durasi</th><th>Download</th><th>Upload</th>
      <th>Total Traffic</th><th>Status</th>
    </tr></thead>
    <tbody>
    <?php if (count($hist_rows)): $no=1; foreach($hist_rows as $r): ?>
      <tr>
        <td class="num"><?= $no++ ?></td>
        <td><span class="uname"><?= htmlspecialchars($r['username']) ?></span></td>
        <td><span class="lcnt"><?= $r['login_count'] ?>× login</span></td>
        <td><span class="mono muted sm"><?= $r['first_login'] ?: '-' ?></span></td>
        <td><span class="mono muted sm"><?= $r['last_login']  ?: '-' ?></span></td>
        <td><span class="dur"><?= formatDuration($r['total_duration']) ?></span></td>
        <td><span class="dl">⬇ <?= fmtBytes($r['total_dl']) ?></span></td>
        <td><span class="ul">⬆ <?= fmtBytes($r['total_ul']) ?></span></td>
        <td>
          <span class="traf">⚡ <?= fmtBytes($r['total_traffic']) ?></span>
          <?php if ($max_traf > 0): ?>
          <div class="pbar"><div class="pfill" style="width:<?= round($r['total_traffic']/$max_traf*100) ?>%"></div></div>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($r['active_now'] > 0): ?><span class="badge b-on">ONLINE</span>
          <?php else: ?><span class="badge b-off">OFFLINE</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="10"><div class="empty"><div class="icon">📭</div><p>Tidak ada data</p></div></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php else:
/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   TAB: DATA SESSION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
?>
<div class="tbox">
  <div class="tbox-hdr">
    <span class="tbox-title">📋 Data Session</span>
    <span class="row-cnt"><?= number_format($total_rows) ?> record · hal <?= $page ?>/<?= $total_pages ?></span>
  </div>
  <div class="scroll">
  <table>
    <thead><tr>
      <th>#</th><th>Username</th><th>NAS IP</th>
      <th>Start Time</th><th>Stop Time</th><th>Durasi</th>
      <th>Download</th><th>Upload</th><th>Total Traffic</th>
      <th>Status</th><th>Terminate</th>
    </tr></thead>
    <tbody>
    <?php if (mysqli_num_rows($result) > 0):
      $no = $offset + 1;
      while ($row = mysqli_fetch_assoc($result)):
        $active  = isActive($row['acctstoptime']);
        $traffic = ($row['acctinputoctets']??0) + ($row['acctoutputoctets']??0);
    ?>
      <tr>
        <td class="num"><?= $no++ ?></td>
        <td><span class="uname"><?= htmlspecialchars($row['username']) ?></span></td>
        <td><span class="mono muted sm"><?= htmlspecialchars($row['nasipaddress']) ?></span></td>
        <td><span class="mono muted sm"><?= $row['acctstarttime'] ?: '-' ?></span></td>
        <td><span class="mono muted sm"><?= $active ? '<span style="color:var(--green)">—</span>' : $row['acctstoptime'] ?></span></td>
        <td><span class="dur"><?= formatDuration($row['acctsessiontime']) ?></span></td>
        <td><span class="dl">⬇ <?= fmtBytes($row['acctinputoctets']) ?></span></td>
        <td><span class="ul">⬆ <?= fmtBytes($row['acctoutputoctets']) ?></span></td>
        <td><span class="traf">⚡ <?= fmtBytes($traffic) ?></span></td>
        <td><?php if ($active): ?><span class="badge b-on">ONLINE</span><?php else: ?><span class="badge b-off">OFFLINE</span><?php endif; ?></td>
        <td><span class="term" title="<?= htmlspecialchars($row['acctterminatecause']) ?>"><?= htmlspecialchars($row['acctterminatecause']) ?: '-' ?></span></td>
      </tr>
    <?php endwhile; else: ?>
      <tr><td colspan="11"><div class="empty"><div class="icon">📭</div><p>Tidak ada data ditemukan</p></div></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- PAGINATION -->
<?php if ($total_pages > 1): ?>
<div class="pagi">
  <a href="<?= buildUrl(['page'=>$page-1]) ?>" class="pg <?= $page<=1?'dis':'' ?>">← Prev</a>
  <?php
    $s=max(1,$page-2); $e=min($total_pages,$page+2);
    if($s>1){ echo '<a href="'.buildUrl(['page'=>1]).'" class="pg">1</a>'; if($s>2) echo '<span class="pg-info">…</span>'; }
    for($i=$s;$i<=$e;$i++) echo '<a href="'.buildUrl(['page'=>$i]).'" class="pg '.($i===$page?'cur':'').'">'.$i.'</a>';
    if($e<$total_pages){ if($e<$total_pages-1) echo '<span class="pg-info">…</span>'; echo '<a href="'.buildUrl(['page'=>$total_pages]).'" class="pg">'.$total_pages.'</a>'; }
  ?>
  <span class="pg-info">dari <?= $total_pages ?> halaman</span>
  <a href="<?= buildUrl(['page'=>$page+1]) ?>" class="pg <?= $page>=$total_pages?'dis':'' ?>">Next →</a>
</div>
<?php endif; ?>
<?php endif; ?>

</div><!-- /wrap -->
</body>
</html>