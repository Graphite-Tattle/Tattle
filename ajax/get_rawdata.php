<?php
include '../inc/init.php';

fAuthorization::requireLoggedIn();
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
$debug = fRequest::get('debug','boolean');

if (!$debug){ 
  header('Content-type: application/json');
}
$check_id = fRequest::get('check_id', 'integer');
$check =  new Check($check_id);

if ( $GLOBALS['PRIMARY_SOURCE'] == "GANGLIA" ) {
  $parts = explode("_|_", $check->prepareTarget());
  $url = $GLOBALS['GANGLIA_URL'] . "/graph.php?graphlot=1&cs=-1day&ce=now&c=" . 
  $parts[0] . "&h=" . $parts[1] . "&m=" . $parts[2];
} else {
  $url = $GLOBALS['GRAPHITE_URL'] . '/graphlot/rawdata?&from=-24hour&until=-0hour' .
         '&target=' . $check->prepareTarget() . 
         '&target=keepLastValue(threshold(' . $check->prepareWarn() .'))' ;
}
$contents = file_get_contents($url);
print $contents;
