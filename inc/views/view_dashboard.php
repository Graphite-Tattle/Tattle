<?php

  $tmpl->set('title', 'Tattle : Self Service Alerts based on Graphite metrics');
  $tmpl->set('full_screen', $full_screen);
//  $tmpl->set('refresh',$dashboard->getRefreshRate());
  $tmpl->place('header');
?>
<div class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container text-center">
			<a href="<?=Dashboard::makeURL('list',$dashboard->getGroupId())?>" class="btn btn-primary">Return to list</a>
			<a href="<?=Dashboard::makeURL('edit',$dashboard)?>" class="btn">Edit this dashboard</a>
			<a href="#" class="btn" id="disable_refresh_btn" onclick="disable_refresh();return false;">Disable refresh</a>
	    	<a href="#" class="btn" id="enable_refresh_btn" onclick="enable_refresh();return false;" style="display:none;">Enable refresh</a>
		
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
    	<img id="loader" src="assets/img/loader2.gif"/>
    	<?=$dashboard->getName(); ?>
    	<br/>
    	<small><?=$dashboard->getDescription(); ?></small>
    </h1>
    <p>
		<em class="text-info inline" id="explanation" style="display:none;">You can select a period on a graph to zoom in</em>
		<em class="text-warning inline" id="refresh_warning" style="display:none; margin-left:10px"><i class="icon-warning-sign"></i>&nbsp;Warning : refresh is disabled</em>
    </p>
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
        <span class="zoomable inline">
        	<img src="<?=$url_graph ?>" rel=<?=($graph_count >= $columns ? 'popover-above' : 'popover-below'); ?> title="<?=$graph->getName(); ?>" data-content="<?=$graph->getDescription(); ?>" />
        	<input type="hidden" name="hasYAxis" value="<?=$graph->has_y_axis_title()?"true":"false"?>" />
        	<br/>
        	<a class="btn graphbtn btn-info" href="<?=Graph::makeUrl('edit',$graph);?>">Edit "<?= $graph->getName();?>"</a>
        	<a class="btn graphbtn btn-inverse" href="#" target="_blank"
        		onclick="$(this).attr('href',$($(this).parent().find('img')).attr('src')+'&width=3000&height=700');return true;"
        	>Large view</a>
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
<script type="text/javascript" src="assets/js/moment.js"></script>
<script type="text/javascript">

	var now = moment();
	var refresh_enabled = true;
	function refresh_page() {
		if (refresh_enabled) {
			var new_now = moment();
			// Diff returns a value in miliseconds
			var diff = new_now.diff(now);
			// If it's time to refresh
			if (diff > <?=$dashboard->getRefreshRate()?> * 1000) {
				// We do so
				window.location.reload(true);
			} else {
				// Else we set a new timeout with the remaining time + 1s to be sure
				setTimeout(refresh_page,(<?=$dashboard->getRefreshRate()?> * 1000) - diff + 1000);
			}
		}
	}
	
	function disable_refresh() {
		refresh_enabled=false;
		$('#disable_refresh_btn').hide();
		$('#enable_refresh_btn').show();
		$('#refresh_warning').show();
		$('body').css("background-color","#F8EEEE");
	}
	
	function enable_refresh() {
		refresh_enabled=true;
		$('#disable_refresh_btn').show();
		$('#enable_refresh_btn').hide();
		$('#refresh_warning').hide();
		$('body').css("background-color","#FFFFFF");
		setTimeout(refresh_page,(<?=$dashboard->getRefreshRate()?> * 1000));
	}

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

	// Function that extract a param from the url
	function getParamValue(param,url) {
		var u = url == undefined ? document.location.href : url;
		var reg = new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
		matches = u.match(reg);
		return (matches != null && matches[2] != undefined) ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
	}

	// That function transforms a graphite unit into a unit that moment.js understands
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

		setTimeout(refresh_page,(<?=$dashboard->getRefreshRate()?> * 1000));

		var loaded_graphs = 0;
		var pos_click = 0;
		var dont_built_div = false;
		$(".zoomable img").each(function(){
			$(this).load(function(){
				loaded_graphs++;
				// We need to wait for all the graphs to be loaded
				// If we dont, the div that tracks the mouse movements won't be on the right place
				// So if all the graphs are loaded
				if (loaded_graphs >= $(".zoomable").size() && !dont_built_div) {
					dont_built_div = true;
					// Hide the loader
					$("#loader").hide();
					$("#explanation").show();
					$(".zoomable img").each(function(){
						var current_graph = this;
						var input_hidden = $(current_graph).parent().find('input');
						var left_margin = ($(input_hidden).val()=="true")?72:37;
						var right_margin = 12;
						var pos=getPosition(current_graph);
						// That div will track the mouse movements
						var new_div = $("<div>").width($(current_graph).width())
												.height($(current_graph).height())
												.css("position","absolute")
												.css("z-index","500")
												.css('left',pos[0]+"px")
												.css('top',pos[1]+"px")
												.attr('class','zoom-div');
						$(this).parent().append(new_div);
						
						$(new_div).mousedown(function(e){
							// The graph begin at [left_margin]px from the left border
							if (e.pageX < pos[0] + left_margin) {
								pos_click = pos[0] + left_margin;
							} else if (e.pageX > pos[0] + $(current_graph).width() - right_margin ) {
								// And it ends at [right_margin]px from the right border
								pos_click = pos[0] + $(current_graph).width() - right_margin;
							} else {
								pos_click = e.pageX;
							}
						});
						$(new_div).mouseout(function(e){
							// If the mouse is out, reset the value and remove the selector
							pos_click = 0;
							pos_t = 0;
							$('#time_selector').remove();
						});
						$(new_div).mousemove(function(e){
							// If the user has clicked
							if (pos_click > 0) {
								// Remove the div that displays the selector
								$('#time_selector').remove();
								// Compute the current position like when the user clicked
								if (e.pageX < pos[0] + left_margin) {
									pos_t = pos[0] + left_margin;
								} else if (e.pageX > pos[0] + $(current_graph).width() - right_margin ) {
									pos_t = pos[0] + $(current_graph).width() - right_margin;
								} else {
									pos_t = e.pageX;
								}
								// Create the div that displays the selector
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
							// If a time was selected, not just a click
							if (diff > 0) {
								try {
									var lowest = (pos_t > pos_click)?pos_click:pos_t;
									// Compute the number of pixel from left border of the graph
									var pos_lowest = lowest - (pos[0] + left_margin);
									var url_graph = $(current_graph).attr("src");
									var from = getParamValue("from",url_graph);
									var until = getParamValue("until",url_graph);
	
									// We built the until
									var until_moment = moment(now);
									if (until != "") {
										// We try to parse the given "until"
										var test_moment = moment(until,"HH:mm_YYYYMMDD");
										if (test_moment.isValid()) {
											until_moment = test_moment;
										} else {
											// If it begin by midnight
											if (until.indexOf("midnight") == 0) {
												// We substract the hours and minutes
												until_moment = until_moment.subtract("minutes",until_moment.format("mm"));
												until_moment = until_moment.subtract("hours",until_moment.format("HH"));
												until = until.substring(8,until.length);
											}
											// Now we compute the rest of the until
											if (until[0] == "-") {
												var reg=new RegExp('-(\\d+)(.+)');
												var until_reg = until.match(reg);
												if (until_reg != null) {
													until_moment = until_moment.subtract(getGoodUnit(until_reg[2]),until_reg[1]);
												}
											}
										}
									}
	
									// Now, we built the "from" exactly as the "until"
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
											var reg=new RegExp('-(\\d+)(.+)');
											var from_reg = from.match(reg);
											if (from_reg != null) {
												from_moment = from_moment.subtract(getGoodUnit(from_reg[2]),from_reg[1]);
											}
										}
									}
									var diff_moment = until_moment.diff(from_moment);
									// If the zoom is greater than a minute (diff return a value in milisecond)
									if (diff_moment > 60000) {
										var nb_pixel_graph = $(current_graph).width() - (left_margin + right_margin);
										var from_zoom = (pos_lowest / nb_pixel_graph) * diff_moment;
										var until_zoom = ((nb_pixel_graph - (pos_lowest + diff)) / nb_pixel_graph) * diff_moment;
										var from_duration = moment.duration(from_zoom);
										var until_duration = moment.duration(until_zoom);
										from_moment = from_moment.add(from_duration);
										until_moment = until_moment.subtract(until_duration);
										var new_from = from_moment.format("HH:mm_YYYYMMDD");
										var new_until = until_moment.format("HH:mm_YYYYMMDD");
										// We have a problem with the rounded so we have to test it manually
										// because sometimes, it has the same value
										if (new_from == new_until) {
											until_moment = until_moment.add("minutes",1);
											new_until = until_moment.format("HH:mm_YYYYMMDD");
										}
										
										// Show the loader
										$("#loader").show();
										// Disable refresh 
										disable_refresh();
										var reloaded_graphs = 0;
										// We apply the zoom to all the graph
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
											$(this).load(function(){
												reloaded_graphs++;
												if (reloaded_graphs >= $(".zoomable").size()) {
													// Hide the loader
													$("#loader").hide();
													reloaded_graphs = 0;
												}
											});
										});
									}
								}	catch(err) {
									
								}
							}
							// After a zoom, reset values and remove the selector
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
