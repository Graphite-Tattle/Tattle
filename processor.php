<?php
include 'inc/init.php';

function log_action($msg) {
  $today = date("d.m.Y");
  $filename = TATTLE_ROOT . "/logs/$today.txt";
  $fd = fopen($filename, "a");
  $str = "[" . date("d/m/Y h:i:s", time()) . "] " . $msg;
  fwrite($fd, $str . PHP_EOL);
  fclose($fd);
}

$time_start = microtime(true);

$debug = false;
$number_of_emails = 0;

if (isset($_SERVER['argc'])) {
    $args = getopt('d::h::',array('debug','help'));
    if (isset($args['debug']) || isset($args['d'])) {
      $debug = true;
    } elseif (isset($args['help']) || isset($args['h'])) {
      print "Tattle Check Processor: \n".
            "\n" .
            "--help, -h : Displays this help \n".
            "\n" .
            "--debug, -d : Enables debuging (?debug=true can be used via a web request) \n";
    } 
} elseif ($debug = fRequest::get('debug','boolean')) {
  $debug = true;
}

if ($debug) {
  print "debug enabled";
  fCore::enableDebugging(TRUE);
} 

$checks = Check::findActive();
foreach ($checks as $check) {
  $data = Check::getData($check);
  if (count($data) > 0) {
    $title = $check->prepareName();
    fCore::debug('Processing :' . $title . "\n",FALSE);

    if($check->getType() == 'threshold') {
      $check_value = Check::getResultValue($data,$check);
      fCore::debug("Threshold Result: " . $check_value . "\n",FALSE);
      $result = Check::setResultsLevel($check_value,$check);
      fCore::debug("Threshold Check Result: " . $status_array[$result] . "\n",FALSE);
    } elseif($check->getType() == 'predictive') {
      $historical_value = Check::getResultHistoricalValue($data,$check);
      fCore::debug("Predictive historical value: " . $historical_value . "\n",FALSE);
      $current_value = Check::getResultCurrentValue($data);
      fCore::debug("Predictive current value: " . $current_value . "\n",FALSE);
      $standard_deviation = Check::getResultStandardDeviation($data,$check);
      fCore::debug("Predictive historical standard deviation: " . $standard_deviation . "\n",FALSE);
      $check_value = abs($historical_value - $current_value) / $standard_deviation;
      fCore::debug("Predictive current number of standard deviations: " . $check_value . "\n",FALSE);
      $result = Check::setPredictiveResultsLevel($current_value,$historical_value,$standard_deviation,$check);
      fCore::debug("Predictive Check Result: " . $status_array[$result] . "\n",FALSE);
    }

    if (is_null($check->getLastCheckTime())) {
      $next_check = new fTimestamp();   
      fCore::debug("is null?\n",FALSE);
    } else {
      $next_check = $check->getLastCheckTime()->adjust('+' . $check->getRepeatDelay() . ' minutes');
    }
    $end = new fTimestamp();
    if ($next_check->lt($end)) {
      fCore::debug("next check is lt then now\n",FALSE);
    } else {
      fCore::debug("not less then now\n",FALSE);
    }
    // If It's been more then the Repeat Delay or the Status has changed
    if (($next_check->lt($end) && $result != 0) || $check->getLastCheckStatus() != $result) {
      fCore::debug("Send Notification \n",FALSE);
      fCore::debug("State :" . $result . "\n",FALSE);
      $check_result = new CheckResult();
      $check_result->setCheckId($check->getCheckId());
      $check_result->setStatus($result);
      $check_result->setValue($check_value);
      $check_result->setState(0);
      $check->setLastCheckStatus($result);
      $check->setLastCheckValue($check_value);
      $check->setLastCheckTime($end);
      $check_result->store();
      $check->store();
      $subscriptions = Subscription::findAll($check->getCheckId());
      foreach ($subscriptions as $subscription) {
        $notify_function = $subscription->getMethod();
        if (function_exists($notify_function)){
         $notify_result = $notify_function($check,$check_result,$subscription);
         $number_of_emails += 1;
        }
      }
    } else {
      fCore::debug("Skip Notification \n",FALSE);  
    }
  }
  fCore::debug("check done moving to next \n\n",FALSE);
}

$time_end = microtime(true);
$duration = $time_end - $time_start;

log_action("Tattle processor duration: " . $duration . " seconds.");
if($number_of_emails > 0) log_action("Tattle processor sent " . $number_of_emails . " emails.");
