<?php

/* CONFIGURATION */
require_once('login.php');

// set the Caching Frequency - Daily, Hourly or None (recommended default: Daily)
define("CACHE_FREQUENCY","Daily");

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
if(isset($_GET['mmsids']))
{
	$mmsids = explode(',', $_GET['mmsids']);

	if(isset($_GET['debug']))
	{
		print("mmsids:\n");
		print_r($mmsids);
	}
}
else
{
	// ERROR: required parameter missing
	print("ERROR: required 'mmsids' parameter missing<br>\n");
	exit();
}



$xml_result = false;

foreach ($mmsids as $mmsid) {

	$mmsid = urlencode($mmsid);
	// BUILD REST REQUEST URL
	$url = "https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/".$mmsid."/requests?request_type=BOOKING&status=active&apikey=".ALMA_BOOKING_API_KEY;

	if(isset($_GET['debug']))
		print("URL:" . $mmsid . " $url<br>\n");

	if(strcmp(CACHE_FREQUENCY,"None"))
	{
		// check cache for hours
		if(file_exists("cache/".$mmsid.".xml"))
		{
			// check last modified datestamp
			$cache_expired = false;
			switch(CACHE_FREQUENCY)
			{
				case 'Hourly': if(filemtime("cache/".$mmsid.".xml") < strtotime(date("Y-m-d H:00:00",strtotime("now")))) $cache_expired = true;
				default: if(filemtime("cache/".$mmsid.".xml") < strtotime(date("Y-m-d 00:00:00",strtotime("now")))) $cache_expired = true;
			}
			//$cache_expired = true;
			if(!$cache_expired)
			{
				$xml_result = simplexml_load_file("cache/".$mmsid.".xml");
				if(isset($_GET['debug'])) print("loaded data from cache file: cache/".$mmsid.".xml<br>\n");
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
			file_put_contents("cache/".$mmsid.".xml",$result);
			if(isset($_GET['debug']))
				{
					print("File written to cache\n");
				}
		}

		$xml_result = simplexml_load_string($result);
	}

	// PARSE RESULTS
	foreach($xml_result->user_request as $user_request)
	{

			$booking_obj = new stdClass();

			$booking_obj->title = (string) $user_request->description;

			$booking_obj->title  .=  " - " . retrievePatronInfo((string) $user_request->user_primary_id);

			$booking_obj->url = 'https://' . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']) . '/booking_delete.php?requestid=' . (string) $user_request->request_id . "&userid=" . (string) $user_request->user_primary_id. "&mmsid=" . $mmsid;

			// Start and End Times are sent back in UTC and fullcalendar.io handles the conversion
			//Adjust times are used, because if the differ they are the actual start/stop times
			date_default_timezone_set('America/New_York');

			$booking_obj->start = date(DATE_ISO8601,strtotime((string) $user_request->adjusted_booking_start_date . ' UTC'));
			$booking_obj->end = date(DATE_ISO8601,strtotime((string) $user_request->adjusted_booking_end_date . ' UTC'));


			// Set colors for json feed based on title startswith
			if (0 === strpos($booking_obj->title, 'HD'))
				{
                // If title starts with 'HD'
					$booking_obj->borderColor = "#5173DA";
					$booking_obj->color = "#99ABEA";
					$booking_obj->textColor = "#000000";
				}
				elseif (0 === strpos($booking_obj->title, 'VID'))
				{
					$booking_obj->borderColor = "#5173DA";
					$booking_obj->color = "yellow";
					$booking_obj->textColor = "#000000";
				}
				elseif (0 === strpos($booking_obj->title, 'TRI'))
				{
					$booking_obj->borderColor = "#5173DA";
					$booking_obj->color = "red";
					$booking_obj->textColor = "white";
				}
				elseif (0 === strpos($booking_obj->title, 'CAM'))
				{
					$booking_obj->borderColor = "#5173DA";
					$booking_obj->color = "gold";
					$booking_obj->textColor = "#000000";
				}
				else
				{
					$booking_obj->borderColor = "#5173DA";
					$booking_obj->color = "black";
					$booking_obj->textColor = "white";
				}

			//Create Description field to send back in JSON feed
			$booking_obj->description = "Request ID:" . (string) $user_request->request_id;

            //Add this booking to the array of bookings using the unique request id as the index value
			$bookings[trim($user_request->request_id)] = $booking_obj;

	}

	if(isset($_GET['debug']))
	{
		print("<pre>\n");
		print_r($xml_result);
		print("</pre>\n");
	}
	$xml_result = false;
}

// CREATE JSON OUTPUT
//strip top level unique id from array
$out = array_values($bookings);
print(json_encode($out));

if(isset($_GET['debug']))
{
	print("<pre>\n");
	print("calculated bookings: \n");
	print_r($out);
	print("</pre>\n");
}

function retrievePatronInfo($puid){
$ch = curl_init();
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

$xml_patron_result = false;
		$puid = urlencode($puid);
		// BUILD REST REQUEST URL
		$url = "https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/".$puid."?user_id_type=all_unique&view=full&expand=none&apikey=".ALMA_BOOKING_API_KEY;
		if(isset($_GET['debug']))
		print("URL:" . $puid . " $url<br>\n");

		if(strcmp(CACHE_FREQUENCY,"None"))
		{
			// check cache for hours
			if(file_exists("cache/".$puid.".xml"))
			{
				// check last modified datestamp
				$cache_expired = false;
				switch(CACHE_FREQUENCY)
				{
					case 'Hourly': if(filemtime("cache/".$puid.".xml") < strtotime(date("Y-m-d H:00:00",strtotime("now")))) $cache_expired = true;
					default: if(filemtime("cache/".$puid.".xml") < strtotime(date("Y-m-d 00:00:00",strtotime("now")))) $cache_expired = true;
				}
				//$cache_expired = true;
				if(!$cache_expired)
				{
					$xml_patron_result = simplexml_load_file("cache/".$puid.".xml");
					if(isset($_GET['debug'])) print("loaded data from cache file: cache/".$puid.".xml<br>\n");
				}
			}
		}

		// if no cache data available, query the Alma API
		if(!$xml_patron_result)
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
				file_put_contents("cache/".$puid.".xml",$result);
				if(isset($_GET['debug']))
					{
						print("File written to cache\n");
					}
			}

			$xml_patron_result = simplexml_load_string($result);
		}

		// PARSE RESULTS

				$fullname = (string) $xml_patron_result->full_name;
				return $fullname;


		if(isset($_GET['debug']))
		{
			print("<pre>\n");
			print_r($xml_patron_result);
			print("</pre>\n");
		}
		$xml_patron_result = false;


	}


curl_close($ch);

?>
