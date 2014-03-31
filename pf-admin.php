<?php

include('pf-session.php');
include('pf-config.php');
include('pf-string.php');
include('pf-get.php');
include('pf-game.php');
include('pf-stats.php');
include('pf-venue.php');
include('pf-notification.php');
include('pf-user.php');

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
    case 'deleteVenue':
      $response = delete_venue($_POST['data']);
      header('Content-type: application/json');
      print json_encode($response);

      break;
    case 'saveVenue':
      $response = save_venue($_POST['data']);
      header('Content-type: application/json');
      print json_encode($response);

      break;
    case 'saveNotification':
      $response = save_notification(json_decode($_POST['data']));
      header('Content-type: application/json');
      print json_encode($response);

      break;
    case 'deleteNotification':
      $response = delete_notification(json_decode($_POST['data']));
      header('Content-type: application/json');
      print json_encode($response);

      break;
  }
}
else {
  switch ($_GET['t']) {
    case 'user':
      $response = get_user($_GET['q']);
      $response = array(
        'user' => $response
      );
      header('Content-type: application/json');
      print json_encode($response);

      break;
    default:
      switch ($_GET['q']) {
        case 'unapproved':
          $response = get_result('unapproved', 'mgmt');
          header('Content-type: application/json');
          print json_encode($response);

          break;
        case 'unapproved_comments':
          $response = get_result('unapprovedcomment', 'mgmt');
          header('Content-type: application/json');
          print json_encode($response);

          break;
        case 'options':
          $response = get_options();
          header('Content-type: application/json');
          print json_encode($response);

          break;
        case 'stats':
          $response = get_stats();
          header('Content-type: application/json');
          print json_encode($response);

          break;
        case 'notifications':
          $response = get_notifications(TRUE);
          header('Content-type: application/json');
          print json_encode($response);

          break;
        case 'sendNotifications':
          $response = send_notifications();
          header('Content-type: application/json');
          print json_encode($response);

          break;
        case 'cleanNotifications':
          $response = clean_notifications();
          user_cull_orphans();
          header('Content-type: application/json');
          print json_encode($response);

          break;
      }

      break;
  }
}

?>