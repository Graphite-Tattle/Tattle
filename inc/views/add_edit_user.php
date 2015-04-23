<?php
$tmpl->set('title', $action == 'add' ? 'Add a User' : 'Edit User');
$tmpl->place('header');
?>
<script type="text/javascript">
	function show_hide_new_password() {
		if ($("#div_new_password").is(":hidden")){
			$("#div_new_password").show();
			$("#btn_change_password").html("Hide change password");
			$("#change_password_hidden_input").val(true);
		} else {
			$("#div_new_password").hide();
			$("#btn_change_password").html("Change password");
			$("#change_password_hidden_input").val(false);
		}
	}
</script>
  <div class="row">
    <div class="col-md-8">
      <form action="?<?=fURL::getQueryString(); ?>" method="post" class="form-horizontal">
        <div class="main" id="main">
          <fieldset>
            <div class="form-group">
		      <label for="user-username" class="col-sm-2 control-label">User Name<em>*</em></label>
	              <div class="col-sm-10">
		      	  <? if ($GLOBALS['ALLOW_HTTP_AUTH']) { 
	                  echo $_SERVER['PHP_AUTH_USER']; ?>
	                <input id="user-username" class=form-control" type="hidden" name="username" value="<?=$_SERVER['PHP_AUTH_USER']; ?>"> 
	              <?  } else { ?>
	                <input id="user-username" class="form-control" type="text" size="30" name="username" value="<?=$user->encodeUsername(); ?>" />
	              <? } ?>  
	            </div>
            </div>
	    <div class="form-group">
	      <label for="user-email" class="col-sm-2 control-label">Email<em>*</em></label>
              <div class="col-sm-10">
                <input id="user-email" class="form-control" type="text" size="30" name="email" value="<?=$user->encodeEmail(); ?>" />
	      </div>
            </div>
            <? if (!$GLOBALS['ALLOW_HTTP_AUTH'] || ($user->getUserId() == 1)) { ?> 
           <? if ($action == 'edit') { ?>  
	    <div class="form-group">
	      <label for="user-password" class="col-sm-2 control-label">Enter your password to confirm the changes<em>*</em></label>
              <div class="col-sm-10">
                <input id="user-password" class="form-control" type="password" size="30" name="password" value="" />
	      </div>
            </div>
            <div id="div_new_password" style="display:none;">
            <hr/>
            <input type="hidden" name="change_password" value="false" id="change_password_hidden_input" />
            <? } ?>
            <div class="form-group">
	      <label for="new-password" class="col-sm-2 control-label">Password<em>*</em></label>
              <div class="col-sm-10">
                <input id="new-password" class="form-control" type="password" size="30" name="new_password" value="" />
	      </div>
            </div>
            <div class="form-group">
	      <label for="confirm-password" class="col-sm-2 control-label">Confirm the password<em>*</em></label>
              <div class="col-sm-10">
                <input id="confirm-password" class="form-control" type="password" size="30" name="confirm_password" value="" />
	      </div>
            </div>
            	<? if ($action == 'edit') { ?>
            	</div>
           <? 	} 
              }?>
           <div class="form-group actions">
           <div class="col-sm-offset-2 col-sm-10">
             <input class="btn btn-primary" type="submit" value="Save" />
	     <? if ($action == 'edit') { ?>
	     	<input class="btn btn-default" type="submit" name="action::delete" value="Delete" />
	     	<a href="#" id="btn_change_password" onclick="show_hide_new_password();return false" class="btn btn-default">Change password</a>
	     <?php } ?>
	     <div class="required"><em>*</em> Required field</div>
	     <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
           </div>
           </div>
         </fieldset>
        </div>       
     </form>
    </div>
</div>
</div>
<?php
$tmpl->place('footer');
