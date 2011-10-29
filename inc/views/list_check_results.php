<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$tmpl->set('graphlot',true);
$tmpl->place('header');

 try {
        $check = new Check($check_id);
	$affected = fMessaging::retrieve('affected', fURL::get());
  } catch (fEmptySetException $e) {
?>
        <p class="info">There are currently no Tattle checks. <a href="<?php echo Check::makeURL('add') ?>">Add one now</a></p>
        <?php

  }
	?>

<fieldset>
        <span>Name : <?php echo $check->prepareName(); ?></span> | 
        <span>Target : <?php echo $check->prepareTarget(); ?></span>
        <div class="graphite">
                <div id="canvas" style="padding:1px">
                    <div id="graphcontainer" style="float:left;">
                        <div id="graph" style="width:600px;height:300px"></div>
                        <div id="overview" style="width:600px;height:66px"></div>
                    </div>
                     <p style="clear:left">&nbsp</p>

                      <div class="metricrow" style="display:none">
                         <span id="target" class="metricName"><?php echo $check->prepareTarget();?></span>.
                         <span id="error_threshold"><?php echo $check->prepareError();?></span>
                         <span id="warn_threshold"><?php echo $check->prepareWarn();?></span>
                         <span id="check_id"><?php echo $check->prepareCheckId();?></span> 
                      </div>

            </div>

        </div>
<!--<span><?php echo Check::showGraph($check); ?></span>
          <span><?php echo Check::showGraph($check,true,'-24Hours',320,true); ?></span> -->
    </fieldset>
<?php
  try {
    $check_results->tossIfEmpty();
    $affectd = fMessaging::retrieve('affected',fURL::get());
   ?>
        <a class="btn small primary" href="<?php echo CheckResult::makeURL('ackAll', $check = new Check($check_id)) ?>">Clear All</a>
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
        <td><?php echo ($check_result->prepareStatus() == 2 ? 'Warning' : 'Error'); ?></td>
        <td><?php echo $check_result->prepareValue() ?></td>
        <td><?php echo $check->prepareError() ?></td>
        <td><?php echo $check->prepareWarn() ?></td>
        <td><?php echo $check_result->prepareState() ?></td>
        <td><?php echo $check_result->prepareTimestamp('Y-m-d H:i:s'); ?></td>
        <?php /*<div id="graphite-event-modal_<?php echo $check_result->getResult_Id(); ?>" class="modal hide fade">
            <div class="modal-header">
              <a href="#" class="close">&times;</a>
              <h3>view Event</h3>
            </div>
            <div class="modal-body">
              Event Details
            </div>
          </div>*/ ?>
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
