<?php
/* tiny write endpoint for stats
   accepts form-encoded post:
     event=visit
     event=click&button=...
   stores ip and user-agent
   reads db credentials via db_config.php
*/

header('content-type: application/json');

require __DIR__ . '/db_config.php';

/* connecting to mysql */
$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>'db_connect']);
  exit;
}

/* reading inputs */
$event = $_POST['event'] ?? '';
$button = trim((string)($_POST['button'] ?? ''));
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

/* handling visit */
if ($event === 'visit') {
  $stmt = $mysqli->prepare("INSERT INTO visits (ip, user_agent) VALUES (?, ?)");
  $stmt->bind_param("ss", $ip, $ua);
  $stmt->execute();
  echo json_encode(['ok'=>true]); exit;
}

/* handling click */
if ($event === 'click' && $button !== '') {
  $stmt = $mysqli->prepare("INSERT INTO clicks (button, ip, user_agent) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $button, $ip, $ua);
  $stmt->execute();
  echo json_encode(['ok'=>true]); exit;
}

/* default response for bad input */
echo json_encode(['ok'=>false, 'error'=>'bad_event']);