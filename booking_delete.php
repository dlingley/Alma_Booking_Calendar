<?php

/* CONFIGURATION */

require_once('login.php');


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
if(isset($_GET['mmsid']))
	$mmsid = urlencode($_GET['mmsid']);
else
{
	// ERROR: required parameter missing
	print("ERROR: required 'mmsid' parameter missing<br>\n");
	exit();
}


$xml_result = false;

	// BUILD REST REQUEST URL
$ch = curl_init();
$url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/' . $userid. '/requests/' . $requestid;
$queryParams = '?' . urlencode('reason') . '=' . urlencode('CancelledAtPatronRequest') . '&' . urlencode('note') . '=' . urlencode($user . ' Cancelled Using Web Interface') . '&' . urlencode('notify_user') . '=' . urlencode('true') . '&' . urlencode('apikey') . '=' . urlencode(ALMA_BOOKING_API_KEY);
curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
$response = curl_exec($ch);
curl_close($ch);

	if(isset($_GET['debug']))
		print("URL:" . $url . $queryParams . "<br>\n");

	// PARSE RESULTS

	if(isset($_GET['debug']))
	{
		print("<pre>\n");
		print_r($response);
		print("</pre>\n");
	}
if($response){
	$xml_delete_result = simplexml_load_string($response);
	$error = (string) $xml_delete_result->errorsExist;
	if(strcmp($error, "true") == 0)
		{
			//start times are not equal, so use the udjusted time
			echo "Delete Error Occurred for Request ID:" . $requestid;
			$errorDetail = (string) $xml_delete_result->errorList->error->errorMessage;
			echo "<BR><BR>ERROR IS: " . $errorDetail;
			echo "<BR><a href=\"javascript:window.open('','_self').close();\">Close tab to return to booking page.</a>";


		}
	}
		else {
			echo "Delete of Booking ID:" . $requestid . " was succesful.  <BR><a href=\"javascript:window.open('','_self').close();\">Close tab to return to booking page.</a>";
		}



// check if cached mmsid file exists and delete if so
		if(file_exists("cache/".$mmsid.".xml"))
		{
			unlink("cache/".$mmsid.".xml");

			if(isset($_GET['debug']))
				{
					print("cache file deleted");
				}

		}
//run check on booking again and if status is history, display success message to user
?>
