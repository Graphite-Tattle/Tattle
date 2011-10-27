<?

// Poor Mans Module Settings
if (!function_exists('email_config')) {
function email_config(){
 return array('name' => 'Email', 'settings' => false);
}


//email plugin
function email_notify($check,$check_result,$subscription) {
 global $status_array;
 $user = new User($subscription->getUserId());
 echo 'email plugin!';
 $email = new fEmail();
 // This sets up fSMTP to connect to the gmail SMTP server
 // with a 5 second timeout. Gmail requires a secure connection.
 $smtp = new fSMTP('smtp.gmail.com', 465, TRUE, 5);
 $smtp->authenticate('tattler@theinternet.com', 'P4ssw0rd!');
 $email->addRecipient($user->getEmail(), $user->getUsername());
 // Set who the email is from
 $email->setFromEmail('tattler@theinternet.com','Tattle');
 // Set the subject include UTF-8 curly quotes
 $email->setSubject('Graphite-Tattle : Alert for ' . $check->prepareName());
 // Set the body to include a string containing UTF-8
 $state = $status_array[$check_result->getStatus()];
 $email->setHTMLBody("<p>$state Alert for {$check->prepareName()} </p><p>The check returned {$check_result->prepareValue()}</p><p>Warning Threshold is : ". $check->getWarn() . "</p><p>Error Threshold is : ". $check->getError() . '</p><p>View Alert Details : <a href="http://192.168.1.6/graphite-tattle/'. CheckResult::makeURL('list',$check_result) . '">'.$check->prepareName()."</a></p>");
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
}
