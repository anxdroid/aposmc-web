<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

/*****************************************/
// CONFIGURAZIONE
/*****************************************/
$feed = 0;
$response = "Benvenuto in gestione casa, mentecatto";
$shouldEndSession = "false";
$feedInfo = $cmdInfo = null;

$baseUrl = "http://anto:resistore@192.168.1.9/";
if ($_SERVER["SERVER_NAME"] == "aphost.altervista.org") {
	$baseUrl = "http://anto:resistore@antopaoletti.ddns.net:10280/";
}

$verbs = array(
	"temp" => array("adesso" => "ci sono", "prima" => "c'erano"),
	"termo" => array("adesso" => "sono", "prima" => "sono stati"),
	"solar" => array("adesso" => "ammonta a", "prima" => "ammontava a")
);

$statuses = array(
	"termo" => array("HEATERS:ON" => "accesi", "HEATERS:OFF" => "spenti"),
);

$systems = array(
	"emoncms" => array("url" => $baseUrl."emoncms/feed/timevalue.json?id=__FEEDID__&apikey=a7441c2c34fc80b6667fdb1717d1606f", "statuses" => $statuses["termo"]),
	"termo" => array("url" => $baseUrl."temp/jobs.php?cmd=__CMD__", "statuses" => $statuses["termo"])
);

$apis = array (
	"sottotetto" => array("nome" => "sottotetto", "articolo" => "nel", "unit" => "gradi", "feedId" => 13, "verbs" => $verbs["temp"], "system" => $systems["emoncms"]),
	"disimpegno" => array("nome" => "disimpegno", "articolo" => "nel", "unit" => "gradi", "feedId" => 10, "verbs" => $verbs["temp"], "system" => $systems["emoncms"]),
	"salotto" => array("nome" => "salotto", "articolo" => "in", "unit" => "gradi", "feedId" => 12, "verbs" => $verbs["temp"], "system" => $systems["emoncms"]),
	"terrazzo" => array("nome" => "terrazzo", "articolo" => "sul", "unit" => "gradi", "feedId" => 9, "verbs" => $verbs["temp"], "system" => $systems["emoncms"]),
	"produzione" => array("nome" => "produzione", "articolo" => "la", "unit" => "kilowatt", "feedId" => 1, "verbs" => $verbs["solar"], "system" => $systems["emoncms"]),
	"consumo" => array("nome" => "consumo", "articolo" => "il", "unit" => "kilowatt", "feedId" => 7, "verbs" => $verbs["solar"], "system" => $systems["emoncms"]),
	"accensione" => array("cmd" => "HEATERS:ON", "device" => "i termosifoni", "verbs" => $verbs["termo"], "system" => $systems["termo"]),
	"spegnimento" => array("cmd" => "HEATERS:OFF", "device" => "i termosifoni", "verbs" => $verbs["termo"], "system" => $systems["termo"]),
	"stato" => array("cmd" => "", "response" => "", "device" => "i termosifoni", "verbs" => $verbs["termo"], "system" => $systems["termo"])
);

/*****************************************/
// ANALISI RICHIESTA
/*****************************************/
$reqArray = json_decode(file_get_contents('php://input'), true);
$request = $reqArray["request"];
$log = print_r($request, true)."\n";

if ($request["type"] == "SessionEndedRequest") {
	$response = "Arrivederci, mentecatto";
	$shouldEndSession = "true";
}elseif (isset($request["intent"])) {

/*****************************************/
// ANALISI INTENT
/*****************************************/

	$intent = $request["intent"];
	$intentName = strtolower($intent["name"]);
	if (isset($apis[$intentName]) && isset($apis[$intentName]["cmd"])) {
		$cmdInfo = $apis[$intentName];
	}
	if ($intent["name"] == "Consumo" || $intent["name"] == "Produzione") {
		$feedInfo = $apis[$intentName]; 
	}
	if ($intent["name"] == "Temperatura" && isset($intent["slots"]["stanza"])) {
		$feedName = $intent["slots"]["stanza"]["value"];
		if (isset($apis[$feedName])) {
			$feedInfo = $apis[$feedName];
		}
	}
	if ($intent["name"] == "AMAZON.StopIntent") {
		$response = "Arrivederci, mentecatto";
		$shouldEndSession = "true";
	}

/*****************************************/
// COMANDI
/*****************************************/
	
	if ($cmdInfo != null) {
		$log .= print_r($cmdInfo, true)."\n";

		$url = str_replace("__CMD__", $cmdInfo["cmd"], $cmdInfo["system"]["url"]);
		$log .= $url."\n";
		$cmdResultArray = json_decode(file_get_contents($url), true);
		
		$data = $cmdResultArray["data"][0];
		$log .= print_r($data, true)."\n";
		$cmdTime = 1*strtotime($data["timestamp"]);
		$timeDiff = time() - $cmdTime;
		$timeUnit = $adesso = $verb = null;
		getTimeAndUnit($cmdInfo, $timeDiff, $timeUnit, $adesso, $verb);
		
		$statuses = $cmdInfo["system"]["statuses"];
		$cmd = str_replace(array_keys($statuses), array_values($statuses), $data["cmd"]);
		$response = ucfirst($cmdInfo["device"])." ".$verb." ".$cmd.((!$adesso) ? " ".$timeDiff." ".$timeUnit." fa" : "");	
	}

/*****************************************/
// DATI
/*****************************************/

	if ($feedInfo != null) {
		$feedId = 1*$feedInfo["feedId"];
		$url = str_replace("__FEEDID__", $feedId, $feedInfo["system"]["url"]);
		$log .= $url."\n";
		$feedDataArray = json_decode(file_get_contents($url), true);
		
		if (isset($feedDataArray["value"])) {
			$feedTime = 1*$feedDataArray["time"];
			$timeDiff = time() - $feedTime;
			$feedValue = round($feedDataArray["value"], 0);
			
			if ($feedValue > 0) {
				$timeUnit = $adesso = $verb = null;
				getTimeAndUnit($feedInfo, $timeDiff, $timeUnit, $adesso, $verb);
				$response = ucfirst($feedInfo["articolo"])." ".$feedInfo["nome"].((!$adesso) ? ", ".$timeDiff." ".$timeUnit." fa," : "")." ".$verb." ".$feedValue." ".$feedInfo["unit"];	
				$shouldEndSession = "false";
			}else{
				$response = "Valore non valido per ".$feedInfo["nome"]." !";
			}
		}else{
			$response = "Valori non presenti per ".$feedInfo["nome"]." !";
		}
	}
}




header('Content-Type: application/json;charset=UTF-8');
header('Content-Length:');

$response = 
'{
	"version": "0.0.1",
	"sessionAttributes": {
		"key": "value"
	},
	"response": {
		"outputSpeech": {
			"type": "PlainText",
			"text": "'.$response.'",
			"playBehavior": "REPLACE_ENQUEUED"
		},
		"card": {
			"type": "Standard",
			"title": "Gestione Casa A&V",
			"content": "'.$response.'",
			"text": "'.$response.'"
		},
		"shouldEndSession": '.$shouldEndSession.'
	}
}';

$log .= $response."\n";
file_put_contents("./log.txt", $log);
echo $response;
?>
