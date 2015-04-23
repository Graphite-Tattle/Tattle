<?php
define('TATTLE_ROOT', '../..');
define('JS_CACHE', TATTLE_ROOT . '/js_cache/');
include '../includes.php';
include TATTLE_ROOT . '/inc/functions.php';
include TATTLE_ROOT . '/inc/config.php';
$filter_text = fRequest::get('filter_text', 'string');
if (!isset($filter_group_id)) {
    $filter_group_id = fRequest::get('filter_group_id', 'integer');
    if (empty($filter_group_id) || ($filter_group_id < 0)) {
        $filter_group_id = -1;
    }
}
?>
<script type="text/javascript">
    $('.badge').tooltip();
    var filter = $("#filter_text").val();
    var reg = new RegExp(filter, "i");
    $("#filtered_dashboards .description").each(function() {
        if (filter != '') {
            if ($(this).html().match(reg) != null) {
                $(this).addClass('success');
            } else {
                $(this).removeClass('success');
            }
        }
    });

    $("#filtered_dashboards .name a").each(function() {
        if (filter != '') {
            if ($.trim($(this).html()).match(reg) != null) {
                $(this).parent().addClass('success');
            } else {
                $(this).parent().removeClass('success');
            }
        }
    });
    $('#loader_filter').hide(); 
</script>
<form method="POST" id="form_mass_export" action="<?= Dashboard::makeURL('mass_export'); ?>" target="_blank">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>    
                <th>Name</th>
                <th>Description</th>
                <th>Group</th>
                <th>Columns</th>
                <th>Background Color</th>
                <th>Action</th>
                <th class="last"><input type="submit" class="btn btn-default" value="Export selected" onclick="$('#form_mass_export').submit();
                        deselectAll();
                        return false;" /></th>
            </tr>    
        </thead>
        <tbody>
            <?php
            $first = TRUE;
            if ($filter_group_id == -1) {
                $dashboards = Dashboard::findAll();
            } else {
                $dashboards = Dashboard::findAllByFilter($filter_group_id);
            }

            /* Filter Graphs */
            foreach ($dashboards as $dashboard) {
                $dashboard_id = $dashboard->getDashboardId();
                $graphs = Graph::findAll($dashboard_id);
                $number_of_lines = 0;
                foreach ($graphs as $graph) {
                    $number_of_lines = $number_of_lines + Line::countAllByFilter($graph->getGraphId(), $filter_text);
                }
                $number_of_graphs = Graph::countAllByFilter($dashboard_id, $filter_text);
            ?>
            <?php if ( $number_of_graphs > 0 || $number_of_lines > 0 || preg_match('/'.$filter_text.'/i', $dashboard->getName()) || preg_match('/'.$filter_text.'/i', $dashboard->getDescription()) ) {?> 
                <tr>
                    <td class="name">
                        <a href="<?= Dashboard::makeURL('view', $dashboard); ?>">
                            <?php if ($dashboard->getName())  ?>
                            <?= $dashboard->prepareName(); ?>
                        </a>
                        <div class="inline pull-right">
                            <span class="badge" style="width: 30px" data-toggle="tooltip" data-placement="left" title="Number of graphs passed through the filter"><?= $number_of_graphs ?></span>
                            <span class="badge" style="width: 30px" data-toggle="tooltip" data-placement="right" title="Number of lines passed through the filter"><?= $number_of_lines ?></span>
                        </div>
                    </td>
                    <td class="description"><?= $dashboard->prepareDescription(); ?></td>
                    <td>
                        <?php
                        try {
                            $dashboard_s_group = new Group($dashboard->getGroupId());
                            echo ($dashboard_s_group->getName());
                        } catch (fNotFoundException $e) {
                            echo "No group found";
                        }
                        ?>
                    </td>
                    <td><?= $dashboard->prepareColumns(); ?></td>
                    <td><?= $dashboard->prepareBackgroundColor(); ?></td>
                    <td>
                        <a href="<?= Dashboard::makeURL('view', $dashboard); ?>">View</a> |
                        <a href="<?= Dashboard::makeURL('edit', $dashboard); ?>">Edit</a> |
                        <a href="<?= Dashboard::makeURL('delete', $dashboard); ?>">Delete</a> |
                        <a href="<?= Dashboard::makeURL('export', $dashboard); ?>" target="_blank">Export</a>
                    </td>
                    <td class="last"><input type="checkbox" name="id_mass_export[]" class="no-margin input-dashboard" value="<?= $dashboard->getDashboardId() ?>" /></td>
                </tr>
                <?php }?>
            <?php } ?>
        </tbody>
    </table>
</form>
