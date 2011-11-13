<?php
include 'inc/init.php';

fRequest::overrideAction();

$action = fRequest::getValid('action', array('list', 'add', 'edit','settings', 'delete'));

if ($action != 'add') {
  fAuthorization::requireLoggedIn();
}

$user_id = fRequest::get('user_id','integer');

if ('edit' == $action) {
  try {
    $user = new User($user_id);
    if (fRequest::isPost()) {
      $user->populate();
      if ($GLOBALS['ALLOW_HTTP_AUTH'] && ($user->getUserId() != 1)) {
        $password = 'basic_auth';
      } else {
        $password = fCryptography::hashPassword($user->getPassword());
        $user->setPassword($password);
      }
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
      if ($GLOBALS['ALLOW_HTTP_AUTH']) {
        $password = 'basic_auth';
      } else {
        $password = fCryptography::hashPassword($user->getPassword());
     }
      $user->setPassword($password);			
      fRequest::validateCSRFToken(fRequest::get('token'));
      $user->store();
      if ($user->getUserId() == 1){
        $user->setRole('admin');
        $user->store();
      }			
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
 
} elseif ('delete' == $action) {
 try {
    $user = new User($user_id);
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      $user->delete();
      fMessaging::create('success', User::makeUrl('edit',$user),
                         'The user ' . $user->getName() . ' was successfully deleted');
      fURL::redirect(User::makeUrl('edit',$user));
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', User::makeUrl('edit',$user),
                       'The line requested could not be found');
    fURL::redirect(User::makeUrl('edit',$user));
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());
  }

  include VIEW_PATH . '/delete.php';
 
} else {
  if (!fAuthorization::checkAuthLevel('admin')) {
    fURL::redirect(User::makeURL('edit',fSession::get('user_id')));
  } else {
    $users = User::findAll();
    include VIEW_PATH . '/list_users.php';
  }
}
