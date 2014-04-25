<?php
include 'inc/init.php';

fAuthorization::requireLoggedIn();

fRequest::overrideAction();

$action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete'));

$breadcrumbs[] = array('name' => 'Groups', 'url' => Group::makeURL('list'),'active'=> true);

$group_id = fRequest::get('group_id', 'integer');

if ('delete' == $action) {
	if ($group_id == $GLOBALS['DEFAULT_GROUP_ID']) {
		fURL::redirect(Group::makeUrl('list'));
	} else {
	   $class_name = 'Group';
	  try {
	    $obj = new Group($group_id);
	    $delete_text = 'Are you sure you want to delete the group : <strong>' . $obj->getName() . '</strong>?';
	    if (fRequest::isPost()) {
	      fRequest::validateCSRFToken(fRequest::get('token'));
	      $obj->delete();
	      fMessaging::create('success', "/".Group::makeUrl('list'),
	                         'The group "' . $obj->getName() . '" was successfully deleted');
	      fURL::redirect(Group::makeUrl('list'));      
	    }
	  } catch (fNotFoundException $e) {
	    fMessaging::create('error', "/".Group::makeUrl('list'),
	                       'The group requested could not be found');
	    fURL::redirect(Group::makeUrl('list'));
	  } catch (fExpectedException $e) {
	    fMessaging::create('error', fURL::get(), $e->getMessage());
	  }
	  
	  include VIEW_PATH . '/delete.php';
	}
 
// --------------------------------- //
} elseif ('edit' == $action) {
	if ($group_id == $GLOBALS['DEFAULT_GROUP_ID']) {
		fURL::redirect(Group::makeUrl('list'));
	} else {
	  try {
	    $group = new Group($group_id);
	    if (fRequest::isPost()) {
	      $group->populate();
	      fRequest::validateCSRFToken(fRequest::get('token'));
	      $group->store();
	      fMessaging::create('success', "/".Group::makeURL("list"),
	                         'The Group ' . $group->getName(). ' was successfully updated');
	      fURL::redirect(Group::makeUrl('list'));
	    }
	  } catch (fNotFoundException $e) {
	    fMessaging::create('error', "/".Group::makeUrl('list'), 
	                       'The Group requested, ' . fHTML::encode($group_id) . ', could not be found');	
	    fURL::redirect(Group::makeUrl('list'));
	  } catch (fExpectedException $e) {
	    fMessaging::create('error', fURL::get(), $e->getMessage());	
	  }
	
	  include VIEW_PATH . '/add_edit_group.php';
  }
	
// --------------------------------- //
} elseif ('add' == $action) {
  	$group = new Group();
	  if (fRequest::isPost()) {	
	    try {
	      $group->populate();
	      fRequest::validateCSRFToken(fRequest::get('token'));
	      $group->store();
	      $group_url = fURL::redirect(Group::makeUrl('list'));
	      fMessaging::create('affected', "/".$group_url, $group->getName());
	      fMessaging::create('success', "/".$group_url, 
	                         'The Group ' . $group->getName() . ' was successfully created');
	      fURL::redirect($group_url);	
	      echo "";
	    } catch (fExpectedException $e) {
	      fMessaging::create('error', fURL::get(), $e->getMessage());	
	    }	
	  } 

 	include VIEW_PATH . '/add_edit_group.php';	
	
} else {
	$groups = Group::findAll();
	include VIEW_PATH . '/list_groups.php';
}
