<?php

use \GeniusTS\PrayerTimes\Prayer;
use \GeniusTS\PrayerTimes\Coordinates;

require('Db.php');
require('TimezoneMapper.php');

class Ifttt
{
	protected $IFTTT_Channel_Key;
	protected $IFTTT_Service_Key;
	protected $config;
	
	protected $prayerNames = [
			'01' => 'fajr',
			//'00' => 'sunrise',
			'02' => 'duhr',
			'03' => 'asr',
			'04' => 'maghrib',
			'05' => 'isha',
		];

	public function __construct($config = [])
	{
		$this->config = $config;

		$configFile = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
    	$config = include($configFile);

		$this->IFTTT_Channel_Key = $config['IFTTT']['IFTTT_Channel_Key'];
		$this->IFTTT_Service_Key = $config['IFTTT']['IFTTT_Service_Key'];
	}

	public function isLive()
	{
		//return (strpos($_SERVER['HTTP_HOST'], '.com') > 0);
		$headers = apache_request_headers();
		return !(isset($headers['IFTTT-Test-Mode']) && $headers['IFTTT-Test-Mode'] == 1);
	}

	public function isValidChannel()
	{
		$headers = apache_request_headers();

		if (isset($headers['Ifttt-Channel-Key']) &&
			strpos($_SERVER['REQUEST_URI'], '/cron/') === false &&
			strpos($_SERVER['REQUEST_URI'], '/oauth2/') === false &&
			$this->isLive() &&
			$headers['Ifttt-Channel-Key'] !== $this->IFTTT_Channel_Key) {

			$errors = [['message' => 'Invalid channel key']];
			return $this->errorResponse($errors, 401);
		}
		else if (isset($headers['Authorization']) &&
			strpos($_SERVER['REQUEST_URI'], '/cron/') === false &&
			strpos($_SERVER['REQUEST_URI'], '/oauth2/') === false &&
			$this->isLive() &&
			$this->getAccessTokenData() === false) {
			$errors = [['message' => 'Invalid access token']];
			return $this->errorResponse($errors, 401);
		}

		return true;
	}

	public function status()
	{
		if ($_SERVER['HTTP_IFTTT_CHANNEL_KEY'] != $this->IIFTTT_Channel_Key) {
			return $this->errorResponse(null);
		}

		return $this->jsonResponse(null);
	}

	public function jsonResponse($data)
	{
		// Specify as Realtime trigger.
		header('X-IFTTT-Realtime: 1');
		
		header('Content-Type: application/json; charset=utf-8');
		return json_encode($data);
	}

	public function errorResponse($errors, $code = 401)
	{
		$data = ['errors' => $errors];

		http_response_code($code);
		return $this->jsonResponse($data);
	}

	public function errorActionResponse($message)
	{
		$errors = [
			'status' => 'SKIP',
			'message' => $message,
		];
		return $this->errorResponse($errors, 200);
	}

	public function __call($name, $args)
	{
		return $this->errorResponse([]);
	}

	public function triggersPrayerTime()
	{
		$errors = null;
		//$this->saveTriggerData($this->config, 0);
			
		if ($this->isLive()) {

			if (!array_key_exists('triggerFields', $this->config)) {
				$errors[] = ['message' => 'Missing trigger fields'];
			}
			else {
				foreach ($this->prayerNames as $prayerId => $prayerName) {
					if ( !array_key_exists($prayerName, $this->config['triggerFields'])) {
						$errors[] = ['message' => 'Missing trigger field ['. $prayerName .']'];
					}
				}
				
				if (!array_key_exists('location', $this->config['triggerFields'])) {
					$errors[] = ['message' => 'Missing trigger fields [location]'];
				}
				else {
					
					if (!array_key_exists('lng', $this->config['triggerFields']['location'])) {
						$errors[] = ['message' => 'Missing trigger fields longitude'];
						
						//$this->config['triggerFields']['location']['lng'] = 13.055010;
					}

					if (!array_key_exists('lat', $this->config['triggerFields']['location'])) {
						$errors[] = ['message' => 'Missing trigger fields latitude'];

						//$this->config['triggerFields']['location']['lat'] = 47.809490;
					}
					
				}
			}

			if ($errors != null) {
				return $this->errorResponse($errors, 400);
			}
		}
		else {
			$testData = $this->getTestData();
			$this->config['trigger_identity'] = $testData['data']['samples']['triggers']['trigger_identity'];
			$this->config['triggerFields'] = $testData['data']['samples']['triggers']['prayer_time'];
		}

		$prayerData = $this->getTodayPrayer($this->config);
		$nextNotification = time();
		for ($i = count($prayerData) - 1; $i >= 0; $i--) {
			if ($prayerData[$i]['meta']['timestamp'] > time()) {
				$nextNotification = $prayerData[$i]['meta']['timestamp'];
				unset($prayerData[$i]);
			}
		}

		$this->saveTriggerData($this->config, $nextNotification);

		krsort($prayerData);
		$data = ['data' => array_values($prayerData)];

		if ( $this->isLive() && array_key_exists('limit', $this->config)) {
			$data['data'] = array_slice($data['data'], 0, $this->config['limit']);
		}
		
		header('X-IFTTT-Realtime: 1');
		return $this->jsonResponse($data);
	}

