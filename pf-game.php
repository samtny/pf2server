<?php

function refresh_gamedict() {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
	$sql = "select name, abbreviation, ipdb from game order by name, abbreviation ";
	
	$result = mysqli_query($link, $sql);
	
	if ($result) {
		$gameDict = '';
		
		while ($row = mysqli_fetch_assoc($result)) {
			$abbr = $row["abbreviation"];
			$name = $row["name"];
			$ipdb = $row["ipdb"];
      
			if (!empty($gameDict)) {
				$gameDict .= '\g';
			}
			
			$gameDict .= $abbr . '\f' . $name . '\f' . $ipdb;
		}
		
		file_put_contents(PF_GAMEDICT_PATH, $gameDict);
	}
}

function new_game_abbreviation($name) {
  $abbr = '';
  
  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  mysql_select_db(DB_NAME, $link);
  
  $name_clean = clean_game_name_string($name);
  
  $words = explode(' ', $name_clean);
  
  foreach ($words as $word) {
    $abbr .= $word[0];
  }
  
  $result = mysql_query("SELECT 1 FROM game WHERE abbreviation = '$abbr' LIMIT 1");
  $exists = mysql_num_rows($result);
  while ($exists) {
    $abbr = explode('_', $abbr);
    
    $increment = isset($abbr[1]) ? intval($abbr[1]) + 1 : 1;
    
    $abbr = $abbr[0] . '_' . $increment;
    
    $result = mysql_query("SELECT 1 FROM game WHERE abbreviation = '$abbr' LIMIT 1");
    $exists = mysql_num_rows($result);
  }
  
  return $abbr;
}

function add_new_game($data) {
  $response = array();
  
  if (isset($data['name'])) {
    $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
    mysql_select_db(DB_NAME, $link);

    $abbreviation = new_game_abbreviation($data['name']);
    $name = mysql_real_escape_string($data['name'], $link);
    $nameclean = clean_game_name_string($name);
    $ipdb = !empty($data['ipdb']) ? mysql_real_escape_string($data['ipdb']) : '';
    $manufacturerid = isset($data['manufacturer']['manufacturerid']) ? $data['manufacturer']['manufacturerid'] : NULL;
    
    $y = intval(date("Y"));
    $newyears = array();
    for ($i = -3; $i < 2; $i++) {
      array_push($newyears, $y + $i);
    }
    
    $new = (isset($data['year']) && in_array($data['year'], $newyears)) ? 1 : 0;

    $sql =  'INSERT INTO game (abbreviation, ipdb, manufacturerid, name, nameclean, new, year) ';
    $sql .= "VALUES ('$abbreviation', '$ipdb', $manufacturerid, '$name', '$nameclean', $new, '$year')";

    $result = mysql_query($sql);
    
    if ($result) {
      $response['success'] = TRUE;
      
      refresh_gamedict();
      
      $response['message'] = 'Game inserted, dictionary refreshed.';
      
    } else {
      $response['success'] = FALSE;
      $response['message'] = 'Database error while inserting new game';
    }
    
  } else {
    $response['success'] = FALSE;
    $response['message'] = 'Must supply a name';
  }
  
  return $response;
}

?>
