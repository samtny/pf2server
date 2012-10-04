<?php

include_once('pf-config.php');

define('TOURNAMENT_DEFAULT_LIMIT', 17);

function get_untagged_tournaments() {
	return get_untagged_tournaments_limit(TOURNAMENT_DEFAULT_LIMIT);
}

function get_untagged_tournaments_limit($limit) {
	
	$tournaments = array();
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	$db_selected = mysql_select_db(DB_NAME, $link);
	
	$sql = "select t.tournamentid, t.name as tournamentName, t.datefrom, t.datethru, t.venueid, t.ifpaid ";
	$sql .= "from tournament t ";
	$sql .= "where t.venueid is null and t.omit = 0 ";
	$sql .= "order by t.datefrom, t.name ";
	$sql .= "limit $limit ";
	
	$result = mysql_query($sql);
	
	if ($result) {
		
		while ($row = mysql_fetch_assoc($result)) {
			
			$id = $row['tournamentid'];
			$name = $row['tournamentName'];
			$from = $row['datefrom'];
			$thru = $row['datethru'];
			$venueid = $row['venueid'];
			$ifpaid = $row['ifpaid'];
			
			$t = new Tournament();
			$t->id = $id;
			$t->name = $name;
			$t->dateFrom = $from;
			$t->dateThru = $thru;
			$t->venueId = $venueid;
			$t->ifpaId = $ifpaid;
			
			$tournaments[] = $t;
			
		}
		
	} else {
		trigger_error(mysql_error());
		// TODO: log it;
	}
	
	return $tournaments;
	
}

function save_tournaments($tournaments) {
	
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	
}

?>