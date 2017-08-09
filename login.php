<?php

require('CAS.php');
require('vendor/autoload.php');

require_once('vendor/adldap/adldap/src/Adldap.php');
use adLDAP\adLDAP as AD;

$config = array(
        'account_suffix'      => '@xxx.yyy.zzz',
        'base_dn'             => 'dc=xxx,dc=yyy,dc=zzz',
        'domain_controllers'  => array('XXXX'),
        'admin_username'      => 'XXXXXX',
        'admin_password'      => 'XXXXXX'
);

// set your Alma Booking API Key
define("ALMA_BOOKING_API_KEY","YOURKEYHERE");

$user = phpCAS::getUser();
//echo $user;
/*var_dump(phpCAS::getAttributes());*/

$ad = new  AD($config);

$result = false;

$groups = array("YOUR_GROUP1","YOUR_GROUP2");
//var_dump($_SESSION);
if($user) {
    foreach ($groups as $i => $group) {
      $result=$ad->user()->inGroup($user,$group);
      if($result) {
        break;
      }
    }
}

if($result != true){
	header("Location: noaccess.php");
	exit;
}
