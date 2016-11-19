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
	//echo print_r($_GET, true)."<br />";
	
	$numSamples = 21000;
	if (isset($_GET["numSamples"]) && (1*$_GET["numSamples"] > 0)) {
		$numSamples = 1*$_GET["numSamples"];
	}
	$avgNum = 100;
	if (isset($_GET["avgNum"]) && (1*$_GET["avgNum"] > 0)) {
                $avgNum = 1*$_GET["avgNum"];
        }
    $calcAvg = true;
    if (isset($_GET["calcAvg"]) && (1*$_GET["calcAvg"] == 0)) {
            $calcAvg = false;
    }
	$from = date("Y-m-d");
	if (isset($_GET["from"]) && $_GET["from"] != "") {
		$from = $_GET["from"];
		$numSamples = 0;
	}

	$lastRow = $maxRow = $minRow = null;
	$avgTemp = 0;
	$avgTempNum = 0;

	$totalNum = 0;

	$mobAvg = array();

/***************************/
/* Temperature query
/***************************/

	$sql = "SELECT temp, datetime(timestamp, 'localtime') timestamp FROM temps WHERE temp >= 0 ";
	if ($from !== null) {
		$sql .= " AND timestamp >= '".$from." 00:00:00'";
	}

	$sql .= " ORDER BY timestamp DESC";

	if ($numSamples > 0) {
		$sql .= " LIMIT 0, ".$numSamples;
	}

	//echo $sql."<hr />";
	$result = $db->query($sql);

/***************************/
/* Events query
/***************************/

	$sql = "SELECT e.*, datetime(e.timestamp, 'localtime') timestamp FROM events e WHERE category='CMDSRV' AND key='RELAY'";
	if ($from !== null) {
		$sql .= " AND timestamp >= '".$from." 00:00:00'";
	}

	$sql .= " ORDER BY timestamp DESC";

	//echo $sql."<hr />";
	$evResult = $db->query($sql);
	$events = array();
	while($row = $evResult->fetchArray()) {
		$events[strtotime($row["timestamp"])] = $row;
	}
	//echo print_r($events, true)."<br />";

/***************************/
/* Process info
/***************************/

	$ps = array();
	exec("ps aux | grep -i \"python temperature.py\" | grep -v grep", $ps);	
	//$ps = explode("\n", $cmd);
	//echo print_r($ps, true)."<hr />";
	//echo $ps[0]."<hr />";
	foreach($ps as $row) {
		//echo $row."<br />";
		$row = preg_replace("/(\s{2,})/", " ", $row);
		$row = explode(" ", $row);
		//echo print_r($row, true)."<br />";
		echo "Temp reader PID: ".$row[1]." started at ".$row[8]."<hr />";
	}

	$ps = array();
	exec("ps aux | grep -i \"sudo python testserver2.py\" | grep -v grep", $ps);	
	//$ps = explode("\n", $cmd);
	//echo print_r($ps, true)."<hr />";
	//echo $ps[0]."<hr />";
	foreach($ps as $row) {
		//echo $row."<br />";
		$row = preg_replace("/(\s{2,})/", " ", $row);
		$row = explode(" ", $row);
		//echo print_r($row, true)."<br />";
		echo "CMDSRV PID: ".$row[1]." started at ".$row[8]."<hr />";
	}
