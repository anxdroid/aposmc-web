<?php
error_reporting(E_ALL);
/**
 * Simple example of extending the SQLite3 class and changing the __construct
 * parameters, then using the open method to initialize the DB.
 */
	$db = new mysqli('localhost', 'apdb', 'pwd4apdb', 'apdb');
	if ($db->connect_error) {
		die('Errore di connessione (' . $db->connect_errno . ') '. $db->connect_error);
	} else {
		//echo 'Connesso. ' . $db->host_info . "\n";
	}
/***************************/
/* INIT
/***************************/
	$compress = false;
    $cmd = "";
	if (isset($_GET["sensor"]) && ($_GET["sensor"] != "")) {
		$sql = "INSERT INTO sensors (timestamp, value, sensor, unit, source) VALUES (NOW(), '".(1*$_GET["value"])."', '".($_GET["sensor"])."', '".($_GET["unit"])."', '".$_SERVER["REMOTE_ADDR"]."')";
		//echo $sql."<br />";
		$db->query($sql);
	}
?>
