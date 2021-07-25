<?php
require 'serverConfig.php';

// this is your ddns update server to send the curl request to (not cPanel server name)
$server = 'ddns.example.net';

// this is your update username specified in serverConfig.php
$login = 'username';

// this is your update password specified in serverConfig.php
$password = 'password';

// this is the update A record specified in serverConfig.php
$hostname = 'newhost.example.net';

// intially null as it will be configure below or fail
$myip = null;

// The request IP i.e. the ddns IP to update the hostname with
if (isset($_GET['myip'])) {
	$myip = $_GET['myip'];
} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$myip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} elseif(isset($_SERVER['REMOTE_ADDR'])) {
	$myip = $_SERVER['REMOTE_ADDR'];
} else {
	exit('911\nnew IP address not specified or detected');
}

$base64auth=base64_encode($login.":".$password);
$header[0] = "Authorization: Basic $base64auth";

//Setup curl object
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

curl_setopt($curl, CURLOPT_URL, "https://$server/nic/update?hostname=$hostname&myip=$myip");
$response = curl_exec($curl);

curl_close($curl);

echo '<pre>';
echo $response;
echo '</pre>';