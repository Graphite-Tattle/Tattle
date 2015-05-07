<?
$page_title = ($action == 'add' ? 'Add a Line' : 'Edit Line');
$tmpl->set('title', $page_title);
$breadcrumbs[] = array('name' => 'Dashboard', 'url' => Dashboard::makeURL('list'),'active' => false);
//$breadcrumbs[] = array('name' => 'Edit Dashboard', 'url' => Dashboard::makeURL('edit',$graph),'active' => false);
$breadcrumbs[] = array('name' => $graph->prepareName(), 'url' => Graph::makeURL('edit',$graph),'active'=> false);
$breadcrumbs[] = array('name' => $page_title, 'url' => '?'.fURL::getQueryString(),'active'=> true);
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
$query_string = '';

if (isset($line_id)) {
  $query_string = "&line_id=$line_id";
} 
if (isset($graph_id) && $action != 'edit') {
  $query_string .= "&graph_id=$graph_id";  
}

?>
  <div class="row">
    <div class="col-md-6">
      <form action="?action=<?=$action.$query_string; ?>" method="post" class="form-horizontal">
           <div class="form-group">
	        <label for="line-alias" class="col-sm-2 control-label">Alias</label>
              <div class="col-sm-10">
	       		<input id="line-alias" class="form-control" type="text" size="30" name="alias" value="<?=$line->encodeAlias(); ?>" />
              </div>
            </div>
            <div class="form-group">
              <label for="line-target" class="col-sm-2 control-label">Target<em>*</em></label>
              <div class="col-sm-10">             
	     	   <input id="line-target" class="form-control" type="text" size="30" name="target" value="<?=$line->encodeTarget(); ?>" />
              </div>
            </div>
            <div class="form-group">
              <label for="line-color" class="col-sm-2 control-label">Line Color</label>
              <div class="col-sm-10">             
                  <input id="line-color" class="form-control" type="text" size="30" name="color" value="<?=$line->encodeColor(); ?>" />
              </div>
            </div>
            <div class="form-group actions">            
      		  <div class="col-sm-offset-2 col-sm-10">
	   		   <input class="btn btn-primary" type="submit" value="Save" />
              <input class="btn btn-default" type="submit" name="action::delete" value="Delete" />
              <div class="required"><em>*</em> Required field</div>
 		     <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
           </div>
        </div>
     </form>
    </div>
    <div class="col-md-10"> 
    </div>
  </div>
</div>
<?php
$tmpl->place('footer');        
