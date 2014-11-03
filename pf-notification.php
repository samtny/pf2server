<?php

include('pf-token.php');

function clean_notifications() {
  $response = array();

  $tokens = array();

  foreach (array(APNS_CERT_PATH, APNS_CERT_PATH_FREE) as $cert_path) {
    $streamContext = stream_context_create();

    stream_context_set_option($streamContext, 'ssl', 'local_cert', $cert_path);
    stream_context_set_option($streamContext, 'ssl', 'passphrase', "");

    $client = stream_socket_client('ssl://' . APNS_FEEDBACK_HOST . ':' . APNS_FEEDBACK_PORT, $error, $errorString, 60, STREAM_CLIENT_CONNECT, $streamContext);

    if ($client) {
      while (!feof($client)) {
        $tuple = fread($client, 38);

        if (strlen($tuple)) {
          $payload = unpack("N1timestamp/n1length/H*devtoken", $tuple);
          $tokens[] = $payload;
        }
      }

      fclose($client);

      if (count($tokens) > 0) {
        notification_tokens_delete($tokens);

        $response['success'] = TRUE;
        $response['message'] = sprintf('Success: removed %d invalid tokens', count($tokens));
        $response['debug']['tokens'] = $tokens;
      }
    } else {
      $response['success'] = FALSE;
      $response['message'] = 'Error creating apns feedback client';
      $response['debug']['error'] = $error;
      $response['debug']['errorString'] = $errorString;

      break;
    }
  }

  if (!isset($response['success'])) {
    $response['success'] = TRUE;
    $response['message'] = 'Success: no tokens to clean up';
  }

  return $response;
}

function notification_tokens_delete($items) {
  foreach ($items as $item) {
    $formatted = '<' . implode(' ', str_split($item['devtoken'], 8)) . '>';

    token_delete($formatted);
  }
}

function get_notifications() {
  $response = array();
  $response['notifications'] = array();

  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  mysql_select_db(DB_NAME, $link);

  $sql = "select notificationid, message, touserid, global, extra from notification ";
  $sql .= "where delivered = 0";

  $result = mysql_query($sql);

  if ($result) {
    while ($row = mysql_fetch_assoc($result)) {
      $id = $row['notificationid'];
      $text = $row['message'];
      $touserid = $row['touserid'];
      $global = $row['global'];
      $extra = $row['extra'];

      $notification = new stdClass();

      $notification->id = $id;
      $notification->text = $text;
      $notification->touserid = $touserid;
      $notification->global = $global;
      $notification->extra = $extra;

      $response['notifications'][] = $notification;
    }

    $response['success'] = TRUE;
    $response['message'] = 'Retrieved notifications';
  } else {
    $response['success'] = FALSE;
    $response['message'] = 'Database error retrieving notifications';
  }

  return $response;
}

function delete_notification($notification) {
  $response = array();

  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  mysql_select_db(DB_NAME, $link);

  $sql = sprintf("DELETE FROM notification WHERE notificationid = %d",
    mysql_real_escape_string($notification->id)
  );

  $result = mysql_query($sql);

  if ($result) {
    $response['success'] = TRUE;
    $response['message'] = 'Deleted notification';
  } else {
    $response['success'] = FALSE;
    $response['message'] = 'Database error deleting notification';
  }

  return $response;
}

function insert_notification($notification) {
  $response = array();

  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  mysql_select_db(DB_NAME, $link);

  $sql = sprintf("INSERT INTO notification (message, touserid, global, extra, delivered) VALUES ('%s', %d, %d, '%s', %d)",
    mysql_real_escape_string($notification->text),
    mysql_real_escape_string($notification->touserid),
    mysql_real_escape_string($notification->global == 'true' ? 1 : 0),
    mysql_real_escape_string($notification->extra),
    mysql_real_escape_string($notification->delivered)
  );

  $result = mysql_query($sql);

  if ($result) {
    $response['success'] = TRUE;
    $response['message'] = 'Inserted notification';
  } else {
    $response['success'] = FALSE;
    $response['message'] = 'Database error inserting notification';
  }

  return $response;
}

