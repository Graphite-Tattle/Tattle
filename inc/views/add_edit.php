<?php
$page_title = ($action == 'add' ? 'Add a Check' : 'Editing : ' . $check->encodeName());
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => $page_title, 'url' => ($action == 'add' ? Check::makeURL($action,$check_type) : Check::makeURL($action,$check_type,$check)), 'active' => true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->set('addeditdocready', true);
$tmpl->place('header');
?>
<script type="text/javascript">
	function expand_form () {
		if ($("#check_graph").is(':hidden')) {
			$("#check_form input[type=text],#check_form select").each(function(){
				$(this).removeClass("span10");
			});
			$('#check_form').addClass("span4").removeClass("span12");
			$('#check_form form').removeClass("form-horizontal");
			$("#expansion_btn").html("Hide the graph");
			$("#check_graph").show();
		} else {
			$("#check_form input[type=text],#check_form select").each(function(){
				$(this).addClass("span10");
			});
			$('#check_form').addClass("span12").removeClass("span4");
			$('#check_form form').addClass("form-horizontal");
			$("#expansion_btn").html("Show the graph");
			$("#check_graph").hide();
		}
	}

	function show_or_hide_period() {
		var checked = $('input[name=all_the_time_or_period]:checked').val();
		if (checked == 'all_the_time') {
			$("#define_period").hide();
		} else {
			$("#define_period").show();
		}
	}

	function compute_period () {
		var explanation = "This check is available ";
		if (!$("input[name=no_time_filter]").is(":checked")) {
			var start_hr = parseInt($("select[name=start_hr]").val());
			var start_min = parseInt($("select[name=start_min]").val());
			var end_hr = parseInt($("select[name=end_hr]").val());
			var end_min = parseInt($("select[name=end_min]").val());
			if ((start_hr != end_hr) || (start_min != end_min)) {
				explanation += "from "  + (start_hr<10?"0":"") + start_hr
										+ ":"
										+ (start_min<10?"0":"") + start_min
										+ " to "
										+ (end_hr<10?"0":"") + end_hr
										+ ":"
										+ (end_min<10?"0":"") + end_min;
				if ((start_hr > end_hr)||((start_hr == end_hr) && (start_min > end_min))) {
					explanation += " of the next day";
				}
			} else {
				explanation += "the whole day";
			}
		} else {
			explanation += "the whole day";
		}
		explanation += " and ";
		if (!$("input[name=no_day_filter]").is(":checked")) {
			var array_days = ["sun", "mon", "tue", "wed", "thu", "fri", "sat"];
			var array_days_display = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "satursday"];
			var start_day = array_days.indexOf($("select[name=start_day]").val());
			var end_day = array_days.indexOf($("select[name=end_day]").val());
			if (start_day != end_day) {
				explanation += "from " + array_days_display[start_day] + " to " + array_days_display[end_day];
				if (start_day > end_day) {
					explanation += " of the next week";
				}
			} else {
				explanation += "the whole week";
			}
		} else {
			explanation += "the whole week";
		}
		$("#explanation").html(explanation);
	}
	$(function(){
		compute_period();
		<?php if ($action == "add") { ?>
			expand_form ();
		<?php } ?>
		$("#define_period").change(function(){
			compute_period();
		});
	});
