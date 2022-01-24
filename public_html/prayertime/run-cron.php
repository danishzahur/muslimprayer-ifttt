<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_time_limit(660);

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/utility/Ifttt.php');

$ifttt = new Ifttt();
//$result = $ifttt->runCronTriggerForNext10Min();
$result = $ifttt->runCronTriggerForNext1Min();