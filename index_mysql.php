<?php
error_reporting(E_ALL);
/***************************/
/* INIT
/***************************/

	$db = new mysqli('localhost', 'apdb', 'pwd4apdb', 'apdb');
	if ($db->connect_error) {
		die('Errore di connessione (' . $db->connect_errno . ') '. $db->connect_error);
	} else {
		//echo 'Connesso. ' . $db->host_info . "\n";
	}
	//echo print_r($_GET, true)."<br />";
	
	$numSamples = 21000;
	if (isset($_GET["numSamples"]) && (1*$_GET["numSamples"] > 0)) {
		$numSamples = 1*$_GET["numSamples"];
	}
	$avgNum = 10;
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

	$source = 'TEMP_SALOTTO';
	if (isset($_GET["source"]) && $_GET["source"] != "") {
		$source = $_GET["source"];
	}	

    $cumulative = false;
    if (isset($_GET["cumulative"]) && $_GET["cumulative"] == "1") {
            $cumulative = true;
    }

	$lastRow = $maxRow = $minRow = null;
	$avgTemp = 0;
	$avgTempNum = 0;

	$totalNum = 0;

	$mobAvg = array();

/***************************/
/* Temperature query
/***************************/

	$sql = "SELECT value, timestamp, unit
		FROM sensors
		WHERE value IS NOT NULL
		AND source = '".$source."'";
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
/* Temperature query
/***************************/

	$sql = "SELECT source, MAX(timestamp) timestamp
		FROM sensors
		WHERE value IS NOT NULL
		GROUP BY source";

	echo $sql."<hr />";
	$sourcesResult = $db->query($sql);
	$sources = array();
	while($row = $sourcesResult->fetch_array(MYSQLI_ASSOC)) {
		$sources[$row["source"]] = strtotime($row["timestamp"]);
	}
	//echo print_r($sources, true)."<br />";

