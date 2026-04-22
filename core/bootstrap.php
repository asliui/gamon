<?php

declare(strict_types=1);

use WebGamon\Core\Auth;
use WebGamon\Core\DB;

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Response.php';

$config = require __DIR__ . '/../config/config.php';

mb_internal_encoding('UTF-8');
date_default_timezone_set('UTC');

ini_set('session.use_strict_mode', '1');
session_name($config['security']['session_name']);
session_start();

DB::init($config);

