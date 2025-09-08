<?php
/* ============================
   Simple Stats Dashboard
   - Protect with ?t=YOUR_TOKEN
   - Requires db_config.php (host, name, user, pass)
   ============================ */

$TOKEN = 'CHANGE_ME_LONG_RANDOM';  // <- set a long random string and DON'T commit this

if (($_GET['t'] ?? '') !== $TOKEN) {
  http_response_code(403);
  echo "Forbidden.";
  exit;
}

require __DIR__ . '/db_config.php';

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
  http_response_code(500);
  echo "DB connection failed.";
  exit;
}

// Totals
$totalVisits = (int)($mysqli->query("SELECT COUNT(*) AS c FROM visits")->fetch_assoc()['c'] ?? 0);
$totalClicks = (int)($mysqli->query("SELECT COUNT(*) AS c FROM clicks")->fetch_assoc()['c'] ?? 0);

// Per-button counts
$btnRes = $mysqli->query("SELECT button, COUNT(*) AS c FROM clicks GROUP BY button ORDER BY c DESC");

// Visits by day (last 14)
$visitsRes = $mysqli->query("
  SELECT DATE(ts) AS d, COUNT(*) AS c
  FROM visits
  GROUP BY DATE(ts)
  ORDER BY d DESC
  LIMIT 14
");

// Clicks by day (last 14)
$clicksRes = $mysqli->query("
  SELECT DATE(ts) AS d, COUNT(*) AS c
  FROM clicks
  GROUP BY DATE(ts)
  ORDER BY d DESC
  LIMIT 14
");

// Recent rows
$recentClicks = $mysqli->query("SELECT ts, button, ip FROM clicks ORDER BY ts DESC LIMIT 25");
$recentVisits = $mysqli->query("SELECT ts, ip FROM visits ORDER BY ts DESC LIMIT 25");

// Helper safe output
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?><!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Stats Dashboard</title>
<style>
  :root{ --bg:#0b0b0b; --card:#151515; --line:#2a2a2a; --txt:#eee; --mut:#aaa; --acc:#FED002; }
  *{ box-sizing:border-box }
  body{ margin:0; background:var(--bg); color:var(--txt); font:14px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; padding:24px; }
  h1{ margin:0 0 16px; font-size:22px }
  h2{ margin:24px 0 12px; font-size:16px; color:var(--acc) }
  .grid{ display:grid; gap:16px; grid-template-columns: 1fr; max-width:1000px; margin:0 auto; }
  @media(min-width:900px){ .grid{ grid-template-columns: 1fr 1fr; } }
  .card{ background:var(--card); border:1px solid var(--line); border-radius:12px; padding:16px; }
  .kpis{ display:flex; gap:16px; }
  .kpi{ flex:1; background:#101010; border:1px solid var(--line); border-radius:12px; padding:12px; text-align:center; }
  .kpi b{ display:block; font-size:22px; margin-top:6px; color:#fff; }
  table{ width:100%; border-collapse:collapse; }
  th,td{ border-bottom:1px solid var(--line); padding:8px 6px; text-align:left; }
  th{ color:var(--acc); font-weight:600; }
  .muted{ color:var(--mut); font-size:12px; }
  .bars{ display:flex; flex-direction:column; gap:6px; }
  .bar{ display:flex; align-items:center; gap:8px; }
  .bar span{ width:80px; color:var(--mut); font-size:12px; }
  .bar .track{ flex:1; height:8px; background:#0f0f0f; border:1px solid var(--line); border-radius:999px; overflow:hidden; }
  .bar .fill{ height:100%; background:var(--acc); width:0 }
</style>
<body>
  <div class="grid">
    <div class="card">
      <h1>Site stats</h1>
      <div class="kpis">
        <div class="kpi"><div>Total visits</div><b><?= $totalVisits ?></b></div>
        <div class="kpi"><div>Total clicks</div><b><?= $totalClicks ?></b></div>
      </div>
      <p class="muted">Protected by token. Keep this URL private.</p>
    </div>

    <div class="card">
      <h2>Clicks by button</h2>
      <table>
        <thead><tr><th>Button</th><th>Clicks</th></tr></thead>
        <tbody>
        <?php while($r = $btnRes->fetch_assoc()): ?>
          <tr><td><?= h($r['button']) ?></td><td><?= (int)$r['c'] ?></td></tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>Visits (last 14 days)</h2>
      <div class="bars">
        <?php
          $vis = [];
          $maxV = 0;
          while($r = $visitsRes->fetch_assoc()){ $vis[] = $r; $maxV = max($maxV, (int)$r['c']); }
          foreach($vis as $r):
            $w = $maxV ? (int)round(100 * (int)$r['c'] / $maxV) : 0;
        ?>
          <div class="bar">
            <span><?= h($r['d']) ?></span>
            <div class="track"><div class="fill" style="width:<?= $w ?>%"></div></div>
            <span><?= (int)$r['c'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h2>Clicks (last 14 days)</h2>
      <div class="bars">
        <?php
          $clk = [];
          $maxC = 0;
          while($r = $clicksRes->fetch_assoc()){ $clk[] = $r; $maxC = max($maxC, (int)$r['c']); }
          foreach($clk as $r):
            $w = $maxC ? (int)round(100 * (int)$r['c'] / $maxC) : 0;
        ?>
          <div class="bar">
            <span><?= h($r['d']) ?></span>
            <div class="track"><div class="fill" style="width:<?= $w ?>%"></div></div>
            <span><?= (int)$r['c'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h2>Recent clicks</h2>
      <table>
        <thead><tr><th>Time</th><th>Button</th><th>IP</th></tr></thead>
        <tbody>
        <?php while($r = $recentClicks->fetch_assoc()): ?>
          <tr>
            <td><?= h($r['ts']) ?></td>
            <td><?= h($r['button']) ?></td>
            <td><?= h($r['ip']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>Recent visits</h2>
      <table>
        <thead><tr><th>Time</th><th>IP</th></tr></thead>
        <tbody>
        <?php while($r = $recentVisits->fetch_assoc()): ?>
          <tr>
            <td><?= h($r['ts']) ?></td>
            <td><?= h($r['ip']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>