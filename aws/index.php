<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$request = file_get_contents('php://input');
file_put_contents("./log.txt", print_r($request, true));
$reqarray = json_decode($request, true);
$request = $reqarray["request"];
//file_put_contents("./log.txt", print_r($request, true));
/*****************************************/
// CONFIGURAZIONE
/*****************************************/
$feedUrl = "http://192.168.1.9/emoncms/feed/timevalue.json";
//echo print_r($_SERVER, true);
if ($_SERVER["SERVER_NAME"] == "aphost.altervista.org") {
	$feedUrl = "http://antopaoletti.ddns.net:10280/emoncms/feed/timevalue.json";
}
$apiKey = "a7441c2c34fc80b6667fdb1717d1606f";

$verbs = array(
	"temp" => array("adesso" => "ci sono", "prima" => "c'erano"),
	"solar" => array("adesso" => "ammonta a", "prima" => "ammontava a")
);

$feeds = array (
	"disimpegno" => array("nome" => "disimpegno", "articolo" => "nel", "unit" => "gradi", "feed" => 10, "verbs" => $verbs["temp"]),
	"salotto" => array("nome" => "salotto", "articolo" => "in", "unit" => "gradi", "feed" => 12, "verbs" => $verbs["temp"]),
	"produzione" => array("nome" => "produzione", "articolo" => "la", "unit" => "kilowatt ora", "feed" => 2, "verbs" => $verbs["solar"])
);
/*****************************************/
// ATTUAZIONE
/*****************************************/
$feed = 0;
$response = "Benvenuto in gestione termosifoni";
$shouldEndSession = "false";
if (isset($request["intent"])) {
	$value = "";
	$intent = $request["intent"];
	if ($intent["name"] == "Produzione") {
		$value = "produzione";
	}
	if ($intent["name"] == "Temperatura" && isset($intent["slots"]["stanza"])) {
		$value = $intent["slots"]["stanza"]["value"];
	}
	if ($value != "") {
		if (isset($feeds[$value])) {
			$feedInfo = $feeds[$value]; 
			$feedId = $feedInfo["feed"];
			$url = $feedUrl."?id=".$feedId."&apikey=".$apiKey;
			//echo $url."\n";
			$feedDataArray = json_decode(file_get_contents($url), true);
			
			if (isset($feedDataArray["value"])) {
				$timeDiff = time() - 1*$feedDataArray["time"];
				$feedValue = round($feedDataArray["value"], 0);
				
				if ($feedValue > 0) {
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
					$shouldEndSession = "false";

					$response = ucfirst($feedInfo["articolo"])." ".$feedInfo["nome"].((!$adesso) ? ", ".$timeDiff." ".$timeUnit." fa," : "")." ".$verb." ".$feedValue." ".$feedInfo["unit"];
					file_put_contents("./log.txt", print_r($response, true));	
				}else{
					$response = "Valore non valido per ".$value." !";
				}
			}else{
				$response = "Valori non presenti per ".$value." !";
			}
		}else{
			$response = "Feed non trovato !";
		}
	}
}


header('Content-Type: application/json;charset=UTF-8');
header('Content-Length:');

$url = "http://192.168.1.9/emoncms/feed/timevalue.json?id=".$feed."&apikey=a7441c2c34fc80b6667fdb1717d1606f";

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

