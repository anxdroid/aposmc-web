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
	if (isset($_GET["cmd"]) && ($_GET["cmd"] != "")) {
		$cmd = urldecode($_GET["cmd"]);
		$sql = "INSERT INTO jobs (cmd, ip, source) VALUES ('".$cmd."', '127.0.0.1', '".$_SERVER["REMOTE_ADDR"]."')";
		//echo $sql."<br />";
		$db->query($sql);
	}
/***************************/
/* Jobs query
/***************************/
	$sql = array();

	$sql["jobs"] = "SELECT e.*, timestamp FROM jobs e WHERE cmd IS NOT NULL ORDER BY timestamp DESC";

/***************************/
/* Output
/***************************/

	$export = "jobs";
	if (isset($_GET["export"]) && ($_GET["export"] != "")) {
		$export = $_GET["export"];
	}
	$result = null;
	if (isset($sql[$export])) {
		$sql = $sql[$export];
        //echo $sql;
		$result = $db->query($sql);
	        $info = array();
	        while ($result !== false && $row = $result->fetch_array(MYSQLI_ASSOC)) {
				//$info[strtotime($row["timestamp"])] = $row;
				$info[] = $row;
	        }
        	$info = json_encode(array("data" => $info));
	        echo ($compress) ? gzcompress($info) : $info;
	}
?>
