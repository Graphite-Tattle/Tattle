<?php

  $tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
  $tmpl->set('full_screen', $full_screen);
  $tmpl->set('refresh',$dashboard->getRefreshRate());
  $tmpl->place('header');
?>
<center> <!-- cssblasphemy but i need it look decent real quick --> 
    <h1><?=$dashboard->getName(); ?>&nbsp<small><?=$dashboard->getDescription(); ?></small></h1>
    <div class="row">
	<?php
        $graph_count = 0;
        $columns = $dashboard->getColumns();
	foreach ($graphs as $graph) {
          $graph_row = ($graph_count % $columns);
        
		?>
        <span class=""><a href="<?=Graph::makeUrl('edit',$graph); ?>"><img src="<?=Graph::drawGraph($graph,$dashboard); ?>" rel=<?=($graph_count >= $columns ? 'popover-above' : 'popover-below'); ?> title="<?=$graph->getName(); ?>" data-content="<?=$graph->getDescription(); ?>" /></a></span>
    <?php 
          $graph_count++;
           if ( $graph_count == $columns) {
             echo '</div><div class="row">';
           }
} ?>
</div>
</div>
</center>
<?php 
if (!$full_screen) {
echo '<a href="' . Dashboard::makeUrl('edit',$dashboard) . '">Edit Dashboard</a> | <a href="' . Graph::makeUrl('add',$dashboard) .'">Add Graph</a> | <a href="' . fUrl::getWithQueryString() . '&full_screen=true">Full Screen</a>';
$tmpl->set('show_bubbles',true);
$tmpl->place('footer') ;
}
?>
