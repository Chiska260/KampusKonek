<?php
// every page and action starts by requiring this file
require_once __DIR__ . '/../config/config.php';
date_default_timezone_set(APP_TIMEZONE);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/orgs.php';
require_once __DIR__ . '/events.php';
require_once __DIR__ . '/tasks.php';
require_once __DIR__ . '/posts.php';
require_once __DIR__ . '/components.php';
require_once __DIR__ . '/layout.php';
