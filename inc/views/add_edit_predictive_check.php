<?php
$page_title = ($action == 'add' ? 'Add a Check' : 'Editing : ' . $check->encodeName());
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => $page_title, 'url' => ($action == 'add' ? Check::makeURL($action,$check_type) : Check::makeURL($action,$check_type,$check)), 'active' => true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->set('addeditdocready',true);
$tmpl->place('header');
?>
</script>
  <div class="row">
    <div class="span4">
      <form class="form-stacked" action="<?=fURL::get(); ?>?action=<?=$action; ?>&type=<?=$check_type; ?>&check_id=<?=$check_id; ?>" method="post">
        <div class="main" id="main">
          <fieldset>
            <div class="clearfix">
	      <label class="masterTooltip" title="Name used to identify this check" for="check-name">Name<em>*</em></label>
              <div class="input">
	        <input id="check-name" class="span3" type="text" size="30" name="name" value="<?=$check->encodeName(); ?>" />
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
	      <label class="masterTooltip" title="The path of your new Graph Target to check up on" for="check-target">Graphite Target<em>*</em></label>
              <div class="input">
                <input id="check-target" class="span3" type="text" size="30" name="target" value="<?=$check->encodeTarget(); ?>" />
              </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	      <label class="masterTooltip" title="Number of acceptable standard deviations before an Error is triggered" for="check-error">Error: Standard Deviations<em>*</em></label>
              <div class="input">
	        <input id="check-error" class="span3" type="text" name="error" value="<?=$check->encodeError(); ?>" />
	      </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	      <label class="masterTooltip" title="Number of acceptable standard deviations before a Warning is triggered" for="check-warn">Warn: Standard Deviations<em>*</em></label>
              <div class="input">
                <input id="check-warn" class="span3" type="text" name="warn" value="<?=$check->encodeWarn(); ?>" />   
	      </div>
            </div><!-- /clearfix -->
         </fieldset>
         <fieldset class="startCollapsed">
            <legend>Advanced</legend>
            <div class="clearfix">
              <label class="masterTooltip" title="Data will be analyized at the same time of day on a daily, weekly or monthly basis. For example, if you recognize a pattern in your data such that it looks similiar on weekly basis, choose the weekly regression type" for="check-regression_type">Regression Type<em>*</em></label>
              <div class="input">
                <select name="regression_type" class="span3">
              <?
                foreach ($regression_type_array as $value => $text) {
                  fHTML::printOption($text, $value, $check->getRegressionType());
                }
              ?>
              </select>
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label class="masterTooltip" title="Given the Regression Type, the number of periods to use when computing the historical analysis for your Predictive Check" for="check-number_of_regressions">Number of Regressions<em>*</em></label>
              <div class="input">
                <select name="number_of_regressions" class="span3">
              <?
                foreach ($number_of_regressions_array as $value => $text) {
                  fHTML::printOption($text, $value, $check->getNumberOfRegressions());
                }
              ?>
              </select>
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label class="masterTooltip" title="Number of data points to use when calculating the average/median. Each data point usually spans one minute. (This is not the case if you use a function like summarize)" for="check-sample">Sample Size<em>*</em></label>
              <div class="input">
                <input id="check-warn" class="span3" type="text" name="sample" value="<?=$check->encodeSample(); ?>" />
	      </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
	      <label class="masterTooltip" title="Method that will be used to calculate the value of the check over the number of sample data points: Average or Median" for="check-baseline">Baseline<em>*</em></label>
              <div class="input">
                <select name="baseline" class="span3">
              <?
                foreach ($average_median_array as $value => $text) {
                  fHTML::printOption($text, $value, $check->getBaseline());
                }
              ?>
              </select>
	      </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	      <label class="masterTooltip" title="Over will trigger an alert when the value retrieved from Graphite is greater than the warning or error threshold. Under will trigger an alert when the value retrieved from Graphite is less than the warning or the error threshold" for="check-over_under">Over/Under/Both<em>*</em></label>
              <div class="input">
                <select name="over_under" class="span3">
                <?
                  foreach ($over_under_both_array as $value => $text) {
                    fHTML::printOption($text, $value, $check->getOverUnder());
                  }
                ?>
                </select>
              </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	     <label class="masterTooltip" title="Public checks can be subscribed to by any user while private checks remain hidden from other users" for="check-visibility">Visibility<em>*</em></label>
             <div class="input">
               <select name="visibility" class="span3">
               <?
                foreach ($visibility_array as $value => $text) {
                  fHTML::printOption($text, $value, $check->getVisibility());
                }
               ?>
               </select>            
             </div>
           </div><!-- /clearfix -->
	   <div class="clearfix">
	     <label class="masterTooltip" title="After an alert is triggered, the number of minutes to wait before sending another one" for="check-repeat_delay">Repeat Delay<em>*</em></label>
             <div class="input">
               <?php 
               $check_delay = (is_null($check->getRepeatDelay()) ? 30 : $check->encodeRepeatDelay()); ?>
	       <input id="check-repeat_delay" class="span3" type="text" size="20" name="repeat_delay" value="<?=$check_delay; ?>" />
	     </div>		   
           </div><!-- /clearfix -->     
           </fieldset>
           <fieldset>
             <div class="actions">
             <input class="btn primary" type="submit" value="Save" />
	     <? if ($action == 'edit') { ?><a href="<?=Check::makeURL('delete', $check_type, $check); ?>" class="btn" >Delete</a><?php } ?>
	     <div class="required"><em>*</em> Required field</div>
	     <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
<?php if ($action == 'add') { ?>
             <input type="hidden" name="user_id" value="<?=fSession::get('user_id'); ?>" />
             <input type="hidden" name="type" value="<?=$check_type; ?>" />
<?php } ?>
           </div>
         </fieldset>
       </div>       
     </form>
    </div>
    <div class="span10">   
      <?php if ($action == 'edit') { ?>
        <div class="sidebar" id="sidebar">
          <fieldset>
            <p>Check : <?=$check->prepareName(); ?></p>
            <p>Target : <?=$check->prepareTarget(); ?></p>
            <p id="graphiteGraph"><?=Check::showGraph($check); ?></p>
            <input class="btn primary" type="submit" value="Reload Graph" onClick="reloadGraphiteGraph()"/>
            <select id="graphiteDateRange" class="span3">
              <? $dateRange = array('-12hours'   => '12 Hours', '-1days' => '1 Day', '-3days' => '3 Days', '-7days' => '7 Days', '-14days' => '14 Days', '-30days' => '30 Days', '-60days' => '60 Days');
                foreach ($dateRange as $value => $text) {
                  fHTML::printOption($text, $value, '-3days');
                }
              ?>
            </select>
          </fieldset>
        </div>
      <?php } ?>
    </div>
</div>
</div>
<?php
$tmpl->place('footer');
