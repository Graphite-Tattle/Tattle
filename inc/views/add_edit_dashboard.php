<?
$page_title = $action == 'add' ? 'Add a Dashboard' : 'Edit Dashboard';
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => $page_title,'url' => '?'.fURL::getQueryString(),'active'=> true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
if (isset($dashboard_id)) {
  $query_string = "&dashboard_id=$dashboard_id";
} else {
  $query_string = '';
}
?>
<script type="text/javascript">
	$(function(){
		$('.btn_popover').each(function(){
			id=$(this).attr('id')
			$(this).popover({
				content : $("#form_clone_into_"+id).html(),
				html : true
			});
		});
	});
</script>
  <div class="row">
    <div class="span4">
      <form class="form-stacked" action="?action=<?=$action.$query_string; ?>" method="post">
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
                 $columns = array('0'=>'0','1' => '1', '2' => '2', '3' => '3');
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
            <div class="clearfix">
            	<label for="dashboard-group">Group</label>
            	<div class="input">
            		<select name="group_id">
            			<?php 
            				foreach (Group::findAll() as $group) {
								fHTML::printOption($group->getName(), $group->getGroupId(), ($action == 'add')?$filter_group_id:$dashboard->getGroupId());
							}
            			?>
            		</select>
            	</div>
            </div>
            <div class="actions">
	      <input class="btn btn-primary" type="submit" value="Save" />
              <input class="btn" type="submit" name="action::delete" value="Delete" onclick="return confirm('Do you really want to delete this dashboard ?');" />
              <a href="<?=Dashboard::makeUrl('view',$dashboard); ?>" class="btn">View</a>
              <a href="<?=Dashboard::makeURL('export', $dashboard); ?>" target="_blank" class="btn">Export</a>
              <div class="required"><em>*</em> Required field</div>
	      <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
              <input type="hidden" name="user_id" value="<?=fSession::get('user_id'); ?>" />
            </div>
         </fieldset>
       </div>       
     </form>
    </div>
    <div class="span8">   
   <? if ($action == 'edit') { ?>
   <p class="info"><a href="<?=Graph::makeURL('add',$dashboard); ?>">Add Graph</a></p>
 <?php
   try {
	$graphs->tossIfEmpty();
	$number_of_graphs = $graphs->count(TRUE);
	$affected = fMessaging::retrieve('affected', fURL::get());
	
	if ($number_of_graphs > 1) {
	?>
		<script type="text/javascript">
			function getPosition(element)
			{
			        var left = 0;
			        var top = 0;
			        // Retrieve the element
			        var e = document.getElementById(element);
			        // While we have a parent
			        while (e.offsetParent != undefined && e.offsetParent != null)
			        {
			                // We add the parent position
			                left += e.offsetLeft + (e.clientLeft != null ? e.clientLeft : 0);
			                top += e.offsetTop + (e.clientTop != null ? e.clientTop : 0);
			                e = e.offsetParent;
			        }
			        return new Array(left,top);
			}
		
			function construct_table_hider () {
				var new_div = $('<div>');
				$(new_div).width($('#sortable').width()+"px")
						  .height($('#sortable').height()+"px")
						  .css('line-height',$('#sortable').height()+"px")
						  .css('display','none')
						  .attr('id','tableHider')
						  .addClass('sortable-loader')
						  .html('<img src="assets/img/loader.gif"/>');
				$('table').append(new_div);
			}
		
			function hide_table() {
				var pos = getPosition('sortable');
				$('#tableHider').css('left',pos[0]).css('top',pos[1]);
				$('#tableHider').show();
			}
		
			function hide_popover(){
				$('.btn_popover').each(function(){
					$(this).popover('hide');
				});
			}
		
			$(function(){
				construct_table_hider();
				
				$('.btn_popover').each(function(){
					id=$(this).attr('id')
					$(this).popover({
						content : $("#form_clone_into_"+id).html(),
						html : true
					});
				});
		
				$('#sortable').sortable({
					placeholder: "sortable-placeholder",
					cancel: "#sortable .popover",
					start : hide_popover,
					update : function (event,ui){
						hide_table();
						var new_weights = new Array();
						var i = 0;
						$('#sortable tr').each(function(){
							new_weights.push($(this).attr('id') + ":" + i);
							i++;
						});
						$(location).attr('href','<?=Graph::makeURL('drag_reorder')?>'+new_weights.join(","));
					}
				});
			});
		</script>
	<?php }?>
    <div>
	<table class="table table-bordered table-striped" id="table-graphs">
          <thead>
          <tr>
          <th>Name</th>
          <th>Description</th>
          <th>Vtitle</th>
          <th>Area</th>
          <th>Action</th>
          <?php if ($number_of_graphs > 1) {?>
          	<th>Reorder&nbsp;*</th>
          <?php }?>
          </tr>    
          </thead>
          <tbody<?= ($number_of_graphs > 1)?' id="sortable"':'';?>>
	<?php
	$first = TRUE;
	$index = 0;
	foreach ($graphs as $graph) {
		?>
    	<tr id="<?=$graph->getGraphId()?>">
        <td><?=$graph->prepareName(); ?></td>
        <td><?=$graph->prepareDescription(); ?></td>
        <td><?=$graph->prepareVtitle(); ?></td>
        <td><?=$graph->prepareArea(); ?></td>        
        <td><a href="<?=Graph::makeURL('edit', $graph); ?>">Edit</a> |
        <a href="<?=Graph::makeURL('delete', $graph); ?>">Delete</a> |
        <form id="form_clone_<?=(int)$graph->getGraphId(); ?>" method="post" action="<?=Graph::makeURL('clone', $graph); ?>" style="display: initial;">
        	<a href="#" onclick="$('#form_clone_<?=(int)$graph->getGraphId(); ?>').submit(); return false;">Clone</a>
        	<input type="hidden" name="token" value="<?=fRequest::generateCSRFToken("/graphs.php"); ?>" />
        </form> |
        <div id="form_clone_into_<?=(int)$graph->getGraphId(); ?>" style="display:none;">
        <form id="" method="post" action="<?=Graph::makeURL('clone_into', $graph); ?>" class="inline no-margin">
        	<input type="hidden" name="token" value="<?=fRequest::generateCSRFToken("/graphs.php"); ?>" />
        	Select destination : 
        	<select name="dashboard_dest_id">
        		<?php 
        			foreach (Dashboard::findAll() as $dashboard_dest) {
						if ($dashboard_dest->prepareDashboardId() != $graph->prepareDashboardId()) {
        		?>
        			<option value="<?=(int)$dashboard_dest->getDashboardId(); ?>"><?=$dashboard_dest->prepareName() ?></option>
        		<?php 
        				}
        			}
        		?>
        	</select>
        	<input type="submit" value="Clone !" class="btn btn-primary"/>
        </form>
        </div>
        <a href="#" id="<?=(int)$graph->getGraphId(); ?>" class="btn_popover">Clone into</a>
        </td>
        <?php if ($number_of_graphs > 1) {?>
	        <td>
	        	<?php if ($index == 0) {?>
	        		<span class="disabled"><i class="icon-arrow-up pointer"></i></span>
	        	<?php } else { ?>
	        		<a href="<?=Graph::makeURL('reorder',$graph,'previous')?>" onclick="hide_table();return true;"><i class="icon-arrow-up pointer" title="Previous"></i></a>
	        	<?php } ?>
	        	<?php if ($index == $number_of_graphs-1) {?>
	        		<span class="disabled"><i class="icon-arrow-down pointer"></i></span>
	        	<?php } else { ?>
	        		<a href="<?=Graph::makeURL('reorder',$graph,'next')?>" onclick="hide_table();return true;"><i class="icon-arrow-down pointer" title="Next"></i></a>
	        	<?php } ?>
	        </td>
	    <?php } ?>
        </tr>
    <?php
    	$index++;
		 } ?>
    </tbody></table>
    <?php if ($number_of_graphs > 1) {?>
    	<p class="text-info"><em>* You can also use "drag and drop" to reorder the graphs.</em></p>
    <?php } ?>
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
