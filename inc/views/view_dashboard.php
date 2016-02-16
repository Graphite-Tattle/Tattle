<?php

  $tmpl->set('title', $dashboard->getName().' dashboard (Tattle)');
  $tmpl->set('full_screen', $full_screen);
  $tmpl->place('header');
?>
<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container-fluid text-center navbar-form">
			<a href="<?=Dashboard::makeURL('list',$dashboard->getGroupId())?>" class="btn btn-primary">Return to list</a>
			<a href="<?=Dashboard::makeURL('edit',$dashboard)?>" class="btn btn-default">Edit this dashboard</a>
			<?php if ($dashboard->getRefreshRate() > 0 ) {?>
				<a href="#" class="btn btn-default" id="disable_refresh_btn" onclick="disable_refresh();return false;">Disable refresh</a>
		    	<a href="#" class="btn btn-default" id="enable_refresh_btn" onclick="enable_refresh();return false;" style="display:none;">Enable refresh</a>
		    <?php } else { ?>
		    	<a href="#" class="btn btn-default" id="enable_refresh_btn" onclick="window.location.reload(true);">Refresh now</a>
		    <?php }?>
		
	<?php 
		if (($display_options_links%2)==1) {
			// True only if its value is 1 or 3
	?>
		<a href="<?=fURL::replaceInQueryString("display_options_links", $display_options_links-1)?>" class="btn btn-default optionsbtn">Hide options</a>
	<?php } else { ?>
		<a href="<?=fURL::replaceInQueryString("display_options_links", $display_options_links+1)?>" class="btn btn-default optionsbtn">Show options</a>
	<?php
		}
		
		if ($display_options_links > 1) {
			// True only if its value is 2 or 3
	?>
		<a href="<?=fURL::replaceInQueryString("display_options_links", $display_options_links-2)?>" class="btn btn-default linksbtn">Hide links</a>
	<?php } else { ?>
		<a href="<?=fURL::replaceInQueryString("display_options_links", $display_options_links+2)?>" class="btn btn-default linksbtn">Show links</a>
	<?php
		}
	?>
	</div>
	</div>
