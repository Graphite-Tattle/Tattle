<?
class Line extends fActiveRecord
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
	static function findAll($graph_id=NULL)
	{
       return fRecordSet::build(
          __CLASS__,
          array('graph_id=' =>$graph_id),
          array()
          );
	}   
    
    static public function makeURL($type, $obj=NULL)
	{
		switch ($type)
		{
			case 'list':
				return 'lines.php';
			case 'add':
				return 'lines.php?action=add&graph_id=' . $obj->getGraphId();
			case 'edit':
				return 'lines.php?action=edit&line_id=' . (int)$obj->getLineId();
			case 'delete':
				return 'lines.php?action=delete&line_id=' . (int)$obj->getLineId();
			case 'list':
				return 'lines.php?action=list&line_id=' . (int)$obj->getLineId();
			case 'clone':
				return 'lines.php?action=clone&line_id=' . (int)$obj->getLineId();
		}	
	}
	
	static public function cloneLine ($line_id, $ignore_clone_name=FALSE, $graph_id=NULL) {
		$line_to_clone = new Line($line_id);
		if (empty($graph_id)) {
			$graph_id = $line_to_clone->getGraphId();
		}
		$line = new Line();
		if ($ignore_clone_name) {
			$clone_alias = $line_to_clone->getAlias();
		} else {
			$clone_alias = 'Clone of ' . $line_to_clone->getAlias();
			// If it's too long, we truncate
			if (strlen($clone_alias) > 255) {
				$clone_alias = substr($clone_alias,0,255);
			}
		}
		$line->setAlias($clone_alias);
		$line->setTarget($line_to_clone->getTarget());
		$line->setColor($line_to_clone->getColor());
		$line->setGraphId($graph_id);
		$line->store();
	}
    
}
