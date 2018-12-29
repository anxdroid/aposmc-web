<?php
function getTimeAndUnit($feedInfo, &$timeDiff, &$timeUnit, &$adesso, &$verb) {
	$timeUnit = "secondi";
	$adesso = false;
	$verb = $feedInfo["verbs"]["prima"];
	if ($timeDiff < 30) {
		$verb = $feedInfo["verbs"]["adesso"];
		$adesso = true;
	}
	if ($timeDiff > 60) {
		$timeDiff /= 60;
		$timeUnit = "minuti";
		if ($timeDiff < 2) {
			$timeUnit = "minuto";
		}
	}
	if ($timeDiff > 60) {
		$timeDiff /= 60;
		$timeUnit = "ore";
		if ($timeDiff < 2) {
			$timeUnit = "ora";
		}
	}
	$timeDiff = round($timeDiff, 0);	
	return 0;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$reqArray = json_decode(file_get_contents('php://input'), true);
$request = $reqArray["request"];
$log = print_r($request, true)."\n";
/*****************************************/
// CONFIGURAZIONE
/*****************************************/
$baseUrl = "http://anto:resistore@192.168.1.9/";
if ($_SERVER["SERVER_NAME"] == "aphost.altervista.org") {
	$baseUrl = "http://anto:resistore@antopaoletti.ddns.net:10280/";
}

$feedUrl = $baseUrl."emoncms/feed/timevalue.json";
//echo print_r($_SERVER, true);
$apiKey = "a7441c2c34fc80b6667fdb1717d1606f";

$jobsUrl = $baseUrl."temp/jobs.php";

$verbs = array(
	"temp" => array("adesso" => "ci sono", "prima" => "c'erano"),
	"solar" => array("adesso" => "ammonta a", "prima" => "ammontava a")
);

$cmds = array(
	"accensione" => array("cmd" => "HEATERS:ON", "response" => "Termosifoni accesi !"),
	"spegnimento" => array("cmd" => "HEATERS:OFF", "response" => "Termosifoni spenti !"),
	"stato" => array("cmd" => "", "response" => "")
);

$feeds = array (
	"disimpegno" => array("nome" => "disimpegno", "articolo" => "nel", "unit" => "gradi", "feed" => 10, "verbs" => $verbs["temp"]),
	"salotto" => array("nome" => "salotto", "articolo" => "in", "unit" => "gradi", "feed" => 12, "verbs" => $verbs["temp"]),
	"terrazzo" => array("nome" => "terrazzo", "articolo" => "sul", "unit" => "gradi", "feed" => 9, "verbs" => $verbs["temp"]),
	"produzione" => array("nome" => "produzione", "articolo" => "la", "unit" => "kilowatt", "feed" => 1, "verbs" => $verbs["solar"]),
	"consumo" => array("nome" => "consumo", "articolo" => "il", "unit" => "kilowatt", "feed" => 7, "verbs" => $verbs["solar"])

);
/*****************************************/
// ATTUAZIONE
/*****************************************/
$feed = 0;
$response = "Benvenuto in gestione casa";
$shouldEndSession = "false";

if ($request["type"] == "SessionEndedRequest") {
	$response = "Arrivederci mentecatto";
	$shouldEndSession = "true";
}

if (isset($request["intent"])) {
	$feedName = "";
	$cmdInfo = null;
	$intent = $request["intent"];

/*****************************************/
// ANALISI INTENT
/*****************************************/

	$intentName = strtolower($intent["name"]);
	if (isset($cmds[$intentName])) {
		$cmdInfo = $cmds[$intentName];
	}
	if ($intent["name"] == "Consumo") {
		$feedName = "consumo";
	}
	if ($intent["name"] == "Produzione") {
		$feedName = "produzione";
	}
	if ($intent["name"] == "Temperatura" && isset($intent["slots"]["stanza"])) {
		$feedName = $intent["slots"]["stanza"]["value"];
	}
	if ($intent["name"] == "AMAZON.StopIntent") {
		$response = "Arrivederci mentecatto";
		$shouldEndSession = "true";
	}

/*****************************************/
// COMANDI
/*****************************************/
	
	if ($cmdInfo != null) {
		$url = $jobsUrl."?cmd=".$cmdInfo["cmd"];
		if ($cmdInfo["cmd"] != null) {
			$log .= $url."\n";
			$cmdResultArray = json_decode(file_get_contents($url), true);
			$log .= print_r($cmdResultArray, true)."\n";
			if ($cmdInfo["response"] != "") {
				$response = $cmdInfo["response"];
			}else{
				if ($intentName == "stato") {
					/*
					$timeDiff = time() - 1*$cmdResultArray["time"];
					$timeUnit = $adesso = $verb = null;
					getTimeAndUnit($feedInfo, $timeDiff, $timeUnit, $adesso, $verb);
					$resonse = "";
					*/
					$resonse = "Stato termosifoni";
				}
			}
		}
	}

/*****************************************/
// DATI
/*****************************************/

	if ($feedName != "") {
		if (isset($feeds[$feedName])) {
			$feedInfo = $feeds[$feedName]; 
			$feedId = $feedInfo["feed"];
			$url = $feedUrl."?id=".$feedId."&apikey=".$apiKey;
			$log .= $url."\n";
			//echo $url."\n";
			$feedDataArray = json_decode(file_get_contents($url), true);
			
			if (isset($feedDataArray["value"])) {
				$timeDiff = time() - 1*$feedDataArray["time"];
				$feedValue = round($feedDataArray["value"], 0);
				
				
				if ($feedValue > 0) {
					/*
					$timeUnit = "secondi";
					$adesso = false;
					$verb = $feedInfo["verbs"]["prima"];
					if ($timeDiff < 30) {
						$verb = $feedInfo["verbs"]["adesso"];
						$adesso = true;
					}
					if ($timeDiff > 60) {
						$timeDiff /= 60;
						$timeUnit = "minuti";
						if ($timeDiff < 2) {
							$timeUnit = "minuto";
						}
					}
					if ($timeDiff > 60) {
						$timeDiff /= 60;
						$timeUnit = "ore";
						if ($timeDiff < 2) {
							$timeUnit = "ora";
						}
					}
					$timeDiff = round($timeDiff, 0);
					*/
					$timeUnit = $adesso = $verb = null;
					getTimeAndUnit($feedInfo, $timeDiff, $timeUnit, $adesso, $verb);
					$shouldEndSession = "false";

					$response = ucfirst($feedInfo["articolo"])." ".$feedInfo["nome"].((!$adesso) ? ", ".$timeDiff." ".$timeUnit." fa," : "")." ".$verb." ".$feedValue." ".$feedInfo["unit"];	
				}else{
					$response = "Valore non valido per ".$feedValue." !";
				}
			}else{
				$response = "Valori non presenti per ".$feedValue." !";
			}
		}else{
			$response = "Feed non trovato per ".$feedValue." !";
		}
	}
}

$log .= $response."\n";
file_put_contents("./log.txt", $log);

header('Content-Type: application/json;charset=UTF-8');
header('Content-Length:');
?>{
  "version": "0.0.1",
  "sessionAttributes": {
    "key": "value"
  },
  "response": {
    "outputSpeech": {
      "type": "PlainText",
	"text": "<?php echo $response; ?>",
      "playBehavior": "REPLACE_ENQUEUED"
    },
    "card": {
      "type": "Standard",
      "title": "Gestione Casa A&V",
      "content": "<?php echo $response; ?>",
      "text": "<?php echo $response; ?>"
    },
    "shouldEndSession": <?php echo $shouldEndSession; ?>
  }
}