</nav>
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
    	<img id="loader" src="assets/img/loader2.gif" style="display:none;"/>
    	<?=$dashboard->getName(); ?>
    	<br/>
    	<small><?=$dashboard->getDescription(); ?></small>
    </h1>
    <p>
		<em class="text-info inline" id="explanation" style="display:none;">You can select a period on a graph to zoom in</em>
		<?php if ($dashboard->getRefreshRate() > 0) { ?>
			<em class="text-warning inline" id="refresh_warning" style="display:none; margin-left:10px"><i class="glyphicon glyphicon-warning-sign"></i>&nbsp;Warning : refresh is disabled</em>
		<?php } else {?>
			<em class="text-warning inline" id="refresh_warning" style="margin-left:10px"><i class="glyphicon glyphicon-warning-sign"></i>&nbsp;Warning : refresh is permanently disabled.</em>
		<?php } ?>
    </p>
    <div class="row">
	<?php
        $graph_count = 0;
        $columns = $dashboard->getColumns();
        $height = $dashboard->getGraphHeight();
        $width = $dashboard->getGraphWidth();
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
			if ($key == "from" or $key == "until") {
				$value = str_replace("+", "%2B", $value);
				$value = str_replace(" ", "%2B", $value);
			} elseif ($key == "height") {
				$height = $value;
			}  elseif ($key == "width") {
				$width = $value;
			}
			$url_graph = addOrReplaceInURL($url_graph,$key,$value);
		  }
        
		?>
        <div class="zoomable inline">
        	<div class="slider" style="width:<?=$width;?>px; height:<?=$height;?>px;">
	        	<img src="<?=$url_graph ?>" rel=<?=($graph_count >= $columns ? 'popover-above' : 'popover-below'); ?> title="<?=$graph->getName(); ?>" data-content="<?=$graph->getDescription(); ?>" />
	        	<input type="hidden" name="hasYAxis" value="<?=$graph->has_y_axis_title()?"true":"false"?>" />
        	</div>
        	<a class="btn graphbtn btn-info" href="<?=Graph::makeUrl('edit',$graph);?>">
        		Edit "
        		<?php
        			$graph_name = $graph->getName();
        			if (strlen($graph_name) > 50) {
						$graph_name = substr($graph_name,0,47) . "...";
					}
					echo $graph_name;
        		?>"
        		<i class=" glyphicon glyphicon-edit"></i>
        	</a>
        	<a class="btn graphbtn btn-default" href="#" target="_blank"
        		onclick="$(this).attr('href',$($(this).parent().find('img')).attr('src')+'&width=3000&height=700');return true;"
        	>Large view <i class="glyphicon glyphicon-new-window"></i></a>
        </div>
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
	var bg_color_body_disabled = "#F8EEEE";
	<?php if ($dashboard->getRefreshRate() > 0) { ?>
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
			$('body').css("background-color",bg_color_body_disabled);
		}
		
		function enable_refresh() {
			refresh_enabled=true;
			$('#disable_refresh_btn').show();
			$('#enable_refresh_btn').hide();
			$('#refresh_warning').hide();
			$('body').css("background-color","#FFFFFF");
			setTimeout(refresh_page,(<?=$dashboard->getRefreshRate()?> * 1000));
		}
	<?php } ?>

	// Function that extract a param from the url
	function getParamValue(param,url) {
		var u = url == undefined ? document.location.href : url;
		var reg = new RegExp('(\\?|&|^)'+param+'=(.*?)(&|$)');
		matches = u.match(reg);
		return (matches != null && matches[2] != undefined) ? decodeURIComponent(matches[2]).replace(/\+/g,' ') : '';
	}

	// That function transforms a graphite unit into a unit that moment.js understands
	function getGoodUnit (unit) {
		if ("secondes".indexOf(unit) == 0) {
			return "secondes";
		} else if ("minutes".indexOf(unit) == 0) {
			return "minutes";
		} else if ("hours".indexOf(unit) == 0) {
			return "hours";
		} else if ("days".indexOf(unit) == 0) {
			return "days";
		} else if ("weeks".indexOf(unit) == 0) {
			return "weeks";
		} else if ("months".indexOf(unit) == 0) {
			return "months";
		} else if ("years".indexOf(unit) == 0) {
			return "years";
		} else {
			return "";
		}
	}

	// This function gives a day that moment.js understands 
	// Default : 1 (monday) 
	function getGoodDay (day) {
		if (day == "sunday") {return 0;}
		if (day == "satursday") {return 6;}
		if (day == "friday") {return 5;}
		if (day == "thursday") {return 4;}
		if (day == "wednesday") {return 3;}
		if (day == "tuesday") {return 2;}
		return 1;
	}

	$(function(){

		<?php if ($dashboard->getRefreshRate() > 0) { ?>
			setTimeout(refresh_page,(<?=$dashboard->getRefreshRate()?> * 1000));
		<?php } else {?>
			$('body').css("background-color",bg_color_body_disabled);
		<?php } ?>

		$(".slider").each(function(){
			var slider_this = this;
			var input_hidden = $("input",$(slider_this));
			var left_margin = ($(input_hidden).val()=="true")?72:34;
			var right_margin = <?=$width?> - 12;
			var mini = 1;
			var maxi = <?=$width?>;
			var start = -1;
			$(this).slider({
				min : mini,
				max : maxi,
				start: function(event, ui) {
					$("#slide_zone").remove();
					start = -1;
					$(slider_this).prepend(
							$("<div id='slide_zone' style='height:<?=$height;?>px;'>")
					);
					$("#slide_zone").css("position","absolute");
				},
				slide: function(event, ui) {
					if (start == -1) {
						start = ui.value;
						if (start < left_margin) {start = left_margin;}
					}

					var current = ui.value;
					if (current < left_margin) {
						current = left_margin;
					} else if (current > right_margin) {
						current = right_margin;
					}

					var left_border_zone = 0;
					var width = 0;
					if (current > start) {
						left_border_zone = start;
						width_zone = current - start;
					} else {
						left_border_zone = current;
						width_zone = start - current;
					}
					$("#slide_zone").css("left", (left_border_zone)+"px");
					$("#slide_zone").css("width",(width_zone)+"px");
				},
				stop : function(event, ui) {
					$("#slide_zone").remove();
					<?php if ($dashboard->getRefreshRate() > 0) { ?>
						disable_refresh();
					<?php } ?>
					var current = ui.value;
					// If we aren't on the border 
					if (current > mini && current < maxi) {
						// We place the curent in the graphe if it isn't 
						if (current < left_margin) {
							current = left_margin;
						} else if (current > right_margin) {
							current = right_margin;
						}
						// If the user REALLY zoomed 
						if (current != start) {
							var src = $("img",slider_this).attr("src");
							var from_reg  = /&from=([^&]*)(&.*|$)/;
							var until_reg = /&until=([^&]*)(&.*|$)/;
							var from_src_match  = src.match(from_reg);
							var until_src_match = src.match(until_reg);
							var from_src  = from_src_match[1];
							var until_src = (until_src_match!=null?until_src_match[1]:"");
							
							var until_moment = moment(now);
							if (until_src != "") {
								// Parse the "until" 
								var test_moment1 = moment(until_src,"HH:mm_YYYYMMDD");
								var test_moment2 = moment(until_src,"YYYYMMDD");
								if (test_moment1.isValid()) {
									until_moment = test_moment1;
								} else if (test_moment2.isValid()) {
									until_moment = test_moment2;
								} else {
									// If it starts with "midnight" 
									if (until_src.indexOf("midnight") == 0) {
										// We substract the hours and minutes
										until_moment = until_moment.subtract("minutes",until_moment.format("mm"));
										until_moment = until_moment.subtract("hours",until_moment.format("HH"));
										until_src = until_src.substring(8,until_src.length);
									}
									var until_day = until_src.match(/^([^-]+)-?/);
									if (until_day != null) {
										// We take the good day 
										until_moment = moment(getGoodDay(until_day[1]) + "_" + now.format("WW"), "E_WW");
										until_src = until_src.replace(until_day[1],"");
									}
									// Now we compute the rest of the until 
									if (until_src[0] == "-") {
										var reg=new RegExp('-(\\d+)(\\D+)');
										var until_match = until_src.match(reg);
										if (until_match != null) {
											until_moment = until_moment.subtract(getGoodUnit(until_match[2]),until_match[1]);
										}
									}
								}
							}
							
							var from_moment = moment(from_moment);
							// Same thing for the "from" 
							test_moment1 = moment(from_src,"HH:mm_YYYYMMDD");
							test_moment2 = moment(from_src,"YYYYMMDD");
							if (test_moment1.isValid()) {
								from_moment = test_moment1;
							} else if (test_moment2.isValid()) {
								from_moment = test_moment2;
							} else {
								if (from_src.indexOf("midnight") == 0) {
									from_moment = from_moment.subtract("minutes",from_moment.format("mm"));
									from_moment = from_moment.subtract("hours",from_moment.format("HH"));
									from_src = from_src.substring(8,from_src.length);
								}
								var from_day = from_src.match(/^([^-]+)-?/);
								if (from_day != null) {
									from_moment = moment(getGoodDay(from_day[1]) + "_" + from_moment.format("WW"), "E_WW");
									from_src = from_src.replace(from_day[1],"");
								}
								if (from_src[0] == "-") {
									var reg=new RegExp('-(\\d+)(\\D+)');
									var from_match = from_src.match(reg);
									if (from_match != null) {
										from_moment = from_moment.subtract(getGoodUnit(from_match[2]),from_match[1]);
									}
								}
							}
							var diff_moment = until_moment.diff(from_moment);
							// If the zoom is greater than a minute (diff return a value in milisecond)
							if (diff_moment > 60000) {
								var diff = Math.abs(start - current);
								var lowest = ((start < current) ? start : current) - left_margin;
								var nb_pixel_graph = right_margin - left_margin;
								var from_zoom = (lowest / nb_pixel_graph) * diff_moment;
								var until_zoom = ((nb_pixel_graph - (lowest + diff)) / nb_pixel_graph) * diff_moment;
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

								$(".slider img").each(function(){
									var url_graph_to_zoom = $(this).attr("src");
									var old_from = getParamValue("from",url_graph_to_zoom);
									var old_until = getParamValue("until",url_graph_to_zoom);
									url_graph_to_zoom = url_graph_to_zoom.replace("from="+old_from,"from="+new_from);
									if (old_until == "") {
										url_graph_to_zoom += "&until="+new_until;
									} else {
										url_graph_to_zoom = url_graph_to_zoom.replace("until="+old_until,"until="+new_until);
									}
									$(this).attr("src",url_graph_to_zoom);
								});
							}
						}
					}
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
