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
    var last_filter;
    function filterChecks() {
            var filter_text = $("#filter_text").val();
            if (last_filter && last_filter == filter_text) {
                    $("#unfiltered_checks").hide();
                    $("#filtered_checks").show();
            } else {    
                if (filter_text.length > 2) {
                        var type = '<?= $check_type?>';
                        var filter_group_id = <?= $filter_group_id?>;
                        $.get(
                            'inc/views/list_filtered_checks.php', 
                            {
                                filter_text: filter_text, 
                                type: type,
                                filter_group_id: filter_group_id
                            }, 
                            function (data) {
                                $("#unfiltered_checks").hide();
                                $("#filtered_checks").html(data);
                                $("#filtered_checks").show();
                            },
                            'html'
                            );
                        last_filter = $("#filter_text").val();
                } else {
                        $("#unfiltered_checks").show();
                        $("#filtered_checks").hide();
                }
            }
    }
    $(document).ready(function() {
        var timeout;
        attachTooltips();
 
        $("#filter_text").keyup(function(){
            if (timeout) {
                clearTimeout(timeout);
                timeout = setTimeout(function() {filterChecks();}, 1000);
            } else {
                timeout = setTimeout(function() {filterChecks();}, 1000);
            }
        });
    });
</script>

<a class="btn small btn-primary" href="<?= Check::makeURL('add', $check_type);?>">Add Check</a>
<div class="form-group inline" style="padding-left: 200px; width:500px">
        <input type="text" class="form-control" placeholder="Search In Checks" id="filter_text" autofocus="autofocus">
</div>
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
<br></br>
<div id="unfiltered_checks">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th><?= fCRUD::printSortableColumn('name', 'Name'); ?></th>
                <th class="masterTooltip" title="Graph Target that will be checked in Graphite"><?= fCRUD::printSortableColumn('target', 'Target'); ?></th>
                <th class="masterTooltip" title="The threshold level at which a Warning will be triggered"><?= fCRUD::printSortableColumn('warn', 'Warn'); ?></th>
                <th class="masterTooltip" title="The threshold level at which an Error will be triggered"><?= fCRUD::printSortableColumn('error', 'Error'); ?></th>
                <th class="masterTooltip" title="Number of data points to use when calculating the moving average. Each data point spans one minute"><?= fCRUD::printSortableColumn('sample', 'Sample'); ?></th>
                <th><?= fCRUD::printSortableColumn('baseline', 'Baseline'); ?></th>
                <th class="masterTooltip" title="Over will trigger an alert when the value retrieved from Graphite is greater than the warning or error threshold. Under will trigger an alert when the value retrieved from Graphite is less than the warning or the error threshold"><?= fCRUD::printSortableColumn('over_under', 'Over/Under'); ?></th>
                <th class="masterTooltip" title="Public checks can be subscribed to by any user while private checks remain hidden from other users"><?= fCRUD::printSortableColumn('visiblity', 'Visibility'); ?></th>
                <th>Action</th>
            </tr></thead>
        <tbody>    
            <?php
            $first = TRUE;
            foreach ($checks as $check) {
                ?>
                <tr>
                    <td><?= '<a href="' . CheckResult::makeUrl('list', $check) . '">' . $check->prepareName(); ?></a></td>    
                    <td class="masterTooltip" style="max-width:300px; overflow:scroll;text-overflow: ellipsis;" title="<?=$check->prepareTarget(); ?>"><?= $check->prepareTarget(); ?></td>
                    <td><?= $check->prepareWarn(); ?></td>
                    <td><?= $check->prepareError(); ?></td>
                    <td><?= $check->prepareSample(); ?></td>
                    <td><?= $check->prepareBaseline(); ?></td>
                    <td><?= $over_under_array[$check->getOver_Under()]; ?></td>
                    <td><?= $visibility_array[$check->getVisibility()]; ?></td>
                    <td><?php
                        if (fSession::get('user_id') == $check->getUserId()) {
                            echo '<a href="' . Check::makeURL('edit', $check_type, $check) . '">Edit</a> |';
                        }
                        ?>
                        <a href="<?= Subscription::makeURL('add', $check); ?>">Subscribe</a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php
    
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
</div>
<div id="filtered_checks"></div>
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
