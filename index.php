<?
include 'inc/init.php';

fAuthorization::requireLoggedIn();
$breadcrumbs[] = array('name' => 'Alerts', 'url' => '#','active' => false);

$page_num = fRequest::get('page', 'int', 1);
$offset = ($page_num - 1)*$GLOBALS['PAGE_SIZE'];

$alert_count_query = 'SELECT count(distinct c.check_id) as count '.
                 'FROM subscriptions s '.
                 'JOIN checks c ON s.check_id = c.check_id '.
                 'JOIN check_results r ON s.check_id = r.check_id '.
                 'WHERE r.timestamp >= DATE_SUB(CURDATE(),INTERVAL 1 DAY) '.
                 'AND r.status IS NOT NULL '.
                 'AND acknowledged = 0 '.
                 'AND s.user_id = ' . fSession::get('user_id') . ';';

$alert_count_results = $mysql_db->query($alert_count_query);
$alert_count = $alert_count_results->fetchScalar();
$results = NULL;
if ($alert_count > $GLOBALS['PAGE_SIZE']) {

    //We need to get both the current page and a count to determine whether paging is needed.
    $latest_alerts = 'SELECT c.check_id,name,r.status,count(c.check_id) as count, r.timestamp '.
                     'FROM subscriptions s '.
                     'JOIN checks c ON s.check_id = c.check_id '.
                     'JOIN check_results r ON s.check_id = r.check_id '.
                     'WHERE r.timestamp >= DATE_SUB(CURDATE(),INTERVAL 1 DAY) '.
                     'AND r.status IS NOT NULL '.
                     'AND acknowledged = 0 '.
                     'AND s.user_id = ' . fSession::get('user_id') . ' ' .
                     'GROUP BY c.check_id ' .
                     'LIMIT ' . $GLOBALS['PAGE_SIZE'] . ' ' .
                     'OFFSET ' . $offset . ';';

    $results = $mysql_db->query($latest_alerts);
}

include 'inc/views/index.php';
