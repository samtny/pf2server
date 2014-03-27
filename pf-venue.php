<?php

function delete_venue($venue) {
  $response = array();

  if (!empty($venue['id'])) {
    $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
    mysql_select_db(DB_NAME, $link);

    $id = stripslashes($venue['id']);
    $sql = "update venue set deleted = 1 where venueid = " . mysql_real_escape_string($id);

    $result = mysql_query($sql);

    if ($result) {
      $response['success'] = TRUE;
      $response['message'] = 'Venue deleted';
    } else {
      $response['success'] = FALSE;
      $response['message'] = 'Database error deleting venue';
    }
  } else {
    $response['success'] = FALSE;
    $response['message'] = 'Must specify an id';
  }

  return $response;
}

function venue_name_clean($name) {
  $clean = $name;

  // special case of 's;
  $clean = preg_replace("/'s\s/i", "s ", $clean);

  // kill apostrophe'd single letters;
  $clean = preg_replace("/'[a-zA-Z0-9]\s/", " ", $clean);

  // kill apostrophes in general;
  $clean = preg_replace("/'/", "", $clean);

  // replace non-alphanumeric with space
  $clean = preg_replace("/[^a-zA-Z0-9\s]/", " ", $clean);

  // remove double-spacing
  $clean = preg_replace("/\s+/", " ", $clean);

  // remove leading "the"
  $clean = preg_replace("/^the/i", "", $clean);

  // normalize numerics one thru ten, eleven
  $clean = preg_replace("/1st/i", "First", $clean);
  $clean = preg_replace("/2nd/i", "Second", $clean);
  $clean = preg_replace("/3rd/i", "Third", $clean);
  $clean = preg_replace("/4th/i", "Fourth", $clean);
  $clean = preg_replace("/5th/i", "Fifth", $clean);
  $clean = preg_replace("/6th/i", "Sixth", $clean);
  $clean = preg_replace("/7th/i", "Seventh", $clean);
  $clean = preg_replace("/8th/i", "Eighth", $clean);
  $clean = preg_replace("/9th/i", "Ninth", $clean);
  $clean = preg_replace("/10th/i", "Tenth", $clean);
  $clean = preg_replace("/11th/i", "Eleventh", $clean);

  // trim
  $clean = trim($clean);
  return $clean;
}

function save_venue($venue) {
  $response = array();

  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  mysql_select_db(DB_NAME, $link);

  $id = stripslashes($venue['id']);
  $name = stripslashes($venue['name']);
  $nameclean = venue_name_clean($name);
  $namedm = string_dm($nameclean);
  $street = stripslashes($venue['street']);
  $city = stripslashes($venue['city']);
  $state = stripslashes($venue['state']);
  $zipcode = stripslashes($venue['zipcode']);
  $phone = stripslashes($venue['phone']);
  $url = stripslashes($venue['url']);
  $coordinate = !empty($venue['lat']) ? 'Point(' . $venue['lat'] . ', ' . $venue['lon'] . ')' : 'null';
  $approved = $venue['approved'] === 'true' ? 1 : 0;

  $sql = sprintf("UPDATE venue SET name = '%s', nameclean = '%s', namedm = '%s', street = '%s', city = '%s', state = '%s', zipcode = '%s', phone = '%s', url = '%s', coordinate = %s, approved = %d, updatedate = NOW() WHERE venueid = %d",
    mysql_real_escape_string($name),
    mysql_real_escape_string($nameclean),
    mysql_real_escape_string($namedm),
    mysql_real_escape_string($street),
    mysql_real_escape_string($city),
    mysql_real_escape_string($state),
    mysql_real_escape_string($zipcode),
    mysql_real_escape_string($phone),
    mysql_real_escape_string($url),
    mysql_real_escape_string($coordinate),
    mysql_real_escape_string($approved),
    mysql_real_escape_string($id));

  $result = mysql_query($sql);

  if ($result) {
    $response['success'] = TRUE;
    $response['message'] = 'Venue updated';
  } else {
    $response['success'] = FALSE;
    $response['message'] = 'Database error updating venue';
  }

  return $response;
}
