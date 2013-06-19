<?php

  $tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
  $tmpl->set('full_screen', $full_screen);
  $tmpl->set('refresh',$dashboard->getRefreshRate());
  $tmpl->place('header');
?>
<div class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container text-center">
			<a href="<?=Dashboard::makeURL('list',$dashboard->getGroupId())?>" class="btn btn-primary">Return to list</a>
			<a href="<?=Dashboard::makeURL('edit',$dashboard)?>" class="btn btn-primary">Edit this dashboard</a>
		
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
	</div>
	</div>
</div>
    <div id="options" class="<?=(($display_options_links%2)==1)?'dashboardoptions':'';?> popover fade bottom in" style="display:none;">
    	<div class="arrow"></div>
    	<div class="popover-content">
    	<h4>Time scale :</h4>
	    	<div class="sized-div">
			    <ul>
			    	<?php 
			    		foreach ($quick_times_desired as $print => $time) {

							if (is_array($time)) {
					?>
						<li><a href="<?=fURL::replaceInQueryString(array_keys($time),array_values($time)) ?>"><?=$print ?></a></li>
					<?php
						} else {
					?>
			    		<li><a href="<?=fURL::replaceInQueryString("from", $time)?>"><?=$print ?></a></li>
					<?php 
							}	
						}
			    	?>
			    </ul>
		    </div>
	    <hr/>
	    
	    <h4>Graphs size :</h4>
		    <div class="sized-div">
			    <ul>
			   		<?php 
			    		foreach ($quick_sizes_desired as $print => $height_and_width) {
					?>
			    	<li><a href="<?=fURL::replaceInQueryString(array_keys($height_and_width),array_values($height_and_width)) ?>"><?=$print ?></a></li>
					<?php 	
						}
			    	?>
			    </ul>
		    </div>
	     <hr/>
	    
	    <h4>Colors :</h4>
		    <div class="sized-div">
			    <table>
			   		<?php 
			    		foreach ($quick_bgcolor_desired as $print => $value) {
					?>
			    	<tr>
			    		<td class="colorname"><?=$print ?> :</td>
			    		<td class="colorlinks">
			    			<a href="<?=fURL::replaceInQueryString("bgcolor", $value)?>">backgrd</a>
			    			or
			    			<a href="<?=fURL::replaceInQueryString("fgcolor", $value)?>">text</a>
			    		</td>
			    	</tr>
					<?php 	
						}
			    	?>
			    </table>
		    </div>
	    </div>
	</div>
	<div id="links" class="<?=($display_options_links > 1)?'dashboardslinks':'';?> popover fade bottom in" style="display:none;">
		<div class="arrow"></div>
    	<div class="popover-content">
		<h4>Dashboard(s) in group :</h4>
		<ul>
			<?php 
				foreach ($other_dashboards_in_group as $dashboard_in_group) {
			?>
				<li><a href="<?=fURL::replaceInQueryString('dashboard_id',$dashboard_in_group->getDashboardId())?>"><?=$dashboard_in_group->getName()?></a></li>
			<?php 
				}
			?>
		</ul>
		</div>
	</div>
	
	<div id="graphscontainer">