	public function triggersPrayerTimeDelete()
	{
		$this->deleteTriggerData($this->config['trigger_identity']);
		
		return $this->jsonResponse(NULL);
	}
	
	private function saveTriggerData($config, $nextNotification)
	{
		if (trim($config['trigger_identity']) != '') {
			
			$row = $this->getDbConnection()->fetchAll(
				'SELECT trigger_identity, next_notification FROM prayer_time WHERE trigger_identity=?',
				$config['trigger_identity']
			);
			
			if (empty($row)) {
				$row = $this->getDbConnection()->fetchAll(
					'INSERT INTO prayer_time (trigger_identity, next_notification) VALUES (?, ?)',
					$config['trigger_identity'],
					$nextNotification
				);
			}
			else {
				$row = $this->getDbConnection()->fetchAll(
					'UPDATE prayer_time SET next_notification = ? WHERE trigger_identity = ?',
					$nextNotification,
					$config['trigger_identity']
				);
			}
		}
	}

	private function getDbConnection()
	{
		if (!isset($this->dbConnection)) {
			$this->dbConnection = new Db();
		}

		return $this->dbConnection;
	}

	private function deleteTriggerData($triggerIdentity)
	{
		$row = $this->getDbConnection()->fetchAll(
			'DELETE FROM prayer_time WHERE trigger_identity = ?',
			$triggerIdentity
		);
	}

	public function runCronTriggerForNext10Min()
	{
		$currTime = strtotime(date('d F Y H:i:00'));
		$rows = $this->getDbConnection()->fetchAll(
			'SELECT DISTINCT next_notification FROM prayer_time WHERE next_notification BETWEEN ? AND ? ORDER BY next_notification',
			$currTime,
			$currTime + 600
		);

		foreach ($rows as $row) {
			if ($row->next_notification - time() > 0) {
				sleep($row->next_notification - time());
			}
			$this->cronTrigger($row->next_notification);
		}

		$row = $this->getDbConnection()->fetchAll(
			//'DELETE FROM prayer_time WHERE next_notification > 0 AND next_notification < ?',
			'UPDATE prayer_time SET next_notification = ? WHERE next_notification > ? AND next_notification < ?',
			0,
			0,
			strtotime('-1 week')
		);
	}

	public function runCronTriggerForNext1Min()
	{
		$currTime = strtotime(date('d F Y H:i:00'));
		$rows = $this->getDbConnection()->fetchAll(
			'SELECT DISTINCT next_notification FROM prayer_time WHERE next_notification BETWEEN ? AND ? ORDER BY next_notification',
			$currTime,
			$currTime + 60
		);

		foreach ($rows as $row) {
			if ($row->next_notification - time() > 0) {
				sleep($row->next_notification - time());
			}
			$this->cronTrigger($row->next_notification);
		}

		$row = $this->getDbConnection()->fetchAll(
			'DELETE FROM prayer_time WHERE next_notification > ? AND next_notification < ?',
			0,
			strtotime('-1 week')
		);
	}

