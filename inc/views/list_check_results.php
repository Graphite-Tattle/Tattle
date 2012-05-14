<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$tmpl->set('graphlot',true);
$tmpl->place('header');

 try {
        $check = new Check($check_id);
	$affected = fMessaging::retrieve('affected', fURL::get());
  } catch (fEmptySetException $e) {
?>
        <p class="info">There are currently no Tattle checks. Add a <a href="<?=Check::makeURL('add', 'threshold'); ?>">threshold</a> based or a <a href="<?=Check::makeURL('add', 'predictive'); ?>">predictive</a> based check now.</p>
        <?php
  } ?>
    <fieldset>
      <div style="padding-bottom:15px;">
        <span>Name : <?=$check->prepareName(); ?></span> | 
        <span>Target : <?='movingAverage(' . $check->prepareTarget() . ',' . $check->prepareSample() . ')'; ?></span>
      </div>
      <span><?=Check::showGraph($check,true,'-48hours',620,true); ?></span>
    </fieldset>
<?php
  try {
    $check_results->tossIfEmpty();
    $affectd = fMessaging::retrieve('affected',fURL::get());
   ?>
        <a class="btn small primary" href="<?=CheckResult::makeURL('ackAll', $check = new Check($check_id)); ?>">Clear All</a>
	<table class="zebra-striped">
    <tr>
    <th>Status</th>
    <th>Value</th>
    <th>Error</th>
    <th>Warn</th>
    <th>State</th>
    <th>Time</th>
       </tr>    
	<?php
	$first = TRUE;
	foreach ($check_results as $check_result) {
        $check = new Check($check_result->getCheck_Id());
	?>
    	<tr>
        <td><?=($check_result->prepareStatus() == 2 ? 'Warning' : 'Error'); ?></td>
        <td><?=$check_result->prepareValue(); ?></td>
        <td><?=$check->prepareError(); ?></td>
        <td><?=$check->prepareWarn(); ?></td>
        <td><?=$check_result->prepareState(); ?></td>
        <td><?=$check_result->prepareTimestamp('Y-m-d H:i:s'); ?></td>
        </tr>
    <?php } ?>
    </table></div>
    <?
} catch (fEmptySetException $e) {
	?>
	<p class="info">There are currently no alerts for this checks.</p>
	<?php
}
?>
<?php $tmpl->place('footer') ?>