<center> <!-- cssblasphemy but i need it look decent real quick --> 
    <h1>
    	<span id="loader"><img src="../../assets/img/loader2.gif"/></span>
    	<?=$dashboard->getName(); ?>
    	&nbsp
    	<small><?=$dashboard->getDescription(); ?></small>
    </h1>
    <p id="explanation" style="display:none;"><em class="text-info">You can select a period on a graph to zoom in</em></p>
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
        <span class="inline zoomable">
        		<img src="<?=$url_graph ?>" rel=<?=($graph_count >= $columns ? 'popover-above' : 'popover-below'); ?> title="<?=$graph->getName(); ?>" data-content="<?=$graph->getDescription(); ?>" />
        </span>
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
<script type="text/javascript" src="../../assets/js/moment.js"></script>
<script type="text/javascript">
	var loaded_graphs = 0;
	var pos_click = 0;

	function getPosition(_this) {
	        var left = 0;
	        var top = 0;
	        // Retrieve the element
	        var e = _this;
	        // While we have a parent
	        while (e.offsetParent != undefined && e.offsetParent != null)
	        {
	                // We add the parent position
	                left += e.offsetLeft + (e.clientLeft != null ? e.clientLeft : 0);
	                top += e.offsetTop + (e.clientTop != null ? e.clientTop : 0);
	                e = e.offsetParent;
	        }
	        return new Array(left,top);
	}

	function getParamValue(param,url) {
		var u = url == undefined ? document.location.href : url;
		var reg = new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
		matches = u.match(reg);
		return (matches != null && matches[2] != undefined) ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
	}

	function getGoodUnit (unit) {
		if ("secondes".indexOf(unit) > -1) {
			return "secondes";
		} else if ("minutes".indexOf(unit) > -1) {
			return "minutes";
		} else if ("hours".indexOf(unit) > -1) {
			return "hours";
		} else if ("days".indexOf(unit) > -1) {
			return "days";
		} else if ("weeks".indexOf(unit) > -1) {
			return "weeks";
		} else if ("months".indexOf(unit) > -1) {
			return "months";
		} else if ("years".indexOf(unit) > -1) {
			return "years";
		} else {
			return "";
		}
	}

	$(function(){

		$(".zoomable img").each(function(){
			$(this).load(function(){
				loaded_graphs++;
				
				if (loaded_graphs == $(".zoomable").size()) {
					$("#loader").hide();
					$("#explanation").show();
					$(".zoomable img").each(function(){
						var current_graph = this;
						var pos=getPosition(current_graph);
						var new_div = $("<div>").width($(current_graph).width())
												.height($(current_graph).height())
												.css("position","absolute")
												.css("z-index","500")
												.css('left',pos[0]+"px")
												.css('top',pos[1]+"px")
												.attr('class','zoom-div');
						$(this).parent().append(new_div);
						
						$(new_div).mousedown(function(e){
							if (e.pageX < pos[0] + 72) {
								pos_click = pos[0] + 72;
							} else if (e.pageX > pos[0] + $(current_graph).width() - 8 ) {
								pos_click = pos[0] + $(current_graph).width() - 8
							} else {
								pos_click = e.pageX;
							}
						});
						$(new_div).mouseout(function(e){
							pos_click = 0;
							pos_t = 0;
							$('#time_selector').remove();
						});
						$(new_div).mousemove(function(e){
							if (pos_click > 0) {
								$('#time_selector').remove();
								if (e.pageX < pos[0] + 72) {
									pos_t = pos[0] + 72;
								} else if (e.pageX > pos[0] + $(current_graph).width() - 8 ) {
									pos_t = pos[0] + $(current_graph).width() - 8
								} else {
									pos_t = e.pageX;
								}
								var time_selector = "<div>";
								time_selector=$(time_selector).width(Math.abs(pos_click-pos_t))
											.height($(current_graph).height())
											.css('left',((pos_click>pos_t)?pos_t:pos_click)+"px")
											.css('top',pos[1]+"px")
											.attr('id','time_selector');
								$(current_graph).parent().append(time_selector);
							}
						});
						$(new_div).mouseup(function(e){
							var diff = Math.abs(pos_t - pos_click);
							if (diff > 0) {
								try {
									var lowest = (pos_t > pos_click)?pos_click:pos_t;
									var pos_lowest = lowest - (pos[0] + 72);
									var url_graph = $(current_graph).attr("src");
									var from = getParamValue("from",url_graph);
									var until = getParamValue("until",url_graph);
	
									// We built the until
									var until_moment = moment();
									if (until != "") {
										var test_moment = moment(until,"HH:mm_YYYYMMDD");
										if (test_moment.isValid()) {
											until_moment = test_moment;
										} else {
											if (until.indexOf("midnight") == 0) {
												until_moment = until_moment.subtract("minutes",until_moment.format("mm"));
												until_moment = until_moment.subtract("hours",until_moment.format("HH"));
												until = until.substring(8,until.length);
											}
											if (until[0] == "-") {
												var reg=new RegExp('-(\\d)+(.+)');
												var until_reg = until.match(reg);
												if (until_reg != null) {
													until_moment = until_moment.subtract(getGoodUnit(until_reg[2]),until_reg[1]);
												}
											}
										}
									}
	
									// Now, we built the from
									var from_moment = moment(until_moment);
									test_moment = moment(from,"HH:mm_YYYYMMDD");
									if (test_moment.isValid()) {
										from_moment = test_moment;
									} else {
										if (from.indexOf("midnight") == 0) {
											from_moment = from_moment.subtract("minutes",from_moment.format("mm"));
											from_moment = from_moment.subtract("hours",from_moment.format("HH"));
											from = from.substring(8,from.length);
										}
										if (from[0] == "-") {
											var reg=new RegExp('-(\\d)+(.+)');
											var from_reg = from.match(reg);
											if (from_reg != null) {
												from_moment = from_moment.subtract(getGoodUnit(from_reg[2]),from_reg[1]);
											}
										}
									}
									var diff_moment = until_moment.diff(from_moment);
									if (diff_moment > 60000) {
										var nb_pixel_graph = $(current_graph).width() - 80;
										var from_zoom = (pos_lowest / nb_pixel_graph) * diff_moment;
										var until_zoom = ((nb_pixel_graph - (pos_lowest + diff)) / nb_pixel_graph) * diff_moment;
										var from_duration = moment.duration(from_zoom);
										var until_duration = moment.duration(until_zoom);
										var new_from = from_moment.add(from_duration).format("HH:mm_YYYYMMDD");
										var new_until = until_moment.subtract(until_duration).format("HH:mm_YYYYMMDD");
										
										$(".zoomable img").each(function(){
											var url_graph_to_zoom = $(this).attr("src");
											var old_from = getParamValue("from",url_graph_to_zoom);
											var old_until = getParamValue("until",url_graph_to_zoom);
											url_graph_to_zoom = url_graph_to_zoom.replace("from="+old_from,"from="+new_from);
											if (old_until == "") {
												url_graph_to_zoom += "until="+new_until;
											} else {
												url_graph_to_zoom = url_graph_to_zoom.replace("until="+old_until,"until="+new_until);
											}
											$(this).attr("src",url_graph_to_zoom);
										});
									}
								}	catch(err) {
									
								}
							}
							pos_click = 0;
							pos_t = 0;
							$('#time_selector').remove();
						});
					});
				}
			});
		});
	});
</script>
<?php 
if (!$full_screen) {
echo '<a href="' . Dashboard::makeUrl('edit',$dashboard) . '">Edit Dashboard</a> | <a href="' . Graph::makeUrl('add',$dashboard) .'">Add Graph</a> | <a href="?' . fURL::getQueryString() . '&full_screen=true">Full Screen</a>';
$tmpl->set('show_bubbles',true);
$tmpl->place('footer') ;
}
?>
