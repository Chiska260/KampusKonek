<?php
// database settings (XAMPP defaults, edit if yours are different)
define('DB_HOST', 'localhost');
define('DB_NAME', 'kampuskonek_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3307);

define('APP_NAME', 'KampusKonek');
define('APP_TIMEZONE', 'Asia/Manila');
define('MIN_PASSWORD_LENGTH', 8);

// institutional email domains
define('STUDENT_DOMAINS', ['students.nu-laguna.edu.ph']);
define('ADMIN_DOMAINS', ['admin.nu-laguna.edu.ph', 'admin.nu-laguna-edu.ph']);

// folder path of the app relative to the web root, e.g. "/kampuskonek".
// figured out automatically so it works in htdocs or as a site root.
$docRoot = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$appDir  = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
define('BASE_URL', ($docRoot !== '' && str_starts_with($appDir, $docRoot)) ? substr($appDir, strlen($docRoot)) : '');
unset($docRoot, $appDir);
?>