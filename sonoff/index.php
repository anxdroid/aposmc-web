<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*****************************************/
// ANALISI RICHIESTA
/*****************************************/
$reqArray = json_decode(file_get_contents('php://input'), true);
$request = $reqArray["request"];
$log = print_r($request, true)."\n";

header('Content-Type: application/json;charset=UTF-8');
header('Content-Length:');

$response = 
'{ 
   "error": 0,
   "reason": "ok",
   "IP": "192.168.1.9",
   "port": "8080"
}';
$log .= $response."\n";

file_put_contents("./log.txt", $log);
echo $response;
?>
