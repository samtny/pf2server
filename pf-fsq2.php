<?php

include('pf-session.php');
include('pf-config.php');

if (!empty($_GET['code'])) {
  pf_fsq_authenticate();
}
else if (!empty($_GET['q'])) {
  $q = $_GET['q'];
  $t = !empty($_GET['t']) ? $_GET['t'] : 'venue';
  $ll = $_GET['ll'];

  switch ($_GET['t']) {
    case 'venue':
      pf_fsq_search_venue($q, $ll);
  }
}

function pf_fsq_search_venue($q, $ll) {
  $params = array(
    'client_id' => FSQ_CLIENT_ID,
    'client_secret' => FSQ_CLIENT_SECRET,
    'v' => '20140411',
    'query' => $q
  );

  if (!empty($ll)) {
    $params['ll'] = $ll;
  };

  $url  = 'https://foursquare.com/v2/venues/search?';
  $url .= http_build_query($params);

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  $response = curl_exec($ch);

  curl_close($ch);

  $data = json_decode($response);
  header('Content-Type: application/json');
  echo $data;
}

function pf_fsq_authenticate() {
  $url  = 'https://foursquare.com/oauth2/access_token?';
  $url .= http_build_query(array(
    'client_id' => FSQ_CLIENT_ID,
    'client_secret' => FSQ_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'redirect_uri' => FSQ_REDIRECT_URI,
    'code' => $_GET['code'],
  ));

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  $response = curl_exec($ch);

  $data = json_decode($response);
  if (!empty($data->access_token)) {
    $access_token = $data->access_token;
    setcookie('fsq_access_token', $access_token, time()+60*60*24*30, '/pf2/');
    header('Location: /pf2/pf-mgmt2.php#/login');
  }

  curl_close($ch);
}