<?php
include realpath(__DIR__ . '/inc/init.php');

fAuthorization::requireLoggedIn();

fRequest::overrideAction();
$breadcrumbs[] = array('name' => 'Dashoards', 'url' => Dashboard::makeUrl('list'),'active' => false);
$action = fRequest::getValid('action',
	array('list', 'add', 'edit', 'delete', 'view')
);

$full_screen = fRequest::get('full_screen');
$dashboard_id = fRequest::get('dashboard_id');
// --------------------------------- //
if ('edit' == $action) {
  try {
    $dashboard = new Dashboard($dashboard_id);
    $graphs = $dashboard->buildGraphs();
    //$graphs = Graph::findAll($dashboard_id);
    if (fRequest::isPost()) {
      $dashboard->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $dashboard->store();
			
      fMessaging::create('affected', fURL::get(), $dashboard->getName());
      fMessaging::create('success', fURL::get(), 
                         'The Dashboard ' . $dashboard->getName(). ' was successfully updated');
			//fURL::redirect($manage_url);	
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', Dashboard::makeUrl('list'), 
                       'The Dashboard requested ' . fHTML::encode($dashboard_id) . 'could not be found');
    fURL::redirect(Dashboard::makeUrl('list'));
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/add_edit_dashboard.php';
	
// --------------------------------- //
} elseif ('add' == $action) {
  $dashboard = new Dashboard();
  if (fRequest::isPost()) {	
    try {
      $dashboard->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $dashboard->store();
      fMessaging::create('affected',fURL::get() , $dashboard->getName());
      fMessaging::create('success', fURL::get(), 
                         'The Dashboard ' . $dashboard->getName() . ' was successfully created');
      fURL::redirect(Dashboard::makeURL('edit',$dashboard));	
    } catch (fExpectedException $e) {
      fMessaging::create('error', fURL::get(), $e->getMessage());	
    }		
  } 

  include VIEW_PATH . '/add_edit_dashboard.php';	
	
} elseif ('view' == $action) {
  $dashboard = new Dashboard($dashboard_id);
  $graphs = Graph::findAll($dashboard_id);
  include VIEW_PATH . '/view_dashboard.php';	
	
} else {
  $sortby = fRequest::get('sortby');
  $sort = fRequest::get('sort');
  $dashboards = Dashboard::findAll();
  include VIEW_PATH . '/list_dashboards.php';
}
