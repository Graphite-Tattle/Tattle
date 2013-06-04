<?php
$tmpl->set('title', 'Log In');
$tmpl->set('no-nav', true);
$tmpl->place('header');
?>
   <form action="?action=log_in" method="post" class="form-horizontal">
     <div class="main" id="main">
       <fieldset>
         <div class="control-group">
           <label for="username" class="control-label">Username</label>
           <div class="controls">
             <input id="username" type="text" name="username" value="<?=fRequest::get('username'); ?>" />
           </div>
         </div><!-- /clearfix -->
         <div class="control-group">
           <label for="password" class="control-label">Password</label>
           <div class="controls">
             <input id="password" type="password" name="password" value="" />
           </div>
         </div><!-- /clearfix -->
         <div class="control-group actions"> 
         	<div class="controls">      
	           <input class="btn" type="submit" value="Log In" />
	           <a class="btn" href="<?=User::makeUrl('add'); ?>">Register</a>
           </div>
         </div>
       </fieldset>
     </div>
   </form>
 </div>
<?php $tmpl->place('footer') ?>
