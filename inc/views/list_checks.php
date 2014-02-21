<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$active_tab_alerts = " class=active";
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');
?>
<script type="text/javascript">
$(function(){
	$('#list_of_filters').change(function(){
		$(location).attr('href',$('#list_of_filters').val());
	});
});
</script>
<?php 
try {
	$checks->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>

<script type="text/javascript">
$(document).ready(function() {
  attachTooltips();
});
</script>

<a class="btn small btn-primary" href="<?= Check::makeURL('add', $check_type);?>">Add Check</a>
<p class="pull-right">
	Filter group :
	<select id="list_of_filters">
		<option value="<?=Check::makeURL('list', $check_type,-1)?>">All checks</option>
		<?php 
			foreach (Group::findAll() as $group) {
		?>
				<option value="<?=Check::makeURL('list', $check_type,$group->getGroupId())?>" <?=($filter_group_id==$group->getGroupId())?'selected="selected"':''?>><?=$group->getName();?></option>
		<?php
			}
		?>
	</select>
</p>
<table class="table table-bordered table-striped">
          <thead>
		<tr>
    <th><?=fCRUD::printSortableColumn('name','Name'); ?></th>
    <th class="masterTooltip" title="Graph Target that will be checked in Graphite"><?=fCRUD::printSortableColumn('target','Target'); ?></th>
    <th class="masterTooltip" title="The threshold level at which a Warning will be triggered"><?=fCRUD::printSortableColumn('warn','Warn'); ?></th>
    <th class="masterTooltip" title="The threshold level at which an Error will be triggered"><?=fCRUD::printSortableColumn('error','Error'); ?></th>
    <th class="masterTooltip" title="Number of data points to use when calculating the moving average. Each data point spans one minute"><?=fCRUD::printSortableColumn('sample','Sample'); ?></th>
    <th><?=fCRUD::printSortableColumn('baseline','Baseline'); ?></th>
    <th class="masterTooltip" title="Over will trigger an alert when the value retrieved from Graphite is greater than the warning or error threshold. Under will trigger an alert when the value retrieved from Graphite is less than the warning or the error threshold"><?=fCRUD::printSortableColumn('over_under','Over/Under'); ?></th>
    <th class="masterTooltip" title="Public checks can be subscribed to by any user while private checks remain hidden from other users"><?=fCRUD::printSortableColumn('visiblity','Visibility'); ?></th>
       </tr></thead><tbody>    
	<?php
	$first = TRUE;
	foreach ($checks as $check) {
	?>
    	<tr>
        <td><?=$check->prepareName() . '<br/><a href="' . CheckResult::makeUrl('list',$check) . '">View';?></a> | <?php if (fSession::get('user_id') == $check->getUserId() || fAuthorization::checkAuthLevel('admin')) { 
                    echo '<a href="' . Check::makeURL('edit', $check_type, $check) . '"> Edit</a> |'; 
                  } ?>
        <a href="<?=Subscription::makeURL('add', $check); ?>">Subscribe</a></td>
        <td style="max-width:300px; overflow:scroll;"><?=$check->prepareTarget(); ?></td>
        <td><?=$check->prepareWarn(); ?></td>
        <td><?=$check->prepareError(); ?></td>
        <td><?=$check->prepareSample(); ?></td>
        <td><?=$check->prepareBaseline(); ?></td>
        <td><?=$over_under_array[$check->getOver_Under()]; ?></td>
        <td><?=$visibility_array[$check->getVisibility()]; ?></td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
    //check to see if paging is needed
    $total_pages = ceil($checks->count(TRUE) / $GLOBALS['PAGE_SIZE']);
    if ($total_pages > 1) {
      $prev_class = 'previous';
      $prev_link = fURL::replaceInQueryString('page', $page_num -1 );
      $next_class = 'next';
      $next_link = fURL::replaceInQueryString('page', $page_num + 1);
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
	<div class="info">
		There are currently no <?=$check_type?> based checks for this group. <a href="<?=Check::makeURL('add', $check_type); ?>&filter_group_id=<?=$filter_group_id?>">Add one now</a>
		<p class="pull-right">
			Filter group :
			<select id="list_of_filters">
				<option value="<?=Check::makeURL('list', $check_type,-1)?>">All checks</option>
				<?php 
					foreach (Group::findAll() as $group) {
				?>
						<option value="<?=Check::makeURL('list', $check_type,$group->getGroupId())?>" <?=($filter_group_id==$group->getGroupId())?'selected="selected"':''?>><?=$group->getName();?></option>
				<?php
					}
				?>
			</select>
		</p>
	</div>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
