<?php

include_once('pf-config.php');

function token_delete($token) {
  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  mysql_select_db(DB_NAME);

  mysql_query(sprintf("delete from token where token = '%s' limit 1", mysql_real_escape_string($token)));
}

function tokens_for_service($service) {
	
	$tokens = array();
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME);
	
	// user must exist;
	$sql = "select t.tokenid, t.token from token t ";
	$sql .= "inner join user u on t.userid = u.userid ";
	$sql .= "where service = '" . mysql_real_escape_string($service) . "'";
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		while ($row = mysql_fetch_assoc($result)) {
			
			$token = new Token();
			$token->id = $row['tokenid'];
			$token->token = $row['token'];
			$token->userid = $userid;
			$token->service = $service;
			
			$tokens[] = $token;
			
		}
		
	} else {
		trigger_error(mysql_error());
	}
	
	return $tokens;

}

function tokens_for_userid_service($userid, $service) {
	
	$tokens = array();
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME);
	
	$sql = "select t.tokenid, t.token from token t where userid = " . mysql_real_escape_string($userid) . " and service = '" . mysql_real_escape_string($service) . "'";
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		while ($row = mysql_fetch_assoc($result)) {
			
			$token = new Token();
			$token->id = $row['tokenid'];
			$token->token = $row['token'];
			$token->userid = $userid;
			$token->service = $service;
			
			$tokens[] = $token;
			
		}
		
	} else {
		trigger_error(mysql_error());
	}
	
	return $tokens;

}

function tokens_for_userid($userid) {
  $tokens = array();

  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  $db_selected = mysql_select_db(DB_NAME);

  $sql = "select t.tokenid, t.token, t.service from token t where userid = " . mysql_real_escape_string($userid);

  $result = mysql_query($sql);

  if ($result) {
    while ($row = mysql_fetch_assoc($result)) {

      $token = new Token();
      $token->id = $row['tokenid'];
      $token->token = $row['token'];
      $token->userid = $userid;
      $token->service = $row['service'];

      $tokens[] = $token;
    }
  } else {
    trigger_error(mysql_error());
  }

  return $tokens;
}

function userid_for_service_token($service, $token) {
	
	$userid;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME);
	
	$sql = "select userid from token where service = '" . mysql_real_escape_string($service) . "' and token = '" . mysql_real_escape_string($token) . "'";
	
	$result = mysql_query($sql);
	
	if ($result) {
		$row = mysql_fetch_assoc($result);
		$userid = $row['userid'];
		mysql_free_result($result);
	} else {
		trigger_error(mysql_error());
	}
	
	return $userid;
	
}

function touch_service_token($service, $token) {
    
    
    
}

function freshen_user_service_token($userid, $service, $token) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME);

	$sql = "select t.tokenid, t.token from token t where t.userid = " . mysql_real_escape_string($userid) . " and service = '" . mysql_real_escape_string($service) . "'";
	
	$result = mysql_query($sql);
	
	if ($result) {

		$found = false;
		
		while ($row = mysql_fetch_assoc($result)) {
			
			if ($row['token'] == $token) {
				$found = true;
				break;
			}
			
		}
		
		if ($found == false) {
			$sql = "insert into token (service, token, userid) ";
			$sql .= "values ('" . mysql_real_escape_string($service) . "', '" . mysql_real_escape_string($token) . "', " . mysql_real_escape_string($userid) . ")";
			mysql_query($sql);
		}
		
	} else {
		trigger_error(mysql_error());
	}
	
}



?>