<?php

include_once('pf-config.php');

function user_cull_orphans() {
  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  mysql_select_db(DB_NAME);

  $sql = "delete from user where not exists (select userid from token where user.userid = token.userid)";

  mysql_query($sql);
}

function get_user($userid) {
	
	$user;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME);
	
	$sql = "select u.userid, u.username, u.password, u.lastname, u.firstname, u.uuid, UNIX_TIMESTAMP(u.lastnotified) as lastnotified, u.banned from user u where u.userid = " . mysql_real_escape_string($userid);
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		$row = mysql_fetch_assoc($result);
		
		$user = new User();
		$user->id = $row["userid"];
		$user->username = $row["username"];
		$user->lname = $row["lastname"];
		$user->fname = $row["firstname"];
		$user->uuid = $row["uuid"];
                $user->lastnotified = $row["lastnotified"];
                $user->banned = $row['banned'] == 1 ? true : false;
		
	} else {
		trigger_error("sql error getting user");
	}
	
	return $user;
	
}

function insert_dummy_user() {
	
	$id;
	
	$username = next_dummy_username();
	
	$user = create_user($username, $password, $lname, $fname);
	$id = $user->id;
	
	return $id;

}

function next_dummy_username() {
	
	$dummy;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME);
	
	$sql = "select max(userid) as lastid from user where username like '" . DUMMY_USERNAME . "%'";
	
	$result = mysql_query($sql);
	
	if ($result) {
		$row = mysql_fetch_assoc($result);
		$lastId;
		if ($row) {
			$lastId = $row['lastid'];
		} else {
			$lastId = 10000;
		}
		$dummy = DUMMY_USERNAME . $lastId;
	} else {
		trigger_error(mysql_error());
	}
	
	return $dummy;
	
}

function touch_user_last_notified($user) {
    
    $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
    $db_selected = mysql_select_db(DB_NAME);
    
    $sql = "update user set lastnotified = NOW() where userid = " . mysql_real_escape_string($user->id);
    
    $result = mysql_query($sql);
    
}

function create_user($username, $password, $lname, $fname) {
	
	$user;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME);
	
	if ($username) {
		
		$sql = "insert into user (username, password, lastname, firstname, uuid) ";
		$sql .= "values (";
		
		$sql .= "'" . mysql_real_escape_string($username) . "' ";
		
		if ($password && strlen($password) > 0) {
			
			$pcrypted = md5($username . $password . PASSWORD_SALT);
			$sql .= ", '" . mysql_real_escape_string($pcrypted) . "' ";
			
		} else {
			
			$pcrypted = md5($username . DEFAULT_PASSWORD . PASSWORD_SALT);
			$sql .= ", '" . mysql_real_escape_string($pcrypted) . "' ";
			
		}
		
		$sql .= $lname ? ", '" . mysql_real_escape_string($lname) . "' " : ", null ";
		$sql .= $fname ? ", '" . mysql_real_escape_string($fname) . "' " : ", null ";
		
		$uuid = uniqid(null, true);
		$sql .= ", '" . mysql_real_escape_string($uuid) . "' ";
		
		$sql .= ") ";
		
		$result = mysql_query($sql);
		
		if ($result) {
			
			$id = mysql_insert_id();
			$user = get_user($id);
			
		} else {
			trigger_error("sql error creating user");
		}
		
	}
	
	return $user;
	
}

function user_matching_uuid($uuid) {
	
	$user;
	
	if ($uuid) {
		
		$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		$db_selected = mysql_select_db(DB_NAME);
		
		$sql = "select u.userid from user u where u.uuid = '" . mysql_real_escape_string($uuid) . "'";
		
		$result = mysql_query($sql);
		
		if ($result) {
			
			if (mysql_num_rows($result)) {
				
				$row = mysql_fetch_assoc($result);
				
				$id = $row["userid"];
				
				$user = get_user($id);
				
			}
			
		} else {
			trigger_error(mysql_error());
		}
		
	}
	
	return $user;
	
}

function user_matching_username($username) {
	
	$user;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME);
	
	$sql = "select u.userid from user u where u.username = '" . mysql_real_escape_string($username) . "'";
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		if (mysql_num_rows($result)) {
			
			$row = mysql_fetch_assoc($result);
			
			$id = $row["userid"];
			
			$user = get_user($id);
			
		}
		
	} else {
		trigger_error(mysql_error());
	}
	
	return $user;
	
}

function user_matching_service_token($service, $token) {
	
	$user;
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME);
	
	$sql = "select t.userid from token t where service = '" . mysql_real_escape_string($service) . "' and token = '" . mysql_real_escape_string($token) . "' ";
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		if (mysql_num_rows($result)) {
			
			$row = mysql_fetch_assoc($result);
			
			$id = $row["userid"];
			
			$user = get_user($id);
			
		}
		
	} else {
		trigger_error(mysql_error());
	}
	
	return $user;
	
}

?>