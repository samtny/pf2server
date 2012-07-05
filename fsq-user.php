<?php

include('fsq-config.php');
include('pf-class.php');

function fsq_get_user($token) {
	
	$user;
	
	$url = FSQ_ENDPOINT_V2 . "/users/self";
	$url .= "?oauth_token=" . $token;
	$url .= "&v=" . FSQ_VERIFIED_DATE;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		header("Content-type: application/json");
		//echo $resultString;
		//die;
		$result = json_decode($resultString, true);
		
		$code = $result["meta"]["code"];
		
		if ($code == "200") {
			
			$user = new User();
			$user->id = $result["response"]["user"]["id"];
			$user->lname = $result["response"]["user"]["lastName"];
			$user->fname = $result["response"]["user"]["firstName"];
			
		}
		
	}
	
	return $user;
	
}

?>