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
    
    static public function makeURL($type, $obj=NULL)
	{
		switch ($type)
		{
			case 'list':
				return 'dashboard.php';
			case 'add':
				return 'dashboard.php?action=add';
			case 'edit':
				return 'dashboard.php?action=edit&dashboard_id=' . $obj->prepareDashboardId();
			case 'delete':
				return 'dashboard.php?action=delete&dashboard_id=' . $obj->prepareDashboardId();
			case 'view':
				return 'dashboard.php?action=view&dashboard_id=' . $obj->prepareDashboardId();
                
		}	
	}
}