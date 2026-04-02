<?php
/**
 * Cron — Apply Penalties
 * Run via: php cron/apply_penalties.php
 * Crontab example: 0 2 * * * php /path/to/cron/apply_penalties.php >> /path/to/logs/penalty_cron.log 2>&1
 */

define('CRON_MODE', true);
define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';
require APP_ROOT . '/core/env.php';
env_load(APP_ROOT . '/.env');

require APP_ROOT . '/config/app.php';
require APP_ROOT . '/config/database.php';

require APP_ROOT . '/core/db.php';
require APP_ROOT . '/core/helpers.php';

$logFile = APP_ROOT . '/logs/penalty_cron.log';
$ts = date('Y-m-d H:i:s');

try {
    $msg = require APP_ROOT . '/modules/finance/actions/apply_penalties.php';
    $line = "[$ts] OK — $msg";
} catch (Throwable $e) {
    $line = "[$ts] ERROR — " . $e->getMessage();
}

file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
echo $line . PHP_EOL;
