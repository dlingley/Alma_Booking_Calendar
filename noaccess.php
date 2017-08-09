<?php
require('CAS.php');

echo $user . " ";

// logout if desired
if (isset($_REQUEST['logout'])) {
phpCAS::logout(['url' =>  'https://XXXXXXXXXXX/booking.php']);
}
?>

<!DOCTYPE html>
<html>
<head>
</head>
<body>
<a href="?logout=">Logout</a><BR>
You do not have access to this application.  Email XXXX @ XXXX if you feel this is an error.
</body>
</html>
