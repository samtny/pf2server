<?php

include_once('fsq-config.php');
include_once('pf-class.php');
include_once('pf-oauth.php');
include_once('pf-user.php');

function fsq_post_result($action, $ll, $uuid, $venueid, $shout, $eventid) {
	
	$result;
	
	if ($action == "checkin") {
		
		$user = user_matching_uuid($uuid);
		$token = token_matching_userid($user->id);
		
		$result = checkin($venueid, $token->token, $shout, $eventid);
		
	} else {
		$result = new Result();
		$result->status->status = "invalidaction";
	}
	
	return $result;
	
}

function checkin($venueid, $token, $shout, $eventid) {
	
	$venueResult = new Result();
	
	$fields = array(
			'venueId'=>urlencode($venueid),
			'oauth_token'=>urlencode($token),
			'v'=>urlencode(FSQ_VERIFIED_DATE),
			'shout'=>urlencode($shout),
			'eventId'=>urlencode($eventid)
        );
	
	$fields_string;
	foreach($fields as $key=>$value) {
		if ($value) {
			$fields_string .= $key.'='.$value.'&';
		}
	}
	rtrim($fields_string,'&');
	
	$url = FSQ_ENDPOINT_V2 . "/checkins/add";
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, FSQ_CURL_USERAGENT);
	
	$resultString = curl_exec($ch);
	
	curl_close($ch);
	
	if ($resultString) {
		
		$checkinResult = json_decode($resultString, true);
		
		$code = $checkinResult["meta"]["code"];
		
		if ($code == "200") {
			
			$venueResult->status->status = "success";
			
		} else {
			$venueResult->status->status = "error";
		}
	
	} else {
		$venueResult->status->status = "error";
	}
	
	return $venueResult;
	
}

?>