	public function cronTrigger($currTime)
	{
		if (!$currTime) {
			$currTime = strtotime(date('d F Y H:i:00'));
		}
		$requestData = [];

		$rows = $this->getDbConnection()->fetchAll(
			'SELECT trigger_identity FROM prayer_time WHERE next_notification > ? AND next_notification <= ?',
			0,
			$currTime
		);
		foreach ($rows as $row) {
			if (trim($row->trigger_identity) != '') {
				$requestData[] = ['trigger_identity' => $row->trigger_identity];
			}
		}

		if (count($requestData) > 0) {
			$i = 0;
			do {
				$requestDataTemp = array_slice($requestData, $i, 1000);
				$i += 1000;

				$ch = curl_init('https://realtime.ifttt.com/v1/notifications');

				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $this->jsonResponse(['data' => $requestDataTemp]) );
				curl_setopt($ch, CURLOPT_HTTPHEADER, [
					'IFTTT-Service-Key: '. $this->IFTTT_Service_Key,
					'Accept: application/json',
					'Accept-Charset: utf-8',
					'Accept-Encoding: gzip, deflate',
					'Content-Type: application/json',
					'X-Request-ID: '. uniqid(),
				]);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				$result = curl_exec($ch);
				curl_close($ch);
				
			} while(count($requestDataTemp) === 1000);
		}

