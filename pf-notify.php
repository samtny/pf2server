<?php

include_once('pf-class.php');

function get_pending_notifications() {
	
	$notifications = array();
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select notificationid, message, touserid, extra from notification ";
	$sql .= "where delivered = 0 ";
	$sql .= "and DATEDIFF(NOW(), createdate) < " . mysql_real_escape_string(PF_NOTIFICATION_MAX_AGE_DAYS) . "";
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		while ($row = mysql_fetch_assoc($result)) {
			
			$id = $row['notificationid'];
			$message = $row['message'];
			$touserid = $row['touserid'];
			$extra = $row['extra'];
			
			$n = new Notification();
			
			$n->id = $id;
			$n->message = $message;
			$n->touserid = $touserid;
			$n->extra = $extra;
			
			$notifications[] = $n;
			
		}
		
		mysql_free_result($result);
		
	} else {
		trigger_error(mysql_error());
	}
	
	return $notifications;
	
}

function mark_notifications_delivered($notifications) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	foreach ($notifications as $n) {
		
		$id = $n->id;
		
		if ($id) {
			
			$sql = "update notification set delivered = 1 where notificationid = " . mysql_real_escape_string($id) . "";
			
			$result = mysql_query($sql);
			
			if (!$result) {
				// TODO: log message save error
			}
			
		}
	
	}
	
}

function save_notifications($notifications) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	foreach ($notifications as $n) {
		
		$sql = "insert into notification (message, touserid, extra) ";
		$sql .= "values (";
		$sql .= "'" . mysql_real_escape_string($n->message) . "'";
		$sql .= ", '" . mysql_real_escape_string($n->touserid) . "'";
		$sql .= ", '" . mysql_real_escape_string($n->extra) . "'";
		$sql .= ")";
		
		$result = mysql_query($sql);
		
		if (!$result) {
			// TODO: log message save error
		}
	
	}
	
}

?>