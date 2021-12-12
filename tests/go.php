<?php
/**************************************************************************************************
* Rocket-Nginx Tests
*
* Maintainer: SatelliteWP
* URL: https://github.com/satellitewp/rocket-nginx
*
* Original author: Maxime Jobin
* URL: https://www.maximejobin.com
*
**************************************************************************************************/

$base_url = 'https://rocket3.1.webint.ca/';

function call_url($baseUrl, $queryString = null, $post_data = null , $cookies = null) {

	// Build URL
	$url = $baseUrl;
	if (!empty($queryString)) {
		$url .= '?' . $queryString;
	}

	$headers = [];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);

	if ($cookies != null) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: {$cookies}"));
	}
	
	// this function is called by curl for each header received
	curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$headers) {
		$len = strlen($header);
		$header = explode(':', $header, 2);
		
		// ignore invalid headers
		if (count($header) < 2) return $len;
	
		$headers[strtolower(trim($header[0]))][] = trim($header[1]);
	
		return $len;
	});

	// Post data
	if (is_array($post_data)) {
		curl_setopt ($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
	}

	$body = curl_exec($ch);
	curl_close($ch);

	return ['headers' => $headers, 'body' => $body];
}
echo "****************************************************************************\n";
echo "* Rocket-Nginx automated testing\n";
echo "****************************************************************************\n";
echo "Let's go!\n";

// Home - Should be a HIT
$response = call_url($base_url);

if (!isset($response['headers']['x-rocket-nginx-reason'])) {
	die('Please set debug to true before running the tests...');
}
$homepage_cached = false;
echo "Getting cached homepage...";
if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'HIT') {
	echo "OK\n";
	$homepage_cached = true;
}
else {
	echo "FAILED (Cache was not primed)\n";
}

// Retesting as cache might not be primed yet
if (!$homepage_cached) {
	$response = call_url($base_url);

	echo "Getting cached homepage again...";
	if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'HIT') {
		echo "OK\n";
		$homepage_cached = true;
	}
	else {
		echo "FAILED\n";
		exit;
	}
}

// Home with a valid argument - Should be a HIT
$response = call_url($base_url . '?country=canada');

$homepage_cached = false;
echo "Getting cached homepage with a valid argument...";
if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'HIT') {
	echo "OK\n";
	$homepage_cached = true;
}
else {
	echo "FAILED (Cache was not primed)\n";
}

// Retesting as cache might not be primed yet
if (!$homepage_cached) {
	$response = call_url($base_url . '?country=canada');

	echo "Getting cached homepage with a valid argument again...";
	if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'HIT') {
		echo "OK\n";
		$homepage_cached = true;
	}
	else {
		echo "FAILED\n";
		exit;
	}
}

// Never cached - Should be a MISS
$response = call_url($base_url . 'never-cached/');

if (!isset($response['headers']['x-rocket-nginx-reason'])) {
	die('Please set debug to true before running the tests...');
}
$homepage_cached = false;
echo "Getting never cached page...";
if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'MISS') {
	echo "OK\n";
}
else {
	echo "FAILED\n";
	$homepage_cached = true;
}

// Retesting as cache might not be primed yet
if ($homepage_cached) {
	$response = call_url($base_url . 'never-cached/');
	
	echo "Getting never cached page again...";
	if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'MISS') {
		echo "OK\n";
		$homepage_cached = true;
	}
	else {
		echo "FAILED\n";
		exit;
	}
}

// Home - With an argument
$response = call_url($base_url, 'rocket=nginx');
echo "Getting cached homepage with an argument...";
if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'BYPASS') {
	echo "OK\n";
}
else {
	echo "FAILED\n";
	exit;
}

// Home - With an allowed ignored argument
$response = call_url($base_url, 'utm_source=nginx');
echo "Getting cached homepage with an allowed argument...";
if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'HIT') {
	echo "OK\n";
}
else {
	echo "FAILED\n";
	exit;
}

// Home - With both ignored allowed and unallowed arguments
$response = call_url($base_url, 'rocket=nginx&utm_source=rocket');
echo "Getting cached homepage with an argument...";
if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'BYPASS') {
	echo "OK\n";
}
else {
	echo "FAILED\n";
	exit;
}

// Home - With an unallowed cookie
$response = call_url($base_url, null, null, 'woocommerce_items_in_cart=yes');
echo "Getting cached homepage with an unallowed cookie...";
if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'BYPASS') {
	echo "OK\n";
}
else {
	echo "FAILED\n";
	exit;
}

// Home - With post data
$response = call_url($base_url, null, ['post' => 'data']);
echo "Getting cached homepage with POST data...";
if ($response['headers']['x-rocket-nginx-serving-static'][0] == 'BYPASS') {
	echo "OK\n";
}
else {
	echo "FAILED\n";
	exit;
}

// CSS - Checking expiration
$response = call_url($base_url . 'wp-includes/css/dashicons.min.css');
echo "Getting CSS file with expiration...";
if (isset($response['headers']['expires'])) {
	$now = new DateTime();
	$expiration = new DateTime($response['headers']['expires'][0]);

	$interval = $expiration->diff($now);
	$days = $interval->format('%a');

	if ($days == '370') {
		echo "OK\n";
	}
	else {
		echo "FAILED\n";
		exit;
	}
}
else {
	echo "FAILED\n";
	exit;
}

// JS - Checking expiration
$response = call_url($base_url . 'wp-includes/js/wp-embed.min.js');
echo "Getting JS file with expiration...";
if (isset($response['headers']['expires'])) {
	$now = new DateTime();
	$expiration = new DateTime($response['headers']['expires'][0]);

	$interval = $expiration->diff($now);
	$days = $interval->format('%a');

	if ($days == '740') {
		echo "OK\n";
	}
	else {
		echo "FAILED\n";
		exit;
	}
}
else {
	echo "FAILED\n";
	exit;
}

// Media - Checking expiration
$response = call_url($base_url . 'wp-admin/images/wordpress-logo.svg');
echo "Getting media file with expiration...";
if (isset($response['headers']['expires'])) {
	$now = new DateTime();
	$expiration = new DateTime($response['headers']['expires'][0]);

	$interval = $expiration->diff($now);
	$days = $interval->format('%a');

	if ($days == '1110') {
		echo "OK\n";
	}
	else {
		echo "FAILED\n";
		exit;
	}
}
else {
	echo "FAILED\n";
	exit;
}

echo "All tests successful!\n";
#var_dump($response['headers']);
#function call_url($baseUrl, $queryString = null, $post_data = null , $cookies = null)
