<?php
require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$kind = dashboard_kind();
render_header('Dashboard', 'dashboard');
echo page_head('Hi, ' . explode(' ', preg_replace('/^(Prof\.|Dr\.|Mr\.|Ms\.|Mrs\.)\s+/', '', $user['full_name']))[0], date('l, F j'));
require __DIR__ . '/views/dashboard_' . $kind . '.php';
render_footer();
