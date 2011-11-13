<?php
include 'inc/init.php';

fAuthorization::requireLoggedIn();

fRequest::overrideAction();

$action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete', 'view'));

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
      fMessaging::create('success', fURL::getWithQueryString(), 
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
	
}
