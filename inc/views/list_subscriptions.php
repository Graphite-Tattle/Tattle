<?php
$tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	$subscriptions->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>
	<table class="zebra-striped">
          <thead>
          <tr>    
          <th>Check</th>
          <th>Alert State</th>
          <th>Method</th>
          <th>Status</th>
          <th>Action</th>
          </tr>    
          </thead>
          <tbody>
	<?php
	$first = TRUE;
	foreach ($subscriptions as $subscription) {
          $check = $subscription->createCheck();      
	?>
    	<tr>
        <td><?php echo $check->prepareName() ?></td>
        <td><?php echo $status_array[$subscription->prepareThreshold()] ?></td>
        <td><?php echo $subscription->prepareMethod() ?></td>
        <td><?php echo ($subscription->getStatus() ? 'Disabled' : 'Enabled') ?></td>
        <td><a href="<?php echo Subscription::makeURL('edit', $subscription) ?>">Edit</a> |
        <a href="<?php echo Subscription::makeURL('delete', $subscription) ?>">Delete</a></td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Tattle check subscriptions for your account. <a href="<?php echo Check::makeURL('list') ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
