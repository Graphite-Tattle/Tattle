<? 

plugin_listener('plugin_settings','email_plugin_settings');
plugin_listener('send_methods','email_plugin_send_methods');
plugin_listener('plugin_user_settings','email_plugin_user_settings');

// Poor Mans Module Settings
function email_plugin_settings(){
  return array( 
              'smtp_server' => array('friendly_name' => 'SMTP Server', 
                                     'default' => 'smtp.gmail.com',
                                     'type' => 'string'),
              'smtp_port' => array('friendly_name' => 'SMTP Port',
                                   'default' => 465, 
                                   'type' => 'integer'),
              'require_auth' => array('friendly_name' => 'Require Auth',
              'default' => 'false',
              'type' => 'string'),
              'require_ssl' => array('friendly_name' => 'Require SSL',
              'default' => 'false',
              'type' => 'string'),
              'smtp_user' => array('friendly_name' => 'SMTP Username',
                                   'default' => 'example@example.com',
                                   'type' => 'email'),
              'smtp_pass' => array('friendly_name' => 'SMTP Password',
                                   'default' => 'example',
                                   'type' => 'password'),
              'email_from' => array('friendly_name' => 'Alert Email From Address',
                                    'default' => 'tattle@example.com',
                                    'type' => 'email'),
              'email_from_display' => array('friendly_name' => 'Alert Email Display Name',
                                            'default' => 'Tattle Processor',
                                            'type' => 'string'),
              'email_subject' => array('friendly_name' => 'Alert Email Subject',
                                       'default' => 'Tattle : Alert from {check_name}', 
                                       'type' => 'string'),
              'email_end_alert_subject' => array('friendly_name' => 'Email For End Alert Subject',
                                       'default' => 'Tattle : END alert from {check_name}',
                                       'type' => 'string')
              );
}

function email_plugin_user_settings() {
  return array(
              'alt_email' => array('friendly_name' => 'Alternative Email Address',
                                   'default' => '',
                                   'type' => 'string')
             );
}
function email_plugin_send_methods(){
  return array('email_plugin_notify' => 'Email','email_plugin_alt_notify' => 'Alternative Email');
}


function email_plugin_notify($check,$check_result,$subscription) {
	return email_plugin_notify_master($check,$check_result,$subscription,false);
}
function email_plugin_alt_notify($check,$check_result,$subscription) {
	return email_plugin_notify_master($check,$check_result,$subscription,true);
}

//email plugin
function email_plugin_notify_master($check,$check_result,$subscription,$alt_email=false) {
  global $status_array;
  $user = new User($subscription->getUserId());
  $email = new fEmail();
  // This sets up fSMTP to connect to the gmail SMTP server
  // with a 5 second timeout. Gmail requires a secure connection.
  $smtp = new fSMTP(sys_var('smtp_server'), sys_var('smtp_port'), sys_var('require_ssl') === 'true'? TRUE: FALSE, 5);
  if (sys_var('require_auth') === 'true')  {
    $smtp->authenticate(sys_var('smtp_user'), sys_var('smtp_pass'));
  }
  if ($alt_email) {
    $email_address = usr_var('alt_email',$user->getUserId());
  } else {
    $email_address = $user->getEmail(); 
  }
  $email->addRecipient($email_address, $user->getUsername());
  // Set who the email is from
  $email->setFromEmail(sys_var('email_from'), sys_var('email_from_display'));
  
  $state = $status_array[$check_result->getStatus()];
  // Set the subject include UTF-8 curly quotes
  if($state == 'OK') {
  	$email->setSubject(str_replace('{check_name}', $check->prepareName(), sys_var('email_end_alert_subject')));
  } else {
	  $email->setSubject(str_replace('{check_name}', $check->prepareName(), sys_var('email_subject')));
  }

  // Set the body to include a string containing UTF-8
  $check_type = '';
  if($check->getType() == 'threshold') {
    $check_type = ' Threshold';
  } elseif($check->getType() == 'predictive') {
    $check_type = ' Standard Deviation';
  }

  $state_email_injection = $state . " Alert ";
  if($state == 'OK') {
    $state_email_injection = "Everything's back to normal ";
  }

  // Remind : ('0' => 'OK', '1'   => 'Error', '2' => 'Warning');
  $state_int = $check_result->getStatus();
  if ($state_int == 0) {
  	$color = "green";
  } else if ($state_int == 2) {
  	$color = "orange";
  } else {
  	$color = "red";
  }
  
  $html_body = "<p style='color:". $color .";'>" . $state_email_injection . "for {$check->prepareName()} </p>"
  			 . "<p>The check returned {$check_result->prepareValue()}</p>"
  			 . "<p>Warning" . $check_type  . " is : ". $check->getWarn() . "</p>"
  			 . "<p>Error" . $check_type . " is : ". $check->getError() . "</p>"
  			 . "<p>View Alert Details : <a href='" . $GLOBALS['TATTLE_DOMAIN'] . '/' . CheckResult::makeURL('list',$check_result) . "'>".$check->prepareName()."</a></p>";
  $email->setHTMLBody($html_body);

  $email->setBody("
  $state Alert for {$check->prepareName()}
The check returned {$check_result->prepareValue()}
Warning" . $check_type  . " is : ". $check->getWarn() . "
Error" . $check_type . " is : ". $check->getError() . "
           ");
  try {  
    $message_id = $email->send($smtp);
  } catch ( fConnectivityException $e) {
    fCore::debug($e,FALSE); 
    fCore::debug("email send failed",FALSE);
    $e->printMessage();
    $e->printTrace();
  }
}

function notify_multiple_users ($user_from,$recipients,$subject,$body) {
	$email = new fEmail();
	// This sets up fSMTP to connect to the gmail SMTP server
	// with a 5 second timeout. Gmail requires a secure connection.
	$smtp = new fSMTP(sys_var('smtp_server'), sys_var('smtp_port'), sys_var('require_ssl') === 'true'? TRUE: FALSE, 5);
	if (sys_var('require_auth') === 'true')  {
		$smtp->authenticate(sys_var('smtp_user'), sys_var('smtp_pass'));
	}
	// Add the recipients
	foreach ($recipients as $rec) {
		$email->addRecipient($rec['mail'], $rec['name']);
	}
	// Set who the email is from
	$email->setFromEmail($user_from->getEmail(), $user_from->getUsername());
	// Set the subject
	$email->setSubject($subject);
	// Set the body
	$email->setHTMLBody($body);
	$email->setBody($body);
	
	try {
		$message_id = $email->send($smtp);
	} catch ( fConnectivityException $e) {
		fCore::debug($e,FALSE);
		fCore::debug("email send failed",FALSE);
		$e->printMessage();
	    $e->printTrace();
	}
}
