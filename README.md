This Application Utilizes the Alma API, PHP, and various JS plugins to create a 
calendar view for Bookings in Alma.  You can also create new bookings using the 
app.

App flow and structure:

The app starts with the booking.php page.

booking.php calls login.php script which handles authentication for the app using CAS.

booking.php outputs the page and loads the fullcalendar JS plugin for display of the bookings returned.

booking.php then makes a call in the Fullcalendar js to the alma_booking_widget_multiple_mms_patron.php
using the MMSID's of the items which you want to see on the calendar.

alma_booking_widget_multiple_mms_patron.php checks the cache for the existence of
a previously retrieved API call and either serves the cache file or retrieves a 
fresh xml file for output via json.

booking.php receives the json data in response to the previously made call and displays
the bookings on the calendar.

Creating new booking:

The Create new button loads a modal window in an iframe using the featherlight js plugin in
combination with the bootstrapmodal JS.

booking_create.php is the page which is loaded in the iframe modal window.

booking_create.php retrieves the list of bookable items using a list of MMSID's
hardcoded in the top of the script.
The form generated from booking_create.php uses several JS plugins so that when a 
user selects a specific time and date on the calendar the default end date is filled in
automatically:
var defaultDeltaDatepair = new Datepair(defaultDeltaExampleEl, {
             'defaultDateDelta': 3,
             'defaultTimeDelta': 0

The user data entered is submitted to the Alma api and either a succesful booking confirmation 
is returned or an error message is recieved.

If a booking is succesful the MMSID cache file is deleted.  Them new MMSID will be retrieved after closing
the modal window.

Upon closing the modal window since the modal was opened using a featherlight js call, the setting
below in the booking.php code causes the page to refresh forcing a new api check and retrieving the 
new data for the MMSID that was just booked:
$.featherlight.defaults.afterClose = function(){
    location.reload();
};


Future Enhancements:
Limit items to be booked by date range selected by user
Create config file to store MMSID's, API Keys OR
    Create logical set in Alma which could be used instead of hardcoding MMSID's
Pull in fulfillment rules to set default times for new booking creation




