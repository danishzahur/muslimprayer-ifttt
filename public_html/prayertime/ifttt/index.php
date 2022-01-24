<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('../../../vendor/autoload.php');
require('../utility/Ifttt.php');

$requestData = json_decode(file_get_contents("php://input"), true);

$uri = str_replace('v1/', '', $_SERVER['REQUEST_URI']);
if (strpos($uri, '?') !== false) {
	$uri = substr($uri, 0, strpos($uri, '?'));
}
$service = substr(strstr($uri, '/ifttt/'), 7);
$service = explode('/', $service);

if (count($service) > 2 && $service[2] != '') {
	for ($i = 2; $i <= count($service) && isset($service[$i + 1]); $i += 2) {
		$requestData[$service[$i]] = @$service[$i + 1];
	}
}

$ifttt = new Ifttt($requestData);
$result = $ifttt->isValidChannel();
if ($result !== true) {
	echo $result;
	exit;
}

$method = (array_key_exists('0', $service) ? strtolower($service[0]) : '') . 
	(array_key_exists('1', $service) ? str_replace(' ', '', ucwords(str_replace('_', ' ', $service[1]))) : '');
if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
	$method .= 'Delete';
}
else if (strpos($_SERVER['REQUEST_URI'], '/fields/') !== false) {
	$method .= 'Fields';
}

echo call_user_func([$ifttt, $method]);