<?php
define('TATTLE_ROOT', '../..');
define('JS_CACHE', TATTLE_ROOT . '/js_cache/');
include '../includes.php';
include TATTLE_ROOT . '/inc/functions.php';
include TATTLE_ROOT . '/inc/config.php';
$filter_text = fRequest::get('filter_text', 'string');
if (!isset($dashboard_id)) {
    $dashboard_id = fRequest::get('filter_group_id', 'integer');
    if (empty($dashboard_id) || ($dashboard_id < 0)) {
        $dashboard_id = -1;
    }
}
?>
<script type="text/javascript">
    $('.badge').tooltip();
    var filter = $("#filter_text").val();
    var reg = new RegExp(filter, "i");
    $(".description").each(function() {
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
            if ($dashboard_id == -1) {
                $dashboards = Dashboard::findAll();
            } else {
                $dashboards = Dashboard::findAllByFilter($dashboard_id);
            }
            /* Create an empty set of Dashboards */
            $dashboard_with_filtered_graphs_and_lines = $dashboards->slice(0, 0);

            /* Filter Graphs */
            foreach ($dashboards as $dashboard) {
                $graphs = Graph::findAll($dashboard->getDashboardId());
                $number_of_lines = 0;
                foreach ($graphs as $graph) {
                    $lines = Line::findAll($graph->getGraphId());
                    if (isset($filter_text) && $filter_text != '') {
                        $lines = $lines->filter(array('getTarget|getAlias~' => $filter_text));
                    }
                    $number_of_lines = $number_of_lines + $lines->count();
                }
                if (isset($filter_text) && $filter_text != '') {
                    $graphs = $graphs->filter(array('getName|getArea|getVtitle|getDescription~' => $filter_text));
                }
                $number_of_graphs = $graphs->count();
                if ($number_of_graphs > 0 || $number_of_lines > 0) {
                    $dashboard_with_filtered_graphs_and_lines = $dashboard_with_filtered_graphs_and_lines->merge($dashboard);
                }
            }
            /* Filter Dashboards */
            if (isset($filter_text) && $filter_text != '') {
                $filtered_dashboards = $dashboards->filter(array('getName|getDescription~' => $filter_text));
            } else {
                $filtered_dashboards = $dashboards;
            }

            /* Merge the two sets of Dashboards */
            $filtered_dashboards = $filtered_dashboards->merge($dashboard_with_filtered_graphs_and_lines);
            /* Remove all duplicate Dashboards after the merge */
            $filtered_dashboards = $filtered_dashboards->unique();

            foreach ($filtered_dashboards as $dashboard) {
                $graphs = Graph::findAll($dashboard->getDashboardId());
                $number_of_lines = 0;
                foreach ($graphs as $graph) {
                    $lines = Line::findAll($graph->getGraphId());
                    if (isset($filter_text) && $filter_text != '') {
                        $lines = $lines->filter(array('getTarget|getAlias~' => $filter_text));
                    }
                    $number_of_lines = $number_of_lines + $lines->count();
                }
                if (isset($filter_text) && $filter_text != '') {
                    $graphs = $graphs->filter(array('getName|getArea|getVtitle|getDescription~' => $filter_text));
                }
                $number_of_graphs = $graphs->count();
                ?>
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
            <?php } ?>
        </tbody>
    </table>
</form>
