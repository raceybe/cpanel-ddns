<?php
require 'testConfig.php';

//Setup curl object
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

curl_setopt($curl, CURLOPT_URL, "https://$serverHostname/nic/update?hostname=$hostname&myip=$myip");
$response = curl_exec($curl);

curl_close($curl);

echo '<pre>';
echo $response;
echo '</pre>';
