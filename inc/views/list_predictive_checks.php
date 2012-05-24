<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$active_tab_alerts = " class=active";
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	$checks->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>

<script type="text/javascript">
$(document).ready(function() {
  attachTooltips();
});
</script>

<a class="small btn primary" href="<?= Check::makeURL('add', $check_type);?>">Add Check</a>
<table class="zebra-striped">
          <thead>
		<tr>
    <th><?=fCRUD::printSortableColumn('name','Name'); ?></th>
    <th class="masterTooltip" title="Graph Target that will be checked in Graphite"><?=fCRUD::printSortableColumn('target','Target'); ?></th>
    <th class="masterTooltip" title="The threshold level at which a Warning will be triggered"><?=fCRUD::printSortableColumn('warn','Warn'); ?></th>
    <th class="masterTooltip" title="The threshold level at which an Error will be triggered"><?=fCRUD::printSortableColumn('error','Error'); ?></th>
    <th><?=fCRUD::printSortableColumn('regression_type','Regression Type'); ?></th>
    <th><?=fCRUD::printSortableColumn('number_of_regressions','Number of Regressions'); ?></th>
    <th class="masterTooltip" title="Number of data points to use when calculating the moving average. Each data point spans one minute"><?=fCRUD::printSortableColumn('sample','Sample'); ?></th>
    <th><?=fCRUD::printSortableColumn('baseline','Baseline'); ?></th>
    <th class="masterTooltip" title="Over will trigger an alert when the value retrieved from Graphite is greater than the warning or error threshold. Under will trigger an alert when the value retrieved from Graphite is less than the warning or the error threshold"><?=fCRUD::printSortableColumn('over_under','Over/Under'); ?></th>
    <th class="masterTooltip" title="Public checks can be subscribed to by any user while private checks remain hidden from other users"><?=fCRUD::printSortableColumn('visibility','Visibility'); ?></th>
    <th>Action</th>
       </tr></thead><tbody>    
	<?php
	$first = TRUE;
	foreach ($checks as $check) {
	?>
    	<tr>
        <td><?='<a href="' . CheckResult::makeUrl('list',$check) . '">' . $check->prepareName(); ?></a></td>
        <td><?=$check->prepareTarget(); ?></td>
        <td><?=$check->prepareWarn(); ?></td>
        <td><?=$check->prepareError(); ?></td>
        <td><?=$check->prepareRegressionType(); ?></td>
        <td><?=$check->prepareNumberOfRegressions(); ?></td>
        <td><?=$check->prepareSample(); ?></td>
        <td><?=$check->prepareBaseline(); ?></td>
        <td><?=$over_under_both_array[$check->getOver_Under()]; ?></td>
        <td><?=$visibility_array[$check->getVisibility()]; ?></td>
        <td><?php if (fSession::get('user_id') == $check->getUserId()) { 
                    echo '<a href="' . Check::makeURL('edit', $check_type, $check) . '">Edit</a> |'; 
                  } ?>
        <a href="<?=Subscription::makeURL('add', $check); ?>">Subscribe</a></td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no <?=$check_type?> based checks. <a href="<?=Check::makeURL('add', $check_type); ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
