<?php
$tmpl->set('title', 'Acknowledge All Check Results');
$tmpl->place('header');
?>

<h1><?php echo $tmpl->prepare('title') ?></h1>

<?php
fMessaging::show('error', fURL::get());
?>

<form action="<?php echo Check::makeURL('ackALl', $check) ?>" method="post">
	
	<p class="warning">
		Are you sure you want to acknowledge all alerts for this check
		<strong><?php echo $check->prepareName() ?></strong>?
	</p>
	
	<p>
		<input class="danger btn" type="submit" value="Yes, delete this check" />
		<a class="btn" href="<?php echo Check::makeURL('list') ?>">No, please keep it</a>
		<input type="hidden" name="token" value="<?php echo fRequest::generateCSRFToken() ?>" />
	</p>
	
</form>

<?php
$tmpl->place('footer');