/***************************/
/* Events query
/***************************/

	$sql = "SELECT e.* FROM events e WHERE (category='CMDSRV' OR category = 'JOBSRV') AND (cmd = 'HEATERS' OR cmd = 'RELAY')";
	if ($from !== null) {
		$sql .= " AND timestamp >= '".$from." 00:00:00'";
	}

	$sql .= " ORDER BY timestamp DESC";

	echo $sql."<hr />";
	$evResult = $db->query($sql);
	$events = array();
	while($evResult !== false && $row = $evResult->fetch_array(MYSQLI_ASSOC)) {
		$events[strtotime($row["timestamp"])] = $row;
	}
	//echo print_r($events, true)."<br />";











	$evKeys = array_keys($events);
	$series = array();

	while($result !== false && $row = $result->fetch_array(MYSQLI_ASSOC)) {
		$row["value"] = round($row["value"], 3);

		$title = $text = "undefined";
		if (is_array($evKeys) && isset($evKeys[0])) {
			$lastEventTime = 1*$evKeys[0];
			
			if ($lastEventTime <= strtotime($row["timestamp"]) && !isset($events[$evKeys[0]]["MAX"])) {
				$events[$evKeys[0]]["MAX"] = strtotime($row["timestamp"]);
				$title = $text = "undefined";
                        }elseif ($lastEventTime > strtotime($row["timestamp"]) && isset($events[$evKeys[0]]["MAX"])) {
				//echo print_r($row, true)." => ".print_r($events[$evKeys[0]], true)."<br />";
				$title = "'".$events[$evKeys[0]]["category"]." HEATER'";
				$text = ((1*$events[$evKeys[0]]["value"] == 0 || $events[$evKeys[0]]["value"] == 'ON')  ? "'ON'" : "'OFF'");
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



		if (($date == date("Y-m-d")) && ($minRow == null || 1*$row["value"] < 1*$minRow["value"])) {
			$minRow = $row;
		}
        if (($date == date("Y-m-d")) && ($maxRow == null || 1*$row["value"] > 1*$maxRow["value"])) {
                $maxRow = $row;
        }
		if ($date == date("Y-m-d")) {
			$avgTemp += 1*$row["value"];
			$avgTempNum++;
		}
		preg_match($pattern, $row["timestamp"], $matches);
		if ($calcAvg) {
			if (count($mobAvg) >= $avgNum) {
				$keys = array_keys($mobAvg);
				unset($mobAvg[$keys[0]]);
			}
			$mobAvg[] = 1*$row["value"];
			$row["value"] = array_sum($mobAvg) / count($mobAvg);
		}

		$series[$row["timestamp"]] = array("date" => array($matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]), "value" => $row["value"], "title" => $title, "text" => $text);
	}
	$avgTemp /= $avgTempNum;


    $lastRow["ago"] = time() - strtotime($lastRow["timestamp"]);
    $lastRow["timeUnit"] = "secs";
    if ($lastRow["ago"] > 60) {
            $lastRow["ago"] /= 60;
            $lastRow["timeUnit"] = "mins";
    }
    if ($lastRow["ago"] > 60) {
            $lastRow["ago"] /= 60;
            $lastRow["timeUnit"] = "hr";
    }


	$maxRow["ago"] = time() - strtotime($maxRow["timestamp"]);
	$maxRow["timeUnit"] = "secs";
	if ($maxRow["ago"] > 60) {
		$maxRow["ago"] /= 60;
		$maxRow["timeUnit"] = "mins";
	}
    if ($maxRow["ago"] > 60) {
            $maxRow["ago"] /= 60;
            $maxRow["timeUnit"] = "hr";
    }

    $minRow["ago"] = time() - strtotime($minRow["timestamp"]);
    $minRow["timeUnit"] = "secs";
    if ($minRow["ago"] > 60) {
            $minRow["ago"] /= 60;
            $minRow["timeUnit"] = "mins";
    }
    if ($minRow["ago"] > 60) {
            $minRow["ago"] /= 60;
            $minRow["timeUnit"] = "hr";
    }
	
	$prevVal = null;
	$prevTs = null;
	$area = 0;

	if ($cumulative) {
		ksort($series);
		foreach($series as $ts => $row) {
			if ($prevVal != null) {
				$timeDiff = abs(strtotime($ts) - $prevTs);
				$area1 = min($row["value"], $prevVal) * $timeDiff;
				$area2 = abs($row["value"] - $prevVal) * $timeDiff / 2;
				$area += ($area1 + $area2) / 3600;
				#echo $row["value"]." prev ".$prevVal."  diff ".$timeDiff." area ".$area1." ".$area2."\n";
				$series[$ts]["cumulative"] = $area;
				$series[$ts]["title_cumulative"] = "undefined";
				$series[$ts]["text_cumulative"] = "undefined";
			}		
			$prevVal = $row["value"];
			$prevTs = strtotime($ts);	

		}		
	}
	//echo print_r($series, true)."<br />";
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
        data.addColumn('number', 'Value');
        data.addColumn('string', 'Title');
        data.addColumn('string', 'Text');
<?php
	if ($cumulative) :
?>
        data.addColumn('number', 'Cumulative');
        data.addColumn('string', 'Title Cumulative');
        data.addColumn('string', 'Text Cumulative');
<?php
	endif;
?>

		data.addRows([

<?php
		foreach($series as $timestamp => $row) :
?>
[new Date(<?=$row["date"][0]?>, <?=$row["date"][1]?> ,<?=$row["date"][2]?>, <?=$row["date"][3]?>, <?=$row["date"][4]?>, <?=$row["date"][5]?>),
          <?=$row["value"]?>, <?=$row["title"]?>, <?=$row["text"]?>
<?php
			if ($cumulative) :
?>
		,<?=$row["cumulative"]?>, undefined, undefined
<?php		
			endif;
?>
		],
<?php
		endforeach;
/*
	$evKeys = array_keys($events);
	$prevVal = null;
	$prevTs = null;
	$area = 0;
	while($result !== false && $row = $result->fetch_array(MYSQLI_ASSOC)) :
		$row["value"] = round($row["value"], 3);
		

		if ($prevVal != null) {
			$timeDiff = abs(strtotime($row["timestamp"]) - $prevTs);
			$area1 = min($row["value"], $prevVal) * $timeDiff;
			$area2 = abs($row["value"] - $prevVal) * $timeDiff / 2;
			$area += ($area1 + $area2) / 3600;
			#echo $row["value"]." prev ".$prevVal."  diff ".$timeDiff." area ".$area1." ".$area2."\n";
		}		
		$prevVal = $row["value"];
		$prevTs = strtotime($row["timestamp"]);	
		


		$title = $text = "undefined";
		if (is_array($evKeys) && isset($evKeys[0])) {
			$lastEventTime = 1*$evKeys[0];
			
			if ($lastEventTime <= strtotime($row["timestamp"]) && !isset($events[$evKeys[0]]["MAX"])) {
				$events[$evKeys[0]]["MAX"] = strtotime($row["timestamp"]);
				$title = $text = "undefined";
                        }elseif ($lastEventTime > strtotime($row["timestamp"]) && isset($events[$evKeys[0]]["MAX"])) {
				//echo print_r($row, true)." => ".print_r($events[$evKeys[0]], true)."<br />";
				$title = "'".$events[$evKeys[0]]["category"]." HEATER'";
				$text = ((1*$events[$evKeys[0]]["value"] == 0 || $events[$evKeys[0]]["value"] == 'ON')  ? "'ON'" : "'OFF'");
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



		if (($date == date("Y-m-d")) && ($minRow == null || 1*$row["value"] < 1*$minRow["value"])) {
			$minRow = $row;
		}
        if (($date == date("Y-m-d")) && ($maxRow == null || 1*$row["value"] > 1*$maxRow["value"])) {
                $maxRow = $row;
        }
		if ($date == date("Y-m-d")) {
			$avgTemp += 1*$row["value"];
			$avgTempNum++;
		}
		preg_match($pattern, $row["timestamp"], $matches);
		if ($calcAvg) {
			if (count($mobAvg) >= $avgNum) {
				$keys = array_keys($mobAvg);
				unset($mobAvg[$keys[0]]);
			}
			$mobAvg[] = 1*$row["value"];
			$row["value"] = array_sum($mobAvg) / count($mobAvg);
		}
?>
          [new Date(<?=$matches[1]?>, <?=$matches[2]?> ,<?=$matches[3]?>, <?=$matches[4]?>, <?=$matches[5]?>, <?=$matches[6]?>),
          <?=$row["value"]?>, <?=$title?>, <?=$text?>
<?php
	if ($cumulative) :
?>
		,<?=$area?>, undefined, undefined
<?php		
	endif;
?>
		],
<?php
	endwhile;
	
	$avgTemp /= $avgTempNum;


        $lastRow["ago"] = time() - strtotime($lastRow["timestamp"]);
        $lastRow["timeUnit"] = "secs";
        if ($lastRow["ago"] > 60) {
                $lastRow["ago"] /= 60;
                $lastRow["timeUnit"] = "mins";
        }
        if ($lastRow["ago"] > 60) {
                $lastRow["ago"] /= 60;
                $lastRow["timeUnit"] = "hr";
        }


	$maxRow["ago"] = time() - strtotime($maxRow["timestamp"]);
	$maxRow["timeUnit"] = "secs";
	if ($maxRow["ago"] > 60) {
		$maxRow["ago"] /= 60;
		$maxRow["timeUnit"] = "mins";
	}
        if ($maxRow["ago"] > 60) {
                $maxRow["ago"] /= 60;
                $maxRow["timeUnit"] = "hr";
        }

        $minRow["ago"] = time() - strtotime($minRow["timestamp"]);
        $minRow["timeUnit"] = "secs";
        if ($minRow["ago"] > 60) {
                $minRow["ago"] /= 60;
                $minRow["timeUnit"] = "mins";
        }
        if ($minRow["ago"] > 60) {
                $minRow["ago"] /= 60;
                $minRow["timeUnit"] = "hr";
        }
*/
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
<?php
/***************************/
/* Process info
/***************************/
/*
	$ps = array();
	exec("ps aux | grep -i \"sudo python test_sensors.py\" | grep -v grep", $ps);	
	//$ps = explode("\n", $cmd);
	//echo print_r($ps, true)."<hr />";
	//echo $ps[0]."<hr />";
	foreach($ps as $row) {
		//echo $row."<br />";
		$row = preg_replace("/(\s{2,})/", " ", $row);
		$row = explode(" ", $row);
		//echo print_r($row, true)."<br />";
		echo "Sensors PID: ".$row[1]." started at ".$row[8]."<hr />";
		break;
	}
*/
	$ps = array();
	exec("ps aux | grep -i \"sudo python test_server_mysql.py\" | grep -v grep", $ps);	
	//$ps = explode("\n", $cmd);
	//echo print_r($ps, true)."<hr />";
	//echo $ps[0]."<hr />";
	foreach($ps as $row) {
		//echo $row."<br />";
		$row = preg_replace("/(\s{2,})/", " ", $row);
		$row = explode(" ", $row);
		//echo print_r($row, true)."<br />";
		echo "Server PID: ".$row[1]." started at ".$row[8]."<hr />";
	}
?>  
<table>
<tr>
<td>
Show from
</td>
<td colspan="2">
	<form action="<?=$_SERVER['PHP_SELF']?>?<?=isset($params) ? $params : ""?>" id="search" method="get">
<?php
	//unset($_GET["source"]);
	$params = "";
	foreach($_GET as $k => $v) {
		if ($k != "from") {
			if ($params != "") {
				$params .= "&";
			}
?>
<input type="hidden" name="<?=$k?>" value="<?=$v?>">
<?php
		}
	}
?>
		
			<input type="text" name="from" value="<?=$from?>" onchange="document.getElementById('search').submit()"><br />
		</form>
</td></tr>
<tr>
<td>
Sensor
</td>
<td colspan="2">
<?php
	//unset($_GET["source"]);
	$params = "";
	foreach($_GET as $k => $v) {
		if ($k != "source") {
			if ($params != "") {
				$params .= "&";
			}
			$params .= $k."=".$v;
		}
	}
	foreach($sources as $mysource => $mytimestamp) :
?>
		<a href="<?=$_SERVER['PHP_SELF']?>?<?=$params?>&amp;source=<?=$mysource?>"><span style="<?php if ($mysource == $source) : ?> font-weight: bold<?php endif; ?>"><?=$mysource?></span></a><br />
<?php
	endforeach;
?>
	
</td></tr>
<tr>
<td>
Last value acquired
</td>
<td style="font-weight:bold;">
<?=$lastRow["value"]?><?=$lastRow["unit"]?>
</td>
<td>
<?=$lastRow["timestamp"]?> (<?=round($lastRow["ago"], 2)?> <?=$lastRow["timeUnit"]?> ago)
</td>
</tr>
<tr>
<td>
Today's highest value acquired
</td>
<td>
<span style="color:red; font-weight:bold;">
<?=round($maxRow["value"], 3)?><?=$maxRow["unit"]?></span>
</td>
<td>
<?=$maxRow["timestamp"]?> (<?=round($maxRow["ago"], 2)?> <?=$maxRow["timeUnit"]?> ago)
</td>
</tr>
<tr>
<td>
Average value
</td>
<td style="font-weight:bold;">
<?=round($avgTemp, 3)?><?=$lastRow["unit"]?>
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
Today's lowest value acquired
</td>
<td>
<span style="color:blue; font-weight:bold;"><?=$minRow["value"]?><?=$minRow["unit"]?></span>
</td>
<td>
<?=$minRow["timestamp"]?> (<?=round($minRow["ago"], 2)?> <?=$minRow["timeUnit"]?> ago)
</td>
</tr>
<?php
	if ($cumulative) :
?>
<tr>
<td>
Today's cumulative
</td>
<td>
<span style="color:red; font-weight:bold;"><?=round($area, 3)?><?=$minRow["unit"]?>h</span>
</td>
<td>
<?=$lastRow["timestamp"]?> (<?=round($lastRow["ago"], 2)?> <?=$lastRow["timeUnit"]?> ago)
</td>
</tr>
<?php		
	endif;
?>

</table>

<!-- <?=$avgNum." ".$numSamples?><br />  style='width: 1280px; height: 1024px;'-->
    <div id='chart_div' style='height: 500px;'></div>
  </body>
</html>