		return $this->jsonResponse($requestData);
	}

	private function getTodayPrayer($config)
	{
		$lat = $config['triggerFields']['location']['lat'];
		$lng = $config['triggerFields']['location']['lng'];

		$prayer = new Prayer();
		$prayer->setCoordinates($lng, $lat);

		//$prayer->setMethod(Prayer::METHOD_MUSLIM_WORLD_LEAGUE);
		$prayer->setMethod(constant('\\GeniusTS\\PrayerTimes\\Prayer::'. $config['triggerFields']['calculation_methods']));
		
		//$prayer->setMathhab(Prayer::MATHHAB_HANAFI');
		$prayer->setMathhab(constant('\\GeniusTS\\PrayerTimes\\Prayer::'. $config['triggerFields']['mathhab']));

		//$prayer->setHighLatitudeRule(Prayer::HIGH_LATITUDE_MIDDLE_OF_NIGHT);
		$prayer->setHighLatitudeRule(constant('\\GeniusTS\\PrayerTimes\\Prayer::'. $config['triggerFields']['high_latitude_rules']));

		$prependId = isset($config['trigger_identity']) ? $config['trigger_identity'] : '';
		$timezone = $this->getTimezone($config);
		
		$data = [];
		foreach ([
			$prayer->times(date('Y-m-d', strtotime('-1 day'))),
			$prayer->times(date('Y-m-d')),
			$prayer->times(date('Y-m-d', strtotime('+1 day')))
			] as $times) {
			foreach ($this->prayerNames as $prayerId => $prayerName) {
				$dates = $this->getDates($times->{$prayerName}->format('c'), $timezone);
				if (@$config['triggerFields'][$prayerName] !== "0")
				{
					$prayerName = ucfirst($prayerName);
					$data[] = [
						'name' => $prayerName,
						'message' => 'Time for '. $prayerName .' prayer at '. $dates['time'],
						// Ashan from http://www.islamcan.com/audio/adhan/index.shtml
						//'audio_url' => 'http://www.islamcan.com/audio/adhan/azan1.mp3',
						'audio_url' => 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] .'/assets/prayertime/azan'. ($prayerName === 'Fajr' ? '-fajr' : '') .'.mp3',
						'image_url' => 'https://assets.ifttt.com/images/channels/73138148/icons/large.png',
						'created_at' => $dates['timeISO'],
						'time' => $dates['time'],
						'date' => $dates['date'],
						'meta' => [
							'id' => $prependId . $dates['id'] . $prayerId,
							'timestamp' => $dates['timestamp'],
						],
					];
				}
			}
		}

		return $data;
	}

	private function getTimezone(&$config)
	{
		try {
			$timezone = \TimezoneMapper::latLngToTimezoneString(
				$config['triggerFields']['location']['lat'],
				$config['triggerFields']['location']['lng']
			);

			if (empty($timezone) || $timezone == 'unknown') {
				$timezone = $config['user']['timezone'];
			}
		} catch (\Exception $e) {
			$timezone = $config['user']['timezone'];
		}

		return $timezone;
	}

	private function getDates($date, $timezome = '+0000')
	{
		if (!is_int($date)) {
			$date = strtotime($date);
		}

		$dateTime = new DateTime(date('c', $date));
		$dateTime->setTimezone(new DateTimeZone($timezome));
		return [
			'id' => date('Ymdhi', $date),
			'timestamp' => $date,
			'timeISO' => $dateTime->format('c'),
			'time' => $dateTime->format('g:iA'),
			'date' => date('F j, Y', $date),
		];
	}

	public function actionsSendNotification()
	{
		$data = [];
		return $this->jsonResponse($data);
	}

	private function getTestData()
	{
		
		return json_decode('{
  "data": {
	"accessToken": "'. base64_encode('Karāchi, Pakistan-24.9267-67.0343') .'",
	"samples": {
	  "triggers": {
		"trigger_identity": "1111111111111111111111111111111111111111",
		"prayer_time": {
		  "fajr": "1",
		  "duhr": "1",
		  "asr": "1",
		  "maghrib": "1",
		  "isha": "1",
		  "location": {
			"lat": "47.809490",
			"lng": "13.055010",
			"address": "Fuschl am See, Austria",
			"description": "",
			"radius": ""
		  },
		  "mathhab": "MATHHAB_STANDARD",
		  "calculation_methods": "METHOD_MUSLIM_WORLD_LEAGUE",
		  "high_latitude_rules": "HIGH_LATITUDE_MIDDLE_OF_NIGHT",
		  "country": "austria",
		  "city": "karachi"
		}
	  },
	  "actions": {
		"send_notification": {
		}
	  },
	  "actionRecordSkipping": {
		"send_notification": {
		}
	  }
	}
  }
}', true);

	}

	public function testSetup()
	{
		$data = $this->getTestData();
		return $this->jsonResponse($data);
	}

	public function oauth2Authorize()
	{
		if (isset($_POST) && !empty($_POST) && isset($_POST['location'])) {
			
			if (trim($_POST['location']) != '') {
				$redirectUri = $_GET['redirect_uri'] .
					'?code=' . urlencode(
								base64_encode(
									$_POST['location'] .'-'.
									$_POST['latitude'] .'-'.
									$_POST['longitude']
								)
							) .
					'&state=' . $_GET['state'];
				
				header('Location: '. $redirectUri);
				exit;
			}

			$data = $_POST;
		}
		else {
			$data = ['location' => '', 'latitude' => '', 'longitude' => ''];
		}

		$html = file_get_contents( __DIR__ . '/../views/Authorize.html' );
		foreach ($data as $key => $value) {
			$html = str_replace("#$key#", $value, $html);
		}

		return $html;
	}

	public function oauth2Token()
	{
		$data = [
			'token_type' => 'Bearer',
			'access_token' => $_POST['code'],
		];

		return $this->jsonResponse($data);
	}

	public function userInfo()
	{
		$data = $this->getAccessTokenData();
		if ($data === false) {
			$errors = [];
			$errors[] = ['message' => 'Invlaid access_token'];
			return $this->errorResponse($errors);
		}

		$data = [
			'data' => [
				'name' => $data['location'],
				'id' => base64_encode($data['location']),
			]
		];
		return $this->jsonResponse($data);
	}

	private function getAccessTokenData()
	{
		$headers = apache_request_headers();
		$accessToken = trim( str_ireplace('Bearer', '', $headers['Authorization']) );
		$data = explode('-', base64_decode( $accessToken ));

		if (count($data) < 3) {
			return false;
		}
		else {
			return [
				'location' => $data [0],
				'latitude' => $data [1],
				'longitude' => $data [2],
			];
		}
	}

	/**
	 * https://danishzahur.herokuapp.com/prayertime/ifttt/v1/triggers/prayer_time/fields/country/options
	 */
	public function triggersPrayerTimeFields()
	{
		error_log('config = '. json_encode($this->config));
		error_log('_GET = '. json_encode($_GET));
		error_log('_POST = '. json_encode($_POST));

		$data = [];

		switch ($this->config['fields']) {
			case 'country':
				$data[] = [
					'label' => "Pakistan",
					'value' => "pakistan"
				];
				$data[] = [
					'label' => "Austria",
					'value' => "austria"
				];
				break;
			case 'city':
				$data[] = [
					'label' => "Karachi",
					'value' => "karachi"
				];
				$data[] = [
					'label' => "Isalamabad",
					'value' => "islamabad"
				];
				break;
		}
		
		$data = [
			'data' => $data,
			'config' => $this->config,
			'get' => $_GET,
			'post' => $_POST,
		];
		return $this->jsonResponse($data);
	}

}