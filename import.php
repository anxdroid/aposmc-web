<?php
die();
/**
 * Simple example of extending the SQLite3 class and changing the __construct
 * parameters, then using the open method to initialize the DB.
 */
class MyDB extends SQLite3
{
    function __construct()
    {
        $this->open('/home/osmc/templog.db');
    }
}

$db = new MyDB();

$info = file('/home/osmc/temp.txt');
//echo print_r($info, true);
foreach($info as $row) {
	$row = explode("\t", $row);
	echo print_r($row, true)."<br />";
	echo substr($row[0], 0, 19)." ".$row[1]."<br />";
	$sql = "INSERT INTO temps (timestamp, temp) VALUES ('".substr($row[0], 0, 19)."', ".(1*$row[1]).")";
	echo $sql ."<br />";
	$db->exec($sql);
	echo $db->lastErrorMsg()."<br />";
}
//$db->exec("SELECT * FROM temps ORDER BY timestamp DESC");
?>
