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
/* Scritture
/***************************/
	$compress = false;
    $cmd = "";
	if (isset($_GET["cmd"]) && ($_GET["cmd"] != "")) {
		$cmd = urldecode($_GET["cmd"]);
		$sql = "INSERT INTO jobs (cmd, ip, source) VALUES ('".$cmd."', '127.0.0.1', '".$_SERVER["REMOTE_ADDR"]."')";
		//echo $sql."<br />";
		$db->query($sql);
	}
	if (isset($_GET["job_id"]) && (1*$_GET["job_id"] > 0)) {
		$sql = "UPDATE jobs SET status = 2, ip = '".$_SERVER["REMOTE_ADDR"]."', ended = NOW() WHERE id = ".(1*$_GET["job_id"]);
                //echo $sql."<br />";
                $db->query($sql);
		$sql = "SELECT * FROM jobs WHERE id = ".(1*$_GET["job_id"]);	
		//echo $sql."<br />";
		$result = $db->query($sql) or trigger_error($mysqli->error."[$sql]");
                //var_dump($result);
		if ($result !== false)
		while ($row = $result->fetch_assoc()) {
			$cmd = explode(":", $row["cmd"]);
			$sql = "INSERT INTO events (id, timestamp, category, cmd, value, source, params) VALUES ('', NOW(), '".(isset($_GET["source"]) ? $_GET["source"] : "EXTERNAL")."', '".$cmd[0]."', '".$cmd[1]."', '".$_SERVER["REMOTE_ADDR"]."', '{}')";
			//echo $sql."<br />";
			$db->query($sql);
		}
	}
/***************************/
/* Letture
/***************************/
	$sql = array();

	$sql["jobs"] = "SELECT e.*, timestamp FROM jobs e WHERE cmd IS NOT NULL";
	if (isset($_GET["req_cmd"]) && ($_GET["req_cmd"] != "")) {
		$sql["jobs"] .= " AND cmd LIKE '".trim($_GET["req_cmd"])."%' AND status = 0";
	}
	$sql["jobs"] .= " ORDER BY timestamp DESC";
	if (isset($_GET["req_cmd"]) && ($_GET["req_cmd"] != "")) {
		$sql["jobs"] .= " LIMIT 0, 1";
		//echo $sql["jobs"]."<br />";
	}
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
		$out = "";
		if (isset($_GET["simple_out"]) && (1*$_GET["simple_out"] == 1)) {
			foreach($info as $row) {
				$out .= $row["id"]." ".$row["cmd"]."\n";
			}
		}else{
        		$out = json_encode(array("data" => $info));
		}
	        echo ($compress) ? gzcompress($out) : $out;
		if (count($info) == 1 && isset($_GET["req_cmd"]) && ($_GET["req_cmd"] != "")) {
			$sql = "UPDATE jobs SET status = 1, ip = '".$_SERVER["REMOTE_ADDR"]."', started = NOW() WHERE id = ".(1*$info[0]["id"]);
                	$db->query($sql);
			//echo $sql."<br />";
		}
	}
?>
