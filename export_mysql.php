<?php
error_reporting(E_ALL);
	$db = new mysqli('localhost', 'apdb', 'pwd4apdb', 'apdb');
	if ($db->connect_error) {
		die('Errore di connessione (' . $db->connect_errno . ') '. $db->connect_error);
	} else {
		//echo 'Connesso. ' . $db->host_info . "\n";
	}
/***************************/
/* INIT
/***************************/
    $numSamples = 100;
    if (isset($_GET["numSamples"]) && (1*$_GET["numSamples"] > 0)) {
            $numSamples = 1*$_GET["numSamples"];
    }
        $from = date("Y-m-d")." 00:00:00";
        if (isset($_GET["from"]) && ($_GET["from"] != "")) {
                $from = urldecode($_GET["from"]);
        }
	$compress = false;
	if (isset($_GET["compress"]) && (1*$_GET["compress"] >= 0)) {
		$compress = (1*$_GET["compress"] == 1);
	}
    $source = 'TEMP_SALOTTO';
    if (isset($_GET["source"]) && $_GET["source"] != "") {
        $source = $_GET["source"];
    }    
	$sql = array();
/***************************/
/* Temperature query
/***************************/

        $sql["sensors"] = "SELECT value, timestamp, source, unit FROM sensors 
WHERE value >= 0 AND source = '".$source."' 
AND timestamp > '".$from."'
AND (timestamp like '____-__-__ __:_0:__' OR TIMESTAMPDIFF(SECOND, timestamp, NOW()) < 10)";

        $sql["sensors"] .= " ORDER BY timestamp DESC";

        if ($numSamples > 0) {
                $sql["sensors"] .= " LIMIT 0, ".$numSamples;
        }

	$sql["sensors"] = "SELECT * FROM (".$sql["sensors"].") ORDER BY timestamp ASC";

/***************************/
/* Events query
/***************************/

        $sql["events"] = "SELECT e.*, timestamp FROM events e WHERE key IS NOT NULL ORDER BY timestamp DESC";
        if ($numSamples > 0) {
                $sql["events"] .= " LIMIT 0, ".$numSamples;
        }

/***************************/
/* Output
/***************************/

	$export = "sensors";
	if (isset($_GET["export"]) && ($_GET["export"] != "")) {
		$export = $_GET["export"];
	}
	$result = null;
	if (isset($sql[$export])) {
		$sql = $sql[$export];
        echo $sql;
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
