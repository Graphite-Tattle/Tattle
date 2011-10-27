<?php
// Congifure based on your environment :
define('GRAPHITE_URL','http://graph');
define('ERROR_COLOR','red');
define('WARN_COLOR','yellow');
define('GRAPH_WIDTH',586);
define('GRAPH_HEIGHT',308);
define('WHISPER_DIR','/opt/graphite/storage/whisper/');
define('SESSION_FILES_PATH','/var/www/graphite-tattle/inc/storage/session/');
define('FLOURISHLIB_PATH','/var/www/graphite-tattle/inc/flourish/');
$database_name = 'graphite_tattle';
$database_user = 'root';
$database_password = 'yoyoyo';


//-----------END CONFIGURATION-------------//


define('VIEW_PATH', realpath(__DIR__ . '/views/'));
$status_array = array('0' => 'OK', '1'   => 'Error', '2' => 'Warning');
//$send_methods = array('email' => 'Email','carrier_pidgin' => 'Carrier Pidgin','sms' => 'SMS');
$visibility_array = array('0'   => 'Public', '1' => 'Private');
$over_under_array = array('0'   => 'Over', '1' => 'Under');
$breadcrumbs = array();
$breadcrumbs[] = array('name' => 'Home', 'url' => '#', 'active'=> false);

error_reporting(E_STRICT | E_ALL);
fCore::enableErrorHandling('html');
fCore::enableExceptionHandling('html');

fTimestamp::setDefaultTimezone('America/New_York');

fAuthorization::setLoginPage(User::makeURL('login'));

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

/**
 * Automatically includes classes
 * 
 * @throws Exception
 * 
 * @param  string $class  Name of the class to load
 * @return void
 */
function __autoload($class)
{
    
	$flourish_file = FLOURISHLIB_PATH . $class . '.php';
	if (file_exists($flourish_file)) {
		return require $flourish_file;
	}
	
	$file = realpath(__DIR__ . '/classes/' . $class . '.php');
 
  	if (file_exists($file)) {
		return require $file;
	}
	
	throw new Exception('The class ' . $class . ' could not be loaded');
}
