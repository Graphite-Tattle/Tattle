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
				return 'dashboard.php';
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
	
}
