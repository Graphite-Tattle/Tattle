<?
class Check extends fActiveRecord
{
    const MINUTES_PER_DAY = 1440;
    const MINUTES_PER_WEEK = 10080;
    const MINUTES_PER_MONTH = 40320; //TODO: adapt number of minutes per month depending on which month it is. Using 4 weeks for now

    protected function configure()
    {
    }

    /**
     * Returns all checks on the system
     *
     * @param  string  $type         The type of check to return 'threshold', 'predictive'
     * @param  string  $sort_column  The column to sort by
     * @param  string  $sort_dir     The direction to sort the column
     * @param  int     $limit        The max number of records to show
     * @param  int     $page         The offset
     * @return fRecordSet  An object containing all meetups
     */
    static function findAll($type, $sort_column = 'name', $sort_dir = 'desc', $limit=NULL, $page=NULL)
    {
      return fRecordSet::build(
        __CLASS__,
        array('type=' => $type, 'enabled=' => true,'user_id=|visibility=' => array(fSession::get('user_id'),0)),
        array($sort_column => $sort_dir),
        $limit,
        $page
      );
    }
    
    /**
     * Returns all checks on the system that matches the group id
     *
     * @param  string  $type         The type of check to return 'threshold', 'predictive'
     * @param  string  $sort_column  The column to sort by
     * @param  string  $sort_dir     The direction to sort the column
     * @param  int     $limit        The max number of records to show
     * @param  int     $page         The offset
     * @return fRecordSet  An object containing all meetups
     */
    static function findAllByGroupId($type, $group_id, $sort_column = 'name', $sort_dir = 'desc', $limit=NULL, $page=NULL)
    {
    	return fRecordSet::build(
    			__CLASS__,
    			array('type=' => $type,'group_id=' => $group_id,'enabled=' => true,'user_id=|visibility=' => array(fSession::get('user_id'),0)),
    			array($sort_column => $sort_dir),
    			$limit,
    			$page
    	);
    }

    /**
    * Returns a target based on check type
    *
    * @param Object $check  The object to get the target of
    * @return Object        The target of the check object
    */
    static public function constructTarget($check)
    {
      if($check->getSample() != '1') {
        if($check->getType() == 'threshold') {
          if($check->getBaseline() == 'average') {
            return 'movingAverage(' . $check->prepareTarget() . ',\'' . $check->getSample() . 'min\')';
          } elseif($check->getBaseline() == 'median') {
            return 'movingMedian(' . $check->prepareTarget() . ',\'' . $check->getSample() . 'min\')';
          } else {
            // TODO should add an error log here
            return 'movingAverage(' . $check->prepareTarget() . ',\'' . $check->getSample() . 'min\')';
          }
        }
      } else {
        return $check->prepareTarget();
      }
    }

    /**
     * Returns all active checks on the system
     *
     * @param  string  $sort_column  The column to sort by
     * @param  string  $sort_dir     The direction to sort the column
     * @return fRecordSet  An object containing all meetups
     */
    static function findActive()
    {
      return fRecordSet::buildFromSQL(
        __CLASS__,
        array("SELECT checks.* FROM checks WHERE enabled = 1;")
      );
    }

    /**
     * Creates all Check related URLs for the site
     * 'action' and 'type' are required in the querystring
     *
     * @param  string $action  The action to be encoded into the URL to make: 'list', 'add', 'edit', 'delete'
     * @param  string $type  The type of check to be encoded into the URL: 'threshold', 'predictive'
     * @param  Check $obj   The Check object for the edit and delete URL types
     * @return string  The URL requested
     */
    static public function makeURL($action, $type, $obj=NULL)
    {
      $baseURLExtension = 'check.php';
      $actionFieldValue = '?action=' . $action;
      $typeFieldValue = '&type=' . $type;
      $checkIdFieldValue = '';

      if (!is_null($obj)) {
      	if (is_numeric($obj)) {
      		// If it's numeric, we're building an URL filtered by group
      		$checkIdFieldValue = '&filter_group_id=' . $obj;
      	} else {
	        $checkIdFieldValue = '&check_id=' . (int)$obj->getCheckId();
      	}
      }

      return $baseURLExtension . $actionFieldValue . $typeFieldValue . $checkIdFieldValue;
    }

    static public function deleteRelated($obj=NULL)
    {
      if (!is_null($obj)) {
        $subscriptions = Subscription::getAll($obj->getCheckId());
        foreach ($subscriptions as $subscription) {
          $subscription->delete();
        }

        $check_results = CheckResult::getAll($obj->getCheckId());
        foreach ($check_results as $check_result) {
          $check_results->delete();
        }
      }
    }

    static public function acknowledgeCheck($check=NULL,$result=NULL,$ackAll=false)
    {
      if (!is_null($check)) {
        if ($ackAll === true) {
          $check_results = CheckResult::findAll($check->getCheckId());
        } elseif (!is_null($result)) {
          $check_results = CheckResult::build($result->getResultId());
        }
        foreach ($check_results as $check_result) {
          $check_result->setAcknowledged(1);
          $check_result->store();
        }
      }
    }

