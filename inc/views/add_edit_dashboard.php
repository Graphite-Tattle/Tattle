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
                
                $('.badge').tooltip();
	});
</script>
  <div class="row">
    <div class="col-md-3">
      <form action="?action=<?=$action.$query_string; ?>" method="post">
          <div class="form-group">
		      <label for="dashboard-name">Name<em>*</em></label>
		        <input id="dashboard-name" class="form-control" type="text" size="30" name="name" value="<?=$dashboard->encodeName(); ?>" placeholder="dashboard name (and title)" />
            </div><!-- /clearfix -->
            <div class="form-group">
              <label for="dashboard-description">Description</label>
                 <textarea class="form-control" id="dashboard-description" name="description" rows="3"><?=$dashboard->encodeDescription(); ?></textarea>
            </div>
            <div class="form-group">
              <label for="dashboard-columns">Columns<em>*</em></label>
                <select name="columns" class="form-control">
                <?
                 $columns = array('0'=>'0','1' => '1', '2' => '2', '3' => '3');
                 foreach ($columns as $value => $text) {
                   fHTML::printOption($text, $value, $dashboard->getColumns());
                 }
                ?>
                </select>            
            </div>
        <div class="form-group">
              <label for="dashboard-graph_width">Graph Width<em>*</em></label>
                 <input id="dashboard-graph_width" class="form-control" type="text" size="30" name="graph_width" value="<?=$dashboard->encodeGraphWidth(); ?>" />
            </div>
        <div class="form-group">
              <label for="dashboard-graph_height">Graph Height<em>*</em></label>
                 <input id="dashboard-graph_height"  class="form-control" type="text" size="30" name="graph_height" value="<?=$dashboard->encodeGraphHeight(); ?>" />
            </div>
            <div class="form-group">
              <label for="dashboard-background_color">Background Color<em>*</em></label>
                  <input id="dashboard-background_color" class="form-control" type="text" size="30" name="background_color" value="<?=$dashboard->encodeBackgroundColor(); ?>" />
            </div>
	    <div class="form-group">
             <label for="dashboard-refresh_rate">Refresh Rate (in seconds)</label>
               <input id="dashboard-refresh_rate" class="form-control" type="text" size="30" name="refresh_rate" value="<?=$dashboard->getRefreshRate(); ?>" />
            </div>
            <div class="form-group">
            	<label for="dashboard-group">Group</label>
            		<select name="group_id" class="form-control">
            			<?php 
            				foreach (Group::findAll() as $group) {
								fHTML::printOption($group->getName(), $group->getGroupId(), ($action == 'add')?$filter_group_id:$dashboard->getGroupId());
							}
            			?>
            		</select>
            </div>
            <div class="actions">
	      <input class="btn btn-primary" type="submit" value="Save" />
              <input class="btn btn-default" type="submit" name="action::delete" value="Delete" onclick="return confirm('Do you really want to delete this dashboard ?');" />
              <a href="<?=Dashboard::makeUrl('view',$dashboard); ?>" class="btn btn-default">View</a>
              <a href="<?=Dashboard::makeURL('export', $dashboard); ?>" target="_blank" class="btn btn-default">Export</a>
              <div class="required"><em>*</em> Required field</div>
	      <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
              <input type="hidden" name="user_id" value="<?=fSession::get('user_id'); ?>" />
            </div>
     </form>
    </div>
    <div class="col-md-9">   
   <? if ($action == 'edit') { ?>
   <div class="form-group inline" style="width:500px">
        <input type="text" class="form-control" placeholder="Search In Graphs" id="filter_text" autofocus="autofocus">
    </div>
   <p class="info"><a href="<?=Graph::makeURL('add',$dashboard); ?>">Add Graph</a></p>
 <?php
   try {
	$graphs->tossIfEmpty();
	$number_of_graphs = $graphs->count(TRUE);
	$affected = fMessaging::retrieve('affected', fURL::get());
	
	if ($number_of_graphs > 1) {
	?>
		<script type="text/javascript">
                        var last_filter;
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
		
			function hide_popover(){
				$('.btn_popover').each(function(){
					$(this).popover('hide');
				});
			}
                        
                        function filterGraphs() {
                                var filter_text = $("#filter_text").val();
                                if (last_filter && last_filter == filter_text) {
                                        $("#unfiltered_graphs").hide();
                                        $("#filtered_graphs").show();
                                } else {    
                                    if (filter_text.length > 2) {
                                            var dashboard_id = <?= $dashboard_id?>;
                                            $.get(
                                                'inc/views/list_filtered_graphs.php', 
                                                {
                                                    filter_text: filter_text, 
                                                    dashboard_id: dashboard_id
                                                }, 
                                                function (data) {
                                                    $("#unfiltered_graphs").hide();
                                                    $("#filtered_graphs").html(data);
                                                    $("#filtered_graphs").show();
                                                },
                                                'html'
                                                );
                                            last_filter = $("#filter_text").val();
                                    } else {
                                            $("#unfiltered_graphs").show();
                                            $("#filtered_graphs").hide();
                                    }
                                }
                        } 
                        
			$(function(){
                                var timeout;
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
						$('#tableHider').show();
						var new_weights = new Array();
						var i = 0;
						$('#sortable tr').each(function(){
							new_weights.push($(this).attr('id') + ":" + i);
							i++;
						});
						$(location).attr('href','<?=Graph::makeURL('drag_reorder')?>'+new_weights.join(","));
					}
				});
                                
                                $("#filter_text").keyup(function(){
                                        if (timeout) {
                                            clearTimeout(timeout);
                                            timeout = setTimeout(function() {filterGraphs();}, 1000);
                                        } else {
                                            timeout = setTimeout(function() {filterGraphs();}, 1000);
                                        }
                                });
			});
		</script>
	<?php }?>
    <div id="table_container">
        <div id="unfiltered_graphs">
            <table class="table table-bordered table-striped" id="table-graphs">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Vtitle</th>
                        <th>Area</th>
                        <th>Action</th>
                        <?php if ($number_of_graphs > 1) { ?>
                        <th>Reorder&nbsp;*</th>
                        <?php } ?>
                    </tr>    
                </thead>
                <tbody<?= ($number_of_graphs > 1)?' id="sortable"':''; ?>>
                    <?php
                    $first = TRUE;
                    $index = 0;
                    foreach ($graphs as $graph) {
                    ?>
                    <tr id="<?= $graph->getGraphId() ?>">
                        <td class="highlight">
                            <?= $graph->prepareName(); ?>
                        </td>
                        <td class="highlight"><?= $graph->prepareDescription(); ?></td>
                        <td class="highlight"><?= $graph->prepareVtitle(); ?></td>
                        <td class="highlight"><?= $graph->prepareArea(); ?></td>        
                        <td><a href="<?= Graph::makeURL('edit', $graph); ?>">Edit</a> |
                            <a href="<?= Graph::makeURL('delete', $graph); ?>">Delete</a> |
                            <form id="form_clone_<?= (int) $graph->getGraphId(); ?>" method="post" action="<?= Graph::makeURL('clone', $graph); ?>" style="display: initial;">
                                <a href="#" onclick="$('#form_clone_<?= (int) $graph->getGraphId(); ?>').submit(); return false;">Clone</a>
                                <input type="hidden" name="token" value="<?= fRequest::generateCSRFToken("/graphs.php"); ?>" />
                            </form> |
                            <div id="form_clone_into_<?= (int) $graph->getGraphId(); ?>" style="display:none;">
                                <form id="" method="post" action="<?= Graph::makeURL('clone_into', $graph); ?>" class="inline no-margin">
                                    <input type="hidden" name="token" value="<?= fRequest::generateCSRFToken("/graphs.php"); ?>" />
                                    Select destination : 
                                    <select name="dashboard_dest_id">
                                        <?php
                                        foreach (Dashboard::findAll() as $dashboard_dest) {
                                        if ($dashboard_dest->prepareDashboardId() != $graph->prepareDashboardId()) {
                                        ?>
                                        <option value="<?= (int) $dashboard_dest->getDashboardId(); ?>"><?= $dashboard_dest->prepareName() ?></option>
                                        <?php
                                        }
                                        }
                                        ?>
                                    </select>
                                    <input type="submit" value="Clone !" class="btn btn-primary"/>
                                </form>
                            </div>
                            <a href="#" id="<?= (int) $graph->getGraphId(); ?>" class="btn_popover">Clone into</a>
                        </td>
                        <?php if ($number_of_graphs > 1) { ?>
                        <td>
                            <?php if ($index == 0) { ?>
                            <span class="disabled"><i class="glyphicon glyphicon-arrow-up pointer"></i></span>
                            <?php } else { ?>
                            <a href="<?= Graph::makeURL('reorder', $graph, 'previous') ?>" onclick="$('#tableHider').show();return true;"><i class="glyphicon glyphicon-arrow-up pointer" title="Previous"></i></a>
                            <?php } ?>
                            <?php if ($index == $number_of_graphs-1) { ?>
                            <span class="disabled"><i class="glyphicon glyphicon-arrow-down pointer"></i></span>
                            <?php } else { ?>
                            <a href="<?= Graph::makeURL('reorder', $graph, 'next') ?>" onclick="$('#tableHider').show();return true;"><i class="glyphicon glyphicon-arrow-down pointer" title="Next"></i></a>
                            <?php } ?>
                        </td>
                        <?php } ?>
                    </tr>
                    <?php
                    $index++;
                    }
                    ?>
                </tbody></table>
        </div>
        <div id="filtered_graphs"></div>
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
