<?
class User extends fActiveRecord
{
    protected function configure()
    {
//     fORMRelated::setOrderBys($this,'Subscription',array('subscriptions.subscription_id' => 'desc'));
    }
	/**
	 * Returns all meetups on the system
	 * 
	 * @param  string  $sort_column  The column to sort by
	 * @param  string  $sort_dir     The direction to sort the column
	 * @return fRecordSet  An object containing all meetups
	 */
	static function findAll()
	{
		
       return fRecordSet::build(
          __CLASS__,
          array(),
          array()
          );
	}    

	static public function makeURL($type, $user=NULL)
	{
                if (is_object($user)) {
                  $user_id = $user->prepareUserId();
                } elseif (is_numeric($user))  {
                  $user_id = $user;
                }
                
		switch ($type)
		{
                        case 'login':
                                return 'login.php?action=log_in';
			case 'list':
				return 'user.php';
			case 'add':
				return 'user.php?action=add';
			case 'edit':
				return 'user.php?action=edit&user_id=' . $user_id;
			case 'delete':
				return 'user.php?action=delete&user_id=' . $user_id;
		}	
	}

}

