#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function drugInfo($request)
{
	try
	{
		$req = $request['drugName'];
		
		$url = "https://api.fda.gov/drug/enforcement.json?search=results.openfda.brand_name=".$req."";
		$result = file_get_contents($url);
		
		$data = json_decode($result, true);
		
		$genericName = $data['results'][0]['openfda']['generic_name'][0];
		$productDec = $data['results'][0]['product_description'];
		$recallReportDate = $data['results'][0]['report_date'];
		$recallData = $data['results'][0]['reason_for_recall'];
		$brandName = $data['results'][0]['openfda']['brand_name'][0];
		$status = $data['results'][0]['status'];
		$refLink = "https://druginfo.nlm.nih.gov/drugportal/name/".$req;

		$stmt = array();
		$stmt['genericName'] = $genericName;
		$stmt['productDesc'] = $productDec;
		$stmt['recallReportDate'] = $recallReportDate;
		$stmt['reason'] = $recallData;
		$stmt['brandName'] = $brandName;
		$stmt['status'] = $status;
		$stmt['reference'] = $refLink;
		echo $url;
		return $stmt;
//return(file_get_contents("https://api.fda.gov/drug/enforcement.json?search=results.openfda.brand_name=%22".$req."%22"));

		
	}
	catch (Exception $e)
	{
		$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
		$request = array();
		$request["type"] = "log";
		$request["message"] = $e->getMessage();
		$client->publish($request);
		echo ("\nException: ". $e->getMessage());
	}
}

function requestProcessor($request)
{
	echo "\nreceived request".PHP_EOL;
	if(!isset($request['type']))
	{
		return "ERROR: Unsupported Message Type";
	} 
	switch ($request['type'])
	{
		 case "apiReq":
		 return drugInfo($request);
	}

	
   return array("returnCode" => '0', 'message'=>"Server received request and processed");


}


$server = new rabbitMQServer("apiRabbitMQ.ini","apiServer");
$server->process_requests('requestProcessor');
exit();

?>
