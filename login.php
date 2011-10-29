<?php
include dirname(__FILE__) . '/inc/init.php';
$action = fRequest::get('action');

// --------------------------------- //
if ('log_out' == $action) {
  fAuthorization::destroyUserInfo();
  fSession::destroy();
  fMessaging::create('success', User::makeUrl('login'), 'You were successfully logged out');
  fURL::redirect(User::makeUrl('login'));	

// --------------------------------- // 
} elseif ('log_in' == $action) {
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
	
} elseif ('register' == $action) {
  $user = new User();
  if (fRequest::isPost()) {
    try {
      $user->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $user->store();

      fMessaging::create('affected',$user_url,$user->getUsername());
      fMessaging::create('success',$user_url,'Welcome ' . $user->getUsername());
      fURL::redirect($user_url);
    } catch (fExpectedException $e) {
      fMessaging::create('error',fURL::get(),$e->getMessage());
    }
  }
  
  include VIEW_PATH . '/add_edit_user.php'; 
} 
