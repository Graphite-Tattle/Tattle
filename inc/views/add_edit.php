<?php
$page_title = ($action == 'add' ? 'Add a Check' : 'Editing : ' . $check->encodeName());
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => $page_title, 'url' => fURL::get(), 'active' => true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
?>
<script>
  $(document).ready(function() {
    $("fieldset.startCollapsed").collapse( { closed: true } );
  });
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
              <label for="check-sample">Sample Size<em>*</em></label>
              <div class="input">
                <select name="check-sample" class="span3">
                <?
                  $statuses = array('-5minutes'   => '5 Minutes', '-10minutes' => '10 Minutes');
                  foreach ($statuses as $value => $text) {
                    fHTML::printOption($text, $value, $check->getSample());
                  }
                 ?>
                </select>
              </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	      <label for="check-baseline">Baseline<em>*</em></label>
              <div class="input">
                <select name="check-baseline" class="span3">
              <?
                $statuses = array('average'   => 'average', 'median' => 'median');
                foreach ($statuses as $value => $text) {
                  fHTML::printOption($text, $value, $check->getBaseline());
                }
              ?>
              </select>
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
	     <? if ($action == 'edit') { ?><a href="<?=Check::makeURL('delete',$check); ?>" class="btn" >Delete</a><?php } ?>
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
            <p>Target : <?=$check->prepareTarget(); ?></p>
            <p><?=Check::showGraph($check); ?></p>
          </fieldset>
        </div>
      <?php } ?>
    </div>
</div>
</div>
<?php
$tmpl->place('footer');
