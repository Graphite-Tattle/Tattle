<?php
$tmpl->set('title', 'Delete Subscription');
$tmpl->place('header');
?>
<form action="<?php echo Subscription::makeURL('delete', $subscription) ?>" method="post">
  <div class="main" id="main">
    <div class="warning">Are you sure you want to delete the subscription to this check
      <strong><?php echo $check->prepareName() ?></strong>?
    </div>
    <div class="actions">
      <input class="btn danger" type="submit" value="Yes, delete this subscription" />
      <a class="btn" href="<?php echo Subscription::makeURL('list') ?>">No, please keep it</a>
      <input type="hidden" name="token" value="<?php echo fRequest::generateCSRFToken() ?>" />
    </div>
  </div>
</form>

<?php
$tmpl->place('footer');
