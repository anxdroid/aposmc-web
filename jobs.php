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
	$compress = false;
    $db = new MyDB();
    $cmd = "";
        if (isset($_GET["cmd"]) && ($_GET["cmd"] != "")) {
                $cmd = urldecode($_GET["cmd"]);
		$sql = "INSERT INTO jobs (cmd, ip, source) VALUES ('".$cmd."', '127.0.0.1', '".$_SERVER["REMOTE_ADDR"]."')";
		//echo $sql."<br />";
		$db->exec($sql);
        }
/***************************/
/* Jobs query
/***************************/
	$sql = array();

        $sql["jobs"] = "SELECT e.*, datetime(e.timestamp, 'localtime') timestamp FROM jobs e WHERE cmd IS NOT NULL";

        $sql["jobs"] .= " ORDER BY timestamp DESC";

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
	        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        	        //$info[strtotime($row["timestamp"])] = $row;
			$info[] = $row;
	        }
        	$info = json_encode(array("data" => $info));
	        echo ($compress) ? gzcompress($info) : $info;
	}
?>
