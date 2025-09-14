<?php
/* simple stats dashboard
   protected by token via ?t=token_value
   reads db credentials and token via db_config.php
*/

require __DIR__ . '/db_config.php';

/* denying if token missing or mismatched */
$token = $_GET['t'] ?? '';
if (!$ADMIN_TOKEN || $token !== $ADMIN_TOKEN) {
  http_response_code(403);
  echo 'forbidden';
  exit;
}

/* connecting to mysql with mysqli */
$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
  http_response_code(500);
  echo 'db connection failed';
  exit;
}

/* fetching totals */
$totalVisits = (int)($mysqli->query("SELECT COUNT(*) AS c FROM visits")->fetch_assoc()['c'] ?? 0);
$totalClicks = (int)($mysqli->query("SELECT COUNT(*) AS c FROM clicks")->fetch_assoc()['c'] ?? 0);

/* fetching per-button counts */
$btnRes = $mysqli->query("
  SELECT button, COUNT(*) AS c
  FROM clicks
  GROUP BY button
  ORDER BY c DESC
");

/* fetching visits by day (last 14) */
$visitsRes = $mysqli->query("
  SELECT DATE(ts) AS d, COUNT(*) AS c
  FROM visits
  GROUP BY DATE(ts)
  ORDER BY d DESC
  LIMIT 14
");

/* fetching clicks by day (last 14) */
$clicksRes = $mysqli->query("
  SELECT DATE(ts) AS d, COUNT(*) AS c
  FROM clicks
  GROUP BY DATE(ts)
  ORDER BY d DESC
  LIMIT 14
");

/* fetching recent rows */
$recentClicks = $mysqli->query("SELECT ts, button, ip FROM clicks ORDER BY ts DESC LIMIT 25");
$recentVisits = $mysqli->query("SELECT ts, ip FROM visits ORDER BY ts DESC LIMIT 25");

/* escaping helper */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>stats dashboard</title>
<style>
  :root{ --bg:#0b0b0b; --card:#151515; --line:#2a2a2a; --txt:#eee; --mut:#aaa; --acc:#FED002; }
  *{ box-sizing:border-box }
  body{ margin:0; background:var(--bg); color:var(--txt); font:14px/1.4 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; padding:24px; }
  h1{ margin:0 0 16px; font-size:22px }
  h2{ margin:24px 0 12px; font-size:16px; color:var(--acc) }
  .grid{ display:grid; gap:16px; grid-template-columns:1fr; max-width:1000px; margin:0 auto; }
  @media(min-width:900px){ .grid{ grid-template-columns:1fr 1fr; } }
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
      <h1>site stats</h1>
      <div class="kpis">
        <div class="kpi"><div>total visits</div><b><?= $totalVisits ?></b></div>
        <div class="kpi"><div>total clicks</div><b><?= $totalClicks ?></b></div>
      </div>
      <p class="muted">protected by token. keep this url private.</p>
    </div>

    <div class="card">
      <h2>clicks by button</h2>
      <table>
        <thead><tr><th>button</th><th>clicks</th></tr></thead>
        <tbody>
          <?php while($r = $btnRes->fetch_assoc()): ?>
            <tr><td><?= h($r['button']) ?></td><td><?= (int)$r['c'] ?></td></tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>visits (last 14 days)</h2>
      <div class="bars">
        <?php
          $vis = []; $maxV = 0;
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
      <h2>clicks (last 14 days)</h2>
      <div class="bars">
        <?php
          $clk = []; $maxC = 0;
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
      <h2>recent clicks</h2>
      <table>
        <thead><tr><th>time</th><th>button</th><th>ip</th></tr></thead>
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
      <h2>recent visits</h2>
      <table>
        <thead><tr><th>time</th><th>ip</th></tr></thead>
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