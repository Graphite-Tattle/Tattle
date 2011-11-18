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
    <?php } ?>
    </tbody></table>
    <?
} catch (fNoRowsException $e) {
	?>
	<p class="info">There are currently no Alerts based on your subscriptions. Smile, looks like everything is happy!</p>
        <p class="warn">This could also mean that you haven't setup the cronjob to poll the processor? <a href="processor.php">Processor</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
