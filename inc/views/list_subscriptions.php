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
          $check = new Check($subscription->getCheckId());      
	?>
    	<tr>
        <td><?=$check->prepareName(); ?></td>
        <td><?=$status_array[$subscription->prepareThreshold()]; ?></td>
        <td><?=$subscription->prepareMethod(); ?></td>
        <td><?=($subscription->getStatus() ? 'Disabled' : 'Enabled'); ?></td>
        <td><a href="<?=Subscription::makeURL('edit', $subscription); ?>">Edit</a> |
        <a href="<?=Subscription::makeURL('delete', $subscription); ?>">Delete</a></td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Tattle check subscriptions for your account. <a href="<?=Check::makeURL('list'); ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
