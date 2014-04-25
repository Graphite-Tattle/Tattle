<?
$page_title = ($action == 'add' ? 'Override setting' : 'Edit Setting');
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => 'Settings', 'url' => Setting::makeURL('list'),'active' => false);
$breadcrumbs[] = array('name' => $page_title, 'url' => '?'.fURL::getQueryString(),'active'=> true);
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
    <div class="col-md-6">
      <form action="?action=<?=$action.$query_string; ?>" method="post" class="form-horizontal">
          <div class="form-group">
	      <label for="line-friendly_name" class="col-sm-2 control-label">Name<em>*</em></label>
              <div class="col-sm-10">
        		<?=$setting->encodeFriendlyName(); ?>
              </div>
            </div>
            <div class="form-group">
              <label for="line-value" class="col-sm-2 control-label">Value<em>*</em></label>
              <div class="col-sm-10">             
	       		 <input id="line-value" class="form-control" type="text" size="30" name="value" value="<?=$setting->encodeValue(); ?>" />
              </div>
            </div>
        <div class="form-group actions">
        <div class="col-sm-offset-2 col-sm-10">
	      <input class="btn btn-primary" type="submit" value="Save" />
	       <? if($action == 'edit') { ?>
	      <input class="btn btn-default" type="submit" name="action::delete" value="Delete" />
              <? } ?>
              <div class="required"><em>*</em> Required field</div>
	      <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
            </div>
            </div>
     </form>
    </div>
  </div>
</div>
<?php
$tmpl->place('footer');        
