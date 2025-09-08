<?php
header('Content-Type: application/json');
require __DIR__ . '/db_config.php';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["ok"=>false, "error"=>"db_connect"]);
    exit;
}

$event = $_POST['event'] ?? '';
$button = $_POST['button'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

if ($event === "visit") {
    $stmt = $mysqli->prepare("INSERT INTO visits (ip, user_agent) VALUES (?, ?)");
    $stmt->bind_param("ss", $ip, $ua);
    $stmt->execute();
    echo json_encode(["ok"=>true]); exit;
}

if ($event === "click" && $button !== '') {
    $stmt = $mysqli->prepare("INSERT INTO clicks (button, ip, user_agent) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $button, $ip, $ua);
    $stmt->execute();
    echo json_encode(["ok"=>true]); exit;
}

echo json_encode(["ok"=>false, "error"=>"bad_event"]);
?>