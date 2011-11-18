<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
        $results->tossIfNoRows();
	?>
<span class="span10">
  <table class="zebra-striped">
          <thead>
    <tr>    
    <th>Check</th>
    <th>Latest Status</th>
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
        <td><?=$row['count']; ?></td>
        <td><a href="<?=CheckResult::makeURL('list', $check); ?>">View</a>
        </td>
        </tr>
    <?php } ?>
    </tbody></table></span>
    <?
} catch (fNoRowsException $e) {
	?>
	<p class="info">There are currently no Alerts based on your subscriptions. Smile, looks like everything is happy!</p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
