<?php
include 'inc/init.php';

if (isset($_SERVER['REQUEST_URI'])) {
  $pos = strrpos($_SERVER['REQUEST_URI'], 'dashboard.php');
  if ($pos === false) {
    $action = 'view';
  }
}
   

if (!isset($filter_group_id)) {
	$filter_group_id = fRequest::get('filter_group_id','integer');
	if (empty($filter_group_id) || ($filter_group_id < 0)) {
		$filter_group_id = -1;
	}
}

fRequest::overrideAction();
$breadcrumbs[] = array('name' => 'Dashboards', 'url' => Dashboard::makeUrl('list',$filter_group_id),'active' => false);

if (!isset($dashboard_id)) {
  $dashboard_id = fRequest::get('dashboard_id','integer');
}

if (!isset($action)) {
  $action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete', 'view', 'export'));
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
  
  $ignored_values = array("action", "dashboard_id");
  $quick_times_desired = array(
  		"last 10 minutes" => "-10min",
  		"last hour" => "-1h",
  		"last 2 hours" => "-2h",
  		"last 8 hours" => "-8h",
  		"last 12 hours" => "-12h",
  		"last day" => "-1d",
  		"last 2 days" => "-2d",
  		"last 3 days" => "-3d",
  		"last week" => "-1w",
  		"last 2 weeks" => "-2w",
  		"last month" => "-1month",
  		"last 2 months" => "-2month",
  		"last 3 months" => "-3month"
    );
  
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

} elseif ('export' == $action) {
	
	$dashboard = new Dashboard($dashboard_id);
	
	$json_name = $dashboard->getName();
	$json_name = str_replace(" ", "_", $json_name) . ".json";
    
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");
	header("Content-Disposition: attachment; filename=\"" . $json_name . "\"");
	header("Content-Transfer-Encoding: binary");
	
	echo $dashboard->export_in_json(),"\n";
	
} else {
  if ($filter_group_id == -1) {
  	$dashboards = Dashboard::findAll();
  } else {
  	$dashboards = Dashboard::findAllByFilter($filter_group_id);
  }
  include VIEW_PATH . '/list_dashboards.php';
}
