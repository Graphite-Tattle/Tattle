<?
$page_title = ($action == 'add' ? 'Add a Graph' : 'Edit Graph');
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => 'Dashboard', 'url' => Dashboard::makeURL('list'),'active' => false);
$breadcrumbs[] = array('name' => $dashboard->encodeName(), 'url' => Dashboard::makeUrl('edit',$graph),'active' => false);
$breadcrumbs[] = array('name' => $page_title, 'url' => '?'.fURL::getQueryString(),'active' => true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
if (!is_null($dashboard_id)) {
  $query_string = "&dashboard_id=$dashboard_id";
} elseif (!is_null($graph_id)) {
  $query_string = "&graph_id=$graph_id";
} else {
  $query_string = '';
}
?>
  <div class="row">
    <div class="span4">
      <form action="?action=<?=$action.$query_string; ?>" method="post" class="form-stacked">
        <div class="main" id="main">
          <fieldset>
            <div class="clearfix">
              <label for="graph-name">Name<em>*</em></label>
              <div class="input">
                <input id="graph-name" class="span3" type="text" size="30" name="name" value="<?=$graph->encodeName(); ?>" />
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="graph-description">Description<em>*</em></label>
              <div class="input">
                 <textarea class="span3" id="graph-description" name="description" rows="3"><?=$graph->encodeDescription(); ?></textarea>
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="graph-vtitle">Y-Axis Title<em>*</em></label>
              <div class="input">
                  <input id="graph-vtitle" class="span3" type="text" size="30" name="vtitle" value="<?=$graph->encodeVtitle(); ?>" />
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
              <label for="graph-range">Range<em>*</em></label>
              <div class="input">
                <select name="time_value" class="span3">
                <?
                 $values = range(0,60);
                 foreach ($values as $value) {
                   fHTML::printOption($value, $value, $graph->getTime_value());
                 }
                ?>
                </select>
              </div>
              <div class="input">
                <select name="unit" class="span3">
                <?
                 $units = array('minutes', 'hours', 'days', 'weeks', 'months', 'years');
                 foreach ($units as $value) {
                   fHTML::printOption($value, $value, $graph->getUnit());
                 }
                ?>
                </select>
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
                <label for="graph-custom-opts">Custom Options</label>
                <div class="input">
                  <input id="graph-custom-opts" class="span3" type="text" size="30" name="custom_opts" value="<?=$graph->encodeCustom_Opts(); ?>" />
              </div>
            </div><!-- /clearfix -->
	    <div class="actions">
	      <input class="btn btn-primary" type="submit" value="Save" />
              <a href="<?=Graph::makeURL('delete',$graph); ?>" class="btn">Delete</a>
              <a href="<?=Dashboard::makeUrl('view',$dashboard); ?>" class="btn">View</a>
              <div class="required"><em>*</em> Required field</div>
	      	  <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
              <input type="hidden" name="user_id" value="<?=fSession::get('user_id'); ?>" />
            </div>
         </fieldset>
       </div>
     </form>
    </div>
    <div class="span8">
    <?php if ($action == 'edit') {  ?>
        <img src="<?=Graph::drawGraph($graph,$dashboard); ?>">
    <p class="info"><a href="<?=Line::makeURL('add',$graph); ?>">Add Line</a></p>
 <?php
   try {
	$lines->tossIfEmpty();
	$number_of_lines = $lines->count(TRUE);
	$affected = fMessaging::retrieve('affected', fURL::get());
	
	if ($number_of_lines > 1) {
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
				var pos = getPosition('sortable');
				$(new_div).css('width',$('#sortable').width()+"px")
						  .css('height',$('#sortable').height()+"px")
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
		
			$(function(){
				construct_table_hider();
				
				$('#sortable').sortable({
					placeholder: "sortable-placeholder",
					update : function (event,ui){
						hide_table();
						var new_weights = new Array();
						var i = 0;
						$('#sortable tr').each(function(){
							new_weights.push($(this).attr('id') + ":" + i);
							i++;
						});
						$(location).attr('href','<?=Line::makeURL('drag_reorder')?>'+new_weights.join(","));
					}
				});
			});
		</script>
	<?php } ?>
    <div>
	<table class="table table-bordered table-striped">
          <thead>
          <tr>
          <th>Alias</th>
          <th>Target</th>
          <th>Color</th>
          <th>Action</th>
          <?php if ($number_of_lines > 1) {?>
          	<th>Reorder&nbsp;*</th>
          <?php }?>
          </tr>
          </thead>
          <tbody id="sortable">
	<?php
	$first = TRUE;
	$index = 0;
	foreach ($lines as $line) {
		?>
    	<tr id="<?=$line->getLineId()?>">
        <td><?=$line->prepareAlias(); ?></td>
        <td><?=$line->prepareTarget(); ?></td>
        <td><?=$line->prepareColor(); ?></td>
        <td><a href="<?=Line::makeURL('edit', $line); ?>">Edit</a> |
        <a href="<?=Line::makeURL('delete', $line); ?>">Delete</a> |
        <form id="form_clone_<?=(int)$line->getLineId(); ?>" method="post" action="<?=Line::makeURL('clone', $line); ?>" class="inline no-margin">
        	<a href="#" onclick="$('#form_clone_<?=(int)$line->getLineId(); ?>').submit(); return false;">Clone</a>
        	<input type="hidden" name="token" value="<?=fRequest::generateCSRFToken("/lines.php"); ?>" />
        </form>
        </td>
         <?php if ($number_of_lines > 1) {?>
	        <td>
	        	<?php if ($index == 0) {?>
	        		<span class="disabled"><i class="icon-arrow-up pointer"></i></span>
	        	<?php } else { ?>
	        		<a href="<?=Line::makeURL('reorder',$line,'previous')?>" onclick="hide_table();return true;"><i class="icon-arrow-up pointer" title="Previous"></i></a>
	        	<?php } ?>
	        	<?php if ($index == $number_of_lines-1) {?>
	        		<span class="disabled"><i class="icon-arrow-down pointer"></i></span>
	        	<?php } else { ?>
	        		<a href="<?=Line::makeURL('reorder',$line,'next')?>" onclick="hide_table();return true;"><i class="icon-arrow-down pointer" title="Next"></i></a>
	        	<?php } ?>
	        </td>
	    <?php } ?>
        </tr>
    <?php
    	$index++;
		 } ?>
    </tbody></table>
    <?php if ($number_of_lines > 1) {?>
    	<p class="text-info"><em>* You can also use "drag and drop" to reorder the graphs.</em></p>
     <?php } ?>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Tattle lines available for this graph . <a href="<?=Line::makeURL('add',$graph); ?>">Add one now</a></p>
	<?php
} }
?>
    </div>
  </div>
</div>
</div>
<?php
$tmpl->place('footer');
