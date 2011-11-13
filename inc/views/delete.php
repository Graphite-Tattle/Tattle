<?php

if (!isset($class_name)) {
  $class_name = 'Check';
}

$tmpl->set('title', 'Delete ' . $class_name);
$tmpl->place('header');
?>

<h1><?php echo $tmpl->prepare('title') ?></h1>

<form action="<?php echo $class_name::makeURL('delete', $obj) ?>" method="post">
	
	<p class="warning"><?=$delete_text;?></p>
	<p>
		<input class="btn danger" type="submit" value="Yes, delete this <?=$class_name;?>" />
		<a href="<?php echo $class_name::makeURL('list') ?>">No, please keep it</a>
		<input type="hidden" name="token" value="<?php echo fRequest::generateCSRFToken() ?>" />
	</p>
	
</form>

<?php
$tmpl->place('footer');
