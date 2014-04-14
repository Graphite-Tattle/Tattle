<?php
define('TATTLE_ROOT', '../..');
define('JS_CACHE', TATTLE_ROOT . '/js_cache/');
include '../includes.php';
include TATTLE_ROOT . '/inc/functions.php';
include TATTLE_ROOT . '/inc/config.php';
$filter_text = fRequest::get('filter_text', 'string');
if (!isset($dashboard_id)) {
    $dashboard_id = fRequest::get('dashboard_id', 'integer');
}
$graphs = Graph::findAll($dashboard_id);
$number_of_graphs = $graphs->count(TRUE);
?>
<script type="text/javascript">
    $('.badge').tooltip();
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

    $(".name").each(function() {
        if (filter != '') {
            if ($.trim($(this).html()).match(reg) != null) {
                $(this).parent().addClass('success');
            } else {
                $(this).parent().removeClass('success');
            }
        }
    });
    
    $('#sortable').sortable({
            placeholder: "sortable-placeholder",
            cancel: "#sortable .popover",
            start : hide_popover,
            update : function (event,ui){
                    $('#tableHider').show();
                    var new_weights = new Array();
                    var i = 0;
                    $('#sortable tr').each(function(){
                            new_weights.push($(this).attr('id') + ":" + i);
                            i++;
                    });
                    $(location).attr('href','<?=Graph::makeURL('drag_reorder')?>'+new_weights.join(","));
            }
    });
</script>
<table class="table table-bordered table-striped" id="table-graphs">
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Vtitle</th>
            <th>Area</th>
            <th>Action</th>
            <?php if ($number_of_graphs > 1) { ?>
                <th>Reorder&nbsp;*</th>
            <?php } ?>
        </tr>    
    </thead>
    <tbody<?= ($number_of_graphs > 1) ? ' id="sortable"' : ''; ?>>
        <?php
        $first = TRUE;
        $index = 0;
        /* Create an empty set of Graphs */
        $graph_with_filtered_lines = $graphs->slice(0, 0);

        /* Filter Lines */
        foreach ($graphs as $graph) {
            $number_of_lines = 0;
            $lines = Line::findAll($graph->getGraphId());
            if (isset($filter_text) && $filter_text != '') {
                $lines = $lines->filter(array('getTarget|getAlias~' => $filter_text));
            }
            $number_of_lines = $number_of_lines + $lines->count();
            if ($number_of_lines > 0) {
                $graph_with_filtered_lines = $graph_with_filtered_lines->merge($graph);
            }
        }

        /* Filter Graphs */
        if (isset($filter_text) && $filter_text != '') {
            $filtered_graphs = $graphs->filter(array('getName|getArea|getVtitle|getDescription~' => $filter_text));
        } else {
            $filtered_graphs = $graphs;
        }

        /* Merge the two sets of Graphs */
        $filtered_graphs = $filtered_graphs->merge($graph_with_filtered_lines);
        /* Remove all duplicate Graphs after the merge */
        $filtered_graphs = $filtered_graphs->unique();

        foreach ($filtered_graphs as $graph) {
            $number_of_lines = 0;
            $lines = Line::findAll($graph->getGraphId());
            if (isset($filter_text) && $filter_text != '') {
                $lines = $lines->filter(array('getTarget|getAlias~' => $filter_text));
            }
            $number_of_lines = $number_of_lines + $lines->count();
            ?>
            <tr id="<?= $graph->getGraphId() ?>">
                <td>
                    <div class="name inline">
                        <?= $graph->prepareName(); ?>
                    </div>
                    <div class="inline pull-right">
                        <span class="badge" style="width: 30px" data-toggle="tooltip" data-placement="left" title="Number of lines passed through the filter"><?= $number_of_lines ?></span>
                    </div>
                </td>
                <td class="highlight"><?= $graph->prepareDescription(); ?></td>
                <td class="highlight"><?= $graph->prepareVtitle(); ?></td>
                <td class="highlight"><?= $graph->prepareArea(); ?></td>        
                <td><a href="<?= Graph::makeURL('edit', $graph); ?>">Edit</a> |
                    <a href="<?= Graph::makeURL('delete', $graph); ?>">Delete</a> |
                    <form id="form_clone_<?= (int) $graph->getGraphId(); ?>" method="post" action="<?= Graph::makeURL('clone', $graph); ?>" style="display: initial;">
                        <a href="#" onclick="$('#form_clone_<?= (int) $graph->getGraphId(); ?>').submit();
                                    return false;">Clone</a>
                        <input type="hidden" name="token" value="<?= fRequest::generateCSRFToken("/graphs.php"); ?>" />
                    </form> |
                    <div id="form_clone_into_<?= (int) $graph->getGraphId(); ?>" style="display:none;">
                        <form id="" method="post" action="<?= Graph::makeURL('clone_into', $graph); ?>" class="inline no-margin">
                            <input type="hidden" name="token" value="<?= fRequest::generateCSRFToken("/graphs.php"); ?>" />
                            Select destination : 
                            <select name="dashboard_dest_id">
                                <?php
                                foreach (Dashboard::findAll() as $dashboard_dest) {
                                    if ($dashboard_dest->prepareDashboardId() != $graph->prepareDashboardId()) {
                                        ?>
                                        <option value="<?= (int) $dashboard_dest->getDashboardId(); ?>"><?= $dashboard_dest->prepareName() ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                            <input type="submit" value="Clone !" class="btn btn-primary"/>
                        </form>
                    </div>
                    <a href="#" id="<?= (int) $graph->getGraphId(); ?>" class="btn_popover">Clone into</a>
                </td>
                <?php if ($number_of_graphs > 1) { ?>
                    <td>
                        <?php if ($index == 0) { ?>
                            <span class="disabled"><i class="glyphicon glyphicon-arrow-up pointer"></i></span>
                        <?php } else { ?>
                            <a href="<?= Graph::makeURL('reorder', $graph, 'previous') ?>" onclick="$('#tableHider').show();
                                                return true;"><i class="glyphicon glyphicon-arrow-up pointer" title="Previous"></i></a>
                            <?php } ?>
                            <?php if ($index == $number_of_graphs - 1) { ?>
                            <span class="disabled"><i class="glyphicon glyphicon-arrow-down pointer"></i></span>
                        <?php } else { ?>
                            <a href="<?= Graph::makeURL('reorder', $graph, 'next') ?>" onclick="$('#tableHider').show();
                                                return true;"><i class="glyphicon glyphicon-arrow-down pointer" title="Next"></i></a>
                            <?php } ?>
                    </td>
                <?php } ?>
            </tr>
            <?php
            $index++;
        }
        ?>
    </tbody>
</table>