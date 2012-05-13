<?php
$tmpl->set('title', 'Self Service Alerts based on Graphite metrics');
$active_tab_alerts = " class=active";
$tmpl->set('breadcrumbs',$breadcrumbs);
$tmpl->place('header');

try {
	$checks->tossIfEmpty();
	$affected = fMessaging::retrieve('affected', fURL::get());
	?>
<a class="small btn primary" href="<?= Check::makeUrl('add');?>">Add Check</a>
<table class="zebra-striped">
          <thead>
		<tr>
    <th><?=fCRUD::printSortableColumn('name','Name'); ?></th>
    <th><?=fCRUD::printSortableColumn('target','Target'); ?></th>
    <th><?=fCRUD::printSortableColumn('warn','Warn'); ?></th>
    <th><?=fCRUD::printSortableColumn('error','Error'); ?></th>
    <th><?=fCRUD::printSortableColumn('sample','Sample'); ?></th>
    <th><?=fCRUD::printSortableColumn('baseline','Baseline'); ?></th>
    <th><?=fCRUD::printSortableColumn('over_under','Over/Under'); ?></th>
    <th><?=fCRUD::printSortableColumn('visiblity','Visibility'); ?></th>
    <th>Action</th>
       </tr></thead><tbody>    
	<?php
	$first = TRUE;
	foreach ($checks as $check) {
	?>
    	<tr>
        <td><?='<a href="' . CheckResult::makeUrl('list',$check) . '">' . $check->prepareName(); ?></a></td>
        <td><?=$check->prepareTarget(); ?></td>
        <td><?=$check->prepareWarn(); ?></td>
        <td><?=$check->prepareError(); ?></td>
        <td><?=$check->prepareSample(); ?></td>
        <td><?=$check->prepareBaseline(); ?></td>
        <td><?=$over_under_array[$check->getOver_Under()]; ?></td>
        <td><?=$visibility_array[$check->getVisibility()]; ?></td>
        <td><?php if (fSession::get('user_id') == $check->getUserId()) { 
                    echo '<a href="' . Check::makeURL('edit', $check) . '">Edit</a> |'; 
                  } ?>
        <a href="<?=Subscription::makeURL('add', $check); ?>">Subscribe</a></td>
        </tr>
    <?php } ?>
    </tbody></table>
    <?
    //check to see if paging is needed
    $total_pages = ceil($checks->count(TRUE) / $GLOBALS['PAGE_SIZE']);
    if ($total_pages > 1) {
      $prev_class = 'previous';
      $prev_link = fURL::get() . '?page=' . ($page_num - 1);
      $next_class = 'next';
      $next_link = fURL::get() . '?page=' . ($page_num + 1);
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
	<p class="info">There are currently no Tattle checks. <a href="<?=Check::makeURL('add'); ?>">Add one now</a></p>
	<?php
}
?>
</div>
<?php $tmpl->place('footer') ?>
