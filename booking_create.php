<?php

/* CONFIGURATION */

require_once('login.php');

$items = false;

$mmsids = array(99169047001901081,99169047001801081,99169047001701081,99169047001601081,99169033101601081);
//Fill selection arrays
// check cache for items
if(file_exists("cache/bookingItems.json"))
{
	// check last modified datestamp
	$cache_expired = false;
  if(filemtime("cache/bookingItems.json") < strtotime(date("Y-m-d 00:00:00",strtotime("now")))) $cache_expired = true;
	//$cache_expired = true;
	if(!$cache_expired)
	{
		$items = json_decode(file_get_contents('cache/bookingItems.json'), true);
		if(isset($_GET['debug'])) print("loaded data from array cache file: cache/bookingItems.json<br>\n");
	}
}
else {
	echo "Refreshing Item data, Please wait...";
}

if(isset($_GET['debug']))
{
		foreach ($_POST as $key => $value)
		echo "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";
}
// if no cache aaray data available, query the Alma API
if(!$items)
{
	foreach ($mmsids as $mmsid) {

		$mmsid = urlencode($mmsid);
		// BUILD get holidng REST REQUEST URL
		$url = "https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/".$mmsid."/holdings?apikey=".ALMA_BOOKING_API_KEY;

		if(isset($_GET['debug']))
			print("Get Bibs URL:" . $mmsid . " $url<br>\n");


			// use curl to make the Bibs API request
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch,CURLOPT_URL, $url);
			$result = curl_exec($ch);

			if(isset($_GET['debug']))
			{
				print("xml result from API<br>\n");
				print("<pre>".htmlspecialchars($result)."</pre>");
			}

			$holding_result = simplexml_load_string($result);
			//get bib holding_link <- code assumes 1 holding per bib
			$holding_link = (string) $holding_result->holding['link'];

			if(isset($_GET['debug']))
	    echo ("***********".$holding_link);
			// use curl to make the Items API request
	    $url = $holding_link . "/items?limit=100&offset=0&apikey=".ALMA_BOOKING_API_KEY;

			if(isset($_GET['debug']))
			print("Get Items URL:" . $url. "<br>\n");

			$ch = curl_init();
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch,CURLOPT_URL, $url);
			$result = curl_exec($ch);

			if(isset($_GET['debug']))
			{
				print("xml result from API<br>\n");
				print("<pre>".htmlspecialchars($result)."</pre>");
			}

		  $item_result = simplexml_load_string($result);
			// PARSE RESULTS
			foreach($item_result as $item)
			{

					$item_obj = new stdClass();

					$item_obj->name = (string) $item->bib_data->title;

					$item_obj->mms_id = (string) $item->bib_data->mms_id;

					$item_obj->holding_id = (string) $item->holding_data->holding_id;

					$item_obj->item_id = (string) $item->item_data->pid;

					$item_obj->description = (string) $item->item_data->description;

					$item_obj->status = (string) $item->item_data->base_status;

					$items[] = $item_obj;
			}

	}
	// save items array to cache
	//strip top level unique id from array
	if(isset($_GET['debug']))
	{
		print("<pre>\n");
		print_r($items);
		print("</pre>\n");
	}

	$out = array_values($items);

	function my_sort($a, $b)
	{
	    if ($a->description < $b->description) {
	        return -1;
	    } else if ($a->description > $b->description) {
	        return 1;
	    } else {
	        return 0;
	    }
	}

	usort($out, 'my_sort');

	file_put_contents("cache/bookingItems.json",json_encode($out));
		if(isset($_GET['debug']))
			{
				print("File written to cache\n");
			}
	//Refresh page after refreshing Item list
	header("Refresh:0");
}





//print item array for debug
if(isset($_GET['debug']))
{
	print("<pre>\n");
	print_r($items);
	print("</pre>\n");
}

// BUILD REST REQUEST URL

