<?php
include realpath(__DIR__ . '/inc/init.php');

fRequest::overrideAction();

$action = fRequest::getValid('action',
	array('list', 'add', 'edit','settings', 'delete')
);

if ($action != 'add') {
  fAuthorization::requireLoggedIn();
}

$user_id = fRequest::get('user_id');

if ('edit' == $action) {
  try {
    $user = new User($user_id);
    if (fRequest::isPost()) {
      $user->populate();
      $password = fCryptography::hashPassword($user->getPassword());
      $user->setPassword($password);
      fRequest::validateCSRFToken(fRequest::get('token'));
      $user->store();
			
      fMessaging::create('affected', User::makeUrl('list'), $user->getUsername());
      fMessaging::create('success',  User::makeUrl('list'), 
                         'The user ' . $user->getUsername(). ' was successfully updated');
      fURL::redirect( User::makeUrl('list'));	
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error',  User::makeUrl('list'),
                       'The user requested, ' . fHTML::encode($user_id) . ', could not be found');	
    fURL::redirect( User::makeUrl('list'));
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/add_edit_user.php';
	
// --------------------------------- //
} elseif ('add' == $action) {
  $user = new User();
  if (fRequest::isPost()) {	
    try {
      $user->populate();
      $password = fCryptography::hashPassword($user->getPassword());
      $user->setPassword($password);			
      fRequest::validateCSRFToken(fRequest::get('token'));
      $user->store();
			
      fMessaging::create('affected', User::makeURL('login'), $user->getUsername());
      fMessaging::create('success', User::makeURL('login'), 
                         'The user ' . $user->getUsername() . ' was successfully created');
      fURL::redirect(User::makeURL('login'));
    } catch (fExpectedException $e) {
      fMessaging::create('error', fURL::get(), $e->getMessage());	
    }	
  } 

  include VIEW_PATH . '/add_edit_user.php';	
	
} elseif ('settings' == $action) {
  $user = new User($user_id);
  if (fRequest::isPost()) {
    try {
      $user->populate();
    } catch (fExpectedException $e) {
      fMessaging::create('error',fURL::get(),$e-getMessage());
    }
  } 
  include VIEW_PATH . '/add_edit_user_settings.php';
 
} else {
  if (fSession::get('user_id') != 1) {
    fURL::redirect(User::makeURL('edit',fSession::get('user_id')));
  } else {
    $users = User::findAll();
    include VIEW_PATH . '/list_users.php';
  }
}
