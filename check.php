<?php
include dirname(__FILE__) . '/inc/init.php';

fAuthorization::requireLoggedIn();

fRequest::overrideAction();
$breadcrumbs[] = array('name' => 'Checks', 'url' => Check::makeUrl('list'), 'active'=> false);

$action = fRequest::getValid('action',
	array('list', 'add', 'edit', 'delete')
);

$sort = fCRUD::getSortColumn(array('name','target','warn','error','status','timestamp','count'));
$sort_dir  = fCRUD::getSortDirection('asc');

$check_id = fRequest::get('check_id', 'integer');

$check_list_url = Check::makeURL('list');
// --------------------------------- //
if ('delete' == $action) {
  try {
    $check = new Check($check_id);
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      $check->delete();
      fMessaging::create('success', $check_list_url, 
                         'The check ' . $check->getName() . ' was successfully deleted');
      fURL::redirect($check_list_url);	
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', $check_list_url, 
                       'The check requested, ' . fHTML::encode($date) . ', could not be found');
    fURL::redirect($check_list_url);
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }
	
  include VIEW_PATH . '/delete.php';	

// --------------------------------- // 
} elseif ('edit' == $action) {
  try {
    $check = new Check($check_id);
    if (fRequest::isPost()) {
      $check->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $check->store();
			
      fMessaging::create('affected', fURL::get(), $check->getName());
      fMessaging::create('success', fURL::get(), 
                         'The check ' . $check->getName(). ' was successfully updated');
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', $check_list_url, 
                       'The check requested, ' . fHTML::encode($check_id) . ', could not be found');	
    fURL::redirect($check_list_url);
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/add_edit.php';
	
// --------------------------------- //
} elseif ('add' == $action) {
  $check = new Check();
  if (fRequest::isPost()) {	
    try {
      $check->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $check->store();
			
      fMessaging::create('affected', $check_list_url, $check->getName());
      fMessaging::create('success', $check_list_url, 
                         'The check ' . $check->getName() . ' was successfully created');
      fURL::redirect($check_list_url);
    } catch (fExpectedException $e) {
      fMessaging::create('error', fURL::get(), $e->getMessage());	
    }	
  } 

  include VIEW_PATH . '/add_edit.php';	
	
} else {
  //$checks = Check::findUsersActive($sort,$sort_dir);
  $checks = Check::findAll($sort,$sort_dir);
  include VIEW_PATH .'/list_checks.php';
}
