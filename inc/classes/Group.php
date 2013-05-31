<?
class Group extends fActiveRecord
{
    protected function configure()
    {
    }
    
  /**
	 * Returns all meetups on the system
	 * 
	 * @return fRecordSet  An object containing all meetups
	 */
	static function findAll($group_id=NULL)
	{
		if (!is_null($group_id) && is_numeric($group_id)){
			$filter = array('group_id=' => $group_id);
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
				return 'groups.php';
			case 'add':
				return 'groups.php?action=add';
			case 'edit':
				return 'groups.php?action=edit&group_id=' . (int)$obj->getGroupId();
			case 'delete':
				return 'groups.php?action=delete&group_id=' . (int)$obj->getGroupId();
			case 'list':
				return 'groups.php?action=list&group_id=' . (int)$obj->getGroupId();
		}
	}
    
}
