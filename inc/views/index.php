<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
try {
        $results->tossIfNoRows();
	?>
  <table class="zebra-striped">
    <thead>
    <tr>    
    <th>Check</th>
    <th>Latest Status</th>
    <th>Last Alert</th>
    <th>Alert Count</th>
    <th>Action</th>
       </tr></thead><tbody>    
	<?php
	$first = TRUE;
	foreach ($results as $row) {
          $check = new Check($row['check_id']);
		?>
    	<tr>
        <td><?=$row['name']; ?></td>
        <td><?=($row['status'] == 2 ? 'Warning' : 'Error'); ?></td>
        <td><?=$row['timestamp']; ?></td>
	<td><?=$row['count']; ?></td>
        <td><a href="<?=CheckResult::makeURL('list', $check); ?>">View</a> | <a href="<?=CheckResult::makeURL('ackAll', $check); ?>">Clear</a>
        </td>
        </tr>
    <?php }
    //check to see if paging is needed
    $total_pages = ceil($alert_count / $GLOBALS['PAGE_SIZE']);
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
    </tbody></table>
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
    <? }
} catch (fNoRowsException $e) {
	?>
	<p class="info">There are currently no Alerts based on your subscriptions. Smile, looks like everything is happy!</p>
        <p class="warn">This could also mean that you haven't set up the cronjob to poll the processor? <a href="processor.php">Processor</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
