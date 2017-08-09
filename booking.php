<?php
require_once('login.php');

echo $user . " ";

// logout if desired
if (isset($_REQUEST['logout'])) {
phpCAS::logout(['url' =>  'http://' . $_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']) . '/booking.php']);
}
?>



<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8' />
<link href='fullcalendar/fullcalendar.css' rel='stylesheet' />
<link href='fullcalendar/fullcalendar.print.css' rel='stylesheet' media='print' />
<script src='fullcalendar/lib/moment.min.js'></script>
<script src='fullcalendar/lib/jquery.min.js'></script>
<script src='fullcalendar/fullcalendar.min.js'></script>
<script src='fullcalendar/lib/bootstrapmodal.min.js'></script>
<link href='fullcalendar/lib/css/bootstrapmodal.css' rel='stylesheet' />
<link href='fullcalendar/lib/css/bootstrap.css' rel='stylesheet' />
<link href='fullcalendar/lib/featherlight.min.css' rel='stylesheet' />
<script src='fullcalendar/lib/featherlight.min.js'></script>


<script>
$.featherlight.defaults.afterClose = function(){
    location.reload();
};
	$(document).ready(function() {

		$('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,basicWeek,basicDay'
			},
			timezone: 'local',
			displayEventTime: true,
			displayEventEnd: true,
			editable: false,
			//eventLimit: true,  // show "more" link when too many events
			events: {
				url: 'alma_booking_widget_multiple_mms_patron.php?mmsids=99169047001901081,99169047001801081,99169047001701081,99169047001601081,99169033101601081',
				error: function() {
					$('#script-warning').show();
				}
			},
			loading: function(bool) {
				$('#overlay').toggle(bool);
			},
			eventRender: function (event, element) {
					element.attr('href', 'javascript:void(0);');
					element.click(function() {
							//set the modal values and open
							$('#modalTitle').html(event.title);
							$("#startTime").html(moment(event.start).format('MMM Do h:mm A'));
							$("#endTime").html(moment(event.end).format('MMM Do h:mm A'));
							$('#modalBody').html(event.description);
							$('#eventUrl').attr('href',event.url);
							$('#fullCalModal').modal();
					});
			}
		});
	});
</script>
<style>

	body {
		margin: 0;
		padding: 0;
		font-family: "Lucida Grande",Helvetica,Arial,Verdana,sans-serif;
		font-size: 14px;
	}

	#script-warning {
		display: none;
		background: #eee;
		border-bottom: 1px solid #ddd;
		padding: 0 10px;
		line-height: 40px;
		text-align: center;
		font-weight: bold;
		font-size: 12px;
		color: red;
	}

	#loading {
		display: none;
		position: absolute;
		top: 10px;
		right: 10px;
	}

	#calendar {
		max-width: 900px;
		margin: 40px auto;
		padding: 0 10px;
	}
  #overlay {
      background: #ffffff;
      color: #666666;
      position: fixed;
      height: 100%;
      width: 100%;
      z-index: 5000;
      top: 0;
      left: 0;
      float: left;
      text-align: center;
      padding-top: 25%;
  }

  .spinner {
    width: 30px;
    height: 30px;
    color: #939BA1;
    background-color: #333;
    margin: 0 auto 12px auto;
    -webkit-animation: rotateplane 1.2s infinite ease-in-out;
    animation: rotateplane 1.2s infinite ease-in-out;
  }

  @-webkit-keyframes rotateplane {
    0% { -webkit-transform: perspective(120px) }
    50% { -webkit-transform: perspective(120px) rotateY(180deg) }
    100% { -webkit-transform: perspective(120px) rotateY(180deg)  rotateX(180deg) }
  }

  @keyframes rotateplane {
    0% {
      transform: perspective(120px) rotateX(0deg) rotateY(0deg);
      -webkit-transform: perspective(120px) rotateX(0deg) rotateY(0deg)
    } 50% {
      transform: perspective(120px) rotateX(-180.1deg) rotateY(0deg);
      -webkit-transform: perspective(120px) rotateX(-180.1deg) rotateY(0deg)
    } 100% {
      transform: perspective(120px) rotateX(-180deg) rotateY(-179.9deg);
      -webkit-transform: perspective(120px) rotateX(-180deg) rotateY(-179.9deg);
    }
  }
</style>
</head>
<body>
<div id="overlay">
    <div class="spinner"></div>
    Refreshing API Data... Please Wait...
</div>
<a href="?logout=" class="btn btn-info" role="button">Logout</a>
	<a href="booking_create.php" class="btn btn-info" role="button" data-featherlight="iframe" data-featherlight-iframe-width="400" data-featherlight-iframe-height="400">Create New Booking >></a>

	<div id="fullCalModal" class="modal fade">
	    <div class="modal-dialog">
	        <div class="modal-content">
	            <div class="modal-header">
	                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span> <span class="sr-only">close</span></button>
	                <h4 id="modalTitle" class="modal-title"></h4>
	            </div>
	            Start: <span id="startTime"></span><br>
	            End: <span id="endTime"></span><br><br>
	            <div id="modalBody" class="modal-body"></div>
	            <div class="modal-footer">
	                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	                <button class="btn btn-primary"><a id="eventUrl" target="_blank">Cancel Booking (User Notified)</a></button>
	            </div>
	        </div>
	    </div>
	</div>

<script> //reload on modal window close
$('#fullCalModal').on('hidden.bs.modal', function () {
 location.reload();
})
</script>

	<div id='script-warning'>
		<code>alma_booking_widget_multiple_mms_patron.php</code> must be running.
	</div>

	<div id='calendar'></div>
</body>
</html>
