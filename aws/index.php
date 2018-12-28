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
$verbs = array(
	"temp" => array("adesso" => "ci sono", "prima" => "c'erano")
);

$feeds = array (
	"disimpegno" => array("nome" => "disimpegno", "articolo" => "nel", "unit" => "gradi", "feed" => 10, "verbs" => $verbs["temp"]),
	"salotto" => array("nome" => "salotto", "articolo" => "in", "unit" => "gradi", "feed" => 12, "verbs" => $verbs["temp"])
);
/*****************************************/
// ATTUAZIONE
/*****************************************/
$feed = 0;
$response = "Benvenuto in gestione termosifoni";
$shouldEndSession = "false";
if (isset($request["intent"])) {
	$intent = $request["intent"];
	if ($intent["name"] == "Produzione") {
		$response = "Produzione Solare";
	}
	if ($intent["name"] == "Temperatura" && isset($intent["slots"]["stanza"])) {
		$value = $intent["slots"]["stanza"]["value"];
		if (isset($feeds[$value])) {
			$stanza = $feeds[$value]; 
			$feed = $stanza["feed"];
			$url = "http://192.168.1.9/emoncms/feed/timevalue.json?id=".$feed."&apikey=a7441c2c34fc80b6667fdb1717d1606f";
			$temp = file_get_contents($url);
			$temparray = json_decode($temp, true);
			$diff = time() - 1*$temparray["time"];
			//echo $diff."\n";
			$unit = "secondi";
			$diff = round($diff, 0);
			$adesso = false;
			$verb = $stanza["verbs"]["prima"];
			if ($diff < 30) {
				$verb = $stanza["verbs"]["adesso"];
				$adesso = true;
			}
			if ($diff > 60) {
				$diff /= 60;
				$unit = "minuti";
				if ($diff < 2) {
					$unit = "minuto";
				}
			}
			if ($diff > 60) {
				$diff /= 60;
				$unit = "ore";
				if ($diff < 2) {
					$unit = "ora";
				}
			}
			$diff = round($diff, 0);
			$shouldEndSession = "false";

			$response = ucfirst($stanza["articolo"])." ".$stanza["nome"].((!$adesso) ? ", ".$diff." ".$unit." fa," : "")." ".$verb." ".$temparray["value"]." ".$stanza["unit"];
			file_put_contents("./log.txt", print_r($response, true));
		}else{
			$response = "Stanza non trovata !";
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

