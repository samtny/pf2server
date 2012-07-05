<?php

include('pf-crypto.php');

if ($_GET) {
	test_get();
}

function test_post() {

	$uuid = $_GET["uuid"];;
	$action = $_GET["action"];
	$venueid = $_GET["venueid"];
	$shout = $_GET["shout"];
	$time = time();

	$params = array("uuid" => $uuid, "action" => $action, "venueid" => $venueid, "shout" => $shout, "time" => $time);

	$hash = hash_for_array($params);

	$params["hash"] = $hash;

	$url = "http://www.pinballfinder.org/pf2/fsq";

	//url-ify the data for the POST
	foreach($params as $key=>$value) { $paramString .= $key.'='.$value.'&'; }
	$paramString = rtrim($paramString,'&');

	$ch = curl_init($url);

	curl_setopt($ch,CURLOPT_POST,count($params));
	curl_setopt($ch,CURLOPT_POSTFIELDS,$paramString);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);

	echo $result;

	curl_close($ch);

}
	
function test_get() {

	$ll = $_GET["ll"];
	$uuid = $_GET["uuid"];;
	$action = $_GET["action"];
	$venueid = $_GET["venueid"];
	$time = time();

	$params = array("ll" => $ll, "uuid" => $uuid, "action" => $action, "venueid" => $venueid, "time" => $time);

	$hash = hash_for_array($params);

	$params["hash"] = $hash;

	$url = "http://www.pinballfinder.org/pf2/fsq";
	$url .= "?ll=$ll";
	$url .= "&uuid=$uuid";
	$url .= "&action=$action";
	$url .= "&venueid=$venueid";
	$url .= "&time=$time";
	$url .= "&hash=$hash";
	echo $url;
	die;
	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_HEADER, false);
	//curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	//$result = curl_exec($ch);
	curl_exec($ch);

	curl_close($ch);
	die;
	header('Content-type: application/xml charset=\"utf-8\"');
	echo $result;
	
}
			
?>