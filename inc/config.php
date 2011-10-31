<?php
// Congifure based on your environment :
define('GRAPHITE_URL','http://graph');
define('GANGLIA_URL','http://localhost:8000/ganglia2');
define('SOURCE_ENGINE', 'GANGLIA');
define('ERROR_COLOR','red');
define('WARN_COLOR','yellow');
define('GRAPH_WIDTH',586);
define('GRAPH_HEIGHT',308);
define('WHISPER_DIR','/opt/graphite/storage/whisper/');
define('SESSION_FILES_PATH','/tmp');
define('FLOURISHLIB_PATH','/flourish/');
define('BOOTSTRAP_PATH','/../bootstrap/');
$database_name = 'graphite_tattle';
$database_user = 'root';
$database_password = 'yoyoyo';


//-----------END CONFIGURATION-------------//
// Help people installing know if we can't find flourishlib or bootstrap
$config_error = '';
$config_exit = false;
if (!file_exists($root_path . FLOURISHLIB_PATH . 'fCore.php')) { 
  $config_error .= "<br/>Tattle Error <br />" .
                   "Flourishlib not found : expected at : " . $root_path . FLOURISHLIB_PATH . "<br />" .
                   "Can be changed in inc/config.php : FLOURISHLIB_PATH";
  $config_exit = true;
}

if (!file_exists($root_path . BOOTSTRAP_PATH . 'bootstrap.css')) {
  $config_error .= "<br/>Tattle Error <br />" .
                   "Bootstrap library not found : expected at : " . $root_path . BOOTSTRAP_PATH . "<br />" .
                   "Can be changed in inc/config.php : BOOTSTRAP_PATH";
  $config_exit = true;
}

if ($config_exit) {
  print $config_error;
  exit;
}

define('VIEW_PATH', $root_path . '/views/');
$status_array = array('0' => 'OK', '1'   => 'Error', '2' => 'Warning');
$visibility_array = array('0'   => 'Public', '1' => 'Private');
$over_under_array = array('0'   => 'Over', '1' => 'Under');
$breadcrumbs = array();
$breadcrumbs[] = array('name' => 'Home', 'url' => '#', 'active'=> false);

error_reporting(E_STRICT | E_ALL);
fCore::enableErrorHandling('html');
fCore::enableExceptionHandling('html');

fTimestamp::setDefaultTimezone('America/New_York');

fAuthorization::setLoginPage(User::makeURL('login'));
fAuthorization::setAuthLevels(
                 array('admin' => 100,
                       'user'  => 50,
                       'guest' => 25
                 )
                );
// This prevents cross-site session transfer
fSession::setPath(SESSION_FILES_PATH);

$plugin_settings = array();
foreach (glob("plugins/*_plugin.php") as $plugin) {
  include_once($plugin);
  $plugin_name = str_replace(array('plugins/', '_plugin.php'), '', $plugin);  
  $plugin_config = $plugin_name . '_config';
  if (function_exists($plugin_config)) {
    $plugin_settings[$plugin_name] = $plugin_config();
    $send_methods[$plugin_name] = $plugin_settings[$plugin_name]['name'];
  }  
}
