<?
include realpath(__DIR__ . '/inc/init.php');

fAuthorization::requireLoggedIn();

$breadcrumbs[] = array('name' => 'Subscription', 'url' => Subscription::makeURL('list'),'active'=>true);
$action = fRequest::getValid('action',
	array('list', 'add', 'edit', 'delete')
);

$subscription_id = fRequest::get('subscription_id');
$check_id = fRequest::get('check_id');
$manage_url = $_SERVER['SCRIPT_NAME'];
// --------------------------------- //
if ('delete' == $action) {
  try {
    $check = new Check($check_id);
    $subscription = new Subscription($subscription_id);
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      $subscription->delete();
      fMessaging::create('success', $manage_url, 
                         'The subscription for ' . $check->getName() . ' was successfully deleted');
      fURL::redirect($manage_url);	
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', $manage_url, 
                       'The subscription requested, ' . fHTML::encode($date) . ', could not be found');
    fURL::redirect($manage_url);
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/delete_subscription.php';	
	
// --------------------------------- // 
} elseif ('edit' == $action) {
  try {
    $subscription = new Subscription($subscription_id);
    $check = new Check($subscription->getCheck_Id());
    if (fRequest::isPost()) {
      $subscription->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $subscription->store();
      fMessaging::create('affected', fURL::get(), $check->getName());
      fMessaging::create('success', fURL::get(), 
                         'The subscription to check ' . $check->getName(). ' was successfully updated');
			//fURL::redirect($manage_url);	
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', $manage_url, 
                       'The subscription requested ' . fHTML::encode($check_id) . ' could not be found');
    fURL::redirect($manage_url);
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/add_edit_subscription.php';
	
// --------------------------------- //
} elseif ('add' == $action) {
  $subscription = new Subscription();

  //Load details of the check we are going to subscribe to
  $check = new Check($check_id);

  if (fRequest::isPost()) {	
    try {
      $subscription->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $subscription->store();
      fMessaging::create('affected',$manage_url , $check->getName());
      fMessaging::create('success', $manage_url, 
                         'The subscription to ' . $check->getName() . ' was successfully created');
      fURL::redirect($manage_url);
    } catch (fExpectedException $e) {
      fMessaging::create('error', fURL::get(), $e->getMessage());	
    }	
  } 

  include VIEW_PATH . '/add_edit_subscription.php';	
	
} else {
  $user = new User(fSession::get('user_id'));
  $subscriptions = $user->buildSubscriptions();
  //$subscriptions = Subscription::findActive();

  include VIEW_PATH . '/list_subscriptions.php';	
}
