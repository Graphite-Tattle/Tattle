<?php
$tmpl->set('title', 'Delete Line');
$tmpl->place('header');
?>
<form action="<?php echo Line::makeURL('delete', $line) ?>" method="post">
  <div class="main" id="main">
    <div class="warning">Are you sure you want to delete this line?
      <strong><?php echo $graph->prepareName() ?></strong>?
    </div>
    <div class="actions">
      <input class="btn danger" type="submit" value="Yes, delete this line" />
      <a class="btn" href="<?php echo Graph::makeURL('edit',$graph) ?>">No, please keep it</a>
      <input type="hidden" name="token" value="<?php echo fRequest::generateCSRFToken() ?>" />
    </div>
  </div>
</form>

<?php
$tmpl->place('footer');
