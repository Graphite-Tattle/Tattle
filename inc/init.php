<?
// Include main class loading config file
define('TATTLE_ROOT', str_replace(array('ajax'),'',getcwd()));
define('VIEW_PATH', TATTLE_ROOT . '/inc/views/');
define('JS_CACHE', TATTLE_ROOT . '/js_cache/');

include TATTLE_ROOT . '/inc/includes.php';
include TATTLE_ROOT . '/inc/functions.php';
include TATTLE_ROOT . '/inc/config.php';
include TATTLE_ROOT . '/inc/constructor_functions.php';

//Set the Template root, and set the header and footer
$tmpl = new fTemplating(VIEW_PATH);


//if (!is_dir(JS_CACHE) || !is_writable(JS_CACHE)){
//  $warning_message .= "JS Caching disabled due to js folder permissions";
//} else {
//  $tmpl->enableMinification('development', JS_CACHE, TATTLE_ROOT);
//}
$tmpl->add('css','bootstrap/bootstrap.min.css'); 
$tmpl->add('css','assets/css/jquery-ui.css');

$tmpl->add('js','assets/js/jquery.min.js'); 
$tmpl->add('js','assets/js/jquery-ui.min.js'); 
$tmpl->add('js','assets/js/jquery.collapsible.js'); 
$tmpl->add('js','assets/js/jquery.graphite.js');

$tmpl->add('js','bootstrap/js/bootstrap-modal.js');
$tmpl->add('js','bootstrap/js/bootstrap-twipsy.js');
$tmpl->add('js','bootstrap/js/bootstrap-popover.js');


$tmpl->set('header', 'header.php');
$tmpl->set('footer', 'footer.php');

//Start the Flourish Session
fSession::open();
