<?php
$tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
?>
<script type="text/javascript">
        var last_filter;    
	function test_file_present () {
		if ($("#fileInput").val() == '') {
				alert("You have to select a file first.");
				return false;
		} else {
			return true;
		}
	}

	function deselectAll() {
		$('td.last input').each(function(){
			$(this).removeAttr('checked');
			$($(this).closest('tr')).removeClass('highlighted');
		});
	}
        
        function filterDashboards() {
                var filter_text = $("#filter_text").val();
                if (last_filter && last_filter == filter_text) {
                        $("#unfiltered_dashboards").hide();
                        $("#filtered_dashboards").show();
                } else {    
                    if ($.trim(filter_text).length > 2) {
                            $("#unfiltered_dashboards").hide();
                            $("#filtered_dashboards").hide();
                            $('#loader_filter').show(); 
                            var filter_group_id = <?= $filter_group_id?>;
                            $.get(
                                'inc/views/list_filtered_dashboards.php', 
                                {
                                    filter_text: filter_text, 
                                    filter_group_id:filter_group_id
                                }, 
                                function (data) {
                                    $("#filtered_dashboards").show();
                                    $("#filtered_dashboards").html(data);
                                },
                                'html'
                                );
                            last_filter = $("#filter_text").val();
                    } else {
                            $("#unfiltered_dashboards").show();
                            $("#filtered_dashboards").hide();
                    }
                }
        }
	$(function(){
                var timeout;
		$('.input-dashboard').click(function(){
			if ($(this).is(":checked")) {
				$($(this).closest('tr')).addClass('highlighted');
			} else {
				$($(this).closest('tr')).removeClass('highlighted');
			}
		});

		$('#list_of_filters').change(function(){
			$(location).attr('href',$('#list_of_filters').val());
		});
                
                $("#filter_text").keyup(function(){
                    if (timeout) {
                        clearTimeout(timeout);
                        timeout = setTimeout(function() {filterDashboards();}, 1000);
                    } else {
                        timeout = setTimeout(function() {filterDashboards();}, 1000);
                    }
		});
                
                $('.badge').tooltip();
	});
</script>
<?php 
try {
	$dashboards->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?><a href="<?=Dashboard::makeURL('add'); ?>" class="btn btn-primary">Add Dashboard</a>
	<form method="post" id="formImport" action="<?=Dashboard::makeURL('import'); ?>" enctype="multipart/form-data" class="inline no-margin" style="padding-left: 10px;">
		<input type="hidden" value="<?= $filter_group_id?>" name="filter_group_id" />
		<p class="inline">
                    <a href="#" onclick="if(test_file_present()){$('#loader').show();$('#formImport').submit();};return false;" class="btn btn-primary">Import</a>
                    <span>this one :</span>
                    <input type="file" name="uploadedfile" id="fileInput" class="inline" />
                    <img id="loader" src="assets/img/loader2.gif" style="margin-left:5px; display:none;">
		</p>
	</form>
        <div class="form-group inline" style="width:500px; margin-bottom: -10px">
            <input type="text" class="form-control" placeholder="Search In Dashboards, Graphs AND Lines" id="filter_text" autofocus="autofocus">
        </div>
        <img id="loader_filter" src="assets/img/loader2.gif" style="margin-left:5px; display:none;">
	<div class="pull-right">
		<span>Filter group :</span>
		<select id="list_of_filters">
			<option value="<?=Dashboard::makeURL('list',-1)?>">All dashboards</option>
			<?php 
				foreach (Group::findAll() as $group) {
			?>
					<option value="<?=Dashboard::makeURL('list',$group->getGroupId())?>" <?=($filter_group_id==$group->getGroupId())?'selected="selected"':''?>><?=$group->getName();?></option>
			<?php
				}
			?>
		</select>
	</div>
        <div id="unfiltered_dashboards">
            <form method="POST" id="form_mass_export" action="<?= Dashboard::makeURL('mass_export'); ?>" target="_blank">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>    
                            <th>Name</th>
                            <th>Description</th>
                            <th>Group</th>
                            <th>Columns</th>
                            <th>Background Color</th>
                            <th>Action</th>
                            <th class="last"><input type="submit" class="btn btn-default" value="Export selected" onclick="$('#form_mass_export').submit();deselectAll(); return false;" /></th>
                        </tr>    
                    </thead>
                    <tbody>
                        <?php
                        $first = TRUE;
                        foreach ($dashboards as $dashboard) {
                        ?>
                        <tr>
                            <td>
                                <a href="<?= Dashboard::makeURL('view', $dashboard); ?>">
                                    <?= $dashboard->prepareName(); ?>
                                </a>
                            </td>
                            <td><?= $dashboard->prepareDescription(); ?></td>
                            <td>
                                <?php
                                try {
                                $dashboard_s_group = new Group($dashboard->getGroupId());
                                echo ($dashboard_s_group->getName());
                                } catch (fNotFoundException $e) {
                                echo "No group found";
                                }
                                ?>
                            </td>
                            <td><?= $dashboard->prepareColumns(); ?></td>
                            <td><?= $dashboard->prepareBackgroundColor(); ?></td>
                            <td>
                                <a href="<?= Dashboard::makeURL('view', $dashboard); ?>">View</a> |
                                <a href="<?= Dashboard::makeURL('edit', $dashboard); ?>">Edit</a> |
                                <a href="<?= Dashboard::makeURL('delete', $dashboard); ?>">Delete</a> |
                                <a href="<?= Dashboard::makeURL('export', $dashboard); ?>" target="_blank">Export</a>
                            </td>
                            <td class="last"><input type="checkbox" name="id_mass_export[]" class="no-margin input-dashboard" value="<?= $dashboard->getDashboardId() ?>" /></td>
                        </tr>
                        <?php } ?>
                    </tbody></table>
            </form>
        </div>
        <div id="filtered_dashboards">
        </div>
    <?
} catch (fEmptySetException $e) {
	?>
	<div class="info">
		There are currently no Tattle Dashboards available for your account with this filter. <a href="<?=Dashboard::makeURL('add'); ?>">Add one now</a> or
		<form method="post" id="formImport" action="<?=Dashboard::makeURL('import'); ?>" enctype="multipart/form-data" class="inline no-margin" style="padding-left: 10px;">
			<input type="hidden" value="<?= $filter_group_id?>" name="filter_group_id" />
			<p class="inline">
				<a href="#" onclick="if(test_file_present()){$('#formImport').submit()};return false;" class="btn btn-primary">Import</a>
				this one :
				<input type="file" name="uploadedfile" id="fileInput" />
			</p>
		</form>
		<p class="pull-right">
			Filter group :
			<select id="list_of_filters">
					<option value="<?=Dashboard::makeURL('list',-1)?>">All dashboards</option>
				<?php 
					foreach (Group::findAll() as $group) {
				?>
						<option value="<?=Dashboard::makeURL('list',$group->getGroupId())?>" <?=($filter_group_id==$group->getGroupId())?'selected="selected"':''?>><?=$group->getName();?></option>
				<?php
					}
				?>
			</select>
		</p>
	</div>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
