<?php
$tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	$dashboards->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?><a href="<?=Dashboard::makeURL('add'); ?>" class="btn primary">Add Dashboard</a>
	<table class="zebra-striped">
          <thead>
          <tr>    
          <th>Name</th>
          <th>Description</th>
          <th>Columns</th>
          <th>Background Color</th>
          <th>Action</th>
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
        <td><?=$dashboard->prepareColumns(); ?></td>
        <td><?=$dashboard->prepareBackgroundColor(); ?></td>
        <td>
        <a href="<?=Dashboard::makeURL('view', $dashboard); ?>">View</a> |
        <a href="<?=Dashboard::makeURL('edit', $dashboard); ?>">Edit</a> |
        <a href="<?=Dashboard::makeURL('delete', $dashboard); ?>">Delete</a> |
        <a href="<?=Dashboard::makeURL('export', $dashboard); ?>" target="_blank">Export</a>
        </td>
        </tr>
    <?php } ?>
    </tbody></table>
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
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Tattle Dashboards available for your account. <a href="<?=Dashboard::makeURL('add'); ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
