<?php

include_once('pf-config.php');
include_once('pf-token.php');
include_once('pf-user.php');
include_once('pf-log.php');

define ('APNS_TIME_LIMIT', 300);

function fetch_apns_invalid_tokens() {
	
	$tokens = array();
	
	$streamContext = stream_context_create();
	stream_context_set_option($streamContext, 'ssl', 'local_cert', APNS_CERT_PATH);
	stream_context_set_option($streamContext, 'ssl', 'passphrase', "");
	
	$apns = stream_socket_client('ssl://' . APNS_FEEDBACK_HOST . ':' . APNS_FEEDBACK_PORT, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);
	
	if ($apns && !$error) {
		
		while (!feof($apns)) {
			$tuple = fread($apns, 38);
			if (strlen($tuple)) {
				$payload = unpack("N1timestamp/n1length/H*devtoken", $tuple);
				$tokens[] = $payload[2];
			}
		}
		
		fclose($apns);
		
	} else {
		// TODO: log it
		echo "error: $errorString";
	}
	
	return $tokens;
	
}

function send_apns_notifications($notifications, $time_limit = APNS_TIME_LIMIT) {
	
  set_time_limit($time_limit);

	send_apns_notifications_service($notifications, APNS_SERVICE);
	send_apns_notifications_service($notifications, APNS_SERVICE_FREE);
	
}

function send_apns_notifications_service($notifications, $service) {
	
    $link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
    $db_selected = mysql_select_db(DB_NAME, $link);

    $apns = _apns_client_create($service, $error, $errorString);

    if (!$error) {

        foreach ($notifications as $n) {

            $message = $n->message;
            $extra = $n->extra;

            $payload = array();
            $payload['aps'] = array(
                    'alert' => $message
            );
            $payload['queryparams'] = $extra;
            $payload = json_encode($payload);

            $tokens;
            if ($n->global == TRUE) {
                    // get all valid apns tokens;
                    $tokens = tokens_for_service($service);
            } else {
                    // look up apns token/s for userid
                    $tokens = tokens_for_userid_service($n->touserid, $service);
            }

            pf_log('token count: ' . count($tokens));
            //print (json_encode("token count: " . count($tokens)));

            $sent = 0;
            $skipped = 0;

            foreach ($tokens as $t) {

                $user = user_matching_service_token($service, $t->token);

                if (!$user->lastnotified || strtotime("now") - APNS_USER_THROTTLE > $user->lastnotified) {

                    $deviceToken = preg_replace('/\s|<|>/', '', $t->token);

                    // simple format;
                    $apnsMessage = chr(0); // command
                    $apnsMessage .= chr(0) . chr(32); //token length
                    $apnsMessage .= pack('H*', $deviceToken); // token
                    $apnsMessage .= chr(0) . chr(mb_strlen($payload)); // payload length
                    $apnsMessage .= $payload;

                    $result = fwrite($apns, $apnsMessage);

                    if ($result == FALSE || !$apns) {
                        if ($GLOBALS['debug'] == true) {
                            print ("$user->uuid - $user->lastnotified");
                            print (" - $deviceToken");
                            print " - CONNECTION SEVERED";
                        }
                        pf_log($user->uuid . ' - ' . $user->lastnotified . ' - ' . $deviceToken . ' - CONNECTION SEVERED');
                        
                        // attempt to recover from closed socket;
                        usleep(1000000);
                        $apns = _apns_client_create($service, $error, $errorString);
                        
                        if ($error) {
                          pf_log('Error creating subsequent APNS client: ' . $errorString);
                          
                          break;
                        }
                    } else {
                        touch_user_last_notified($user);
                        $sent++;
                    }

                } else {
                    $skipped++;
                }

            }

            if ($GLOBALS['debug'] == true) {
                print "skipped: $skipped \t sent: $sent\n";
            }
            
            pf_log('skipped: ' . $skipped . ' sent: ' . $sent);

        }

        fclose($apns);

    } else {
            pf_log('Error creating initial APNS client: ' . $errorString);
    }

}

function _apns_client_create($service, &$error, &$errorString) {
  $streamContext = stream_context_create();

  if ($service == APNS_SERVICE) {
          stream_context_set_option($streamContext, 'ssl', 'local_cert', APNS_CERT_PATH);
          stream_context_set_option($streamContext, 'ssl', 'passphrase', "");
  } else if ($service == APNS_SERVICE_FREE) {
          stream_context_set_option($streamContext, 'ssl', 'local_cert', APNS_CERT_PATH_FREE);
          stream_context_set_option($streamContext, 'ssl', 'passphrase', "");
  }
  
  return stream_socket_client('ssl://' . APNS_HOST . ':' . APNS_PORT, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);
}

?>