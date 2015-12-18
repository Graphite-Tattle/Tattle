<?php
$tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	$subscriptions->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>
	<table class="table table-bordered table-striped">
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
        <td>
        	<?php $status_sub = $status_array[$subscription->prepareThreshold()]; 
        		if ( 'Error' == $status_sub ) {
        	?>
        		<span class="text-error"><?=$status_sub ?></span>
        	<?php } else {?>
        		<span class="text-warning"><?=$status_sub ?></span>
        	<?php } ?>
        </td>
        <td><?=$subscription->prepareMethod(); ?></td>
        <td>
        	<?php if ($subscription->getStatus()) { ?>
        		<i class="glyphicon glyphicon-warning-sign" style="margin-right:3px"></i><span>Disabled</span>
        	<?php } else {?>
        		<i class="glyphicon glyphicon-" style="margin-right:3px"></i><span>Enabled</span>
        	<?php } ?>
        </td>
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
      $prev_link = fURL::replaceInQueryString('page', $page_num -1 );
      $next_class = 'next';
      $next_link = fURL::replaceInQueryString('page', $page_num + 1);
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
	<p class="info">There are currently no Tattle check subscriptions for your account. Add a <a href="<?=Check::makeURL('list', 'threshold'); ?>">threshold</a> based or a <a href="<?=Check::makeURL('list', 'predictive'); ?>">predictive</a> based subscription now.</p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
