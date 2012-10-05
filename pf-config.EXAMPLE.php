<?php

// set up include paths;
$path = '/path/to/install/dir';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

// database;
define('DB_NAME', 'foo');
define('DB_USER', 'bar');
define('DB_PASSWORD', 'baz');
define('DB_HOST', 'localhost');

// crypto
define( 'PFHASH', "MyStrongHash" );

// user auth;
define('PASSWORD_SALT', 'MyStrongSalt');
define('DEFAULT_PASSWORD', 'MyStrongPwd');
define('DUMMY_USERNAME', 'Dummy');

// google (obsolete as of 6/4/2012);
define('GOOGLE_MAPS_API_KEY', 'MyMapsAPIKey');
define('GOOGLE_MAPS_HOST', 'maps.google.com');

// pinballfinder libs;
define('PF_LIB_URL', 'http://mysite.pinfinder.com/installdir/');

// service endpoint;
define('PF_ENDPOINT_PF2', 'http://mysite.pinfinder.com/installdir/pf');

// log files;
define('PF_LOG_FILE_PF2', '/path/to/log/dir/pf-log.txt');
define('PF_LOG_FILE_FSQ', '/path/to/log/dir/pf-log-fsq.txt');

// notifications;
define('PF_NOTIFICATION_MAX_AGE_DAYS', 7);
define('PF_NEW_LOCATION_APPROVED_MSG_GENERIC', "The venue you recently added was approved!  Thank you!  -The Pinfinder Team");
define('PF_NEW_LOCATION_APPROVED_MSG_TEMPLATE', "The venue '%s' you added was approved!  Thank you!  -The Pinfinder Team");

// apns certs;
define ('APNS_CERT_PATH_DEV', '/path/to/certificates/cert.dev.pem');
define ('APNS_CERT_PATH_PROD', '/path/to/certificates/cert.prod.pem');
define ('APNS_CERT_PATH_FREE_DEV', '/path/to/certificates/free.cert.dev.pem');
define ('APNS_CERT_PATH_FREE_PROD', '/path/to/certifications/free.cert.prod.pem');

// toggle apns dev/prod here;
define ('APNS_CERT_PATH', APNS_CERT_PATH_DEV);
define ('APNS_CERT_PATH_FREE', APNS_CERT_PATH_FREE_DEV);

// misc;
define('PF_GAMEDICT_PATH', '/path/to/gamedict.txt');

define('PF_PRIVATE_DATA_DIR', '/path/to/pinfinder_data_dir');

// query defaults;
define('PF_VENUES_LIMIT_DEFAULT', 70);
define('PF_GAMENAMES_LIMIT_DEFAULT', 25);

define('PF_SAME_VENUE_MILES', 1.0);

// foursquare;
define('FSQ_CLIENT_ID', 'MyFSQClientId');
define('FSQ_CLIENT_SECRET', 'MyFSQClientSecret');
define('FSQ_VERIFIED_DATE', 'MyFSQVerifiedDateYYYYMMDD');

// yelp
define ('OAUTH_CONSUMER_KEY_YELP', 'MyYelpOauthConsumerKey');
define ('OAUTH_CONSUMER_SECRET_YELP', 'MyYelpOauthConsumerSecret');
define ('OAUTH_TOKEN_YELP', 'MyYelpOauthToken');
define ('OAUTH_TOKEN_SECRET_YELP', 'MyYelpOauthTokenSecret');

?>