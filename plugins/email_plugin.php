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

//email plugin
function email_plugin_notify($check,$check_result,$subscription,$alt_email=false) {
  global $status_array;
  $user = new User($subscription->getUserId());
  $email = new fEmail();
  // This sets up fSMTP to connect to the gmail SMTP server
  // with a 5 second timeout. Gmail requires a secure connection.
  $smtp = new fSMTP(sys_var('smtp_server'), sys_var('smtp_port'), TRUE, 5);
  $smtp->authenticate(sys_var('smtp_user'), sys_var('smtp_pass'));
  if ($alt_email) {
    $email_address = usr_var('alt_email',$user->getUserId());
  } else {
    $email_address = $user->getEmail(); 
  }
  $email->addRecipient($email_address, $user->getUsername());
  // Set who the email is from
  $email->setFromEmail(sys_var('email_from'), sys_var('email_from_display'));
  // Set the subject include UTF-8 curly quotes
  $email->setSubject(str_replace('{check_name}', $check->prepareName(), sys_var('email_subject')));
  // Set the body to include a string containing UTF-8
  $state = $status_array[$check_result->getStatus()];
  $email->setHTMLBody("<p>$state Alert for {$check->prepareName()} </p><p>The check returned {$check_result->prepareValue()}</p><p>Warning Threshold is : ". $check->getWarn() . "</p><p>Error Threshold is : ". $check->getError() . '</p><p>View Alert Details : <a href="' . fURL::getDomain() . '/' . CheckResult::makeURL('list',$check_result) . '">'.$check->prepareName()."</a></p>");
  $email->setBody("
  $state Alert for {$check->prepareName()}
The check returned {$check_result->prepareValue()}
Warning Threshold is : ". $check->getWarn() . "
Error Threshold is : ". $check->getError() . "
           ");
  try {  
    $message_id = $email->send($smtp);
  } catch ( fConnectivityException $e) { 
    fCore::debug("email send failed",FALSE);
  }
}
