<?php

$url = 'http://www.pinballfinder.org/pf2/pf';

if ($_GET) {
	$url .= "?";
	foreach ($_GET as $key => $val) {
		$url .= $key . "=" . urlencode($val) . "&";
	}
	$url = rtrim($url, "&");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

$result = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="games.xsl"?>', $result);

header("Content-type: application/xml");
echo $result;

?>