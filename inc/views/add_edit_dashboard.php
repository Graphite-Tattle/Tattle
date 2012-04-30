<?
$page_title = $action == 'add' ? 'Add a Dashboard' : 'Edit Dashboard';
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => $page_title,'url' => fURL::getWithQueryString(),'active'=> true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
if (isset($dashboard_id)) {
  $query_string = "&dashboard_id=$dashboard_id";
} else {
  $query_string = '';
}
?>
  <div class="row">
    <div class="span4">
      <form class="form-stacked" action="<?=fURL::get(); ?>?action=<?=$action.$query_string; ?>" method="post">
        <div class="main" id="main">
          <fieldset>
                <div class="clearfix">
	      <label for="dashboard-name">Name<em>*</em></label>
              <div class="input">
	        <input id="dashboard-name" class="span3" type="text" size="30" name="name" value="<?=$dashboard->encodeName(); ?>" />
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="dashboard-description">Description<em>*</em></label>
              <div class="input">             
                 <textarea class="span3" id="dashboard-description" name="description" rows="3"><?=$dashboard->encodeDescription(); ?></textarea>
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="dashboard-columns">Columns<em>*</em></label>
              <div class="input">
                <select name="columns" class="span3">
                <?
                 $columns = array('1' => '1', '2'   => '2', '3' => '3');
                 foreach ($columns as $value => $text) {
                   fHTML::printOption($text, $value, $dashboard->getColumns());
                 }
                ?>
                </select>            
              </div>
            </div><!-- /clearfix -->
        <div class="clearfix">
              <label for="dashboard-graph_width">Graph Width<em>*</em></label>
              <div class="input">             
                 <input id="dashboard-graph_width" class="span3" type="text" size="30" name="graph_width" value="<?=$dashboard->encodeGraphWidth(); ?>" />
              </div>
            </div><!-- /clearfix -->
        <div class="clearfix">
              <label for="dashboard-graph_height">Graph Height<em>*</em></label>
              <div class="input">             
                 <input id="dashboard-graph_height"  class="span3" type="text" size="30" name="graph_height" value="<?=$dashboard->encodeGraphHeight(); ?>" />
            </div><!-- /clearfix -->           
            </div>
            <div class="clearfix">
              <label for="dashboard-background_color">Background Color<em>*</em></label>
              <div class="input">             
                  <input id="dashboard-background_color" class="span3" type="text" size="30" name="background_color" value="<?=$dashboard->encodeBackgroundColor(); ?>" />
              </div>
            </div><!-- /clearfix -->            
	    <div class="clearfix">
             <label for="dashboard-refresh_rate">Refresh Rate<em>*</em> (in seconds)</label>
             <div class="input">
               <input id="dashboard-refresh_rate" class="span3" type="text" size="30" name="refresh_rate" value="<?=$dashboard->getRefreshRate(); ?>" />
             </div>
            </div>
            <div class="actions span4">
	      <input class="btn primary" type="submit" value="Save" />
              <input class="btn" type="submit" name="action::delete" value="Delete" />
              <a href="<?=Dashboard::makeUrl('view',$dashboard); ?>" class="btn">View</a>
              <div class="required"><em>*</em> Required field</div>
	      <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
              <input type="hidden" name="user_id" value="<?=fSession::get('user_id'); ?>" />
            </div>
         </fieldset>
       </div>       
     </form>
    </div>
    <div class="span10">   
   <? if ($action == 'edit') { ?>
   <p class="info"><a href="<?=Graph::makeURL('add',$dashboard); ?>">Add Graph</a></p>
 <?php
   try {
	$graphs->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>
    <div>
	<table class="zebra-striped">
          <thead>
          <tr>
          <th>Weight</th>    
          <th>Name</th>
          <th>Description</th>
          <th>Vtitle</th>
          <th>Area</th>
          <th>Action</th>
          </tr>    
          </thead>
          <tbody>
	<?php
	$first = TRUE;
	foreach ($graphs as $graph) {
		?>
    	<tr>
        <td><?=$graph->prepareWeight(); ?></td>
        <td><?=$graph->prepareName(); ?></td>
        <td><?=$graph->prepareDescription(); ?></td>
        <td><?=$graph->prepareVtitle(); ?></td>
        <td><?=$graph->prepareArea(); ?></td>        
        <td><a href="<?=Graph::makeURL('edit', $graph); ?>">Edit</a> |
        <a href="<?=Graph::makeURL('delete', $graph); ?>">Delete</a></td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Tattle graph available for this Dashboard . <a href="<?=Graph::makeURL('add',$dashboard); ?>">Add one now</a></p>
	<?php
} }
?>
    </div>
  </div>
</div>
</div>
<?php
$tmpl->place('footer');        
