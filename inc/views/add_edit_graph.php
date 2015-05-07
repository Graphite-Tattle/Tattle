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
    <div class="col-md-3">
      <form action="?action=<?=$action.$query_string; ?>" method="post" class="form-stacked">
            <div class="form-group">
              <label for="graph-name">Name<em>*</em></label>
                <input id="graph-name" class="form-control" type="text" size="30" name="name" value="<?=$graph->encodeName(); ?>" placeholder="graph name (and title)" />
            </div>
            <div class="form-group">
              <label for="graph-description">Description</label>
                 <textarea class="form-control" id="graph-description" name="description" rows="3"><?=$graph->encodeDescription(); ?></textarea>
            </div>
            <div class="form-group">
              <label for="graph-vtitle">Y-Axis Title</label>
              <input id="graph-vtitle" class="form-control" type="text" size="30" name="vtitle" value="<?=$graph->encodeVtitle(); ?>" placeholder="e.g.: requests/s" />
            </div>
            <div class="form-group">
              <label for="graph-area">Area Mode<em>*</em></label>
                <select name="area" class="form-control">
                <?
                 $areaModes = array('none' => 'None', 'first' => 'First', 'stacked' => 'Stacked', 'all' => 'All');
                 foreach ($areaModes as $value => $text) {
                   fHTML::printOption($text, $value, $graph->getArea());
                 }
                ?>
                </select>
            </div>
            <div class="form-group">
              <label for="graph-range">Range<em>*</em></label>
              <div class="row">
              	<div class="col-md-6">
                <select name="time_value" class="form-control">
                <?
                 $values = range(0,60);
                 foreach ($values as $value) {
                   fHTML::printOption($value, $value, $graph->getTime_value());
                 }
                ?>
                </select>
                </div>
                <div class="col-md-6">
                <select name="unit" class="form-control col-xs-2">
                <?
                 $units = array('minutes', 'hours', 'days', 'weeks', 'months', 'years');
                 foreach ($units as $value) {
                   fHTML::printOption($value, $value, $graph->getUnit());
                 }
                ?>
                </select>
                </div>
                </div>
            </div>
            
            <div class="checkbox">
            	<label>
           			<input type="checkbox" name="starts_at_midnight" <?= ($graph->getStartsAtMidnight())?'checked="checked"':''?> value="true"> Starts at midnight
           		</label>
           	</div>
            <div class="form-group">
                <label for="graph-custom-opts">Custom Options</label>
                  <input id="graph-custom-opts" class="form-control" type="text" size="30" name="custom_opts" value="<?=$graph->encodeCustom_Opts(); ?>" placeholder="options appended to the url e.g.: yMin=0&hideLegend=false" />
            </div>
	    <div class="actions">
	      <input class="btn btn-primary" type="submit" value="Save" />
              <a href="<?=Graph::makeURL('delete',$graph); ?>" class="btn btn-default">Delete</a>
              <a href="<?=Dashboard::makeUrl('view',$dashboard); ?>" class="btn btn-default">View</a>
              <div class="required"><em>*</em> Required field</div>
	      	  <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
              <input type="hidden" name="user_id" value="<?=fSession::get('user_id'); ?>" />
            </div>
     </form>
    </div>
    <div class="col-md-9">
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
		
			function construct_table_hider () {
				var div = $("<div>");
				div.height($("#table_container").height())
					.width($("#table_container").width())
					.css('line-height',$('#table_container').height()+"px")
					.css('z-index',"100")
					.css('display',"none")
					.addClass('sortable-loader')
					.attr("id","tableHider")
					.html('<img src="assets/img/loader.gif"/>');
				$("#table_container").prepend(div);
			}
		
			$(function(){
				construct_table_hider();
				
				$('#sortable').sortable({
					placeholder: "sortable-placeholder",
					cancel: "#sortable .line-target",
					update : function (event,ui){
						$('#tableHider').show();
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
    <div id="table_container">
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
          <tbody<?=($number_of_lines > 1)?" id='sortable'":""?>>
	<?php
	$first = TRUE;
	$index = 0;
	foreach ($lines as $line) {
		?>
    	<tr id="<?=$line->getLineId()?>">
        <td><?=$line->prepareAlias(); ?></td>
        <td class="line-target"><?=$line->prepareTarget(); ?></td>
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
	        		<span class="disabled"><i class="glyphicon glyphicon-arrow-up pointer"></i></span>
	        	<?php } else { ?>
	        		<a href="<?=Line::makeURL('reorder',$line,'previous')?>" onclick="$('#tableHider').show();return true;"><i class="glyphicon glyphicon-arrow-up pointer" title="Previous"></i></a>
	        	<?php } ?>
	        	<?php if ($index == $number_of_lines-1) {?>
	        		<span class="disabled"><i class="glyphicon glyphicon-arrow-down pointer"></i></span>
	        	<?php } else { ?>
	        		<a href="<?=Line::makeURL('reorder',$line,'next')?>" onclick="$('#tableHider').show();return true;"><i class="glyphicon glyphicon-arrow-down pointer" title="Next"></i></a>
	        	<?php } ?>
	        </td>
	    <?php } ?>
        </tr>
    <?php
    	$index++;
		 } ?>
    </tbody></table>
    <?php if ($number_of_lines > 1) {?>
    	<p class="text-info"><em>* You can also use "drag and drop" to reorder the lines.</em></p>
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
