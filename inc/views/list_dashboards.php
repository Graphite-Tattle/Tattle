<?php
$tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	$dashboards->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?><a href="<?php Dashboard::makeURL('add'); ?>" class="btn primary">Add Dashboard</a>
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
        <td><?php echo $dashboard->prepareName() ?></td>
        <td><?php echo $dashboard->prepareDescription() ?></td>
        <td><?php echo $dashboard->prepareColumns() ?></td>
        <td><?php echo $dashboard->prepareBackgroundColor() ?></td>
        <td>
        <a href="<?php echo Dashboard::makeURL('view', $dashboard) ?>">View</a> |
        <a href="<?php echo Dashboard::makeURL('edit', $dashboard) ?>">Edit</a> |
        <a href="<?php echo Dashboard::makeURL('delete', $dashboard) ?>">Delete</a></td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Tattle Dashboards available for your account. <a href="<?php echo Dashboard::makeURL('add') ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
