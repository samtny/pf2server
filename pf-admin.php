<?php

include('pf-session.php');
include('pf-config.php');
include('pf-get.php');
include('pf-game.php');

function get_options() {
  $options = array('manufacturers' => array());
  
  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $link);
  
  $result = mysql_query('SELECT manufacturerid, name FROM manufacturer ORDER BY name, manufacturerid');
  
  while ($row = mysql_fetch_assoc($result)) {
    $options['manufacturers'][] = array('manufacturerid' => $row['manufacturerid'], 'name' => $row['name']);
  }
  
  return $options;
}

if (!empty($_POST)) {
  switch($_POST['op']) {
    case 'newgame':
      $response = add_new_game($_POST['data']);
      header('Content-type: application/json');
      print json_encode($response);
  
      break;
  }
}
else {
  switch ($_GET['q']) {
    case 'unapproved':
      $response = get_result('unapproved', 'mgmt');
      header('Content-type: application/json');
      print json_encode($response);

      break;
    case 'options':
      $response = get_options();

      header('Content-type: application/json');
      print json_encode($response);

      break;
  }
}

?>