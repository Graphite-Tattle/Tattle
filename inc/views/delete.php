<?php
$tmpl->set('title', 'Delete Check');
$tmpl->place('header');
?>

<h1><?php echo $tmpl->prepare('title') ?></h1>

<p class="nav">
	<a href="<?php echo Check::makeURL('list') ?>">List all checks</a> |
	<a class="related" href="<?php echo Check::makeURL('edit', $check) ?>">Edit this check</a>
</p>

<?php
fMessaging::show('error', fURL::get());
?>

<form action="<?php echo Check::makeURL('delete', $check) ?>" method="post">
	
	<p class="warning">
		Are you sure you want to delete the check
		<strong><?php echo $check->prepareTitle() ?></strong>?
	</p>
	
	<p>
		<input class="delete" type="submit" value="Yes, delete this check" />
		<a href="<?php echo Check::makeURL('list') ?>">No, please keep it</a>
		<input type="hidden" name="token" value="<?php echo fRequest::generateCSRFToken() ?>" />
	</p>
	
</form>

<?php
$tmpl->place('footer');