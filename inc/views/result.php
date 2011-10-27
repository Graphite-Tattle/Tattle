<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/graphite-tattle/inc/init.php');

fAuthorization::requireLoggedIn();


$action = fRequest::getValid(
	'action',
	array('list', 'add', 'edit', 'delete','ackAll')
);


$result_id = fRequest::get('result_id','integer');
$check_id = fRequest::get('check_id');
$manage_url = '/graphite-tattle/';

/*------------------------------------*/

fCore:expose($action);

if ($action == 'ackAll') {
	
	try {
		
		$check = new Check($check_id);
		
		if (fRequest::isPost()) {
		
			fRequest::validateCSRFToken(fRequest::get('token'));
			
			$check->acknowledgeCheck();
			
			fMessaging::create('success', $manage_url, 'The alerts for the check ' . $check->getTitle() . ' were successfully acknowledged');
			fURL::redirect($manage_url);	
		}
	
	} catch (fNotFoundException $e) {
		fMessaging::create('error', $manage_url, 'The check requested, ' . fHTML::encode($date) . ', could not be found');
		fURL::redirect($manage_url);
	
	} catch (fExpectedException $e) {
		fMessaging::create('error', fURL::get(), $e->getMessage());	
	}

	include DOC_ROOT . '/views/ackAll_results.php';	


} else {
  $check_results = Check_Result::findActive($check_id);

  include DOC_ROOT . '/views/list_check_results.php';	
}
	
