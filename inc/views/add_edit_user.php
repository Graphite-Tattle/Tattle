<?php
$tmpl->set('title', $action == 'add' ? 'Add a User' : 'Edit User');
$tmpl->place('header');
?>
  <div class="row">
    <div class="span6">
      <form action="<?=fURL::getWithQueryString(); ?>" method="post">
        <div class="main" id="main">
          <fieldset>
            <div class="clearfix">
	      <label for="user-username">User Name<em>*</em></label>
              <div class="input">
	       <? if ($GLOBALS['ALLOW_HTTP_AUTH']) { 
                  echo $_SERVER['PHP_AUTH_USER']; ?>
                <input id="user-username" class="span3" type="hidden" name="username" value="<?=$_SERVER['PHP_AUTH_USER']; ?>"> 
              <?  } else { ?>
                <input id="user-username" class="span3" type="text" size="30" name="username" value="<?=$user->encodeUsername(); ?>" />
               <? } ?>  
            </div>
            </div><!-- /clearfix -->
	    <div class="clearfix">
	      <label for="user-email">Email<em>*</em></label>
              <div class="input">
                <input id="user-email" class="span3" type="text" size="30" name="email" value="<?=$user->encodeEmail(); ?>" />
	      </div>
            </div><!-- /clearfix -->
            <? if (!$GLOBALS['ALLOW_HTTP_AUTH'] || ($user->getUserId() == 1)) { ?> 
	    <div class="clearfix">
	      <label for="user-password">Password<em>*</em></label>
              <div class="input">
                <input id="user-password" class="span3" type="password" size="30" name="password" value="" />
	      </div>
            </div><!-- /clearfix -->
           <? } ?>
           <div class="actions">
             <input class="btn primary" type="submit" value="Save" />
	     <? if ($action == 'edit') { ?><input class="btn" type="submit" name="action::delete" value="Delete" /><?php } ?>
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
