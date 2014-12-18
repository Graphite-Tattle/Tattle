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
          array('weight,graph_id' => 'asc')
          );
	}
	
        static public function countAllByFilter($dashboard_id, $filter) {
            return fRecordSet::tally(
            __CLASS__,
            array(
                'dashboard_id=' =>$dashboard_id,
                'name|vtitle|description~' => $filter
            )
            );
        }
    static public function makeURL($type, $obj=NULL, $move=NULL)
	{
		switch ($type)
		{
			case 'list':
				return 'graphs.php';
			case 'add':
				return 'graphs.php?action=add&dashboard_id=' . $obj->getDashboardId();
			case 'edit':
				return 'graphs.php?action=edit&graph_id=' . (int)$obj->getGraphId();
			case 'delete':
				return 'graphs.php?action=delete&graph_id=' . (int)$obj->getGraphId();
			case 'list':
				return 'graphs.php?action=list&graph_id=' . (int)$obj->getGraphId();
			case 'clone':
				return 'graphs.php?action=clone&graph_id=' . (int)$obj->getGraphId();
			case 'clone_into':
				return 'graphs.php?action=clone_into&graph_id=' . (int)$obj->getGraphId();
			case 'reorder':
				return 'graphs.php?action=reorder&graph_id=' . $obj->getGraphId() . '&move=' . $move;
			case 'drag_reorder':
				return 'graphs.php?action=reorder&drag_order=';

		}
	}

    	static function drawGraph($obj=NULL,$parent=NULL)
	{
        $link = $GLOBALS['GRAPHITE_URL'].'/render/?drawNullAsZero=true&';
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
              $link .= 'from=' . ($obj->getStartsAtMidnight()?'midnight':'') . '-' . $obj->getTime_Value() . $obj->getUnit() . '&';
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
		$graph->setStartsAtMidnight($graph_to_clone->getStartsAtMidnight());
		$graph->store();
			
		// Clone of the lines
		$lines = Line::findAll($graph_id);
		foreach($lines as $line_to_clone) {
			Line::cloneLine($line_to_clone->getLineId(),TRUE,$graph->getGraphId());
		}
	}

	public function export_in_json ()
	{
		$graph_id = $this->getGraphId();
		$json_env = parent::export_in_json();
		
		// Find all the lines of this graph
		$lines = Line::findAll($graph_id);
		$json_lines_array = array();
		foreach ($lines as $line_in_graph) {
			// Export them in JSON
			$json_lines_array[] = $line_in_graph->export_in_json();
		}
		// Implode them
		$json_lines = "\"lines\":[";
		if (!empty($json_lines_array)) {
			$json_lines .= implode(",", $json_lines_array);
		}
		$json_lines .= "]";
		
		// Erase the last } of the json
		$json_env[strlen($json_env)-1] = ",";
		// Concat the graph with its lines
		$json_env .= ($json_lines . "}");
		
		return $json_env;
	}
	
	public function has_y_axis_title () {
		$title = $this->getVtitle();
		return (!empty($title));
	}
	
	static public function import_from_array_to_dashboard($input,$dashboard_id)
	{
		$result = true;
		if (!empty($input)) {
			$columns_to_ignore = array('graph_id','dashboard_id','lines');
			$new_graph = fActiveRecord::array_to_dbentry($input, __CLASS__,$columns_to_ignore);
			if ($new_graph !== NULL) {
				$new_graph->setDashboardId($dashboard_id);
				$new_graph->store();
				if (in_array('lines', array_keys($input))) {
					$new_graph_id = $new_graph->getGraphId();
					foreach ($input['lines'] as $line) {
						$result_line = (Line::import_from_array_to_graph($line, $new_graph_id));
						$result = $result && $result_line;
					}
				}
			} else {
				$result = false;
			}
		}
		return $result;
	}

}