function update_notification($notification) {
  $response = array();

  $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  mysql_select_db(DB_NAME, $link);

  $sql = sprintf("UPDATE notification SET message = '%s', touserid = %d, global = %d, extra = '%s', delivered = %d WHERE notificationid = %d",
    mysql_real_escape_string($notification->text),
    mysql_real_escape_string($notification->touserid),
    mysql_real_escape_string($notification->global == 'true' ? 1 : 0),
    mysql_real_escape_string($notification->extra),
    mysql_real_escape_string($notification->delivered),
    mysql_real_escape_string($notification->id)
  );

  $result = mysql_query($sql);

  if ($result) {
    $response['success'] = TRUE;
    $response['message'] = 'Updated notification';
  } else {
    $response['success'] = FALSE;
    $response['message'] = 'Database error updating notification';
  }

  return $response;
}

function save_notification($notification) {
  if (isset($notification->id)) {
    return update_notification($notification);
  } else {
    return insert_notification($notification);
  }
}

function send_notifications() {
  $response = array();

  $result = get_notifications();

  if ($result['success'] === TRUE) {
    $notifications = $result['notifications'];

    if (count($notifications) > 0) {
      foreach ($notifications as $notification) {
        $payload = array();
        $payload['aps'] = array(
          'alert' => $notification->text
        );
        $payload['queryparams'] = $notification->extra;
        $payload = json_encode($payload);

        $result = send_notification($notification, $payload);

        if ($result['success'] == TRUE) {
          $notification->delivered = 1;

          update_notification($notification);
        }
        else {
          $response['success'] = FALSE;
          $response['message'] = 'Error sending notification';
          $response['debug'] = $result;

          break;
        }
      }
    }
    else {
      $response['success'] = FALSE;
      $response['message'] = 'Nothing to send';
    }
  }
  else {
    $response['success'] = FALSE;
    $response['message'] = 'Error getting notifications';
  }

  if (!isset($response['success'])) {
    $response['success'] = TRUE;
    $response['message'] = 'Sent notifications';
  }

  return $response;
}

function send_notification($notification, $payload) {
  $response = array();

  if ($notification->global == FALSE) {
    $tokens = tokens_for_userid($notification->touserid);

    foreach ($tokens as $token) {
      if ($client = create_apns_client($token->service, $error, $errorString)) {
        $token = preg_replace('/\s|<|>/', '', $token->token);

        // apns simple format;
        $bytes = chr(0); // command
        $bytes .= chr(0) . chr(32); //token length
        $bytes .= pack('H*', $token); // token
        $bytes .= chr(0) . chr(mb_strlen($payload)); // payload length
        $bytes .= $payload;

/*
        header('Content-type: application/json');
        print json_encode(array(
          'notification' => $notification,
          'payload' => $payload,
          'tokens' => $tokens,
          'error' => $error,
          'errorString' => $errorString,
          'token' => $token,
          'bytes' => $bytes,
          'bytes_bin2hex' => bin2hex($bytes),
          ));
        exit(0);
  */

        $result = fwrite($client, $bytes);

        if (!$result) {
          $response['success'] = FALSE;
          $response['message'] = 'Failed to write to apns client';

          break;
        }
      } else {
        $response['success'] = FALSE;
        $response['message'] = 'Failed to create apns client';

        break;
      };
    }
  }
  else {
    // TODO: global notification
    $response['success'] = FALSE;
    $response['message'] = 'Global apns notifications not implemented';
  }

  if (!isset($response['success'])) {
    $response['success'] = TRUE;
    $response['message'] = 'Sent notification';
  }

  return $response;
}

function create_apns_client($service, &$error, &$errorString, $force = FALSE) {
  $client = null;

  static $clients = array();

  if (!$force && isset($clients[$service])) {
    return $clients[$service];
  }

  $streamContext = stream_context_create();

  if ($service == APNS_SERVICE) {
    stream_context_set_option($streamContext, 'ssl', 'local_cert', APNS_CERT_PATH);
    stream_context_set_option($streamContext, 'ssl', 'passphrase', "");
  } else if ($service == APNS_SERVICE_FREE) {
    stream_context_set_option($streamContext, 'ssl', 'local_cert', APNS_CERT_PATH_FREE);
    stream_context_set_option($streamContext, 'ssl', 'passphrase', "");
  }

  $client = stream_socket_client('ssl://' . APNS_HOST . ':' . APNS_PORT, $error, $errorString, 60, STREAM_CLIENT_CONNECT, $streamContext);

  $clients[$service] = $client;

  return $client;
}
