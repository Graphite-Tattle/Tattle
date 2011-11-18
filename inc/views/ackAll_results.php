<?php
$tmpl->set('title', 'Acknowledge All Check Results');
$tmpl->place('header');
?>
<h1><?=$tmpl->prepare('title'); ?></h1>
<form action="<?=Check::makeURL('ackALl', $check); ?>" method="post">
  <p class="warning">Are you sure you want to acknowledge all alerts for this check
    <strong><?=$check->prepareName(); ?></strong>?
  </p>
  <p>
    <input class="danger btn" type="submit" value="Yes, delete this check" />
    <a class="btn" href="<?=Check::makeURL('list'); ?>">No, please keep it</a>
    <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
  </p>
</form>
<?php
$tmpl->place('footer');
