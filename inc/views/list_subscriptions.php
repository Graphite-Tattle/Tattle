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
    //check to see if paging is needed
    $total_pages = ceil($subscriptions->count(TRUE) / $GLOBALS['PAGE_SIZE']);
    if ($total_pages > 1) {
      $prev_class = 'previous';
      $prev_link = fURL::get() . '?page=' . ($page_num - 1);
      $next_class = 'next';
      $next_link = fURL::get() . '?page=' . ($page_num + 1);
      if ($page_num == 1) {
        $prev_class .= ' disabled';
        $prev_link = '#';
      } elseif ($page_num == $total_pages) {
        $next_class .= ' disabled';
        $next_link = '#';
      }
      ?>
      <div class="pagination">
        <ul class="pager">
          <li class="<?=$prev_class; ?>">
            <a href="<?=$prev_link; ?>">&larr; Previous</a>
          </li>
          <li class="<?=$next_class; ?>">
            <a href="<?=$next_link; ?>">Next &rarr;</a>
          </li>
        </ul>
      </div>
    <?php }
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Tattle check subscriptions for your account. <a href="<?=Check::makeURL('list'); ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