</script>
<?php if ($action == "edit") { ?>
<a href="#" id="expansion_btn" class="btn btn-primary" onclick="expand_form();return false;">Hide the graph</a>
<?php } ?>
  <div class="row">
    <div id="check_form" class="<?=($action == 'edit')?"span4":"span12"?>">
      <form <?=($action == 'add')?"class='form-horizontal'":""?> action="?action=<?=$action; ?>&type=<?=$check_type; ?>&check_id=<?=$check_id; ?>" method="post">
          <fieldset>
          	<legend class="no-margin">Main</legend>
            <div class="control-group">
              <label class="masterTooltip control-label" title="Name used to identify this check" for="check-name">Name<em>*</em></label>
              <div class="controls">
                <input id="check-name" type="text" size="30" name="name" value="<?=$check->encodeName(); ?>" />
              </div>
            </div>
            <div class="control-group">
              <label class="masterTooltip control-label" title="The path of your new Graph Target to check up on" for="check-target">Graphite Target<em>*</em></label>
              <div class="controls">
                <input id="check-target" type="text" size="30" name="target" value="<?=$check->encodeTarget(); ?>" />
              </div>
            </div>
            <div class="control-group">
              <label class="masterTooltip control-label" title="The threshold level at which an Error will be triggered" for="check-error">Error Threshold<em>*</em></label>
              <div class="controls">
                <input id="check-error" type="text" name="error" value="<?=$check->encodeError(); ?>" />
              </div>
            </div>
            <div class="control-group">
              <label class="masterTooltip control-label" title="The threshold level at which a Warning will be triggered" for="check-warn">Warn Threshold<em>*</em></label>
              <div class="controls">
                <input id="check-warn" type="text" name="warn" value="<?=$check->encodeWarn(); ?>" />
              </div>
            </div>
         </fieldset>
         <fieldset>
         	<legend class="no-margin">Period</legend>
         	<?php 
         		$hour_start = $check->getHourStart();
         		$day_start = $check->getDayStart();
         		$has_period = (!empty($hour_start)) || (!empty($day_start));
         	?>
         	<div class="control-group">
         		<div class="controls">
         			<label class="radio">
         				<input type="radio" 
         				name="all_the_time_or_period" 
         				value="all_the_time" 
         				onclick="show_or_hide_period();"
         				<?= $has_period?"":"checked='checked'"?>/>
         				All the time
         			</label>
         			<label class="radio">
         				<input type="radio" 
         				name="all_the_time_or_period" 
         				value="period" 
         				onclick="show_or_hide_period();"
         				<?= !$has_period?"":"checked='checked'"?>/>
         				A defined period
         			</label>
         		</div>
         	</div>
         	<div id="define_period"  <?=(!$has_period)?"style='display:none;'":""?>>
	         	<div class="control-group">
	         		<?php 
	         			if ($has_period) {
							if (!empty($hour_start)) {
								$hour_end = $check->getHourEnd();
								$start_hr = explode(":", $hour_start)[0];
								$start_min = explode(":", $hour_start)[1];
								$end_hr = explode(":", $hour_end)[0];
								$end_min = explode(":", $hour_end)[1];
							} else {
								$start_hr = 0;
								$start_min = 0;
								$end_hr = 0;
								$end_min = 0;
							}
							if (!empty($day_start)) {
								$start_day = $day_start;
								$end_day = $check->getDayEnd();
							} else {
								$start_day = "mon";
								$end_day = "mon";
							}
						} else {
							$start_hr = 0;
							$start_min = 0;
							$end_hr = 23;
							$end_min = 59;
							$start_day = "sun";
							$end_day = "sat";
						}
	         		?>
	         		<div class="controls">
	         			<label class="checkbox">
	         				<input type="checkbox" 
	         				name="no_time_filter" 
	         				onclick="$(this).is(':checked')?$('#time_filters').hide():$('#time_filters').show()"
	         				<?=($has_period && empty($hour_start))?"checked='checked'":""?>
	         				/>
	         				Don't filter the time
	         			</label>
	         		</div>
	         		<div id="time_filters" <?=($has_period && empty($hour_start))?"style='display:none;'":""?>>
		         		<label class="control-label">Start time :</label>
		         		<div class="controls">
		         			<select class="span1" name="start_hr">
		         				<?php 
		         					for ($i=0;$i<24;$i++) {
										fHTML::printOption(($i<10?"0":"").$i, $i,$start_hr);
									}
		         				?>
		         			</select>&nbsp;:&nbsp;
		         			<select class="span1" name="start_min">
		         				<?php 
		         					for ($i=0;$i<60;$i++) {
										fHTML::printOption(($i<10?"0":"").$i, $i,$start_min);
									}
		         				?>
		         			</select>
		         		</div>
		         		<label class="control-label">End time :</label>
		         		<div class="controls">
		         			<select class="span1" name="end_hr">
		         				<?php 
		         					for ($i=0;$i<24;$i++) {
										fHTML::printOption(($i<10?"0":"").$i, $i,$end_hr);
									}
		         				?>
		         			</select>&nbsp;:&nbsp;
		         			<select class="span1" name="end_min">
		         				<?php 
		         					for ($i=0;$i<60;$i++) {
										fHTML::printOption(($i<10?"0":"").$i, $i,$end_min);
									}
		         				?>
		         			</select>
		         		</div>
	         		</div>
	         	</div>
	         	<div class="control-group">
	         		<div class="controls">
	         			<label class="checkbox">
	         				<input type="checkbox" 
	         				name="no_day_filter" 
	         				onclick="$(this).is(':checked')?$('#day_filters').hide():$('#day_filters').show()"
	         				<?=($has_period && empty($day_start))?"checked='checked'":""?>
	         				/>
	         				Don't filter the day
	         			</label>
	         		</div>
	         		<div id="day_filters" <?=($has_period && empty($day_start))?"style='display:none;'":""?>>
		         		<label class="control-label">Start day of week :</label>
		         		<div class="controls">
		         			<select class="span3" name="start_day">
		         				<?php 
		         					$days = array(
											"Sunday" => "sun",
		         							"Monday" => "mon",
											"Tuesday" => "tue",
											"Wednesday" => "wed",
											"Thursday" => "thu",
											"Friday" => "fri",
											"Satursday" => "sat"
		         					);
		         					foreach ($days as $print => $value) {
										echo "<option value='".$value."'".(($value == $start_day)?" selected='selected'":"").">".$print."</option>";
									}
		         				?>
		         			</select>
		         		</div>
		         		<label class="control-label">End day of week :</label>
		         		<div class="controls">
		         			<select class="span3" name="end_day">
		         				<?php 
									foreach ($days as $print => $value) {
										echo "<option value='".$value."'".(($value == $end_day)?" selected='selected'":"").">".$print."</option>";
									}
		         				?>
		         			</select>
		         		</div>
		         	</div>
	         	</div>
	         	<div class="control-group">
	         		<div class="controls">
	         			<em id="explanation" class="text-info"></em>
	         		</div>
	         	</div>
	         </div>
         </fieldset>
         <fieldset>
            <legend class="no-margin">Advanced</legend>
            <div class="control-group">
              <label class="masterTooltip control-label" title="Number of data points to use when calculating the moving average. Each data point spans one minute" for="check-sample">Sample Size in Minutes<em>*</em></label>
              <div class="controls">
                <input id="check-warn" type="text" name="sample" value="<?=$check->encodeSample(); ?>" />
              </div>
            </div><!-- /clearfix -->
            <div class="control-group">
              <label class="masterTooltip control-label" title="Over will trigger an alert when the value retrieved from Graphite is greater than the warning or error threshold. Under will trigger an alert when the value retrieved from Graphite is less than the warning or the error threshold" for="check-over_under">Over/Under<em>*</em></label>
              <div class="controls">
                <select name="over_under">
                <?
                  foreach ($over_under_array as $value => $text) {
                    fHTML::printOption($text, $value, $check->getOverUnder());
                  }
                ?>
                </select>
              </div>
            </div><!-- /clearfix -->
            <div class="control-group">
             <label class='masterTooltip control-label' title="Public checks can be subscribed to by any user while private checks remain hidden from other users" for="check-visibility">Visibility<em>*</em></label>
             <div class="controls">
               <select name="visibility">
               <?
                foreach ($visibility_array as $value => $text) {
                    fHTML::printOption($text, $value, $check->getVisibility());
                }
