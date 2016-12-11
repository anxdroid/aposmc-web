<?php
error_reporting(E_ALL);
/**
 * Simple example of extending the SQLite3 class and changing the __construct
 * parameters, then using the open method to initialize the DB.
 */
class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open('/media/LaCie/Anto/templog.db');
        //echo $this->lastErrorMsg()."<br />";
    }
}
/***************************/
/* INIT
/***************************/

    $db = new MyDB();
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

        $sql["sensors"] = "SELECT value, datetime(timestamp, 'localtime') timestamp, source, unit FROM sensors 
WHERE value >= 0 AND source = '".$source."' 
AND datetime(timestamp, 'localtime') > '".$from."'
AND (timestamp like '____-__-__ __:_0:__' OR cast( ( strftime('%s',datetime('now'))-strftime('%s',timestamp) ) AS real ) < 10)";

        $sql["sensors"] .= " ORDER BY timestamp DESC";

        if ($numSamples > 0) {
                $sql["sensors"] .= " LIMIT 0, ".$numSamples;
        }

	$sql["sensors"] = "SELECT * FROM (".$sql["sensors"].") ORDER BY timestamp ASC";

/***************************/
/* Events query
/***************************/

        $sql["events"] = "SELECT e.*, datetime(e.timestamp, 'localtime') timestamp FROM events e WHERE key IS NOT NULL";

        $sql["events"] .= " ORDER BY timestamp DESC";
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
        //echo $sql;
		$result = $db->query($sql);
	        $info = array();
	        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        	        //$info[strtotime($row["timestamp"])] = $row;
			$info[] = $row;
	        }
        	$info = json_encode(array("data" => $info));
	        echo ($compress) ? gzcompress($info) : $info;
	}
?>