    /**
     * Requests Graphite Data for check
     *
     * @param  Check $obj   The Check object to get the graphite data for
     * @return array either a Graphite json_data array or an empty one
     */
    static public function getData($obj=NULL)
    {
      if($obj->getType() == 'threshold') {
        if ( $GLOBALS['PRIMARY_SOURCE'] == "GANGLIA" ) {
          $check_url = $GLOBALS['GANGLIA_URL'] . '/graph.php/?' .
            'target=' . $obj->prepareTarget() . 
            '&cs=-'. $obj->prepareSample() . 'minutes' .
            '&ce=now&format=json';
        } else {
	  $target = Check::constructTarget($obj);
          $target = str_replace("&quot;","\"",$target);
          $target = urlencode($target);
          $check_url = $GLOBALS['PROCESSOR_GRAPHITE_URL'];
          if ($check_url == "")
             $check_url = $GLOBALS['GRAPHITE_URL'];
          $check_url = "$check_url/render/?target=$target&format=json";
          if ($GLOBALS['ALERTS_TIME_OFFSET'] > 0) {
          	$check_url .= "&from=-" . ($obj->getSample() + $GLOBALS['ALERTS_TIME_OFFSET']) . "minutes"
          				. "&until=-" . $GLOBALS['ALERTS_TIME_OFFSET'] ."minutes";
          } else {
	            $check_url .= '&from=-'. $obj->prepareSample() . 'minutes';
          }
        }
        $json_data = @file_get_contents($check_url);
        if ($json_data) {
          $data = json_decode($json_data);
	  if (count($data) <= 0 )
          {
		fCore::debug("bad json data for $check_url\n",FALSE);
		fCore::debug("Json: $json_data\n");
          }
        } else {
	  fCore::debug("no data for $check_url\n",FALSE);
          $data = array();
        }
        return $data;
      } elseif($obj->getType() == 'predictive') {
        $data = array();
        for($i = $obj->getNumberOfRegressions(); $i >= 0; $i--) {
          $regression_size = 0;

          if($obj->getRegressionType() == 'daily') {
            $regression_size = self::MINUTES_PER_DAY * $i;
          } elseif($obj->getRegressionType() == 'weekly') {
            $regression_size = self::MINUTES_PER_WEEK * $i;
          } elseif($obj->getRegressionType() == 'monthly') {
            $regression_size = self::MINUTES_PER_MONTH * $i;
          }

          $from = $regression_size + $obj->getSample();
          $until = $regression_size;

          $check_url = $GLOBALS['PROCESSOR_GRAPHITE_URL'] . '/render/?' .
            'target=' . $obj->prepareTarget() .
            '&from=-' . $from . 'minutes' .
            '&until=-' . $until . 'minutes' .
            '&format=json';

          $json_data = @file_get_contents($check_url);
          if($json_data) {
            $temp_data = json_decode($json_data);
            $value = 0;

            if($obj->getBaseline() == 'average') {
              $value = subarray_average($temp_data[0]->datapoints);
            } elseif($obj->getBaseline() == 'median') {
              $value = subarray_median($temp_data[0]->datapoints);
            }

            array_push($data, $value);

            //$temp_data = $temp_data[0]->datapoints;
            //fCore::debug("Iteration: " . $i,FALSE);
            //for($j=0; $j < count($temp_data); $j++) {
            //  if($temp_data[$j][0] != 0) {
            //    fCore::debug($temp_data[$j][0],FALSE);
            //  }
            //}
            //fCore::debug("\n",FALSE);
            //$data = array_merge($data, $temp_data);
          }
        }
        return $data;
      }
    }

    /**
     * Creates all Check related URLs for the site
     *
     * @param  string $type  The type of URL to make: 'list', 'add', 'edit', 'delete'
     * @param  Meetup $obj   The Check object for the edit and delete URL types
     * @return string  The URL requested
     */
    static public function getResultValue($data,$obj=NULL)
    {
      $value = false;
      if ($obj->getBaseline() == 'average') {
        $value = subarray_endvalue($data[0]->datapoints);
      } elseif ($obj->getBaseline() == 'median') {
        $value = subarray_median($data[0]->datapoints);
      }
      return $value;
    }

    //@TODO COMMENT THIS
    static public function getResultHistoricalValue($data,$obj)
    {
      $value = 0;
      if(count($data) <= 1) {
        return $value; //TODO: Handle cases where amount of data returned is less than expected
      }
      $historical_data = array_slice($data, 0, count($data) - 1);
      if($obj->getBaseline() == 'average') {
        $value = average($historical_data);
      } elseif($obj->getBaseline() == 'median') {
        $value = median($historical_data);
      }
      return $value;
    }

