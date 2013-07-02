<?php
include 'inc/init.php';

function log_action($msg) {
  $today = date("d.m.Y");
  $filename = $GLOBALS['PROCESSOR_LOG_PATH'] . "$today.txt";
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
//$debug = true; // FIXME

if ($debug) {
  print "debug enabled\n";
  fCore::enableDebugging(TRUE);
}

$checks = Check::findActive();
foreach ($checks as $check) {
  $data = Check::getData($check);
    $title = $check->prepareName();
    fCore::debug('Processing :' . $title . "\n",FALSE);
  if (count($data) > 0) {

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
    	
    	// Check if it's in the period
    	$notify = TRUE;
    	$hour_start = $check->getHourStart();
    	$day_start = $check->getDayStart();
    	if (!empty($hour_start)) {
		fCore::debug("Evaluating alert time window.");
    		$hour_end = $check->getHourEnd();
    		$current_time = $end->date("H:i");
    		if (compare_hours($hour_start, $hour_end) <= 0) {
    			// In this case $hour_start is lower than $hour_end
    			// For example from 6 AM to 9 AM
    			// The current time must be between these two hours 
    			$notify =  (compare_hours($hour_start, $current_time) <= 0) && (compare_hours($current_time, $hour_end) <= 0);
    		} else {
    			// In this case $hour_start is greater than $hour_end
    			// For example from 10 PM to 6 AM of the next day
    			// The current time must be outside the interval
                        $notify = (compare_hours($hour_start, $current_time) <= 0 || (compare_hours($current_time, $hour_end) <= 0));
    		}
		if (!$notify) {
			fCore::debug("Won't notify because the alert is not curently active (active only for hours between "
				.$hour_start." and ".$hour_end." and current time is ".$current_time.")");
		}
	}
    	// If notify is already FALSE, it doesn't matter which day we are
    	if ($notify) {
    		if (!empty($day_start)) {
			fCore::debug("Evaluating alert day of week window.".$day_start." ".$check->getDayEnd()." ".$end->date("w"));
			    $days = array(
			    	"sun" => 0,
			    	"mon" => 1,
			    	"tue" => 2,
			    	"wed" => 3,
			    	"thu" => 4,
			    	"fri" => 5,
			    	"sat" => 6	
			    );
			    $day_end = $check->getDayEnd();
			    $current_day = $end->date("w");
			    if ($days[$day_start] <= $days[$day_end]) {
			    	// In this case $day_start is lower than $day_end
			    	// For example from tuesday to friday
			    	// The current day must be between these two days
			    	$notify = ($days[$day_start] <= $current_day) && ($current_day <= $days[$day_end]) ;
			    } else {
			    	// In this case $day_start is greater than $day_end
			    	// For example from satursday to monday of the next week
			    	// The current day must be outside the interval
				$notify = ($days[$day_start] <= $current_day) || ($current_day <= $days[$day_end]);
			    }
			if (!$notify) {
				fCore::debug("Won't notify because the alert is not curently active (active only for days between "
					.$days[$day_start]." and ".$days[$day_end]." and we are ".$current_day.")");
			}
		}
	}
	     if ($notify) {
		      fCore::debug("Send Notification \n",FALSE);
		      fCore::debug("State :" . $result . ":\n",FALSE);
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
		        if (function_exists($notify_function) && $subscription->getStatus() == 0 ){
		         $notify_result = $notify_function($check,$check_result,$subscription);
		         $number_of_emails += 1;
		        }
		      }
             }
    } else {
      fCore::debug("Skip Notification because check status did not change\n",FALSE);
    }
  }
  else {
      fCore::debug("couldn't get data!\n",FALSE);
      fCore::debug("Data: $data\n",FALSE);
  }
  fCore::debug("check done moving to next \n\n",FALSE);
}

$time_end = microtime(true);
$duration = $time_end - $time_start;

log_action("Tattle processor duration: " . $duration . " seconds.");
if($number_of_emails > 0) log_action("Tattle processor sent " . $number_of_emails . " emails.");
