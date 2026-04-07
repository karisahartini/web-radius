<?php
$conn2 = new mysqli("localhost", "root", "", "radius");
$conn2->set_charset("utf8mb4");

$query = "SELECT username, nasipaddress,
          acctstarttime, acctsessiontime,
          acctinputoctets, acctoutputoctets
          FROM radacct
          WHERE (acctstoptime IS NULL OR acctstoptime = '0000-00-00 00:00:00')
          ORDER BY acctstarttime DESC";

$result = mysqli_query($conn2, $query);
if (!$result) die("Query error: " . $conn2->error);

$total_online = mysqli_num_rows($result);

if (!function_exists('formatDurationAS')) {
    function formatDurationAS($seconds) {
        if (!$seconds || $seconds < 0) return '00:00:00';
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
if (!function_exists('fmtMB')) {
    function fmtMB($bytes) {
        if (!$bytes) return '0 MB';
        $mb = $bytes / 1024 / 1024;
        if ($mb >= 1024) return round($mb/1024, 2).' GB';
        return round($mb, 2).' MB';
    }
}

$total_dl = 0; $total_ul = 0;
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $total_dl += $row['acctinputoctets'];
    $total_ul += $row['acctoutputoctets'];
    $rows[] = $row;
}
?>

<style>
.as-wrap { color: #dde6f5; font-family: 'Arial', sans-serif; }
.as-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
.as-title { display:flex; align-items:center; gap:12px; }
.as-title-icon { width:42px; height:42px; border-radius:10px; background:linear-gradient(135deg,#10b981,#00c8f0); display:flex; align-items:center; justify-content:center; font-size:20px; }
.as-title h2 { margin:0; font-size:18px; color:#fff; }
.as-title p { margin:2px 0 0; font-size:12px; color:#536278; font-family:monospace; }
.as-live { display:flex; align-items:center; gap:6px; background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.3); border-radius:20px; padding:5px 13px; font-size:11px; color:#10b981; font-family:monospace; }
.as-dot { width:6px; height:6px; background:#10b981; border-radius:50%; animation:as-pulse 1.5s infinite; }
@keyframes as-pulse{0%,100%{opacity:1}50%{opacity:.3}}

.as-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-bottom:18px; }
.as-sc { background:#1a2a3a; border:1px solid #2a3f55; border-radius:12px; padding:15px 17px; position:relative; overflow:hidden; }
.as-sc::before { content:''; position:absolute; top:0; left:0; right:0; height:2px; }
.as-sc.c1::before { background:linear-gradient(90deg,#10b981,transparent); }
.as-sc.c2::before { background:linear-gradient(90deg,#38bdf8,transparent); }
.as-sc.c3::before { background:linear-gradient(90deg,#a78bfa,transparent); }
.as-sc-icon { font-size:18px; margin-bottom:7px; }
.as-sc-val { font-family:monospace; font-size:22px; font-weight:700; line-height:1; margin-bottom:4px; }
.as-sc.c1 .as-sc-val { color:#10b981; }
.as-sc.c2 .as-sc-val { color:#38bdf8; }
.as-sc.c3 .as-sc-val { color:#a78bfa; }
.as-sc-lbl { font-size:11px; color:#536278; text-transform:uppercase; letter-spacing:.5px; }

.as-tbox { background:#1a2a3a; border:1px solid #2a3f55; border-radius:12px; overflow:hidden; }
.as-tbox-hdr { padding:12px 18px; border-bottom:1px solid #2a3f55; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
.as-tbox-title { font-size:12px; font-weight:700; color:#536278; text-transform:uppercase; letter-spacing:1px; }
.as-row-cnt { font-family:monospace; font-size:12px; color:#10b981; }
.as-scroll { overflow-x:auto; }
.as-tbl { width:100%; border-collapse:collapse; font-size:13px; }
.as-tbl thead th { padding:9px 14px; text-align:left; background:#0f1e2d; color:#536278; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; border-bottom:1px solid #2a3f55; }
.as-tbl tbody tr { border-bottom:1px solid #1e3045; transition:background .15s; }
.as-tbl tbody tr:last-child { border-bottom:none; }
.as-tbl tbody tr:hover { background:rgba(52,152,219,.05); }
.as-tbl td { padding:11px 14px; white-space:nowrap; }
.as-uname { font-family:monospace; color:#00c8f0; font-weight:700; }
.as-mono { font-family:monospace; color:#536278; font-size:12px; }
.as-dl { color:#38bdf8; font-family:monospace; }
.as-ul { color:#a78bfa; font-family:monospace; }
.as-dur { color:#f59e0b; font-family:monospace; }
.as-num { color:#536278; font-family:monospace; font-size:11px; }
.as-badge { display:inline-flex; align-items:center; gap:4px; background:rgba(16,185,129,.12); color:#10b981; border:1px solid rgba(16,185,129,.25); padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; font-family:monospace; }
.as-badge::before { content:''; width:5px; height:5px; background:#10b981; border-radius:50%; animation:as-pulse 1.5s infinite; }
.as-empty { text-align:center; padding:50px 20px; color:#536278; }
.as-empty .icon { font-size:2.5rem; margin-bottom:8px; }
.as-refresh { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.3); border-radius:8px; color:#10b981; font-size:12px; text-decoration:none; cursor:pointer; transition:all .2s; }
.as-refresh:hover { background:rgba(16,185,129,.2); }
</style>

<div class="as-wrap">

  <!-- HEADER -->
  <div class="as-header">
    <div class="as-title">
      <div class="as-title-icon">🟢</div>
      <div>
        <h2>Active Sessions</h2>
        <p>User yang sedang online · radacct</p>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <a href="?page=active_session" class="as-refresh">🔄 Refresh</a>
      <div class="as-live"><div class="as-dot"></div>LIVE DATA</div>
    </div>
  </div>

  <!-- STAT CARDS -->
  <div class="as-stats">
    <div class="as-sc c1">
      <div class="as-sc-icon">🟢</div>
      <div class="as-sc-val"><?= $total_online ?></div>
      <div class="as-sc-lbl">User Online</div>
    </div>
    <div class="as-sc c2">
      <div class="as-sc-icon">⬇️</div>
      <div class="as-sc-val"><?= fmtMB($total_dl) ?></div>
      <div class="as-sc-lbl">Total Download</div>
    </div>
    <div class="as-sc c3">
      <div class="as-sc-icon">⬆️</div>
      <div class="as-sc-val"><?= fmtMB($total_ul) ?></div>
      <div class="as-sc-lbl">Total Upload</div>
    </div>
  </div>

  <!-- TABLE -->
  <div class="as-tbox">
    <div class="as-tbox-hdr">
      <span class="as-tbox-title">📋 Daftar User Online</span>
      <span class="as-row-cnt"><?= $total_online ?> user aktif</span>
    </div>
    <div class="as-scroll">
    <table class="as-tbl">
      <thead>
        <tr>
          <th>#</th>
          <th>Username</th>
          <th>NAS IP</th>
          <th>Start Time</th>
          <th>Durasi</th>
          <th>Download</th>
          <th>Upload</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($rows) > 0): $no = 1; foreach ($rows as $row): ?>
        <tr>
          <td class="as-num"><?= $no++ ?></td>
          <td><span class="as-uname"><?= htmlspecialchars($row['username']) ?></span></td>
          <td><span class="as-mono"><?= htmlspecialchars($row['nasipaddress']) ?></span></td>
          <td><span class="as-mono"><?= $row['acctstarttime'] ?: '-' ?></span></td>
          <td><span class="as-dur"><?= formatDurationAS($row['acctsessiontime']) ?></span></td>
          <td><span class="as-dl">⬇ <?= fmtMB($row['acctinputoctets']) ?></span></td>
          <td><span class="as-ul">⬆ <?= fmtMB($row['acctoutputoctets']) ?></span></td>
          <td><span class="as-badge">ONLINE</span></td>
        </tr>
      <?php endforeach; else: ?>
        <tr>
          <td colspan="8">
            <div class="as-empty">
              <div class="icon">📭</div>
              <p>Tidak ada user yang sedang online</p>
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
setTimeout(() => location.reload(), 30000);
</script>