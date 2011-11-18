<?php

if (!isset($class_name)) {
  $class_name = 'Check';
}

if ($class_name == 'Setting') {
   if ($setting_type == 'user') {
     $form_url = $class_name::makeURL('delete',$setting_type,$setting_name,$owner_id);
     $list_url = $class_name::makeURL('list',$setting_type,NULL,$owner_id);
   } else {
     $form_url = $class_name::makeURL('delete',$setting_type,$setting_name,$owner_id);
     $list_url = $class_name::makeURL('list',$setting_type,NULL,$owner_id);
   }
} else {
  $form_url = $class_name::makeURL('delete',$obj); 
  $list_url = $class_name::makeURL('list');
}
$tmpl->set('title', 'Delete ' . $class_name);
$tmpl->place('header');
?>
<h1><?=$tmpl->prepare('title'); ?></h1>

<form action="<?=$form_url; ?>" method="post">
	<p class="warning"><?=$delete_text; ?></p>
	<p>
		<input class="btn danger" type="submit" value="Yes, delete this <?=$class_name;?>" />
		<a href="<?=$list_url; ?>">No, please keep it</a>
		<input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
	</p>
</form>
<?
$tmpl->place('footer');
