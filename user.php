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
    	$session_user = new User(fSession::get('user_id'));
    	if (fSession::get('user_id') == $user->getUserId() || fAuthorization::checkAuthLevel('admin')) {
	    	$valid_pass = fCryptography::checkPasswordHash(
	    			fRequest::get('password'),
	    			$session_user->getPassword()
	    	);
	    	if ($valid_pass) {
		      $has_error = false;
		      $password = "";
		      if (fRequest::get('change_password','boolean')) {
		      	$new_password = fRequest::get('new_password');
		      	$confirm_password = fRequest::get('confirm_password');
		      	if ($new_password != $confirm_password) {
		      		fMessaging::create('error', fURL::get(),"The two passwords don't match, the changes was not applied.");
		      		$has_error = true;
		      	} else {
		      		if ($new_password == "") {
		      			fMessaging::create('error', fURL::get(),"An empty password is forbidden, the changes was not applied.");
		      			$has_error = true;
		      		} else {
			      		$password = fCryptography::hashPassword($new_password);
		      		}
		      	}
		      } else {
			      if ($GLOBALS['ALLOW_HTTP_AUTH'] && ($user->getUserId() != 1)) {
			        $password = 'basic_auth';
			      } else {
			        $password = $user->getPassword();
			      }
		      }
		      $user->populate();
		      $user->setPassword($password);
		      fRequest::validateCSRFToken(fRequest::get('token'));
		      if (!$has_error) {
			      $user->store();
						
			      fMessaging::create('affected', "/".User::makeUrl('list'), $user->getUsername());
			      fMessaging::create('success',  "/".User::makeUrl('list'), 
			                         'The user "' . $user->getUsername(). '" was successfully updated');
			      fURL::redirect( User::makeUrl('list'));	
		      }
	    	} else {
	    		fMessaging::create('error', fURL::get(),'The given password is wrong, the changes was not applied.');
	    	}
    	} else {
    		fMessaging::create('error', fURL::get(),"You don't have the right to modify this user");
    	}
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
      $has_error = false;
      if ($GLOBALS['ALLOW_HTTP_AUTH']) {
        $password = 'basic_auth';
      } else {
      	$new_password = fRequest::get('new_password');
      	$confirm_password = fRequest::get('confirm_password');
      	if ($new_password != $confirm_password) {
      		fMessaging::create('error', fURL::get(),"The two passwords don't match, the user was not created.");
      		$has_error = true;
      	} else {
      		if ($new_password == "") {
      			fMessaging::create('error', fURL::get(),"An empty password is forbidden, the user was not created.");
      			$has_error = true;
      		} else {
      			$password = fCryptography::hashPassword($new_password);
      		}
      	}
     }
      fRequest::validateCSRFToken(fRequest::get('token'));
      if (!$has_error) {
	      $user->setPassword($password);			
	      $user->store();
	      if ($user->getUserId() == 1){
	        $user->setRole('admin');
	        $user->store();
	      }			
	      fMessaging::create('affected', User::makeURL('login'), $user->getUsername());
	      fMessaging::create('success', User::makeURL('login'), 
	                         'The user ' . $user->getUsername() . ' was successfully created');
	      fURL::redirect(User::makeURL('login'));
      }
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
 $class_name = 'User';
 try {
	$obj = new User($user_id);
	$delete_text = 'Are you sure you want to delete user : <strong>'. $obj->getUsername() . '</strong>?';
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      $obj->delete();
      fMessaging::create('success', "/".User::makeUrl('list'),
                         'The user ' . $obj->getUsername() . ' was successfully deleted');
      fURL::redirect(User::makeUrl('list'));
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', "/".User::makeUrl('list'),
                       'The requested user could not be found');
    fURL::redirect(User::makeUrl('list'));
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