//Capture Request data entered from form
$entered_puid = isset($_POST['entered_puid']) ? $_POST['entered_puid'] : '';
if(isset($_GET['debug']))
{
	echo ($entered_puid);
}

$selected_item_data = isset($_POST['selected_item_data']) ? $_POST['selected_item_data'] : '';
if(isset($_GET['debug']))
{
	echo ($selected_item_data);
}
//Explode Item data into Item ID and MMS ID
if($selected_item_data != '')
		{
			$item_data_explode = explode('|', $selected_item_data);
			$selected_mms_id = (string) $item_data_explode[0];
			$selected_item_id = (string) $item_data_explode[1];
		}
//Read in date and time passed from form
$start_time = isset($_POST['start_time']) ? $_POST['start_time'] : '';
if(isset($_GET['debug']))
{
	echo ($start_time);
}
$end_time = isset($_POST['end_time']) ? $_POST['end_time'] : '';
if(isset($_GET['debug']))
{
	echo ($end_time);
}

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
if(isset($_GET['debug']))
{
	echo ($start_date);
}
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
if(isset($_GET['debug']))
{
	echo ($end_date);
}

//Only process request if all request data is present
if ($entered_puid != '' && $selected_item_data != '' && $start_time != '' && $end_time != '' && $start_date != '' && $end_date != '')
{

	//concatenate and convert entered start time and stop time to Zulu time
	$booking_start = $start_date . " " . $start_time;
	$booking_stop = $end_date . " " . $end_time;

	$start = gmdate('Y-m-d\TH:i:s\Z', strtotime($booking_start));
	$stop = gmdate('Y-m-d\TH:i:s\Z', strtotime($booking_stop));

	$timestamp = time()+date("Z");
  $request_date = gmdate('Y-m-d\Z', strtotime($timestamp));

	// Store XML Request object in Variable for passing to curl
	$request_xml = '<?xml version="1.0" encoding="UTF-8"?>
	<user_request>
	  <request_type>BOOKING</request_type>
	  <pickup_location>ITaP Equipment</pickup_location>
	  <pickup_location_type>LIBRARY</pickup_location_type>
	  <pickup_location_library>dlc</pickup_location_library>
		<material_type>EQUIP</material_type>
		<comment>Created using web app by '.$user.'</comment>
	  <request_date>'.$request_date.'</request_date>
	  <booking_start_date>'.$start.'</booking_start_date>
	  <booking_end_date>'.$stop.'</booking_end_date>
	</user_request>';

	$ch = curl_init();
	$url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/'.$entered_puid.'/requests';
	$queryParams = '?' . urlencode('user_id_type') . '=' . urlencode('all_unique') . '&' . urlencode('mms_id') . '=' . $selected_mms_id . '&' . urlencode('item_pid') . '=' . $selected_item_id . '&' . urlencode('apikey') . '=' . urlencode(ALMA_BOOKING_API_KEY);
	curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request_xml);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
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
			$xml_create_result = simplexml_load_string($response);
			if(isset($_GET['debug']))
			{
				print("<pre>\n");
				print_r($xml_create_result);
				print("</pre>\n");
			}
			$error = (string) $xml_create_result->errorsExist;
			if(strcmp($error, "true") == 0)
				{
					echo "Create Error Occurred for Item: <b>" . $selected_item_id;
					$errorDetail = (string) $xml_create_result->errorList->error->errorMessage;
					echo "<BR>" . $errorDetail;
					echo "</B><BR>If Error is not clear, possible reasons for failure Include:  <LI>Booking request exceeded max time allowed (3 days)";
					echo "<LI>Only 1 booking per type of item allowed per patron in the same time period";
					echo "<LI>You can try booking in Alma directly using ITEM ID from error Message";
				}
				else {
					$booking_id = (string) $xml_create_result->request_id;
					//Grab the Start Stop Times so we can see if they have been adjusted
					$booking_start = (string) $xml_create_result->booking_start_date;
					$adjusted_start = (string) $xml_create_result->adjusted_booking_start_date;
					$booking_end = (string) $xml_create_result->booking_end_date;
					$adjusted_end = (string) $xml_create_result->adjusted_booking_end_date;

					echo "Creation of Booking ID:" . $booking_id . " was succesful.";
					echo "<BR>Desc:  " . (string) $xml_create_result->description;
					echo "<BR>Status:" . (string) $xml_create_result->request_status;
					if(strcmp($booking_start, $adjusted_start) !== 0)
						{
							//start times are not equal, so Start time was adjusted
							echo "<BR><b>Booking start time was adjusted to fall within library open hours</b>";
						}
					echo "<BR>Start: " . date('m-d-Y h:i A T',strtotime((string) $xml_create_result->adjusted_booking_start_date. ' UTC'));
					if(strcmp($booking_end, $adjusted_end) !== 0)
						{
							//End times are not equal, so end time was adjusted
							echo "<BR><b>Booking end time was adjusted to fall within library open hours</b>";
						}
					echo "<BR>Stop:  " . date('m-d-Y h:i A T',strtotime((string) $xml_create_result->adjusted_booking_end_date. ' UTC'));
					echo "<BR>To create another booking simply fill form and submit again.";
				}
			}



		// check if cached mmsid file exists and delete so calendar will immediately update
				if(file_exists("cache/".$selected_mms_id.".xml"))
				{
					unlink("cache/".$selected_mms_id.".xml");

					if(isset($_GET['debug']))
						{
							print("cache file deleted");
						}

				}

		//Clear Selected item data:
		$selected_item_data = '';
}
else {

	 //Check if form has been submitted to display all values needed message if true.
	  if ($_SERVER['REQUEST_METHOD'] == 'POST')
		{
		echo "Must provide all values to place request.";
		}
}

