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
    <div class="col-md-6">
      <form action="?action=<?=$action.$query_string; ?>" method="post" class="form-horizontal">
          <div class="form-group">
	    	  <label for="group-name" class="col-sm-2 control-label">Name<em>*</em></label>
              <div class="col-sm-10">
       			<input id="group-name" class="form-control" type="text" size="30" name="name" value="<?=$group->encodeName(); ?>" />
              </div>
            </div><!-- /clearfix -->
            <div class="form-group">
              <label for="group-description" class="col-sm-2 control-label">Description<em>*</em></label>
              <div class="col-sm-10">             
                 <textarea class="form-control" id="group-description" name="description" rows="3"><?=$group->encodeDescription(); ?></textarea>
              </div>
            </div>
        <div class="actions form-group">
        	<div class="col-sm-offset-2 col-sm-10">
	      <input class="btn btn-primary" type="submit" value="Save" />
              <input class="btn btn-default" type="submit" name="action::delete" value="Delete" />
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
