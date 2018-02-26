<?php
error_reporting(E_ALL);
$db = new mysqli('localhost', 'apdb', 'pwd4apdb', 'apdb');
if ($db->connect_error) {
	die('Errore di connessione (' . $db->connect_errno . ') '. $db->connect_error);
}
/***************************/
/* Scritture
 ****************************/
$compress = false;
$cmd = "";
if (isset($_GET["sensor"]) && ($_GET["sensor"] != "") && isset($_GET["min"]) && (1*$_GET["min"] > 0) && isset($_GET["max"]) && (1*$_GET["max"] > 0)) {
	$sensor = urldecode($_GET["sensor"]);
	$min = 1*urldecode($_GET["min"]);
	$max = 1*urldecode($_GET["max"]);
	if ($max > $min) {
		$sql = "INSERT INTO thresholds (sensor, min, max, ip, active) VALUES ('".$sensor."', ".$min.", ".$max.", '".$_SERVER["REMOTE_ADDR"]."', 1)";
		echo $sql."<br />";
		$db->query($sql);
	}
}
/***************************/
/* Letture
 ****************************/
$sql = array();
if (isset($_GET["sensor"]) && ($_GET["sensor"] != "")) {
	$sensor = urldecode($_GET["sensor"]);
	$sql["thresholds"] = "SELECT e.* FROM thresholds e WHERE sensor = '".$sensor."' and active = 1 ORDER BY started DESC";
}

$export = "thresholds";
if (isset($_GET["export"]) && ($_GET["export"] != "")) {
	$export = $_GET["export"];
}
$result = null;
if (isset($sql[$export])) {
	$sql = $sql[$export];
	//echo $sql."<br />";
	$result = $db->query($sql);
	$info = array();
	while ($result !== false && $row = $result->fetch_array(MYSQLI_ASSOC)) {
		$info[] = $row;
	}
	$out = json_encode(array("data" => $info));
	echo ($compress) ? gzcompress($out) : $out;
}
?>
