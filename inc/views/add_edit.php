<?php
$page_title = ($action == 'add' ? 'Add a Check' : 'Editing : ' . $check->encodeName());
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => $page_title, 'url' => fURL::get(), 'active' => true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
?>
<script language="javascript">
  $(document).ready(function() {
    $("fieldset.startCollapsed").collapse( { closed: false } );
    reloadGraphiteGraph();
  });

  function reloadGraphiteGraph() {
    var imageURL = document.images['renderedGraphImage'].src;
    document.images['renderedGraphImage'].src = "";
    if(imageURL.indexOf("?preventCaching=") === -1 && imageURL.indexOf("&preventCaching=") === -1) {
      imageURL = imageURL + "&preventCaching=" + (new Date()).getTime(); 
    }
    else {
      preventCachingRegex = /([?|&]preventCaching=)[^\&]+/;
      imageURL = imageURL.replace(preventCachingRegex, '$1' + (new Date()).getTime());
    }
    if(imageURL.indexOf("?from=") === -1 && imageURL.indexOf("&from=") === -1) {
      imageURL = imageURL + "&from=" + document.getElementById("graphiteDateRange").value;
    }
    else {
      graphDateRangeRegex = /([?|&]from=)[^\&]+/;
      imageURL = imageURL.replace(graphDateRangeRegex, '$1' + document.getElementById("graphiteDateRange").value);
    }
    document.images['renderedGraphImage'].src = imageURL; 
  }
</script>
  <div class="row">
    <div class="span4">
      <form class="form-stacked" action="<?=fURL::get(); ?>?action=<?=$action; ?>&check_id=<?=$check_id; ?>" method="post">
        <div class="main" id="main">
          <fieldset>
            <div class="clearfix">
	      <label for="check-name">Name<em>*</em></label>
              <div class="input">
	        <input id="check-name" class="span3" type="text" size="30" name="name" value="<?=$check->encodeName(); ?>" />
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
	      <label for="check-target">Graphite Target<em>*</em></label>
              <div class="input">
                <input id="check-target" class="span3" type="text" size="30" name="target" value="<?=$check->encodeTarget(); ?>" />
              </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	      <label for="check-error">Error Threshold<em>*</em></label>
              <div class="input">
	        <input id="check-error" class="span3" type="text" name="error" value="<?=$check->encodeError(); ?>" />
	      </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	      <label for="check-warn">Warn Threshold<em>*</em></label>
              <div class="input">
                <input id="check-warn" class="span3" type="text" name="warn" value="<?=$check->encodeWarn(); ?>" />   
	      </div>
            </div><!-- /clearfix -->
         </fieldset>
         <fieldset class="startCollapsed">
            <legend>Advanced</legend>
            <div class="clearfix">
              <label for="check-sample">Sample Size in Minutes<em>*</em></label>
              <div class="input">
                <input id="check-warn" class="span3" type="text" name="sample" value="<?=$check->encodeSample(); ?>" />
	      </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	      <label for="check-over_under">Over/Under<em>*</em></label>
              <div class="input">
                <select name="over_under" class="span3">
                <?
                  foreach ($over_under_array as $value => $text) {
                    fHTML::printOption($text, $value, $check->getOverUnder());
                  }
                ?>
                </select>
              </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	     <label for="check-visibility">Visibility<em>*</em></label>
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
	     <label for="check-repeat_delay">Repeat Delay<em>*</em></label>
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
	     <? if ($action == 'edit') { ?><a href="<?=Check::makeURL('delete', $check); ?>" class="btn" >Delete</a><?php } ?>
	     <div class="required"><em>*</em> Required field</div>
	     <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
<?php if ($action == 'add') { ?>
             <input type="hidden" name="user_id" value="<?=fSession::get('user_id'); ?>" />
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
            <p>Target : <?='movingAverage(' . $check->prepareTarget() . ',' . $check->prepareSample() . ')'; ?></p>
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
