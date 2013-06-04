<?
$page_title = ($action == 'add' ? 'User Settings' : 'Edit Settings');
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => $page_title, 'url' => '?'.fURL::getQueryString(),'active'=> true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
$plugin = fRequest::get('plugin');
if (isset($plugin_settings[$plugin]) && is_array($plugin_settings[$plugin]['settings'])) { 

} else {
//redirect
}

?>
  <div class="row">
    <div class="span6">
      <form action="?" method="post" class="form-horizontal">
        <div class="main" id="main">
          <ul class="tabs">
          <?php foreach($plugin_settings as $plugin_name => $plugin_config) {
              $tab_plugin_settings = $plugin_config['settings'];
              if (is_array($tab_plugin_settings)) {
               echo '<li>' . $plugin_name . '</li>';
              } 
           } ?></ul> 
          <fieldset>
                <div class="control-group">
	      <label for="line-alias" class="control-label">Alias<em>*</em></label>
              <div class="controls">
	        <input id="line-alias" class="span3" type="text" size="30" name="alias" value="<?=$line->encodeAlias(); ?>" />
              </div>
            </div>
            <div class="control-group">
              <label for="line-target" class="control-label">Target<em>*</em></label>
              <div class="controls">             
	        <input id="line-target" class="span3" type="text" size="30" name="target" value="<?=$line->encodeTarget(); ?>" />
              </div>
            </div>
            <div class="control-group">
              <label for="line-color" class="control-label">Line Color<em>*</em></label>
              <div class="controls">             
                  <input id="line-color" class="span3" type="text" size="30" name="color" value="<?=$line->encodeColor(); ?>" />
              </div>
            </div>
        <div class="control-group actions">
        <div class="controls">
	      <input class="btn btn-primary" type="submit" value="Save" />
              <input class="btn" type="submit" name="action::delete" value="Delete" />
              <div class="required"><em>*</em> Required field</div>
	      <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
            </div>
            </div>
         </fieldset>
       </div>       
     </form>
    </div>
  </div>
</form>
</div>
<?php
$tmpl->place('footer');        
