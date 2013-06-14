<?php
include 'inc/init.php';

fAuthorization::requireLoggedIn();

$action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete','ackAll','notifyAll'));

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
                       'The check requested, ' . fHTML::encode($check_id) . ', could not be found');
    fURL::redirect($manage_url);
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/ackAll_results.php';	

} else if($action == 'notifyAll') {
	try {
		$check = new Check($check_id);
		$subject_mail = fRequest::get('subject_mail');
		$content_mail = fRequest::get('content_mail');
		if (fRequest::isPost()) {
			if (empty($subject_mail) || empty($content_mail)) {
				fMessaging::create('error', fURL::get(),"You have to fill the subject and the content to send this mail");
			} else {
				fRequest::validateCSRFToken(fRequest::get('token'));
				$recipients = array();
				$alt_ids = array();
				$subscription_alt = Subscription::findAll($check_id,NULL,NULL,NULL,TRUE);
				foreach ($subscription_alt as $alt) {
					$user = new User($alt->getUserId());
					$recipients[] = array("mail" => usr_var('alt_email',$user->getUserId()), "name" => $user->getUsername());
					$alt_ids[] = $alt->getUserId();
				}
				$subscriptions = $db->query("SELECT DISTINCT user_id,check_id FROM subscriptions WHERE check_id=".$check_id.";");
				foreach ($subscriptions as $sub) {
					$user_id = $sub['user_id'];
					if (!in_array($user_id,$alt_ids)) {
						$user = new User($sub['user_id']);
						$recipients[] = array("mail" => $user->getEmail(), "name" => $user->getUsername());
					}
				}
				echo "<pre>";
				print_r($recipients);
				echo "</pre>";
				if (!empty($recipients)) {
					// Send the mail to everybody
					notify_multiple_users (fSession::get('user_id'),$recipients,$subject_mail,$content_mail);
					fMessaging::create('success', fURL::get(), 'The mail "'.$subject_mail.'" was successfully sent to all the users who subscribe to "' . $check->getName() . '"');
				} else {
					fMessaging::create('error', fURL::get(),"Nobody subscribe to this check");
				}
			}
		}
	} catch (fNotFoundException $e) {
		fMessaging::create('error', $manage_url,
		'The check requested, ' . fHTML::encode($check_id) . ', could not be found');
		fURL::redirect($manage_url);
	} catch (fExpectedException $e) {
		fMessaging::create('error', fURL::get(), $e->getMessage());
	}
	
	$page_num = fRequest::get('page', 'int', 1);
	$url_redirect = CheckResult::makeURL('list',$check)."&page=".$page_num;
	fURL::redirect($url_redirect);
	
} else {
  $page_num = fRequest::get('page', 'int', 1);
  $check_results = CheckResult::findAll($check_id, false, $GLOBALS['PAGE_SIZE'], $page_num);

  include VIEW_PATH . '/list_check_results.php';	
}
