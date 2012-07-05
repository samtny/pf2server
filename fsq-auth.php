<?php

include('fsq-config.php');
include('fsq-user.php');

include('pf-oauth.php');
include('pf-user.php');
include('pf-util.php');

$action = $_GET["action"];
$code = $_GET["code"];

if ($action == "authenticate") {
	
	$url = "https://foursquare.com/oauth2/authenticate";
	$url .= "?client_id=" . FSQ_CLIENT_ID;
	$url .= "&response_type=code";
	$url .= "&redirect_uri=" . urlencode(FSQ_REDIRECT_URI);
	
	Redirect($url);

} else if ($action == "deauth") {
	
	$uuid = $_GET["uuid"];
	
	if ($uuid) {
	
		$user = user_matching_uuid($uuid);
		
		
		
	}
	
} else if ($code) {
	
	$url = "https://foursquare.com/oauth2/access_token";
	$url .= "?client_id=" . FSQ_CLIENT_ID;
	$url .= "&client_secret=" . FSQ_CLIENT_SECRET;
	$url .= "&grant_type=authorization_code";
	$url .= "&redirect_uri=" . urlencode(FSQ_REDIRECT_URI);
	$url .= "&code=" . $code;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		$r = json_decode($resultString);
		
		if ($r->access_token) {
			
			$fsqToken = $r->access_token;
			
			$user = user_matching_service_token(FSQ_SERVICE_PREFIX, $fsqToken);
			
			if (!$user) {
				
				$fsqUser = fsq_get_user($fsqToken);
				
				if ($fsqUser) {
					
					$userName = FSQ_SERVICE_PREFIX . $fsqUser->id;
					
					$user = user_matching_username($userName);
					
					if (!$user) {
					
						$user = create_user($userName, null, $fsqUser->lname, $fsqUser->fname);
					
					}
					
					$token = new Token();
					$token->service = FSQ_SERVICE_PREFIX;
					$token->token = $fsqToken;
					$token->userid = $user->id;
					
					if (!token_exists($token)) {
						save_token($token);
					}
					
				} else {
					
					header("Content-type: text/plain");
					echo "There was a problem linking Pinfinder to your Foursquare account; please try again or contact the server administrator.\n";
					die;
					
				}
				
			}
			
			$uuid = $user->uuid;
			
			if ($uuid) {
			
				$url = FSQ_REDIRECT_URI . "?uuid=" . urlencode($uuid);
				Redirect($url);
			
			} else {
				
				header("Content-type: text/plain");
				echo "There was a problem linking Pinfinder to your Foursquare account; please try again or contact the server administrator.\n";
				die;
			}
			
		} else {
			header("Content-type: text/plain");
			echo "There was a problem retrieving the Foursquare access token; please try again or contact the server administrator.\n";
			die;
		}
		
	} else {
		
		header("Content-type: text/plain");
		echo "There was a problem connecting to Foursquare to retrieve the access token; please try again or contact the server administrator.\n";
		die;
		
	}

}

?>