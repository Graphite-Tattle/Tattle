<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$tmpl->set('graphlot',true);
$tmpl->place('header');

 try {
        $check = new Check($check_id);
        $affected = fMessaging::retrieve('affected', fURL::get());
  } catch (fEmptySetException $e) {
?>
        <p class="info">There are currently no Tattle checks. Add a <a href="<?=Check::makeURL('add', 'threshold'); ?>">threshold</a> based or a <a href="<?=Check::makeURL('add', 'predictive'); ?>">predictive</a> based check now.</p>
        <?php
  } ?>
<div class="row">
	<div class="col-md-8">
	    <div style="padding-bottom:15px;">
	        <span>Name : <?=$check->prepareName(); ?></span> |
	        <span>Target : <?=Check::constructTarget($check); ?></span>
	    </div>
	    <span><?=Check::showGraph($check,true,'-48hours',620,true); ?></span>
	</div>
	<div class="col-md-4">
		<form method="POST" action="<?=CheckResult::makeURL('notifyAll',$check)?>" class="form-horizontal">
			<fieldset>
				<legend>Mail everyone</legend>
				<em class="text-info" style="margin-bottom:10px;display:block;">Send a mail to all the people who subscribe to this check</em>
				<div class="form-group">
					<label class="control-label" for="subject">Subject&nbsp;*</label>
					<input type="text" id="subject" class="form-control" name="subject_mail" value='Message about : "<?=$check->getName();?>"'/>
				</div>
				<div class="form-group">
					<label class="control-label" for="content">Content&nbsp;*</label>
					<textarea id="content" class="form-control" name="content_mail"></textarea>
				</div>
				<input type="hidden" name="token" value="<?=fRequest::generateCSRFToken(); ?>" />
				<div class="form-group actions">
					<div>
						<input type="submit" 
						value="Send this mail" 
						class="btn btn-primary" 
						onclick="if(($('#subject').val() == '') || ($('#content').val() == '')) {alert('You have to set a subject and a content before sending the mail.');return false;}"/>
						<div class="required"><em>*</em> Required field</div>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>
<a class="btn btn-default" href="<?=Check::makeURL('edit', $check->getType(),$check)?>">Edit check</a>
<a href="<?=Subscription::makeURL('add', $check); ?>" class="btn btn-default">Subscribe</a>
<?php
  try {
    $check_results->tossIfEmpty();
    $affectd = fMessaging::retrieve('affected',fURL::get());
   ?>
        <a class="btn small btn-primary" href="<?=CheckResult::makeURL('ackAll', $check = new Check($check_id)); ?>">Clear All</a>
        <table class="table table-bordered table-striped">
    <tr>
    <th>Status</th>
    <th>Value</th>
    <th>Error</th>
    <th>Warn</th>
    <th>State</th>
    <th>Time</th>
       </tr>
<?php
    $first = TRUE;
    foreach ($check_results as $check_result) {
        $check = new Check($check_result->getCheck_Id());
?>
        <tr>
        <td><?=$status_array[$check_result->prepareStatus()]; ?></td>
        <td><?=$check_result->prepareValue(); ?></td>
        <td><?=$check->prepareError(); ?></td>
        <td><?=$check->prepareWarn(); ?></td>
        <td><?=$check_result->prepareState(); ?></td>
        <td><?=$check_result->prepareTimestamp('Y-m-d H:i:s'); ?></td>
        </tr>
    <?php } ?>
    </table></div>
    <?
    //check to see if paging is needed
    $total_pages = ceil($check_results->count(TRUE) / $GLOBALS['PAGE_SIZE']);
    if ($total_pages > 1) {
      $prev_class = 'previous';
      $prev_link = fURL::replaceInQueryString('page', $page_num -1 );
      $next_class = 'next';
      $next_link = fURL::replaceInQueryString('page', $page_num + 1);
      $current_link = "?action=$action&check_id=$check_id";
      if ($page_num == 1) {
        $prev_class .= ' disabled';
        $prev_link = '#';
      } elseif ($page_num == $total_pages) {
        $next_class .= ' disabled';
        $next_link = '#';
      }
      ?>
      <div class="pagination">
        <ul class="pager">
          <li class="<?=$prev_class; ?>">
            <a href="<?=$prev_link; ?>">&larr; Previous</a>
          </li>
          <li class="<?=$next_class; ?>">
            <a href="<?=$next_link; ?>">Next &rarr;</a>
          </li>
        </ul>
      </div>
    <?php }
} catch (fEmptySetException $e) {
?>
        <p class="info">There are currently no alerts for this checks.</p>
<?php
}
?>
<?php $tmpl->place('footer') ?>
