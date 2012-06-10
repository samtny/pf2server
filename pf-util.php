<?php

include_once('pf-config.php');
include_once('pf-string.php');

function freshen_game_clean_names() {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select g.gameid, g.name from game g ";
	
	$result = mysql_query($sql);
	
	if ($result) {
		while ($row = mysql_fetch_assoc($result)) {
			$id = $row["gameid"];
			$name = $row["name"];
			$nameclean = clean_game_name_string($name);
			save_game_clean_name($id, $nameclean);
		}
	} else {
		trigger_error(mysql_error());
	}
	
}

function save_game_clean_name($gameid, $nameclean) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "update game set nameclean = '" . mysql_real_escape_string($nameclean) . "' where gameid = " . mysql_real_escape_string($gameid);
	
	$result = mysql_query($sql);
	
	if (!$result) {
		trigger_error(mysql_error());
	}
	
}

function freshen_venue_clean_names() {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select v.venueid, v.name from venue v where deleted = 0";
	
	$result = mysql_query($sql);
	
	if ($result) {
		while($row = mysql_fetch_assoc($result)) {
			$id = $row["venueid"];
			$name = $row["name"];
			$nameclean = clean_location_name_string($name);
			save_venue_clean_name($id, $nameclean);
		}
	} else {
		trigger_error(mysql_error());
	}
	
}

function save_venue_clean_name($venueid, $nameclean) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "update venue set nameclean = '" . mysql_real_escape_string($nameclean) . "' where venueid = $venueid";
	
	$result = mysql_query($sql);
	
	if (!($result == 1)) {
		trigger_error(mysql_error());
	}
	
}

?>