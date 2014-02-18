<?php
$tmpl->set('title', 'Log In');
$tmpl->set('no-nav', true);
$tmpl->place('header');
?>
<div class="row">
<div class="col-md-offset-4 col-md-6">
   <form action="?action=log_in" method="post" class="form-horizontal">
         <div class="form-group">
           <label for="username" class="col-sm-2 control-label">Username</label>
           <div class="col-sm-10">
             <input id="username" class="form-control" type="text" name="username" value="<?=fRequest::get('username'); ?>" />
           </div>
         </div>
         <div class="form-group">
           <label for="password" class="col-sm-2 control-label">Password</label>
           <div class="col-sm-10">
             <input id="password" class="form-control" type="password" name="password" value="" />
           </div>
         </div>
         <div class="form-group actions"> 
         	<div class="col-sm-offset-2 col-sm-10">      
	           <input class="btn btn-default" type="submit" value="Log In" />
	           <a class="btn btn-default" href="<?=User::makeUrl('add'); ?>">Register</a>
           </div>
         </div>
   </form>
 </div>
 </div>
<?php $tmpl->place('footer') ?>
