<?php
$tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
?>
<script type="text/javascript">
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

	$(function(){
		$('.input-dashboard').click(function(){
			if ($(this).is(":checked")) {
				$($(this).closest('tr')).addClass('highlighted');
			} else {
				$($(this).closest('tr')).removeClass('highlighted');
			}
		});
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
			<a href="#" onclick="if(test_file_present()){$('#formImport').submit()};return false;" class="btn btn-primary">Import</a>
			this one :
			<input type="file" name="uploadedfile" id="fileInput" />
		</p>
	</form>
	
	<p class="pull-right">
		Filter group :
		<select id="list_of_filters" onclick="$(location).attr('href',$('#list_of_filters').val());return false;">
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
	<form method="POST" id="form_mass_export" action="<?=Dashboard::makeURL('mass_export');?>" target="_blank">
		<table class="table table-bordered table-striped">
	          <thead>
	          <tr>    
	          <th>Name</th>
	          <th>Description</th>
	          <th>Group</th>
	          <th>Columns</th>
	          <th>Background Color</th>
	          <th>Action</th>
	          <th class="last"><input type="submit" class="btn" value="Export selected" onclick="$('#form_mass_export').submit();deselectAll(); return false;" /></th>
	          </tr>    
	          </thead>
	          <tbody>
		<?php
		$first = TRUE;
		foreach ($dashboards as $dashboard) {
			?>
	    	<tr>
	        <td><?=$dashboard->prepareName(); ?></td>
	        <td><?=$dashboard->prepareDescription(); ?></td>
	        <td>
	        	<?php 
	        		$dashboard_s_group = new Group($dashboard->getGroupId());
	        		echo ($dashboard_s_group->getName());
	        	?>
	        </td>
	        <td><?=$dashboard->prepareColumns(); ?></td>
	        <td><?=$dashboard->prepareBackgroundColor(); ?></td>
	        <td>
	        <a href="<?=Dashboard::makeURL('view', $dashboard); ?>">View</a> |
	        <a href="<?=Dashboard::makeURL('edit', $dashboard); ?>">Edit</a> |
	        <a href="<?=Dashboard::makeURL('delete', $dashboard); ?>">Delete</a> |
	        <a href="<?=Dashboard::makeURL('export', $dashboard); ?>" target="_blank">Export</a>
	        </td>
	        <td class="last"><input type="checkbox" name="id_mass_export[]" class="no-margin input-dashboard" value="<?=$dashboard->getDashboardId()?>" /></td>
	        </tr>
	    <?php } ?>
	    </tbody></table>
    </form>
    <?
} catch (fEmptySetException $e) {
	?>
	<div class="info">
		There are currently no Tattle Dashboards available for your account with this filter. <a href="<?=Dashboard::makeURL('add'); ?>">Add one now</a> or
		<form method="post" id="formImport" action="<?=Dashboard::makeURL('import'); ?>" enctype="multipart/form-data" class="inline no-margin" style="padding-left: 10px;">
			<input type="hidden" value="<?= $filter_group_id?>" name="filter_group_id" />
			<p class="inline">
				<a href="#" onclick="if(test_file_present()){$('#formImport').submit()};return false;" class="btn primary">Import</a>
				this one :
				<input type="file" name="uploadedfile" id="fileInput" />
			</p>
		</form>
		<p class="pull-right">
			Filter group :
			<select id="list_of_filters" onclick="$(location).attr('href',$('#list_of_filters').val());return false;">
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
