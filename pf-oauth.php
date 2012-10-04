<?php

include_once('pf-config.php');
include_once('pf-class.php');

function get_token($id) {
	
	$token;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select o.oauthid, o.service, o.token, o.userid from oauth o where oauthid = " . mysql_real_escape_string($id);
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		$row = mysql_fetch_assoc($result);
		
		$token = new Token();
		$token->id = $row["oauthid"];
		$token->service = $row["service"];
		$token->token = $row["token"];
		$token->userid = $row["userid"];
		
	} else {
		trigger_error(mysql_error());
	}
	
	return $token;
	
}

function service_token_matching_userid($service, $userid) {
	
	$token;
	
	if ($userid) {
		
		$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		$db_selected = mysql_select_db(DB_NAME, $link);
		
		$sql = "select o.oauthid from oauth o where service = '" . mysql_real_escape_string($service) . "' and userid = " . mysql_real_escape_string($userid);
		
		$result = mysql_query($sql);
		
		if ($result) {
			$row = mysql_fetch_assoc($result);
			$token = get_token($row["oauthid"]);
		} else {
			trigger_error(mysql_error());
		}
		
	}
	
	return $token;
	
}

function token_exists($token) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select oauthid from oauth where service = '" . mysql_real_escape_string($token->service) . "' ";
	$sql .= "and token = '" . mysql_real_escape_string($token->token) . "' ";
	
	if ($token->userid) {
		$sql .= "and userid = " . mysql_real_escape_string($token->userid);
	}
	
	$result = mysql_query($sql);
	
	if ($result) {
		if (mysql_num_rows($result)) {
			return true;
		}
	} else {
		trigger_error(mysql_error());
	}
	
	return false;
	
}

function save_token($token) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "insert into oauth (service, token, userid) values (";
	$sql .= "'" . mysql_real_escape_string($token->service) . "', ";
	$sql .= "'" . mysql_real_escape_string($token->token) . "' ";
	$sql .= $token->userid ? ", " . mysql_real_escape_string($token->userid) : ", null ";
	$sql .= ") ";
	
	$result = mysql_query($sql);
	
	if (!$result) {
		trigger_error(mysql_error());
	}
	
}

function service_token_exists($service, $token) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select oauthid from oauth where service = '" . mysql_real_escape_string($service) . "' ";
	$sql .= "and token = '" . mysql_real_escape_string($token) . "'";
	
	$result = mysql_query($sql);
	
	if ($result) {
		if (mysql_num_rows($result)) {
			return true;
		}
	} else {
		trigger_error(mysql_error());
	}
	
	return false;
	
}

function save_service_token($service, $token) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "insert into oauth (service, token) values (";
	$sql .= "'" . mysql_real_escape_string($service) . "', ";
	$sql .= "'" . mysql_real_escape_string($token) . "') ";
	
	$result = mysql_query($sql);
	
	if (!$result) {
		trigger_error(mysql_error());
	}
	
}

function sign_request($method, $url, &$params = array(), $consumer_key, $consumer_secret, $token_secret = "") {
	
	$signed;
	
	// add some parameters required for netflix authentication;
	$params['oauth_consumer_key'] = $consumer_key;
	$params['oauth_nonce'] = rand_str(10);
	$params['oauth_signature_method'] = 'HMAC-SHA1';
	$params['oauth_timestamp'] = time();
	$params['oauth_version'] = "1.0";
	
	// sort params array by *key*;
	ksort($params);
	
	$paramString;
	foreach ($params as $key => $value) {
		$paramString .= $key . "=" . urlencode($value) . '&';
	}
	$paramString = substr($paramString, 0, strlen($paramString)-1);

	// this will be used during hash;
	$sign_with = $consumer_secret . "&" . $token_secret;

	// what are we signing?
	$tosign = $method . '&' . urlencode($url) . '&' . urlencode($paramString);

	// do it;
	$signature = base64_encode(hash_hmac("SHA1", $tosign, $sign_with, true));
	
	// add signature to params array and to paramString;
	$params['oauth_signature'] = $signature;
	$paramString .= "&oauth_signature=" . urlencode($signature);
	
	$signed =  $url . '?' . $paramString;

	return $signed;
	
}

function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
{
    // Length of character list
    $chars_length = (strlen($chars) - 1);

    // Start our string
    $string = $chars{rand(0, $chars_length)};
   
    // Generate random string
    for ($i = 1; $i < $length; $i = strlen($string))
    {
        // Grab a random character from our list
        $r = $chars{rand(0, $chars_length)};
       
        // Make sure the same two characters don't appear next to each other
        if ($r != $string{$i - 1}) $string .=  $r;
    }
   
    // Return the string
    return $string;
}

?>