?>
               </select>
             </div>
           </div><!-- /clearfix -->
           <div class="control-group">
             <label class="masterTooltip control-label" title="After an alert is triggered, the number of minutes to wait before sending another one" for="check-repeat_delay">Repeat Delay<em>*</em></label>
             <div class="controls">
<?php
                $check_delay = (is_null($check->getRepeatDelay()) ? 30 : $check->encodeRepeatDelay());
?>
                <input id="check-repeat_delay" type="text" size="20" name="repeat_delay" value="<?=$check_delay; ?>" />
              </div>
           </div>
           <div class="control-group">
             <label class="masterTooltip control-label" title="The group to classify in" for="check-group">Group</label>
             <div class="controls">
            	<select name="group_id">
            		<?php 
            			foreach (Group::findAll() as $group) {
							fHTML::printOption($group->getName(), $group->getGroupId(), ($action == 'add')?$filter_group_id:$check->getGroupId());
						}
            		?>
            	</select>
             </div>
           </div>
             <div class="control-group actions">
            <div class="controls">
             <input class="btn btn-primary" type="submit" value="Save" />
             <? if ($action == 'edit') { ?><a href="<?=Check::makeURL('delete', $check_type, $check); ?>" class="btn" >Delete</a><?php } ?>
             <div class="required"><em>*</em> Required field</div>
             <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
<?php if ($action == 'add') { ?>
             <input type="hidden" name="user_id" value="<?=fSession::get('user_id'); ?>" />
             <input type="hidden" name="type" value="<?=$check_type; ?>" />
<?php } ?>
           </div>
           </div>
         </fieldset>
     </form>
    </div>
    <div id="check_graph" class="span8">
      <?php if ($action == 'edit') { ?>
        <div class="sidebar" id="sidebar">
          <fieldset>
            <p>Check : <?=$check->prepareName(); ?></p>
            <p>Target : <?=Check::constructTarget($check); ?></p>
            <p id="graphiteGraph"><?=Check::showGraph($check); ?></p>
            <input class="btn btn-primary" type="submit" value="Reload Graph" onClick="reloadGraphiteGraph()"/>
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
