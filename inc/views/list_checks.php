<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$active_tab_alerts = " class=active";
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	$checks->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>
<a class="small btn primary" href="<?= Check::makeUrl('add');?>">Add Check</a>
<table class="zebra-striped">
          <thead>
		<tr>
	<th>Name</th>
    <th>Target</th>
    <th>Warn</th>
    <th>Error</th>
    <th>Sample</th>
    <th>Baseline</th>
    <th>Over/Under</th>
    <th>Visibility</th>
    <th>Action</th>
       </tr></thead><tbody>    
	<?php
	$first = TRUE;
	foreach ($checks as $check) {
	?>
    	<tr>
        
        <td><?php echo '<a href="' . CheckResult::makeUrl('list',$check) . '">' . $check->prepareName() ?></a></td>
        <td><?php echo $check->prepareTarget() ?></td>
        <td><?php echo $check->prepareWarn() ?></td>
        <td><?php echo $check->prepareError() ?></td>
        <td><?php echo $check->prepareSample() ?></td>
        <td><?php echo $check->prepareBaseline() ?></td>
        <td><?php echo $over_under_array[$check->getOver_Under()] ?></td>
        <td><?php echo $visibility_array[$check->getVisibility()] ?></td>
        <td><?php if (fSession::get('user_id') == $check->getUserId()) { 
                    echo '<a href="' . Check::makeURL('edit', $check) . '">Edit</a> |'; 
                  } ?>
        <a href="<?php echo Subscription::makeURL('add', $check) ?>">Subscribe</a></td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no Graphite-Tattle checks. <a href="<?php echo Check::makeURL('add') ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
