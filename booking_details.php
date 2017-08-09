<?php

/* CONFIGURATION */
require_once('login.php');

// set the Caching Frequency - Daily, Hourly or None (recommended default: Daily)
define("CACHE_FREQUENCY","Hourly");

// if this file is not on the same host as the widget JavaScript file, cross-site scripting (XSS) access needs to be allowed
//$allowed_domains = array();

/* END OF CONFIGURATION */


//if(in_array($_SERVER['HTTP_ORIGIN'],$allowed_domains))
//	header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
//else
	header('Access-Control-Allow-Origin: none');

$bookings = array();

$ch = curl_init();
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

// REQUIRED PARAMETERS
if(isset($_GET['requestid']))
	$requestid = urlencode($_GET['requestid']);
else
{
	// ERROR: required parameter missing
	print("ERROR: required 'requestid' parameter missing<br>\n");
	exit();
}
if(isset($_GET['userid']))
	$userid = urlencode($_GET['userid']);
else
{
	// ERROR: required parameter missing
	print("ERROR: required 'useerid' parameter missing<br>\n");
	exit();
}


$xml_result = false;

	// BUILD REST REQUEST URL
	$url = "https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/".$userid."/requests/".$requestid."?user_id_type=all_unique&apikey=".ALMA_BOOKING_API_KEY;

	if(isset($_GET['debug']))
		print("URL:" . $requestid . " " . $userid . " $url<br>\n");

	if(strcmp(CACHE_FREQUENCY,"None"))
	{
		// check cache for hours
		if(file_exists("cache/".$requestid.".xml"))
		{
			// check last modified datestamp
			$cache_expired = false;
			switch(CACHE_FREQUENCY)
			{
				case 'Hourly': if(filemtime("cache/".$requestid.".xml") < strtotime(date("Y-m-d H:00:00",strtotime("now")))) $cache_expired = true;
				default: if(filemtime("cache/".$requestid.".xml") < strtotime(date("Y-m-d 00:00:00",strtotime("now")))) $cache_expired = true;
			}
			//$cache_expired = true;
			if(!$cache_expired)
			{
				$xml_result = simplexml_load_file("cache/".$requestid.".xml");
				if(isset($_GET['debug'])) print("loaded data from cache file: cache/".$requestid.".xml<br>\n");
			}
		}
	}

	// if no cache data available, query the Alma API
	if(!$xml_result)
	{
		// use curl to make the API request
		curl_setopt($ch,CURLOPT_URL, $url);
		$result = curl_exec($ch);

		if(isset($_GET['debug']))
		{
			print("xml result from API<br>\n");
			print("<pre>".htmlspecialchars($result)."</pre>");
		}

		// save result to cache
		if(strcmp(CACHE_FREQUENCY,"None") && is_writable("cache/"))
		{
			file_put_contents("cache/".$requestid.".xml",$result);
			if(isset($_GET['debug']))
				{
					print("File written to cache\n");
				}
		}

		$xml_result = simplexml_load_string($result);
	}

	// PARSE RESULTS

			date_default_timezone_set('America/New_York');

			$booking_obj = new stdClass();

			$booking_obj->booking_id = (string) $xml_result->request_id;

			$booking_obj->request_initiated_date = date('m-d-Y',strtotime((string) $xml_result->request_date));

			$booking_obj->status = (string) $xml_result->request_status;

			$booking_obj->title = (string) $xml_result->title;

			$booking_obj->description = (string) $xml_result->description;

			$booking_obj->pickup_location = (string) $xml_result->pickup_location . " - HIKS UGRL Room G950";

			$booking_start = date('m-d-Y h:i A T',strtotime((string) $xml_result->booking_start_date . ' UTC'));
			$adjusted_start = date('m-d-Y h:i A T',strtotime((string) $xml_result->adjusted_booking_start_date . ' UTC'));

			if(strcmp($booking_start, $adjusted_start) !== 0)
				{
					//start times are not equal, so use the udjusted time
					$booking_obj->start_time = "Adjusted:". $adjusted_start;
				}
				else
				{
					$booking_obj->start_time = $booking_start;
				}

			$booking_end = date('m-d-Y h:i A T',strtotime((string) $xml_result->booking_end_date. ' UTC'));
			$adjusted_end = date('m-d-Y h:i A T',strtotime((string) $xml_result->adjusted_booking_end_date. ' UTC'));

			if(strcmp($booking_end, $adjusted_end) !== 0)
				{
					//end times are not equal, so use the udjusted time
					$booking_obj->end_time = "Adjusted:". $adjusted_end;
				}
				else
				{
					$booking_obj->end_time = $booking_end;
				}


	if(isset($_GET['debug']))
	{
		print("<pre>\n");
		print_r($xml_result);
		print("</pre>\n");
	}

// CREATE JSON OUTPUT
//strip top level unique id from array
//$out = array_values($booking);
//print(json_encode($out));
print(json_encode($booking_obj));

if(isset($_GET['debug']))
{
	print("<pre>\n");
	print("Booking Details: \n");
	print_r($booking_obj);
	print("</pre>\n");
}

curl_close($ch);

?>
