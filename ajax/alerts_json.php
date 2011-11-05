<?php
include '../inc/init.php';

fAuthorization::requireLoggedIn();
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

$check_id = fRequest::get('check_id', 'integer');
$page_id = fRequest::get('page_id', 'integer');

/*------------------------------------*/

$check_results = CheckResult::findAll($check_id);

$check_results_json = array();
foreach ($check_results as $check_result) {
  $check = new Check($check_result->getCheck_Id());
  $timestamp = $check_result->getTimestamp();
  $check_results_json[] = array('what' => $status_array[$check_result->getStatus()]. ' : '. $check_result->prepareValue(), 'data' => $check_result->prepareValue(),'when' => strtotime($timestamp->__toString()),'id' => $check_result->getResultId(),'tags' => 'alerts');
    }
$encoded_content = json_encode($check_results_json);
print $encoded_content ;

