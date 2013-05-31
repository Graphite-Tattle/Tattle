<?php

  $tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
  $tmpl->set('full_screen', $full_screen);
  $tmpl->set('refresh',$dashboard->getRefreshRate());
  $tmpl->place('header');
?>
	<?php 
		if (($display_options_links%2)==1) {
			// True only if its value is 1 or 3
	?>
		<a href="<?=fURL::replaceInQueryString("display_options_links", $display_options_links-1)?>" class="btn optionsbtn">Hide options</a>
	<?php } else { ?>
		<a href="<?=fURL::replaceInQueryString("display_options_links", $display_options_links+1)?>" class="btn optionsbtn">Show options</a>
	<?php
		}
		
		if ($display_options_links > 1) {
			// True only if its value is 2 or 3
	?>
		<a href="<?=fURL::replaceInQueryString("display_options_links", $display_options_links-2)?>" class="btn linksbtn">Hide links</a>
	<?php } else { ?>
		<a href="<?=fURL::replaceInQueryString("display_options_links", $display_options_links+2)?>" class="btn linksbtn">Show links</a>
	<?php
		}
	?>
    <div id="options" class="<?=(($display_options_links%2)==1)?'dashboardoptions':'';?>" style="display:none;">
    	<h3>Time scale :</h3>
	    <ul>
	    	<?php 
	    		foreach ($quick_times_desired as $print => $value) {
			?>
	    	<li><a href="<?=fURL::replaceInQueryString("from", $value)?>"><?=$print ?></a></li>
			<?php 	
				}
	    	?>
	    </ul>
	    <hr/>
	    
	    <h3>Graphs size :</h3>
	    <ul>
	   		<?php 
	    		foreach ($quick_sizes_desired as $print => $height_and_width) {
			?>
	    	<li><a href="<?=fURL::replaceInQueryString(array_keys($height_and_width),array_values($height_and_width)) ?>"><?=$print ?></a></li>
			<?php 	
				}
	    	?>
	    </ul>
	     <hr/>
	    
	    <h3>Colors :</h3>
	    <table>
	   		<?php 
	    		foreach ($quick_bgcolor_desired as $print => $value) {
			?>
	    	<tr>
	    		<td class="colorname"><?=$print ?> :</td>
	    		<td class="colorlinks">
	    			<a href="<?=fURL::replaceInQueryString("bgcolor", $value)?>">background</a>
	    			or
	    			<a href="<?=fURL::replaceInQueryString("fgcolor", $value)?>">text</a>
	    		</td>
	    	</tr>
			<?php 	
				}
	    	?>
	    </table>
	</div>
	<div id="links" class="<?=($display_options_links > 1)?'dashboardslinks':'';?>" style="display:none;">
		<h5>Dashboard(s) in group :</h5>
		<ul>
			<?php 
				foreach ($other_dashboards_in_group as $dashboard_in_group) {
			?>
				<li><a href="<?=Dashboard::makeURL('view',$dashboard_in_group)?>"><?=$dashboard_in_group->getName()?></a></li>
			<?php 
				}
			?>
		</ul>
	</div>
	
	<div id="graphscontainer">
<center> <!-- cssblasphemy but i need it look decent real quick --> 
    <h1><?=$dashboard->getName(); ?>&nbsp<small><?=$dashboard->getDescription(); ?></small></h1>
    <div class="row">
	<?php
        $graph_count = 0;
        $columns = $dashboard->getColumns();
	foreach ($graphs as $graph) {
//           $graph_row = ($graph_count % $columns);
          $url_graph = Graph::drawGraph($graph,$dashboard);
          $req = $_REQUEST;
          if (isset($ignored_values)) {
			$keys = array_keys($req);
			foreach ($ignored_values as $ignore_it) {
				if (in_array($ignore_it, $keys)) {
					unset($req[$ignore_it]);
				}
			}
		  }
          foreach ($req as $key => $value) {
			$url_graph = addOrReplaceInURL($url_graph,$key,$value);
		  }
        
		?>
        <span class="inline"><a href="<?=Graph::makeUrl('edit',$graph); ?>"><img src="<?=$url_graph ?>" rel=<?=($graph_count >= $columns ? 'popover-above' : 'popover-below'); ?> title="<?=$graph->getName(); ?>" data-content="<?=$graph->getDescription(); ?>" /></a></span>
    <?php 
          $graph_count++;
           if ( $graph_count == $columns) {
             echo '</div><div class="row">';
             $graph_count = 0;
           }
} ?>
</div>
</div>
</center>
<?php 
if (!$full_screen) {
echo '<a href="' . Dashboard::makeUrl('edit',$dashboard) . '">Edit Dashboard</a> | <a href="' . Graph::makeUrl('add',$dashboard) .'">Add Graph</a> | <a href="?' . fURL::getQueryString() . '&full_screen=true">Full Screen</a>';
$tmpl->set('show_bubbles',true);
$tmpl->place('footer') ;
}
?>
