<?
$page_title = $action == 'add' ? 'Add a Subscription' : 'Edit Subscription';
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => 'Checks', 'url' => Check::makeUrl('list'),'active' => false);
$breadcrumbs[] = array('name' => $check->getName(),'url'=> Check::makeUrl('edit',$check),'active' => false);
$breadcrumbs[] = array('name' => $page_title , 'url' => fURL::getWithQueryString(),'active' => false);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

$query_string = '';
if (isset($check_id)) {
  $query_string .= "&check_id=$check_id";
} 
if (isset($subscription_id)) {
  $query_string .= "&subscription_id=$subscription_id";  
}
?>
  <div class="row">
    <div class="span4">
      <form class="form-stacked" action="<?=fURL::get(); ?>?action=<?=$action.$query_string; ?>" method="post">
        <div class="main" id="main">
          <fieldset>
            <div class="clearfix">
	      <label for="check-threshold">Alert State<em>*</em></label>
              <div class="input">
                <select name="threshold" class="span3">
                <?
              // We want to standardize on using the same global array, but not allow subscribing to the OK state
                $tmp_status_array = $status_array;
                unset($tmp_status_array[0]);
                foreach ($tmp_status_array as $value => $text) {
                  fHTML::printOption($text, $value, $subscription->getThreshold());
                }
                ?>
                </select>
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="check-method">Send Method<em>*</em></label>
              <div class="input">            
                <select name="method" class="span3">
                <?
                  foreach ($send_methods as $value => $text) {
                    fHTML::printOption($text, $value, $subscription->getMethod());
                  }
                ?>
                </select>
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="check-status">Status<em>*</em></label>
              <div class="input">
                <select name="status" class="span3">
                <?
                 $statuses = array('0'   => 'Enabled', '1' => 'Disabled');
                 foreach ($statuses as $value => $text) {
                   fHTML::printOption($text, $value, $subscription->getStatus());
                 }
                ?>
                </select>            
              </div>
            </div><!-- /clearfix -->
	    <div class="actions">
	      <input class="btn primary" type="submit" value="Save" />
              <?php if ($action == 'edit') { ?> 
                 <a class="btn" href="<?=Subscription::makeUrl('delete',$subscription); ?>">Delete</a>
              <?php } ?>
              <div class="required"><em>*</em> Required field</div>
	      <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
              <input type="hidden" name="user_id" value="<?=fSession::get('user_id'); ?>" />
            </div>
         </fieldset>
       </div>       
     </form>
    </div>
    <div class="span10">   
      <fieldset>
        <p>Check : <?=$check->prepareName(); ?></p>
        <p>Target : <?=$check->prepareTarget(); ?></p>
        <p><?=Check::showGraph($check); ?></p>
      </fieldset>
    </div>
  </div>
</div>
<?php
$tmpl->place('footer');        
