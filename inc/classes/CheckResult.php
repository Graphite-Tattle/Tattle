<?
class CheckResult extends fActiveRecord
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
	static function findAll($check_id=NULL,$all=false,$limit=NULL,$page=NULL)
	{
	if (!is_null($check_id) && is_numeric($check_id)) {
          $filter = array('check_id=' => $check_id);
        } else {
          $filter = array();
        } 
        if (!$all) {
          $filter['acknowledged='] = 0;
        }
       return fRecordSet::build(
          __CLASS__,
          $filter,
          array('timestamp' => 'desc'),
          $limit,
          $page
          );
	}    

        static function ackAll($check_id=NULL)
	{
	if (!is_null($check_id) && is_numeric($check_id)) {
          $filter = array('check_id=' => $check_id);
        } else {
          $filter = array();
        } 
       return fRecordSet::build(
          __CLASS__,
          $filter,
          array('status' => 'desc')
          );
	}


        static public function findUsersResults()
        {
         return fRecordSet::buildFromSQL(
           __CLASS__,
           array('SELECT check_results.* FROM check_results JOIN subscriptions ON check_results.check_id = subscriptions.check_id and subscriptions.user_id = ' . fSession::get('user_id')));
         }
    /**
	 * Creates all Check related URLs for the site
	 * 
	 * @param  string $type  The type of URL to make: 'list', 'add', 'edit', 'delete'
	 * @param  Meetup $obj   The Check object for the edit and delete URL types
	 * @return string  The URL requested
	 */
	static public function makeURL($type, $obj=NULL)
	{    
		switch ($type)
		{
			case 'list':
				return 'result.php?action=list&check_id=' . $obj->prepareCheckId();
                return '';
			case 'edit':
				return 'result.php?action=edit&check_id=' . $obj->prepareCheckId();
			case 'delete':
				return 'result.php?action=delete&check_id=' . $obj->prepareCheckId();
			case 'ackAll':
				return 'result.php?action=ackAll&check_id=' . $obj->prepareCheckId();
		}	
	}       
}
