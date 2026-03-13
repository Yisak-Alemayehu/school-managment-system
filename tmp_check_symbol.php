<?php

define('APP_ROOT', __DIR__);
require 'config/app.php';
require 'config/database.php';
require 'core/db.php';

$val = db_fetch_value("SELECT setting_value FROM settings WHERE setting_group = ? AND setting_key = ?", ['system','currency_symbol']);
var_dump($val);
echo "-- done\n";
