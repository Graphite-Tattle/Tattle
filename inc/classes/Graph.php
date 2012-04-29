<?
class Graph extends fActiveRecord
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
       return fRecordSet::build(
          __CLASS__,
          array('dashboard_id=' =>$dashboard_id),
          array('weight' => 'asc')
          );
	}

    static public function makeURL($type, $obj=NULL)
	{
		switch ($type)
		{
			case 'list':
				return 'graphs.php';
			case 'add':
				return 'graphs.php?action=add&dashboard_id=' . $obj->getDashboardId();
			case 'edit':
				return 'graphs.php?action=edit&graph_id=' . $obj->prepareGraphId();
			case 'delete':
				return 'graphs.php?action=delete&graph_id=' . $obj->prepareGraphId();
			case 'list':
				return 'graphs.php?action=list&graph_id=' . $obj->prepareGraphId();

		}
	}

    	static function drawGraph($obj=NULL,$parent=NULL)
	{
        $link = $GLOBALS['GRAPHITE_URL'].'/render/?';
        $lines = Line::findAll($obj->getGraphId());
        foreach($lines as $line) {
           $link .= 'target=';
           $target =  'alias(' . $line->getTarget() . '%2C%22' . $line->getAlias() . '%22)';
           if ($line->getColor() != '') {
             $target = 'color(' . $target . '%2C%22' . $line->getColor() . '%22)';
           }
           $link .= $target .'&';
        }
        if (!is_null($parent)) {
          $link .= 'width=' . $parent->getGraphWidth() .'&';
          $link .= 'height=' . $parent->getGraphHeight() .'&';
          if ($obj->getVtitle() != '') {
              $link .= 'vtitle=' . $obj->getVtitle() .'&';
          }
          if ($obj->getName() != '') {
              $link .= 'title=' . $obj->getName() .'&';
          }
          if ($obj->getArea() != 'none') {
              $link .= 'areaMode=' . $obj->getArea() .'&';
          }
          if ($obj->getTime_Value() != '' && $obj->getUnit() != '') {
              $link .= 'from=-' . $obj->getTime_Value() . $obj->getUnit() . '&';
          }
          if ($obj->getCustom_Opts() != '') {
              $link .= $obj->getCustom_Opts() . '&';
          }
        }
       return $link;
	}


}
