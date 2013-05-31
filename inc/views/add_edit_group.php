<?
$page_title = ($action == 'add' ? 'Add a Group' : 'Edit Group');
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => $page_title, 'url' => '?'.fURL::getQueryString(),'active'=> true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
$query_string = '';

if (isset($group_id)) {
  $query_string = "&group_id=$group_id";
} 

?>
  <div class="row">
    <div class="span6">
      <form action="?action=<?=$action.$query_string; ?>" method="post">
        <div class="main" id="main">
          <fieldset>
                <div class="clearfix">
	      <label for="group-name">Name<em>*</em></label>
              <div class="input">
	        <input id="group-name" class="span3" type="text" size="30" name="name" value="<?=$group->encodeName(); ?>" />
              </div>
            </div><!-- /clearfix -->
            <div class="clearfix">
              <label for="group-description">Description<em>*</em></label>
              <div class="input">             
                 <textarea class="span3" id="group-description" name="description" rows="3"><?=$group->encodeDescription(); ?></textarea>
              </div>
            </div>
        <div class="actions">
	      <input class="btn primary" type="submit" value="Save" />
              <input class="btn" type="submit" name="action::delete" value="Delete" />
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
