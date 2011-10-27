<?
$page_title = ($action == 'add' ? 'Add a Graph' : 'Edit Graph');
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => 'Dashboard', 'url' => Dashboard::makeURL('list'),'active' => false);
$breadcrumbs[] = array('name' => $dashboard->encodeName(), 'url' => Dashboard::makeUrl('edit',$graph),'active' => false);
$breadcrumbs[] = array('name' => $page_title, 'url' => fURL::getWithQueryString(),'active' => true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
if (isset($dashboard_id)) {
  $query_string = "&dashboard_id=$dashboard_id";
} elseif (isset($graph_id)) {
  $query_string = "&graph_id=$graph_id";  
} else {
  $query_string = '';
}
?>
  <div class="row">
    <div class="span4">
      <form action="<?php echo fURL::get() ?>?action=<? echo $action.$query_string; ?>" method="post" class="form-stacked">
        <div class="main" id="main">
          <fieldset>
                <div class="clearfix">
	      <label for="graph-name">Name<em>*</em></label>
              <div class="input">
	        <input id="graph-name" class="span3" type="text" size="30" name="name" value="<?php echo $graph->encodeName() ?>" />
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="graph-description">Description<em>*</em></label>
              <div class="input">             
                 <textarea class="span3" id="graph-description" name="description" rows="3"><?php echo $graph->encodeDescription() ?></textarea>
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="graph-vtitle">Y-Axis Title<em>*</em></label>
              <div class="input">             
                  <input id="graph-vtitle" class="span3" type="text" size="30" name="vtitle" value="<?php echo $graph->encodeVtitle() ?>" />
              </div>
            </div><!-- /clearfix -->                  
            <div class="clearfix">
              <label for="graph-area">Area Mode<em>*</em></label>
              <div class="input">
                <select name="area" class="span3">
                <?
                 $areaModes = array('none' => 'None', 'first' => 'First', 'stacked' => 'Stacked', 'all' => 'All');
                 foreach ($areaModes as $value => $text) {
                   fHTML::printOption($text, $value, $graph->getArea());
                 }
                ?>
                </select>            
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="graph-weight">Weight<em>*</em></label>
              <div class="input">
                <select name="weight" class="span3">
                <?
                 $weights = array(0,1,2,3,4,5,6,7,8,9,10);
                 foreach ($weights as $value) {
                   fHTML::printOption($value, $value, $graph->getWeight());
                 }
                ?>
                </select>            
              </div>
            </div><!-- /clearfix -->      
	    <div class="actions">
	      <input class="btn primary" type="submit" value="Save" />
              <input class="btn" type="submit" name="action::delete" value="Delete" />
              <a href="<?php echo Dashboard::makeUrl('view',$dashboard);?>" class="btn">View</a>
              <div class="required"><em>*</em> Required field</div>
	      <input type="hidden" name="token" value="<?php echo fRequest::generateCSRFToken() ?>" />
              <input type="hidden" name="user_id" value="<?php echo fSession::get('user_id'); ?>" />
            </div>
         </fieldset>
       </div>       
     </form>
    </div>
    <div class="span10"> 
    <?php if ($action == 'edit') {  ?>
        <img src="<?php echo Graph::drawGraph($graph,$dashboard) ?>">
    <p class="info"><a href="<?php echo Line::makeURL('add',$graph) ?>">Add Line</a></p>
 <?php
   try {
	$lines->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>
    <div>
	<table class="zebra-striped">
          <thead>
          <tr>    
          <th>Alias</th>
          <th>Target</th>
          <th>Color</th>
          <th>Action</th>
          </tr>    
          </thead>
          <tbody>
	<?php
	$first = TRUE;
	foreach ($lines as $line) {
		?>
    	<tr>
        <td><?php echo $line->prepareAlias() ?></td>
        <td><?php echo $line->prepareTarget() ?></td>
        <td><?php echo $line->prepareColor() ?></td>
        <td><a href="<?php echo Line::makeURL('edit', $line) ?>">Edit</a> |
        <a href="<?php echo Line::makeURL('delete', $line) ?>">Delete</a></td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Graphite-Tattle lines available for this graph . <a href="<?php echo Line::makeURL('add',$graph) ?>">Add one now</a></p>
	<?php
} }
?>    
    </div>
  </div>
</div>
</div>
<?php
$tmpl->place('footer');        
