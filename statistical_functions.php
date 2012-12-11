<?php

/***************************************************************************
 *                                percentile_to_zscore.php
 *                            -------------------
 *   begin                : Friday, January 16, 2004
 *   copyright            : (C) 2004 Michael Nickerson
 *   email                : nickersonm@yahoo.com
 *
 ***************************************************************************/

function percentile_to_zscore($p) {
	//Inverse ncdf approximation by Peter John Acklam, implementation adapted to
	//PHP by Michael Nickerson, using Dr. Thomas Ziegler's C implementation as
	//a guide. http://home.online.no/~pjacklam/notes/invnorm/index.html
	//I have not checked the accuracy of this implementation.  Be aware that PHP
	//will truncate the coeficcients to 14 digits.

	//You have permission to use and distribute this function freely for
	//whatever purpose you want, but please show common courtesy and give credit
	//where credit is due.

	//Updated by Jordan Patapoff (jpatapoff@gmail.com) on Tuesday, May 15, 2012
	//Modified function to return two sided z-critical value.
	//I also modified the input paramater to take the probability as a percentage
	//within the Confidence Interval.

  //Input paramater is $p - probability as a percentage - where 0 < p < 100.
  $p = ($p / 200) + 0.5;

  //Coefficients in rational approximations
  $a = array(1 => -3.969683028665376e+01, 2 => 2.209460984245205e+02,
    			 3 => -2.759285104469687e+02, 4 => 1.383577518672690e+02,
    			 5 => -3.066479806614716e+01, 6 => 2.506628277459239e+00);

  $b = array(1 => -5.447609879822406e+01, 2 => 1.615858368580409e+02,
          		 3 => -1.556989798598866e+02, 4 => 6.680131188771972e+01,
    			 5 => -1.328068155288572e+01);

  $c = array(1 => -7.784894002430293e-03, 2 => -3.223964580411365e-01,
    	 			 3 => -2.400758277161838e+00, 4 => -2.549732539343734e+00,
    			 5 => 4.374664141464968e+00, 6 => 2.938163982698783e+00);

  $d = array(1 => 7.784695709041462e-03, 2 => 3.224671290700398e-01,
    	 			 3 => 2.445134137142996e+00, 4 => 3.754408661907416e+00);

  //Define break-points.
  //Use lower region approx. below this
  $p_low =  0.02425;
  //Use upper region approx. above this
  $p_high = 1 - $p_low;

  //Define/list variables (doesn't really need a definition)
  //$p (probability), $sigma (std. deviation), and $mu (mean) are user inputs
  $q = NULL; $x = NULL; $y = NULL; $r = NULL;

  //Rational approximation for lower region.
  if (0 < $p && $p < $p_low) {
    $q = sqrt(-2 * log($p));
    $x = ((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) *
   	 	 	 $q + $c[6]) / (((($d[1] * $q + $d[2]) * $q + $d[3]) * $q + $d[4]) *
  	 		 $q + 1);
  }

  //Rational approximation for central region.
  elseif ($p_low <= $p && $p <= $p_high) {
    $q = $p - 0.5;
    $r = $q * $q;
    $x = ((((($a[1] * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) *
   	 	 	 $r + $a[6]) * $q / ((((($b[1] * $r + $b[2]) * $r + $b[3]) * $r +
  	 		 $b[4]) * $r + $b[5]) * $r + 1);
  }

  //Rational approximation for upper region.
  elseif ($p_high < $p && $p < 1) {
    $q = sqrt(-2 * log(1 - $p));
    $x = -((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q +
   	 	 	 $c[5]) * $q + $c[6]) / (((($d[1] * $q + $d[2]) * $q + $d[3]) *
  	 		 $q + $d[4]) * $q + 1);
  }

  //If 0 < p < 1, return a null value
  else {
  	$x = NULL;
  }

  return $x;
  //END inverse ncdf implementation.
}


// Function to calculate square of value - mean
function sd_square($x, $mean) { return pow($x - $mean,2); }

// Function to calculate standard deviation (uses sd_square)    
function sd($array) {
    // square root of sum of squares devided by N-1
    return sqrt(array_sum(array_map("sd_square", $array, array_fill(0,count($array), (array_sum($array) / count($array)) ) ) ) / (count($array)-1) );
}



//printf(percentile_to_zscore(50) . "<br/>");
//printf(percentile_to_zscore(68) . "<br/>");
//printf(percentile_to_zscore(68.2689492) . "<br/>");
//printf(percentile_to_zscore(80) . "<br/>");
//printf(percentile_to_zscore(90) . "<br/>");
//printf(percentile_to_zscore(95) . "<br/>");
//printf(percentile_to_zscore(95.4499736) . "<br/>");
//printf(percentile_to_zscore(99) . "<br/>");
//echo "End percentile_to_zscore test<br/><br/>";

//printf(sd(array('1','2','3','4')) . "<br/>");
//printf(sd(array('7','8','99','99')) . "<br/>");
//printf(sd(array('11','22','33','44')) . "<br/>");
//printf(sd(array('5','32','37','47')) . "<br/>");
//printf(sd(array('1','28','27','44')) . "<br/>");
//printf(sd(array('7','7','7','7')) . "<br/>");
//printf(sd(array('111','2222','656565','765')) . "<br/>");
//printf(sd(array('90','20','333','44')) . "<br/>");
//echo "End standard deviation test<br/>";
?>
