<?php
/**********************
* Configuration
***********************/

require "config.php";

/*************************
* Execution
**************************/
//Make simple API call to ensure credentials are valid
$query="/json-api/cpanel?cpanel_jsonapi_user=" . $cpUser . "&cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=Rand&cpanel_jsonapi_func=getranddata";
$response = queryApi($cpUser, $cpPassword, $cpServer, $cpPort, $query);
$errorText=$response->cpanelresult->error;

//check if call was successful or if it returned an error, probably due to credentials
if($errorText == "Access denied"){
	exit("911\nAPI access denied");
}

//The request hostname i.e. the ddns host to be updated
//TODO: sanitize values
$requestHostname = strtolower($_GET['hostname']);

//The request IP i.e. the ddns IP to update the hostname with
//TODO: sanitize values
if (isset($_GET['myip']))		{
	$requestIp = $_GET['myip'];
}else{
	$requestIp = $_SERVER['REMOTE_ADDR'];
}
//The request username and password i.e. identifies a value user to update the hostname
//TODO: sanitize values...necessary?
list($requestUser, $requestPassword) = explode(':' , base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));

//Exit if domain not listed as a valid domain
$hostnameIndex = array_search($requestHostname,$authHostnames);
if ($hostnameIndex === false){
	exit("nohost $requestIp $requestHostname");
}

//Exit if credentials don't match the domain authorized user
if (!(($authUsers[$hostnameIndex] === $requestUser) && ($authPasswords[$hostnameIndex] === $requestPassword))){
	exit("badauth $requestIp $requestHostname");
}

$query="/json-api/cpanel?cpanel_jsonapi_user=" . $cpUser . "&cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=DomainLookup&cpanel_jsonapi_func=getbasedomains";
$response = queryApi($cpUser, $cpPassword, $cpServer, $cpPort, $query);

$jsonCpanelResult=$response->cpanelresult;
$result=$jsonCpanelResult->event->result;
$jsonUserDomains=$jsonCpanelResult->data;

//check if the API result indicates an error
if($result == 0){
	exit("911 $requestIp $requestHostname");
}

//check if no domains were returned for the user
if(sizeof($jsonUserDomains) == 0){
	exit("nohost $requestIp $requestHostname");
}

//result is returned
foreach($jsonUserDomains as $userDomain){
	$userDomains[]=$userDomain->domain;
}

$hostnameLevels = explode(".",$requestHostname);

//generate array of domain names to search
//for example, sub.domain.com would search
//sub.domain.com, domain.com, for the record
for ($i = 0 ; $i < (sizeof($hostnameLevels)-1) ; $i++){
	$possibleDomains[]=implode(".",array_slice($hostnameLevels,$i));
}

//reverse the array so foreach will go from shortest to longest name
$possibleDomains=array_reverse($possibleDomains);

//Will be set to true when the record is found
$foundHostnameRecord=false;

//determine if any of the possible domains from the hostname is one of the users domains
foreach($possibleDomains as $possibleDomain){
	$domainPart=$possibleDomain;
	$domainLevelCount=sizeof(explode(".",$domainPart));

	$hostLevelCount=sizeof($hostnameLevels)-$domainLevelCount;
	$hostPart=implode(".",array_slice($hostnameLevels,0,$hostLevelCount));

	$indexOfDomainPart = array_search($domainPart,$userDomains);
	if($indexOfDomainPart !== false){
		//found a domain, now check to see if the hostname record exists for this domain
		$matchedDomainPart=$userDomains[$indexOfDomainPart];
		
		//Query for $requestHostname in the list of $matchedDomainPart records
		$query="/json-api/cpanel?cpanel_jsonapi_user=" . $cpUser . "&cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=ZoneEdit&cpanel_jsonapi_func=fetchzone_records&domain=" . $matchedDomainPart . "&name=" . $requestHostname . ".";
		$response = queryApi($cpUser, $cpPassword, $cpServer, $cpPort, $query);
		$jsonCpanelResult=$response->cpanelresult;
		$result=$jsonCpanelResult->event->result;

		if ($result==1){
			//query was successful so check if a record was returned
			if(sizeof($jsonCpanelResult->data) > 0){
				//index the first/only record since there should only be one match
				$hostnameLine=$jsonCpanelResult->data[0]->line;
				$hostnameIp=$jsonCpanelResult->data[0]->address;
				
				//matched a record, so stop foreach loop
				$foundHostnameRecord=true;
				break;
			}			
		}
	}
}

//do stuff if the record is found, if not, exit with nohost
if($foundHostnameRecord){
	//check if the current IP is the same as the new IP...if so, exit with nochg response
	if($hostnameIp == $requestIp){
		exit("nochg $requestIp $requestHostname");
	}
	
	//found the record, so update it since we know the $domainPart, $hostPart, and $hostnameLine
	$query="/json-api/cpanel?cpanel_jsonapi_user=" . $cpUser . "&cpanel_jsonapi_apiversion=2&cpanel_jsonapi_module=ZoneEdit&cpanel_jsonapi_func=edit_zone_record&domain=" . $domainPart . "&name=" . $hostPart . "&line=" . $hostnameLine . "&type=A" . "&address=" . $requestIp;
	$response = queryApi($cpUser, $cpPassword, $cpServer, $cpPort, $query);
	$jsonCpanelResult=$response->cpanelresult;
	$status=$jsonCpanelResult->data[0]->result->status;
	if($status == 1){
		exit("good $requestIp $requestHostname");
	}else{
		exit("911 $requestIp $requestHostname\nAPI Error");
	}
}else{
	//didn't find the record, so return the nohost response
	exit("nohost $requestIp $requestHostname");
}

/*********************************
* Helpers
**********************************/

function queryApi($user, $password, $server, $port, $query){
	$headerArray[0] = "Authorization: Basic " . base64_encode($user.":".$password);
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
	curl_setopt($curl, CURLOPT_URL, $server . ":" . $port . $query);
	
	$response = curl_exec($curl);
	curl_close($curl);
	
	$json=json_decode($response);

	return $json;
}