<?php
$tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	$groups->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?><a href="<?=Group::makeURL('add'); ?>" class="btn primary">Add Group</a>
	
	<table class="zebra-striped">
          <thead>
          <tr>    
          <th>Name</th>
          <th>Description</th>
          <th>Action</th>
          </tr>    
          </thead>
          <tbody>
	<?php
	$first = TRUE;
	foreach ($groups as $group) {
		?>
    	<tr>
        <td><?=$group->prepareName(); ?></td>
        <td><?=$group->prepareDescription(); ?></td>
        <td>
        	<?php
        		if ($group->getGroupId() != $GLOBALS['DEFAULT_GROUP_ID']) {
			?>
		        <a href="<?=Group::makeURL('edit', $group); ?>">Edit</a> |
		        <a href="<?=Group::makeURL('delete', $group); ?>">Delete</a>
	       <?php
		    }
		    ?>
        </td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Tattle Groups available. <a href="<?=Group::makeURL('add'); ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
