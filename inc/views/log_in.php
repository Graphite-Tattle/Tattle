<?php
$tmpl->set('title', 'Log In');
$tmpl->set('no-nav', true);
$tmpl->place('header');
?>
   <form action="<?=fURL::get() . '?action=log_in'; ?>" method="post">
     <div class="main" id="main">
       <fieldset>
         <div class="clearfix">
           <label for="username">Username</label>
           <div class="input">
             <input id="username" type="text" name="username" value="<?=fRequest::get('username'); ?>" />
           </div>
         </div><!-- /clearfix -->
         <div class="clearfix">
           <label for="password">Password</label>
           <div class="input">
             <input id="password" type="password" name="password" value="" />
           </div>
         </div><!-- /clearfix -->
         <div class="actions">       
           <input class="btn" type="submit" value="Log In" />
           <a class="btn" href="<?=User::makeUrl('add'); ?>">Register</a>
         </div>
       </fieldset>
     </div>
   </form>
 </div>
<?php $tmpl->place('footer') ?>
