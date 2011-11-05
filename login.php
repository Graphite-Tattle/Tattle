<?php
include 'inc/init.php';
$action = fRequest::get('action');

// --------------------------------- //
if ('log_out' == $action) {
  fAuthorization::destroyUserInfo();
  fSession::destroy();
  fMessaging::create('success', User::makeUrl('login'), 'You were successfully logged out');
  fURL::redirect(User::makeUrl('login'));	

// --------------------------------- // 
} else {
  if (!fAuthorization::checkLoggedIn()) {
    if (fRequest::isPost()) {	
      try {
        $user = new User(array('username' => fRequest::get('username')));
        $valid_pass = fCryptography::checkPasswordHash(
                      fRequest::get('password'),
                      $user->getPassword()
                      );
        if (!$valid_pass) {
          throw new fValidationException('The login or password entered is invalid');	
        }
        fAuthorization::setUserToken($user->getEmail());
        fAuthorization::setUserAuthLevel($user->getRole());
        fSession::set('user_id',$user->getUserId());
        fSession::set('user_name',$user->getUsername());
      
        fURL::redirect(fAuthorization::getRequestedURL(TRUE,'index.php'));
      } catch (fExpectedException $e) {
        fMessaging::create('error', fURL::get(), $e->getMessage());
      }	
    }
    include VIEW_PATH . '/log_in.php';
  } else {
        fURL::redirect('index.php');
  }
}
