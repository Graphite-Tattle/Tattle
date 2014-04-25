<?php fHTML::sendHeader() ?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?=$this->prepare('title'); ?><?=(strpos($this->get('title'), 'Tattle') === FALSE ? ' - Tattle' : ''); ?></title>
    <!-- Le HTML5 shim, for IE6-8 support of HTML elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

   <?php
       $this->place('css');
       $this->place('js');
    if ($this->get('graphlot')) { ?>
    <script type="text/javascript" src="<?=$GLOBALS['GRAPHITE_URL']; ?>/content/js/jquery.flot.js"></script>
    <script type="text/javascript" src="<?=$GLOBALS['GRAPHITE_URL']; ?>/content/js/jquery.autocomplete.js"></script>
    <script type="text/javascript" src="<?=$GLOBALS['GRAPHITE_URL']; ?>/content/js/jquery.flot.selection.js"></script>
    <script type="text/javascript" src="<?=$GLOBALS['GRAPHITE_URL']; ?>/content/js/jquery.flot.crosshair.js"></script>
    <script type="text/javascript">

    $(document).ready(function () {
        $('.graphite').graphiteGraph();
    });

    </script>

<?php }

if ($this->get('addeditdocready')) { ?>
  <script type="text/javascript">
    $(document).ready(function() {
      $("fieldset.startCollapsed").collapse( { closed: false } );
      reloadGraphiteGraph();
      attachTooltips();
    });
  </script>
<?php }

if (!$this->get('full_screen')) { ?>
    <style type="text/css">
      body {
        /*padding-top: 60px;*/
      }
    </style>
<?php } ?>

    <script type="text/javascript">
      $(function() {
        $("#check-target").autocomplete({
          source: "ajax/autocomplete.php",
          minLength: 2,
          select: function(event, ui) {
            $('#check-target').val(ui.item.id);
          }
        });
      });

    </script>
    <script type="text/javascript">
      $(function() {
        $("#line-target").autocomplete({
          source: "ajax/autocomplete.php",
          minLength: 2,
          select: function(event, ui) {
            $('#line-target').val(ui.item.id);
          }
        });
      });

    </script>
<?php
if ($this->get('full_screen') && $this->get('refresh') > 0) {
  echo '<meta http-equiv="refresh" content="' . $this->get('refresh') . '">';
} ?>
  </head>
  <body>

<?php
if (!$this->get('full_screen')) { ?>
   <nav class="navbar navbar-inverse">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="navbar-brand" href="index.php">Tattle </a>
          <div class="navbar-collapse collapse">
	          <ul class="nav navbar-nav">
	            <?
	
	              $current_url = '?'.fURL::getQueryString();
	              echo '<li' . (fURL::is_menu_active('index') ? ' class="active"' : '') . '><a href="index.php">Alerts</a></li>'. "\n";
	              $threshold_check_list = Check::makeURL('list', 'threshold');
	              echo '<li' . (fURL::is_menu_active('check','type=threshold') ? ' class="active"' : '') . '><a href="' . $threshold_check_list . '" >Threshold Checks</a></li>' . "\n";
	              $predictive_check_list = Check::makeURL('list', 'predictive');
	              echo '<li' . (fURL::is_menu_active('check','type=predictive') ? ' class="active"' : '') . '><a href="' . $predictive_check_list . '" >Predictive Checks</a></li>' . "\n";
	              $subscription_list = Subscription::makeURL('list');
	              echo '<li' . (fURL::is_menu_active('subscription') ? ' class="active"' : '') .'><a href="' . $subscription_list . '" >Subscriptions</a></li>' . "\n";
	              $dashboard_list = Dashboard::makeURL('list');
	              echo '<li' . (fURL::is_menu_active('dashboard') ? ' class="active"' : '') . '><a href="' . $dashboard_list . '">Dashboards</a></li>';
	              $group_list = Group::makeURL('list');
	              echo '<li' . (fURL::is_menu_active('group') ? ' class="active"' : '') . '><a href="' . $group_list . '">Groups</a></li>';
	              $setting_list = Setting::makeURL('list','user');
	              echo '<li' . (fURL::is_menu_active('setting','type=user') ? ' class="active"' : '') . '><a href="' . $setting_list . '" >Settings</a></li>' . "\n";
	if (fAuthorization::checkAuthLevel('admin')) {
	              $setting_list = Setting::makeURL('list','system');
	              echo '<li' . (fURL::is_menu_active('setting','type=system') ? ' class="active"' : '') . '><a href="' . $setting_list . '" >System Settings</a></li>' . "\n";
	              $user_list = User::makeURL('list');
	              echo '<li' . (fURL::is_menu_active('user') ? ' class="active"' : '') . '><a href="' . User::makeURL('list') . '" >Users</a></li>';
	}
	?>
	          </ul>
 <?php   if (is_numeric(fSession::get('user_id'))) { ?>
 		<p class="navbar-text navbar-right">
 			<a href="<?=User::makeUrl('edit',fSession::get('user_id'));?>">
 				<span style="color:white;">Logged in as</span>&nbsp;
 				<span style="color:#0088cc;"><?=fSession::get('user_name'); ?></span>
 			</a>
 		</p>
    <?php } ?>
	     </div>
</div>
        </div>
      </nav>
<?php } ?>
<div class="container-fluid">
<?php
    $breadcrumbs = $this->get('breadcrumbs');
    if (is_array($breadcrumbs)) {
    echo '<ul class="breadcrumb">';
      foreach($breadcrumbs as $crumb) {
        echo '<li' . (isset($crumb['class']) ? ' class="' . $crumb['class'] .'"' : ' class="active"' ) .'><a href="'. $crumb['url'] . '">' . $crumb['name'] . '</a>';
      }
     echo '</ul>';
    } ?>
<?php

if (fMessaging::check('error', fURL::get())) {
  echo '<div class="alert alert-danger">';
  echo '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    fMessaging::show('error', fURL::get());
  echo '</div>';
}


if (fMessaging::check('success', fURL::get())) {
  echo '<div class="alert alert-success">';
  echo '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    fMessaging::show('success', fURL::get());
  echo '</div>';
}

