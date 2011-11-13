<?php
include 'inc/init.php';

if (isset($_SERVER['REQUEST_URI'])) {
  $pos = strrpos($_SERVER['REQUEST_URI'], 'dashboard.php');
  if ($pos === false) {
    $action = 'view';
  }
}
   


fRequest::overrideAction();
$breadcrumbs[] = array('name' => 'Dashboards', 'url' => Dashboard::makeUrl('list'),'active' => false);

if (!isset($dashboard_id)) {
  $dashboard_id = fRequest::get('dashboard_id','integer');
}

if (!isset($action)) {
  $action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete', 'view'));
}

// Don't require login for tv monitors that just need to view the dashboards. Will add a public/private feature for dashboards as phase two
if ($action != 'view') {
  fAuthorization::requireLoggedIn();
}

if (!isset($full_screen)) {
  $full_screen = fRequest::get('full_screen','boolean',false);
}


$sort = fRequest::getValid('sort',array('name'),'name');
$sortby = fRequest::getValid('sortby',array('asc','desc'),'asc');
// --------------------------------- //
if ('edit' == $action) {
  try {
    $dashboard = new Dashboard($dashboard_id);
    $graphs = Graph::findAll($dashboard_id);

    if (fRequest::isPost()) {
      $dashboard->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $dashboard->store();
			
      fMessaging::create('affected', fURL::get(), $dashboard->getName());
      fMessaging::create('success', fURL::get(), 
                         'The Dashboard ' . $dashboard->getName(). ' was successfully updated');
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
  $url_parts = explode('/',$_SERVER['REQUEST_URI']);
    $clean_url = false;
    foreach($url_parts as $url_part) {
      if ($url_part == 'dash') {
        $clean_url = true;
        break;
      } else {
        array_shift($url_parts);
      }
    }
    if ($clean_url && $url_parts[0] == 'dash' && is_numeric($url_parts[1])) {
      $dashboard_id = $url_parts[1];
    } 
      
    $full_screen = true;
    $dashboard = new Dashboard($dashboard_id);

    if ($clean_url && isset($url_parts[2]) && is_numeric($url_parts[2])) {
       $dashboard->setGraphHeight($url_parts[2]);
    }     
    if ($clean_url && isset($url_parts[3]) && is_numeric($url_parts[3])) {
       $dashboard->setGraphWidth($url_parts[3]);
    } 

  $graphs = Graph::findAll($dashboard_id);
  include VIEW_PATH . '/view_dashboard.php';	
	
} elseif ('delete' == $action) {
  $class_name = 'Dashboard';
  try {
    $obj = new Dashboard($dashboard_id);
    $delete_text = 'Are you sure you want to delete dashboard : <strong>'. $obj->getName() . '</strong>?';
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      $obj->delete();
      $graphs = Graph::findAll($dashboard_id);
      // Do Dashboard Subelement Cleanup
      foreach($graphs as $graph) {
        $lines = Line::findAll($graph->getGraphId());
        foreach($lines as $line) {
          $line->delete();
        }
        $graph->delete(); 
      }
      fMessaging::create('success', Dashboard::makeUrl('list'),
                         'The Dashboard ' . $obj->getName() . ' was successfully deleted');
      fURL::redirect(Dashboard::makeUrl('list'));
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', Dashboard::makeUrl('list'),
                       'The Dashboard requested could not be found');
    fURL::redirect(Dashboard::makeUrl('list'));
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());
  }

  include VIEW_PATH . '/delete.php';

} else {
  $dashboards = Dashboard::findAll();
  include VIEW_PATH . '/list_dashboards.php';
}
