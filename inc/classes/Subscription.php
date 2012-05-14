<?
class Subscription extends fActiveRecord
{
    protected function configure()
    {
    }
	/**
	 * Returns all subscriptions on the system
	 * 
	 * @param  string  $sort_column  The column to sort by
	 * @param  string  $sort_dir     The direction to sort the column
	 * @return fRecordSet  An object containing all meetups
	 */
	static function findAll($check_id=NULL,$user_id=NULL,$limit=NULL, $page=NULL)
	{
        $filter = array();
        if (!is_null($check_id) && is_numeric($check_id)){
            $filter['check_id='] = $check_id;
        }
        if (!is_null($user_id) && is_numeric($user_id)) {
            $filter['user_id='] = $user_id;
        }
		return fRecordSet::build(
          __CLASS__,
          $filter,
          array(),
          $limit,
          $page
          );
	}    

	static function findActive($check_id=NULL)
	{
        if (!is_null($check_id) && is_numeric($check_id)){
            $filter = ' AND check_id=' . $check_id;
        } else {
            $filter = '';  
        }
         return fRecordSet::buildFromSQL(
           __CLASS__,
          array('SELECT subscriptions.* FROM subscriptions WHERE user_id = ' . fSession::get('user_id') . $filter)
          );
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
				return 'subscription.php';
			case 'add':
				return 'subscription.php?action=add&check_id=' . $obj->prepareCheck_Id();
			case 'edit':
				return 'subscription.php?action=edit&subscription_id=' . $obj->prepareSubscription_Id();
			case 'delete':
				return 'subscription.php?action=delete&subscription_id=' . $obj->prepareSubscription_Id();
		}	
	}        

}
