<?php

include_once('pf-config.php');

function pf_log($string) {
	file_put_contents(PF_LOG_FILE_PF2, $string . "\n", FILE_APPEND);
}

?>