<?php
include dirname(__FILE__) . '/../inc/init.php';

fAuthorization::requireLoggedIn();
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
$debug = fRequest::get('debug','boolean');

if (!$debug){ 
  header('Content-type: application/json');
}
$check_id = fRequest::get('check_id', 'integer');

$check =  new Check($check_id);

if ( $GLOBALS['SOURCE_ENGINE'] == "GANGLIA" ) {

  $parts = explode("_|_", $check->prepareTarget());
  $url = $GLOBALS['GANGLIA_URL'] . "/graph.php?graphlot=1&cs=-1day&ce=now&c=" . 
    $parts[0] . "&h=" . $parts[1] . "&m=" . $parts[2];

} else {

  $url = GRAPHITE_URL . '/graphlot/rawdata?&from=-24hour&until=-0hour' .
       '&target=' . $check->prepareTarget() . 
       '&target=keepLastValue(threshold(' . $check->prepareWarn() .'))' ;
  //       '&target=threshold(' . $check->prepareError() . ')';

} 

$contents = file_get_contents($url);

//$contents = file_get_contents(GRAPHITE_URL . '/graphlot/rawdata?&from=-24hour&until=-0hour&target=' . $check->prepareTarget() . '&target=' . $check->prepareWarn() . '&target=' . $check->prepareError());
print $contents;
