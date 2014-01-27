<?php
session_start();

$session = $_COOKIE['session'];
if (!$session) {
	header('Location: pf-login.php');
	die();
}
