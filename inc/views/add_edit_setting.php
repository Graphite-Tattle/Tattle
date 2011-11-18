<?
$page_title = ($action == 'add' ? 'Override setting' : 'Edit Setting');
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => 'Settings', 'url' => Setting::makeURL('list'),'active' => false);
$breadcrumbs[] = array('name' => $page_title, 'url' => fURL::getWithQueryString(),'active'=> true);
//$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
$query_string = '';
if (isset($setting_name)) {
  $query_string = "&setting_name=$setting_name";
}
if (isset($user_id)) {
  $query_string .="&user_id=$user_id";
}
if (isset($setting_type)) {
  $query_string .="&setting_type=$setting_type";
}
?>
  <div class="row">
    <div class="span6">
      <form action="<?=fURL::get(); ?>?action=<?=$action.$query_string; ?>" method="post">
        <div class="main" id="main">
          <fieldset>
                <div class="clearfix">
	      <label for="line-friendly_name">Name<em>*</em></label>
              <div class="input">
	        <?=$setting->encodeFriendlyName(); ?>
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="line-value">Value<em>*</em></label>
              <div class="input">             
	        <input id="line-value" class="span3" type="text" size="30" name="value" value="<?=$setting->encodeValue(); ?>" />
              </div>
            </div><!-- /clearfix -->
        <div class="actions">
	      <input class="btn primary" type="submit" value="Save" />
	       <? if($action == 'edit') { ?>
	      <input class="btn" type="submit" name="action::delete" value="Delete" />
              <? } ?>
              <div class="required"><em>*</em> Required field</div>
	      <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
            </div>
         </fieldset>
       </div>       
     </form>
    </div>
    <div class="span10"> 
    </div>
  </div>
</div>
<?php
$tmpl->place('footer');        
