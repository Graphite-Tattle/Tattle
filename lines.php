<?php
include 'inc/init.php';

fAuthorization::requireLoggedIn();

fRequest::overrideAction();

$action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete', 'view', 'clone', 'reorder'));

$line_id = fRequest::get('line_id', 'integer');
$graph_id = fRequest::get('graph_id', 'integer');

if ('delete' == $action) {
   $class_name = 'Line';
  try {
    $obj = new Line($line_id);
    $graph = new Graph($obj->getGraphId());
    $delete_text = 'Are you sure you want to delete the line : <strong>' . $obj->getAlias() . '</strong>?';
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      $obj->delete();
      fMessaging::create('success', Graph::makeUrl('edit',$graph),
                         'The line for ' . $graph->getName() . ' was successfully deleted');
      fURL::redirect(Graph::makeUrl('edit',$graph));      
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', Graph::makeUrl('edit',$graph),
                       'The line requested could not be found');
    fURL::redirect(Graph::makeUrl('edit',$graph));
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());
  }
  
  include VIEW_PATH . '/delete.php';
 
// --------------------------------- //
} elseif ('edit' == $action) {
  try {
    $line = new Line($line_id);
    $graph = new Graph($line->getGraphId()); 
    if (fRequest::isPost()) {
      $line->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $line->store();
			
      fMessaging::create('affected', fURL::get(), $graph->getName());
      fMessaging::create('success', '?'.fURL::getQueryString(),
                         'The Line ' . $line->getAlias(). ' was successfully updated');
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', Graph::makeUrl('edit',$graph), 
                       'The Line requested, ' . fHTML::encode($line_id) . ', could not be found');	
    fURL::redirect(Graph::makeUrl('edit',$graph));
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/add_edit_line.php';
	
// --------------------------------- //
} elseif ('add' == $action) {
  $line = new Line();
  $graph = new Graph($graph_id); 
  if (fRequest::isPost()) {	
    try {
      $line->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $line->store();
      $graph_url = Graph::makeUrl('edit',$graph);
      fMessaging::create('affected', $graph_url, $line->getAlias());
      fMessaging::create('success', $graph_url, 
                         'The Line ' . $line->getAlias() . ' was successfully created');
      fURL::redirect($graph_url);	
    } catch (fExpectedException $e) {
      fMessaging::create('error', fURL::get(), $e->getMessage());	
    }	
  } 

  include VIEW_PATH . '/add_edit_line.php';	
	
} elseif ('clone' == $action) {
	$line_to_clone = new Line($line_id);
	$graph_id = $line_to_clone->getGraphId();
	$graph = new Graph($graph_id);
	if (fRequest::isPost()) {
		try {
			fRequest::validateCSRFToken(fRequest::get('token'));
			
			Line::cloneLine($line_id);
			
			$graph_url = Graph::makeUrl('edit',$graph);
			fMessaging::create('affected', $graph_url, $line_to_clone->getAlias());
			fMessaging::create('success', $graph_url,
			'The Line ' . $line_to_clone->getAlias() . ' was successfully cloned');
			fURL::redirect($graph_url);
		} catch (fExpectedException $e) {
			fMessaging::create('error', fURL::get(), $e->getMessage());
		}
	}
	
	$dashboard = new Dashboard($graph->getDashboardId());
	$dashboard_id = $graph->getDashboardId();
	$lines = Line::findAll($graph_id);
	
	include VIEW_PATH . '/add_edit_graph.php';
	
} elseif ('reorder' == $action) {
	
	$drag_order = fRequest::get('drag_order');
	$error = false;
	
	if (empty($drag_order)) {
		// In this case, the user clicks on the arrow
		$move = fRequest::getValid('move', array('previous', 'next'));
		$line_to_move = new Line($line_id);
		$graph_id = $line_to_move->getGraphId();
		$lines_in_graph = Line::findAll($graph_id);
		
		$number_of_lines = $lines_in_graph->count(TRUE); 
		$skip_next = false;
		
		for ($i=0; $i < $number_of_lines; $i++) {
			if (!$skip_next) {
				$current_line = $lines_in_graph[$i];
				if ($current_line->getLineId() != $line_id) {
					// This isn't the concerned line
					$current_line->setWeight($i);
				} else {
					if ('previous' == $move) {
						if ($i > 0) {
							$current_line->setWeight($i-1);
							$previous_line = $lines_in_graph[$i-1];
							$previous_line->setWeight($i);
						}
					} else if ('next' == $move) {
						if ($i < $number_of_lines -1) {
							$current_line->setWeight($i+1);
							$next_line = $lines_in_graph[$i+1];
							$next_line->setWeight($i);
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
		$lines_in_graph = array();
		foreach ($array_of_weights as $new_weight) {
			$expl = explode(":",$new_weight);
			$current_line = new Line($expl[0]);
			if (empty($graph_id)) {
				$graph_id = $current_line->getGraphId();
			} else {
				// Check if all the lines are in the same graph
				if ($graph_id != $current_line->getGraphId()) {
					$error = true;
					break;
				}
			}
			$current_line->setWeight($expl[1]);
			$lines_in_graph[] = $current_line;
		}
	}
	
	
	if (!$error) {
		foreach ($lines_in_graph as $line_to_store) {
			$line_to_store -> store();
		}
		
		$graph = new Graph($graph_id);
		$url_redirect = Graph::makeURL('edit',$graph);
		fMessaging::create("success", "/graphs.php","The lines have been successfully reordered");
		
	} else {
		$url_redirect = Dashboard::makeURL('list');
		fMessaging::create("success", "/dashboard.php","An error occured and the lines couldn't be reordered");
	}


	fURL::redirect($url_redirect);
}
