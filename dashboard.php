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
  $action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete', 'view', 'export', 'mass_export', 'import'));
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
  
  $other_dashboards_in_group = Dashboard::findAllByFilter($dashboard->getGroupId());
  
  $ignored_values = array("action", "dashboard_id","display_options_links");
  
  $quick_times_desired = array(
  		"Last 10 minutes" => "-10min",
  		"Last hour" => "-1h",
  		"Last 2 hours" => "-2h",
  		"Last 8 hours" => "-8h",
  		"Last 12 hours" => "-12h",
  		"Last day" => "-1d",
  		"Yesterday" => array("from" => "midnight-1day"),
  		"Last 2 days" => "-2d",
  		"Last 3 days" => "-3d",
  		"Last week" => "-1w",
  		"Last full week" => array("from" => "midnight-7day"),
  		"Last 2 weeks" => "-2w",
  		"Last month" => "-1month",
  		"Last 2 months" => "-2month",
  		"Last 3 months" => "-3month"
    );
  
  $quick_bgcolor_desired = array(
  		"Red" => "red",
  		"Green" => "green",
  		"Blue" => "blue",
  		"Yellow" => "yellow",
  		"Black" => "black",
  		"White" => "white"
  );
  
  $quick_sizes_desired = array(
  		"100 x 50" => array("width" => "100", "height" => "50"),
  		"300 x 150" => array("width" => "300", "height" => "150"),
  		"600 x 300" => array("width" => "600", "height" => "300"),
  		"900 x 450" => array("width" => "900", "height" => "450"),
  );
  
  $display_options_links = fRequest::get('display_options_links','integer');
  if (empty($display_options_links) || $display_options_links > 3 || $display_options_links < 0) {
  	// The only possibles values are 0 to 3
  	// 0 -> Nothing displayed
  	// 1 -> Only options are displayed
  	// 2 -> Only links are displayed
  	// 3 -> Both are displayed
  	$display_options_links = 0;
  }
  
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
} elseif ('mass_export' == $action) {
	
	if (in_array('id_mass_export',array_keys($_POST))) {
		$dashboards_to_export = $_POST['id_mass_export'];
		$number_of_dashboards = count($dashboards_to_export);
		if ( $number_of_dashboards == 1) {
			$dashboard = new Dashboard($dashboards_to_export[0]);
			$json_to_send = $dashboard->export_in_json();
			$json_name = $dashboard->getName();
			$json_name = str_replace(" ", "_", $json_name) . ".json";
		} else {
			$json_to_send = "[";
			foreach ($dashboards_to_export as $dashboard_id) {
				$dashboard = new Dashboard($dashboard_id);
				$json_to_send .= $dashboard->export_in_json();
				$json_to_send .= ",";
			}
			$json_to_send[strlen($json_to_send)-1] = "]";
			$json_name = "Array_of_".$number_of_dashboards."_dashboards.json";
		}
		
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment; filename=\"" . $json_name . "\"");
		header("Content-Transfer-Encoding: binary");
		
		echo $json_to_send,"\n";
	}
	
} elseif ('import' == $action) {	
	
	if ((isset($_FILES['uploadedfile']['tmp_name'])&&($_FILES['uploadedfile']['error'] == UPLOAD_ERR_OK))) {
		$file = $_FILES['uploadedfile']['tmp_name'];
		$content = fread(fopen($file, "r"), filesize($file));
		$filter_group_id = $_POST['filter_group_id'];
		if ($filter_group_id < 0) {
			$result_ok = Dashboard::import_from_json_to_group($content);
		} else {
			$result_ok = Dashboard::import_from_json_to_group($content,$filter_group_id);
		}
		if ($result_ok) {
			fMessaging::create('success', "/" . Dashboard::makeUrl('list'),'The Dashboard was successfully imported');
		}
	}
	
    fURL::redirect(Dashboard::makeUrl('list',$filter_group_id));
	
	
} else {
  if ($filter_group_id == -1) {
  	$dashboards = Dashboard::findAll();
  } else {
  	$dashboards = Dashboard::findAllByFilter($filter_group_id);
  }
  include VIEW_PATH . '/list_dashboards.php';
}
