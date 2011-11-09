<?php
include 'inc/init.php';

fAuthorization::requireLoggedIn();

fRequest::overrideAction();

$action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete', 'view'));

$dashboard_id = fRequest::get('dashboard_id', 'integer?');
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
      fMessaging::create('success', fURL::getWithQueryString(), 
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
      $graph->store();
			
      fMessaging::create('affected', $manage_url, $graph->getName());
      fMessaging::create('success', $manage_url, 
                         'The Graph ' . $graph->getName() . ' was successfully created');
      fURL::redirect(Dashboard::makeUrl('edit',$dashboard));	
    } catch (fExpectedException $e) {
      fMessaging::create('error', fURL::get(), $e->getMessage());	
    }	
  } 

  include VIEW_PATH . '/add_edit_graph.php';	
	
}
