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
				$id = $obj->prepareGraphId();
				return 'graphs.php?action=edit&graph_id=' . (empty($id)?'':(new fNumber($id))->__toString());
			case 'delete':
				$id = $obj->prepareGraphId();
				return 'graphs.php?action=delete&graph_id=' . (empty($id)?'':(new fNumber($id))->__toString());
			case 'list':
				$id = $obj->prepareGraphId();
				return 'graphs.php?action=list&graph_id=' . (empty($id)?'':(new fNumber($id))->__toString());
			case 'clone':
				$id = $obj->prepareGraphId();
				return 'graphs.php?action=clone&graph_id=' . (empty($id)?'':(new fNumber($id))->__toString());

		}
	}

    	static function drawGraph($obj=NULL,$parent=NULL)
	{
        $link = $GLOBALS['GRAPHITE_URL'].'/render/?';
        $lines = Line::findAll($obj->getGraphId());
        foreach($lines as $line) {
           $link .= 'target=';
           $alias = $line->getAlias();
           if (empty($alias)) {
           	 $target = $line->getTarget();
           } else {
	           $target =  'alias(' . $line->getTarget() . '%2C%22' . $line->getAlias() . '%22)';
	           if ($line->getColor() != '') {
	             $target = 'color(' . $target . '%2C%22' . $line->getColor() . '%22)';
	           }
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
	
	static public function cloneGraph ($graph_id, $dashboard_id=NULL) {
		$graph_to_clone = new Graph($graph_id);
		if (empty($dashboard_id)) {
			$dashboard_id = $graph_to_clone->getDashboardId();
		}
		$graph = new Graph();
		$clone_name = 'Clone of ' . $graph_to_clone->getName();
		// If it's too long, we truncate
		if (strlen($clone_name) > 255) {
			$clone_name = substr($clone_name,0,255);
		}
		$graph->setName($clone_name);
		$graph->setArea($graph_to_clone->getArea());
		$graph->setVtitle($graph_to_clone->getVtitle());
		$graph->setDescription($graph_to_clone->getDescription());
		$graph->setDashboardId($dashboard_id);
		$graph->setWeight($graph_to_clone->getWeight());
		$graph->setTimeValue($graph_to_clone->getTimeValue());
		$graph->setUnit($graph_to_clone->getUnit());
		$graph->setCustomOpts($graph_to_clone->getCustomOpts());
		$graph->store();
			
		// Clone of the lines
		$lines = Line::findAll($graph_id);
		foreach($lines as $line_to_clone) {
			Line::cloneLine($line_to_clone->getLineId(),TRUE,$graph->getGraphId());
		}
	}


}
