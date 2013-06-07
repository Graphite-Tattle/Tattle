<?php
include 'inc/init.php';

fAuthorization::requireLoggedIn();

fRequest::overrideAction();

$action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete', 'view', 'clone', 'clone_into', 'reorder'));

$dashboard_id = fRequest::get('dashboard_id', 'integer?');
$dashboard_dest_id = fRequest::get('dashboard_dest_id', 'integer?');
$graph_id = fRequest::get('graph_id', 'integer?');
$manage_url = $_SERVER['SCRIPT_NAME'];

// --------------------------------- //
if ('edit' == $action) {
  try {
    $graph = new Graph($graph_id);
    $dashboard = new Dashboard($graph->getDashboardId());
    $lines = Line::findAll($graph_id);                
    if (fRequest::isPost()) {
      $graph->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $graph->store();
			
      fMessaging::create('affected', fURL::get(), $graph->getName());
      fMessaging::create('success', '?'.fURL::getQueryString(),
                         'The Graph ' . $graph->getName(). ' was successfully updated');
      fURL::redirect(Dashboard::makeUrl('edit',$dashboard));
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', $manage_url, 
                       'The Graph requested, ' . fHTML::encode($graph_id) . ', could not be found');	
    fURL::redirect($manage_url);
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/add_edit_graph.php';
	
// --------------------------------- //
} elseif ('add' == $action) {
  $graph = new Graph();
  $dashboard = new Dashboard($dashboard_id);
  if (fRequest::isPost()) {	
    try {
      $graph->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $graphs_in_dashboard = Graph::findAll($dashboard_id);
      $graph->setWeight($graphs_in_dashboard->count(TRUE));
      $graph->store();
			
      fMessaging::create('affected', $manage_url, $graph->getName());
      fMessaging::create('success', $manage_url, 
                         'The Graph ' . $graph->getName() . ' was successfully created');
      fURL::redirect(Graph::makeUrl('edit',$graph));	
    } catch (fExpectedException $e) {
      fMessaging::create('error', fURL::get(), $e->getMessage());	
    }	
  } 

  include VIEW_PATH . '/add_edit_graph.php';	
	
} elseif ('delete' == $action) {
  $class_name = 'Graph';
  try {
    $obj = new Graph($graph_id);
    $dashboard = new Dashboard($obj->getDashboardId());
    $delete_text = 'Are you sure you want to delete the graph : <strong>' . $obj->getName() . '</strong>?';
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      $obj->delete();
      $lines = Line::findAll($graph_id);
      foreach($lines as $line) {
        $line->delete();
      }
      fMessaging::create('success', Dashboard::makeUrl('edit',$dashboard),
                         'The graph for ' . $dashboard->getName() . ' was successfully deleted');
      fURL::redirect(Dashboard::makeUrl('edit',$dashboard));
    }                    
  } catch (fNotFoundException $e) {
    fMessaging::create('error', Dashboard::makeUrl('edit',$dashboard),
                       'The line requested could not be found');
    fURL::redirect(Dashboard::makeUrl('edit',$dashboard));
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());
  }

  include VIEW_PATH . '/delete.php';
  

}  elseif ('clone' == $action) {
	if (fRequest::isPost()) {
		$graph_to_clone = new Graph($graph_id);
		$dashboard_id = $graph_to_clone->getDashboardId();
		$dashboard = new Dashboard($dashboard_id);
		try {
			fRequest::validateCSRFToken(fRequest::get('token'));
			
			Graph::cloneGraph($graph_id);
			
			fMessaging::create('affected',fURL::get() , $graph_to_clone->getName());
			fMessaging::create('success', fURL::get(),
			'The Graph ' . $graph_to_clone->getName() . ' was successfully cloned');
			fURL::redirect(Dashboard::makeURL('edit',$dashboard));
		} catch (fExpectedException $e) {
			fMessaging::create('error', fURL::get(), $e->getMessage());
		}
	}
	
	include VIEW_PATH . '/add_edit_dashboard.php';
	
}  elseif ('clone_into' == $action) {
	
	if (fRequest::isPost()) {
		$graph_to_clone = new Graph($graph_id);
		$dashboard = new Dashboard($dashboard_dest_id);
		try {
			fRequest::validateCSRFToken(fRequest::get('token'));
				
			Graph::cloneGraph($graph_id,$dashboard_dest_id);
			
			$url_redirect = Dashboard::makeURL('list');
				
			fMessaging::create('affected',$url_redirect , $graph_to_clone->getName());
			fMessaging::create('success', "/" . $url_redirect,
			'The Graph "' . $graph_to_clone->getName() . '" was successfully cloned into the Dashboard "' . $dashboard->getName() . '"');
			fURL::redirect($url_redirect);
		} catch (fExpectedException $e) {
			fMessaging::create('error', fURL::get(), $e->getMessage());
		}
	}

	include VIEW_PATH . '/list_dashboards.php';
} elseif ('reorder' == $action) {
	
	$drag_order = fRequest::get('drag_order');
	$error = false;
	
	if (empty($drag_order)) {
		// In this case, the user clicks on the arrow
		$move = fRequest::getValid('move', array('previous', 'next'));
		$graph_to_move = new Graph($graph_id);
		$dashboard_id = $graph_to_move->getDashboardId();
		$graphs_in_dashboard = Graph::findAll($dashboard_id);
		
		$number_of_graphs = $graphs_in_dashboard->count(TRUE); 
		$skip_next = false;
		
		for ($i=0; $i < $number_of_graphs; $i++) {
			if (!$skip_next) {
				$current_graph = $graphs_in_dashboard[$i];
				if ($current_graph->getGraphId() != $graph_id) {
					// This isn't the concerned graph
					$current_graph->setWeight($i);
				} else {
					if ('previous' == $move) {
						if ($i > 0) {
							$current_graph->setWeight($i-1);
							$previous_graph = $graphs_in_dashboard[$i-1];
							$previous_graph->setWeight($i);
						}
					} else if ('next' == $move) {
						if ($i < $number_of_graphs -1) {
							$current_graph->setWeight($i+1);
							$next_graph = $graphs_in_dashboard[$i+1];
							$next_graph->setWeight($i);
							$skip_next = true;
						}
					}
				}
			} else {
				$skip_next = false;
			}
		}
		
	} else {
		// In this case the user has used the drag and drop functionnality
		$array_of_weights = explode(",", $drag_order);
		$graphs_in_dashboard = array();
		foreach ($array_of_weights as $new_weight) {
			$expl = explode(":",$new_weight);
			$current_graph = new Graph($expl[0]);
			if (!isset($dashboard_id)) {
				$dashboard_id = $current_graph->getDashboardId();
			} else {
				// Check if all the graphs are in the same dashboard
				if ($dashboard_id != $current_graph->getDashboardId()) {
					$error = true;
					break;
				}
			}
			$current_graph->setWeight($expl[1]);
			$graphs_in_dashboard[] = $current_graph;
		}
	}
	
	
	if (!$error) {
		foreach ($graphs_in_dashboard as $graph_to_store) {
			$graph_to_store -> store();
		}
		
		$dashboard = new Dashboard($dashboard_id);
		$url_redirect = Dashboard::makeURL('edit',$dashboard);
		fMessaging::create("success", "/dashboard.php","The graphs have been successfully reordered");
		
	} else {
		$url_redirect = Dashboard::makeURL('list');
		fMessaging::create("success", "/dashboard.php","An error occured and the graphs couldn't be reordered");
	}


	fURL::redirect($url_redirect);

}
