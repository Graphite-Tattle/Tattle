<?php
include 'inc/init.php';

fAuthorization::requireLoggedIn();

$action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete','ackAll'));

$result_id = fRequest::get('result_id','integer');
$check_id = fRequest::get('check_id', 'integer');
$manage_url = $_SERVER['SCRIPT_NAME'];

/*------------------------------------*/
if ($action == 'ackAll') {
  try {
    $check = new Check($check_id);
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      //$check->acknowledgeCheck();
      Check::acknowledgeCheck($check,NULL,true);
      fMessaging::create('success', $manage_url, 
                         'The alerts for ' . $check->getName() . ' were successfully acknowledged');
      //fURL::redirect($manage_url);	
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', $manage_url, 
                       'The check requested, ' . fHTML::encode($date) . ', could not be found');
    fURL::redirect($manage_url);
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/ackAll_results.php';	

} else {
  $page_num = fRequest::get('page', 'int', 1);
  $check_results = CheckResult::findAll($check_id, false, $GLOBALS['PAGE_SIZE'], $page_num);

  include VIEW_PATH . '/list_check_results.php';	
}
