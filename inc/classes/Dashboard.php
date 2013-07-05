<?
class Dashboard extends fActiveRecord
{
    protected function configure()
    {
    }
    
  /**
	 * Returns all meetups on the system
	 * 
	 * @param  string  $sort_column  The column to sort by
	 * @param  string  $sort_dir     The direction to sort the column
	 * @return fRecordSet  An object containing all meetups
	 */
	static function findAll($dashboard_id=NULL)
	{
        if (!is_null($dashboard_id) && is_numeric($dashboard_id)){
            $filter = array('dashboard_id=' => $dashboard_id);
        } else {
            $filter = array();  
        }
       return fRecordSet::build(
          __CLASS__,
          $filter,
          array()
          );
	}   
	
	/**
	 * Returns all meetups on the system
	 *
	 * @param  string  $sort_column  The column to sort by
	 * @param  string  $sort_dir     The direction to sort the column
	 * @return fRecordSet  An object containing all meetups
	 */
	static function findAllByFilter($group_id)
	{
		return fRecordSet::build(
				__CLASS__,
				array('group_id=' => $group_id),
				array()
		);
	}
    
    static public function makeURL($type, $obj=NULL)
	{
		switch ($type)
		{
			case 'list':
				if (empty($obj)) {
					return 'dashboard.php';
				} else {
					return 'dashboard.php?filter_group_id=' . $obj;
				}
			case 'add':
				return 'dashboard.php?action=add';
			case 'edit':
				return 'dashboard.php?action=edit&dashboard_id=' . (int)$obj->getDashboardId();
			case 'delete':
				return 'dashboard.php?action=delete&dashboard_id=' . (int)$obj->getDashboardId();
			case 'view':
				return 'dashboard.php?action=view&dashboard_id=' . (int)$obj->getDashboardId();
			case 'export':
				return 'dashboard.php?action=export&dashboard_id=' . (int)$obj->getDashboardId();
			case 'mass_export':
				return 'dashboard.php?action=mass_export';
			case 'import':
				return 'dashboard.php?action=import';
			case 'clean':
				return 'dash/' . (int)$obj->getDashboardId();
                
		}	
	}
	
	public function export_in_json ()
	{
		$dashboard_id = $this->getDashboardId();
		$json_env = parent::export_in_json();
	
		// Find all the lines of this graph
		$graphs = Graph::findAll($dashboard_id);
		$json_graphs_array = array();
		foreach ($graphs as $graph_in_dashboard) {
			// Export them in JSON
			$json_graphs_array[] = $graph_in_dashboard->export_in_json();
		}
		// Implode them
		$json_graph = "\"graphs\":[";
		if (!empty($json_graphs_array)) {
			$json_graph .= implode(",", $json_graphs_array);
		}
		$json_graph .= "]";
	
		// Replace the last } of the json
		$json_env[strlen($json_env)-1] = ",";
		// Concat the graph with its lines
		$json_env .= ($json_graph . "}");
	
		return $json_env;
	}
	
	
	static public function import_from_json_to_group($json,$group_id=NULL)
	{
		$result = true;
		$json_array = json_decode($json,TRUE);
		if (!empty($json_array)) {
			if (array_key_exists("user_id",$json_array)) {
				// In this case, we only have a dashboard, not an array of dashboard
				// We convert it into an array
				$json_array = array($json_array);
			}
			foreach ($json_array as $dashboard_to_create) {
				$column_to_ignore = array('dashboard_id','group_id','graphs');
				$new_dashboard = fActiveRecord::array_to_dbentry($dashboard_to_create, __CLASS__,$column_to_ignore);
				if ($new_dashboard !== NULL) {
					$new_dashboard->setGroupId(empty($group_id)?$GLOBALS['DEFAULT_GROUP_ID']:$group_id);
					$new_dashboard->setUserId(fSession::get('user_id',1));
					$new_dashboard->store();
					if (in_array('graphs', array_keys($dashboard_to_create))) {
						$new_dashboard_id = $new_dashboard->getDashboardId();
						foreach ($dashboard_to_create['graphs'] as $graph) {
							$result_graph = (Graph::import_from_array_to_dashboard($graph, $new_dashboard_id));
							$result = $result && $result_graph;
						}
					}
				} else {
					$result = false;
				}
			}
		} else {
			fMessaging::create('error', "/".Dashboard::makeUrl('list'),"Empty or malformed file");
			$result = false;
		}
		return $result;
	}
	
}