    //@TODO COMMENT THIS
    static public function getResultStandardDeviation($data,$obj)
    {
      $value = 0;
      if(count($data) <= 1) {
        return $value; //TODO: Handle cases where amount of data returned is less than expected
      }
      $historical_data = array_slice($data, 0, count($data) - 1);
      return sd($historical_data);
    }

    //@TODO COMMENT THIS
    static public function getResultCurrentValue($data)
    {
      $value = 0;
      if(count($data) == 0) {
        return $value;
      }
      return end($data);
    }

    //@TODO COMMENT THIS
    static public function setPredictiveResultsLevel($current_value,$historical_value,$stdev,$check)
    {
      $upper_error = $historical_value + ($check->getError() * $stdev);
      $upper_warn = $historical_value + ($check->getWarn() * $stdev);
      $lower_error = $historical_value - ($check->getError() * $stdev);
      $lower_warn = $historical_value - ($check->getWarn() * $stdev);
      $state = 0;

      if ($check->getOverUnder() == 0 || $check->getOverUnder() == 2) {
        if ($current_value >= $upper_error) {
          $state = 1;
        } elseif ($current_value >= $upper_warn) {
          $state = 2;
        }

        if($state != 0) {
          return $state;
        }
      }
      if ($check->getOverUnder() == 1 || $check->getOverUnder() == 2) {
        if ($current_value <= $lower_error) {
          $state = 1;
        } elseif ($current_value <= $lower_warn) {
          $state = 2;
        }
      }
      return $state;
    }

    /**
     * Creates all Check related URLs for the site
     *
     * @param  string $type  The type of URL to make: 'list', 'add', 'edit', 'delete'
     * @param  Meetup $obj   The Check object for the edit and delete URL types
     * @return string  The URL requested
     */
    static public function setResultsLevel($value,$obj=NULL)
    {
      if ($obj->getOverUnder() == 0) {
        if ($value >= $obj->getError()) {
          $state = 1;
        } elseif ($value >= $obj->getWarn()) {
          $state = 2;
        } else {
          //echo 'all good ' . " $value <br />";
          $state = 0;
        }
        return $state;
      }

      if ($obj->getOverUnder() == 1) {
        if ($value > $obj->getWarn()) {
          $state = 0;
        } elseif ($value > $obj->getError()) {
          $state = 2;
        } else {
          fCore::debug('error state' . " $value compared to " . $obj->getError() . "<br />",FALSE);
          $state = 1;
        }
        return $state;
      }
    }

    /**
     * Creates all Check related URLs for the site
     *
     * @param  string $type  The type of URL to make: 'list', 'add', 'edit', 'delete'
     * @param  Meetup $obj   The Check object for the edit and delete URL types
     * @return string  The URL requested
     */
    static public function showGraph($obj=NULL,$img=true,$sample=false,$width=false,$hideLegend=false)
    {
      if ($img) {
        $link = '<img id="renderedGraphImage" src="';
      } else {
        $link = '<a href="';
      }

      if ( $GLOBALS['PRIMARY_SOURCE'] == "GANGLIA" ) {

        $parts = explode("_|_", $obj->prepareTarget());
        $link  .= $GLOBALS['GANGLIA_URL'] . "/graph.php?json=1&ce=now&c=" .
          $parts[0] . "&h=" . $parts[1] . "&m=" . $parts[2];

        if ($sample !== False) {
          $link .= '&cs=' . $sample;
        } else {
          $link .= '&cs=-' . $obj->prepareSample() . 'minutes';
        }

      } else {

        $link .=  $GLOBALS['GRAPHITE_URL'] . '/render/?';
        $link .= 'target=legendValue(alias(' . Check::constructTarget($obj) . '%2C%22Check : ' . $obj->prepareName() .'%22),%22last%22)';
        if ($sample !== False) {
          $link .= '&from=' . $sample;
        } else {
          $link .= '&from=-' . $obj->prepareSample() . 'minutes';
        }
        if ($width !== false) {
          $link .= '&width=' .$width;
        } else {
          $link .= '&width=' .$GLOBALS['GRAPH_WIDTH'];
        }
        $link .= '&height=' .$GLOBALS['GRAPH_HEIGHT'];
        $link .= '&target=color(alias(threshold('. $obj->getError() . ')%2C%22Error%20('. $obj->getError() . ')%22)%2C%22' . $GLOBALS['ERROR_COLOR'] . '%22)';
        $link .= '&target=color(alias(threshold('. $obj->getWarn() . ')%2C%22Warning%20('. $obj->getWarn() . ')%22)%2C%22' . $GLOBALS['WARN_COLOR'] . '%22)';
        if ($hideLegend !== false) {
          $link .= '&hideLegend=true';
        }
      }

      if ($img) {
        $link .= '" title="' . $obj->prepareName() . '" alt="' . $obj->prepareName();
        $link .= '" />';
      } else {
        $link .= '"> ' . $obj->prepareTarget() .'</a>';
      }
      return $link;
    }

}
