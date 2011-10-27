<?php
include_once('inc/init.php');
//$debug = false;
$debug = true;
if ($debug) {
  fCore::enableDebugging(TRUE);
}

$checks = Check::findActive();
fCore::debug('<pre>',FALSE);
foreach ($checks as $check) {
  $data = Check::getData($check);
  if (count($data) > 0) {
    $title = $check->prepareName();
    fCore::debug('Processing :' . $title . ":\n",FALSE);
    $check_value = Check::getResultValue($data,$check);
    fCore::debug("Result :" . $check_value . ":\n",FALSE);
    $result = Check::setResultsLevel($check_value,$check);
    fCore::debug("Check Value:" . $result . ":\n",FALSE);
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
    if ($next_check->lt($end) || $check->getLastCheckStatus() != $result) {
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
        $notify_function = $subscription->getMethod() . '_notify';
        if (function_exists($notify_function)){
         $notify_result = $notify_function($check,$check_result,$subscription);  
        }
      }
    } else {
      fCore::debug("Skip Notification \n",FALSE);  
    }
  } else {
    //echo "Check Failed <br />";    
  }
  fCore::debug("check done moving to next \n\n",FALSE);
}
fCore::debug('</pre>',FALSE);