?>
<html>
  <head>
    <script type='text/javascript' src='https://www.gstatic.com/charts/loader.js'></script>
    <script type='text/javascript'>
      google.charts.load('current', {'packages':['annotatedtimeline']});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('date', 'Ora');
        data.addColumn('number', 'Temp');
        data.addColumn('string', 'Title');
        data.addColumn('string', 'Text');
        data.addRows([
<?php
	$evKeys = array_keys($events);
	while($row = $result->fetchArray()) :
		
		$title = $text = "undefined";
		if (is_array($evKeys) && isset($evKeys[0])) {
			$lastEventTime = 1*$evKeys[0];
			
			if ($lastEventTime <= strtotime($row["timestamp"]) && !isset($events[$evKeys[0]]["MAX"])) {
				$events[$evKeys[0]]["MAX"] = strtotime($row["timestamp"]);
				$title = $text = "undefined";
                        }elseif ($lastEventTime > strtotime($row["timestamp"]) && isset($events[$evKeys[0]]["MAX"])) {
				//echo print_r($row, true)." => ".print_r($events[$evKeys[0]], true)."<br />";
				$title = "'HEATER'";
				$text = (1*$events[$evKeys[0]]["value"] == 0 ? "'ON'" : "'OFF'");
				unset($events[$evKeys[0]]);
				$evKeys = array_keys($events);
			}else{
				$title = $text = "undefined";
			}
			
		}
		

		$totalNum++;
		if ($lastRow == null) {
			$lastRow = $row;
		}
		$pattern = "/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/";
		$matches = array();

		$date  =  substr($row["timestamp"], 0, 10);



		if (($date == date("Y-m-d")) && ($minRow == null || 1*$row["temp"] < 1*$minRow["temp"])) {
			$minRow = $row;
		}
        if (($date == date("Y-m-d")) && ($maxRow == null || 1*$row["temp"] > 1*$maxRow["temp"])) {
                $maxRow = $row;
        }
		if ($date == date("Y-m-d")) {
			$avgTemp += 1*$row["temp"];
			$avgTempNum++;
		}
		preg_match($pattern, $row["timestamp"], $matches);
		if ($calcAvg) {
			if (count($mobAvg) >= $avgNum) {
				$keys = array_keys($mobAvg);
				unset($mobAvg[$keys[0]]);
			}
			$mobAvg[] = 1*$row["temp"];
			$row["temp"] = array_sum($mobAvg) / count($mobAvg);
		}
?>
          [new Date(<?=$matches[1]?>, <?=$matches[2]?> ,<?=$matches[3]?>, <?=$matches[4]?>, <?=$matches[5]?>, <?=$matches[6]?>),
          <?=$row["temp"]?>, <?=$title?>, <?=$text?>],
<?php
	endwhile;
	$avgTemp /= $avgTempNum;


        $lastRow["ago"] = time() - strtotime($lastRow["timestamp"]);
        $lastRow["unit"] = "secs";
        if ($lastRow["ago"] > 60) {
                $lastRow["ago"] /= 60;
                $lastRow["unit"] = "mins";
        }
        if ($lastRow["ago"] > 60) {
                $lastRow["ago"] /= 60;
                $lastRow["unit"] = "hr";
        }


	$maxRow["ago"] = time() - strtotime($maxRow["timestamp"]);
	$maxRow["unit"] = "secs";
	if ($maxRow["ago"] > 60) {
		$maxRow["ago"] /= 60;
		$maxRow["unit"] = "mins";
	}
        if ($maxRow["ago"] > 60) {
                $maxRow["ago"] /= 60;
                $maxRow["unit"] = "hr";
        }

        $minRow["ago"] = time() - strtotime($minRow["timestamp"]);
        $minRow["unit"] = "secs";
        if ($minRow["ago"] > 60) {
                $minRow["ago"] /= 60;
                $minRow["unit"] = "mins";
        }
        if ($minRow["ago"] > 60) {
                $minRow["ago"] /= 60;
                $minRow["unit"] = "hr";
        }
?>
        ]);
	var options = {
		width: 1280,
		height: 1024,
		//smoothLine: true,
		title: 'Temperatura',
		curveType: 'function',
		//displayAnnotations: true,
		hAxis: {
			gridlines: {
            			count: -1,
            			units: {
              				days: {format: ['MMM dd']},
              				hours: {format: ['HH:mm', 'ha']},
            			}
          		},
          		minorGridlines: {
            			units: {
              				hours: {format: ['hh:mm:ss a', 'ha']},
              				minutes: {format: ['HH:mm a Z', ':mm']}
            			}
          		}
		}
	};

        var chart = new google.visualization.AnnotationChart(document.getElementById('chart_div'));
        //var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
	chart.draw(data, options);
      }
    </script>
  </head>

  <body>
<table>
<tr>
<td>
Now is
</td>
<td colspan="2"><em><?=date("Y-m-d H:i:s")?></em> (<?=time()?>)</td></tr>
<tr>
<td>
Last temperature acquired
</td>
<td style="font-weight:bold;">
<?=$lastRow["temp"]?>&deg;
</td>
<td>
<?=$lastRow["timestamp"]?> (<?=round($lastRow["ago"], 2)?> <?=$lastRow["unit"]?> ago)
</td>
</tr>
<tr>
<td>
Today's highest temperature acquired
</td>
<td>
<span style="color:red; font-weight:bold;"><?=round($maxRow["temp"], 3)?>&deg;</span>
</td>
<td>
<?=$maxRow["timestamp"]?> (<?=round($maxRow["ago"], 2)?> <?=$maxRow["unit"]?> ago)
</td>
</tr>
<tr>
<td>
Average temperature
</td>
<td style="font-weight:bold;">
<?=round($avgTemp, 3)?>&deg;
</td>
<td>
(<?=$avgTempNum?> samples)
</td>
</tr>
<tr>
<td>
</tr>
<tr>
<td>
Today's lowest temperature acquired
</td>
<td>
<span style="color:blue; font-weight:bold;"><?=$minRow["temp"]?>&deg;</span>
</td>
<td>
<?=$minRow["timestamp"]?> (<?=round($minRow["ago"], 2)?> <?=$minRow["unit"]?> ago)
</td>
</tr>
</table>

<!-- <?=$avgNum." ".$numSamples?><br />  style='width: 1280px; height: 1024px;'-->
    <div id='chart_div' style='height: 500px;'></div>
  </body>
</html>
