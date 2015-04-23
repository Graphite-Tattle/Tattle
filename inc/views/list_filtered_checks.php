<?php
define('TATTLE_ROOT', '../..');
define('JS_CACHE', TATTLE_ROOT . '/js_cache/');
include '../includes.php';
include TATTLE_ROOT . '/inc/functions.php';
include TATTLE_ROOT . '/inc/config.php';
$filter_text = fRequest::get('filter_text', 'string');
$check_type = fRequest::getValid('type', array('predictive', 'threshold'));
$filter_group_id = fRequest::get('filter_group_id', 'integer');
if (empty($filter_group_id) || ($filter_group_id < 0)) {
    $filter_group_id = -1;
}
?>
<script type="text/javascript">
    var filter = $("#filter_text").val();
    var reg = new RegExp(filter, "i");
    $(".highlight").each(function() {
        if (filter != '') {
            if ($(this).html().match(reg) != null) {
                $(this).addClass('success');
            } else {
                $(this).removeClass('success');
            }
        }
    });

    $(".name a").each(function() {
        if (filter != '') {
            if ($.trim($(this).html()).match(reg) != null) {
                $(this).parent().addClass('success');
            } else {
                $(this).parent().removeClass('success');
            }
        }
    });

    attachTooltips();
</script>
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Name</th>
            <th class="masterTooltip" title="Graph Target that will be checked in Graphite">Target</th>
            <th class="masterTooltip" title="The threshold level at which a Warning will be triggered">Warn</th>
            <th class="masterTooltip" title="The threshold level at which an Error will be triggered">Error</th>
            <th class="masterTooltip" title="Number of data points to use when calculating the moving average. Each data point spans one minute">Sample</th>
            <th>Baseline</th>
            <th class="masterTooltip" title="Over will trigger an alert when the value retrieved from Graphite is greater than the warning or error threshold. Under will trigger an alert when the value retrieved from Graphite is less than the warning or the error threshold">Over/Under</th>
            <th class="masterTooltip" title="Public checks can be subscribed to by any user while private checks remain hidden from other users">Visibility</th>
            <th>Action</th>
        </tr></thead>
    <tbody>    
        <?php
        $first = TRUE;
        if ($filter_group_id == -1) {
            $checks = Check::findAll($check_type);
        } else {
            $checks = Check::findAllByGroupId($check_type, $filter_group_id);
        }
        if (isset($filter_text) && $filter_text != '') {
            $filtered_checks = $checks->filter(array('getTarget|getName~' => $filter_text));
        } else {
            $filtered_checks = $checks;
        }
        foreach ($filtered_checks as $check) {
        ?>
            <tr>
                <td class="name">
                    <a href="<?= CheckResult::makeURL('list', $check); ?>">
                        <?php if ($check->getName())  ?>
                        <?= $check->prepareName(); ?>
                    </a>
                </td>    
                <td class="highlight" style="max-width:300px; overflow:scroll;"><?= $check->prepareTarget(); ?></td>
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