?>
<html>
   <head>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://jonthornton.github.io/jquery-timepicker/jquery.timepicker.js"></script>
    <link rel="stylesheet" type="text/css" href="https://jonthornton.github.io/jquery-timepicker/jquery.timepicker.css" />
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.5.0/js/bootstrap-datepicker.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.5.0/css/bootstrap-datepicker.standalone.css" />
		<script src="fullcalendar/lib/datepair.min.js"></script>
   </head>

   <body>
		 <BR>
		 <form method="post" action="">

		 Select Item : <select name="selected_item_data">
	   <option value="">-----------------</option>
		 <?php
		 foreach ($items as $key => $arr) {
				 printf('<option value="%s|%s">%s</option>', $arr['mms_id'], $arr['item_id'], $arr['description']).PHP_EOL;
		 }
		 ?>
		 </select>
		 <p id="defaultDeltaExample">
				<BR><input type="text" name="start_date" class="date start" value="Select Start Date"/>
				<input type="text" name="start_time" class="time start"  value="Select Start Time" /><br>
				 <BR><input type="text" name="end_date" class="date end"/>
				<input type="text" name="end_time" class="time end"/>
		</p>
		 Enter Patron PUID (Format: 0015137885): <BR>
			 <Center><input type="text" name="entered_puid">
		 <input type="submit"></center>
		 </form>

		 <script>
         $('#defaultDeltaExample .time').timepicker({
             'showDuration': true,
						 'scrollDefault': 'now',
             'timeFormat': 'g:ia',
						 'disableTimeRanges': [
							        ['12am', '6:59am']
							    ]

         });

         $('#defaultDeltaExample .date').datepicker({
             'format': 'm/d/yyyy',
						 todayHighlight: true,
             'autoclose': true
         });

         var defaultDeltaExampleEl = document.getElementById('defaultDeltaExample');
         var defaultDeltaDatepair = new Datepair(defaultDeltaExampleEl, {
             'defaultDateDelta': 3,
             'defaultTimeDelta': 0
         });
      </script>

   </body>
</html>
