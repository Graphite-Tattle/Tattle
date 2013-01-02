<?php
$tmpl->set('title', 'Acknowledge All Check Results');
$tmpl->place('header');
?>
<h1><?=$tmpl->prepare('title'); ?></h1>
<form method="post">
  <p class="warning">Are you sure you want to acknowledge all alerts for this check
    <strong><?=$check->prepareName(); ?></strong>?
  </p>
  <p>
    <input class="danger btn" type="submit" value="Yes, acknowledge all" />
    <a class="btn" href="<?=Check::makeURL('list', $check->prepareType()); ?>">No, please keep it</a>
    <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
  </p>
</form>
<?php
$tmpl->place('footer');
