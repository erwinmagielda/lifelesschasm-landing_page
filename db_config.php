<?php
/* loading credentials from json sitting in the same directory */
$cred_path = __DIR__ . '/credentials.json';
$raw = @file_get_contents($cred_path);

/* failing fast if file missing or unreadable */
if ($raw === false) {
  http_response_code(500);
  exit('missing credentials.json');
}

/* decoding json and validating structure */
$creds = json_decode($raw, true);
if (!is_array($creds)) {
  http_response_code(500);
  exit('invalid credentials.json');
}

/* exporting expected vars for other scripts */
$DB_HOST = $creds['db_host'] ?? 'localhost';
$DB_NAME = $creds['db_name'] ?? '';
$DB_USER = $creds['db_user'] ?? '';
$DB_PASS = $creds['db_pass'] ?? '';

/* exporting admin token used by admin.php */
$ADMIN_TOKEN = $creds['admin_token'] ?? '';