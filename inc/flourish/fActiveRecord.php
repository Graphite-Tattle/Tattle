<?php
/**
 * An [http://en.wikipedia.org/wiki/Active_record_pattern active record pattern] base class
 * 
 * This class uses fORMSchema to inspect your database and provides an
 * OO interface to a single database table. The class dynamically handles
 * method calls for getting, setting and other operations on columns. It also
 * dynamically handles retrieving and storing related records.
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fActiveRecord
 * 
 * @version    1.0.0b81
 * @changes    1.0.0b81  Fixed a bug with updating a record that contains only an auto-incrementing primary key [wb, 2011-09-06]
 * @changes    1.0.0b80  Added support to ::checkCondition() for the `^~` and `$~` operators [wb, 2011-06-20]
 * @changes    1.0.0b79  Fixed some bugs in handling relationships between PHP 5.3 namespaced classes [wb, 2011-05-26]
 * @changes    1.0.0b78  Backwards Compatibility Break - ::reflect() now returns an associative array instead of a string [wb, 2011-05-10]
 * @changes    1.0.0b77  Fixed ::inspect() to not throw an fProgrammerException when a valid element has a `NULL` value [wb, 2011-05-10]
 * @changes    1.0.0b76  Added ::clearIdentityMap() [wb, 2011-05-09]
 * @changes    1.0.0b75  Fixed a bug where child records of a record with a non-auto-incrementing primary key would not be saved properly for a new record [wb, 2010-12-06]
 * @changes    1.0.0b74  Updated ::populate() to use the `binary` type for fRequest::get() [wb, 2010-11-30]
 * @changes    1.0.0b73  Backwards Compatibility Break - changed column set methods to treat strings of all whitespace the same as empty string and convert them to `NULL` [wb, 2010-11-29]
 * @changes    1.0.0b72  Added the new `comment` element to the reflection signature for `inspect` methods [wb, 2010-11-28]
 * @changes    1.0.0b71  Updated class to use fORM::getRelatedClass() [wb, 2010-11-24]
 * @changes    1.0.0b70  Added support for PHP 5.3 namespaced fActiveRecord classes [wb, 2010-11-11]
 * @changes    1.0.0b69  Backwards Compatibility Break - changed ::validate() to return a nested array of validation messages when there are validation errors on child records [wb-imarc+wb, 2010-10-03]
 * @changes    1.0.0b68  Added hooks to ::replicate() [wb, 2010-09-07]
 * @changes    1.0.0b67  Updated code to work with the new fORM API [wb, 2010-08-06]
 * @changes    1.0.0b66  Fixed a bug with ::store() and non-primary key auto-incrementing columns [wb, 2010-07-05]
 * @changes    1.0.0b65  Fixed bugs with ::inspect() making some `min_value` and `max_value` elements available for non-numeric types, fixed ::reflect() to list the `min_value` and `max_value` elements [wb, 2010-06-08]
 * @changes    1.0.0b64  BackwardsCompatibilityBreak - changed ::validate()'s returned messages array to have field name keys - added the option to ::validate() to remove field names from messages [wb, 2010-05-26]
 * @changes    1.0.0b63  Changed how is_subclass_of() is used to work around a bug in 5.2.x [wb, 2010-04-06]
 * @changes    1.0.0b62  Fixed a bug that could cause infinite recursion starting in v1.0.0b60 [wb, 2010-04-02]
 * @changes    1.0.0b61  Fixed issues with handling `populate` actions when working with mapped classes [wb, 2010-03-31]
 * @changes    1.0.0b60  Fixed issues with handling `associate` and `has` actions when working with mapped classes, added ::validateClass() [wb, 2010-03-30]
 * @changes    1.0.0b59  Changed an extended UTF-8 arrow character into the correct `->` [wb, 2010-03-29]
 * @changes    1.0.0b58  Fixed ::reflect() to specify the value returned from `set` methods [wb, 2010-03-15]
 * @changes    1.0.0b57  Added the `post::loadFromIdentityMap()` hook and fixed ::__construct() to always call the `post::__construct()` hook [wb, 2010-03-14]
 * @changes    1.0.0b56  Fixed `$force_cascade` in ::delete() to work even when the restricted relationship is once-removed through an unrestricted relationship [wb, 2010-03-09]
 * @changes    1.0.0b55  Fixed ::load() to that related records are cleared, requiring them to be loaded from the database [wb, 2010-03-04]
 * @changes    1.0.0b54  Fixed detection of route name for one-to-one relationships in ::delete() [wb, 2010-03-03]
 * @changes    1.0.0b53  Fixed a bug where related records with a primary key that contained a foreign key with an on update cascade clause would be deleted when changing the value of the column referenced by the foreign key [wb, 2009-12-17]
 * @changes    1.0.0b52  Backwards Compatibility Break - Added the $force_cascade parameter to ::delete() and ::store() - enabled calling ::prepare() and ::encode() for non-column get methods, added `::has{RelatedRecords}()` methods [wb, 2009-12-16]
 * @changes    1.0.0b51  Made ::changed() properly recognize that a blank string and NULL are equivalent due to the way that ::set() casts values [wb, 2009-11-14]
 * @changes    1.0.0b50  Fixed a bug with trying to load by a multi-column primary key where one of the columns was not specified [wb, 2009-11-13]
 * @changes    1.0.0b49  Fixed a bug affecting where conditions with columns that are not null but have a default value [wb, 2009-11-03]
 * @changes    1.0.0b48  Updated code for the new fORMDatabase and fORMSchema APIs [wb, 2009-10-28]
 * @changes    1.0.0b47  Changed `::associate{RelatedRecords}()`, `::link{RelatedRecords}()` and `::populate{RelatedRecords}()` to allow for method chaining [wb, 2009-10-22]
 * @changes    1.0.0b46  Changed SQL statements to use value placeholders and identifier escaping [wb, 2009-10-22]
 * @changes    1.0.0b45  Added support for `!~`, `&~`, `><` and OR comparisons to ::checkConditions(), made object handling in ::checkConditions() more robust [wb, 2009-09-21]
 * @changes    1.0.0b44  Updated code for new fValidationException API [wb, 2009-09-18]
 * @changes    1.0.0b43  Updated code for new fRecordSet API [wb, 2009-09-16]
 * @changes    1.0.0b42  Corrected a grammar bug in ::hash() [wb, 2009-09-09]
 * @changes    1.0.0b41  Fixed a bug in the last version that would cause issues with classes containing a custom class to table mapping [wb, 2009-09-01]
 * @changes    1.0.0b40  Added a check to the configuration part of ::__construct() to ensure modelled tables have primary keys [wb, 2009-08-26]
 * @changes    1.0.0b39  Changed `set{ColumnName}()` methods to return the record for method chaining, fixed a bug with loading by multi-column unique constraints, fixed a bug with ::load() [wb, 2009-08-26]
 * @changes    1.0.0b38  Updated ::changed() to do a strict comparison when at least one value is NULL [wb, 2009-08-17]
 * @changes    1.0.0b37  Changed ::__construct() to allow any Iterator object instead of just fResult [wb, 2009-08-12]
 * @changes    1.0.0b36  Fixed a bug with setting NULL values from v1.0.0b33 [wb, 2009-08-10]
 * @changes    1.0.0b35  Fixed a bug with unescaping data in ::loadFromResult() from v1.0.0b33 [wb, 2009-08-10]
 * @changes    1.0.0b34  Added the ability to compare fActiveRecord objects in ::checkConditions() [wb, 2009-08-07]
 * @changes    1.0.0b33  Performance enhancements to ::__call() and ::__construct() [wb, 2009-08-07] 
 * @changes    1.0.0b32  Changed ::delete() to remove auto-incrementing primary keys after the post::delete() hook [wb, 2009-07-29]
 * @changes    1.0.0b31  Fixed a bug with loading a record by a multi-column primary key, fixed one-to-one relationship API [wb, 2009-07-21]
 * @changes    1.0.0b30  Updated ::reflect() for new fORM::callReflectCallbacks() API [wb, 2009-07-13]
 * @changes    1.0.0b29  Updated to use new fORM::callInspectCallbacks() method [wb, 2009-07-13]
 * @changes    1.0.0b28  Fixed a bug where records would break the identity map at the end of ::store() [wb, 2009-07-09]
 * @changes    1.0.0b27  Changed ::hash() from a protected method to a static public/internal method that requires the class name for non-fActiveRecord values [wb, 2009-07-09]
 * @changes    1.0.0b26  Added ::checkConditions() from fRecordSet [wb, 2009-07-08]
 * @changes    1.0.0b25  Updated ::validate() to use new fORMValidation API, including new message search/replace functionality [wb, 2009-07-01]
 * @changes    1.0.0b24  Changed ::validate() to remove duplicate validation messages [wb, 2009-06-30]
 * @changes    1.0.0b23  Updated code for new fORMValidation::validateRelated() API [wb, 2009-06-26]
 * @changes    1.0.0b22  Added support for the $formatting parameter to encode methods on char, text and varchar columns [wb, 2009-06-19]
 * @changes    1.0.0b21  Performance tweaks and updates for fORM and fORMRelated API changes [wb, 2009-06-15]
 * @changes    1.0.0b20  Changed replacement values in preg_replace() calls to be properly escaped [wb, 2009-06-11]
 * @changes    1.0.0b19  Added `list{RelatedRecords}()` methods, updated code for new fORMRelated API [wb, 2009-06-02]
 * @changes    1.0.0b18  Changed ::store() to use new fORMRelated::store() method [wb, 2009-06-02]
 * @changes    1.0.0b17  Added some missing parameter information to ::reflect() [wb, 2009-06-01]
 * @changes    1.0.0b16  Fixed bugs in ::__clone() and ::replicate() related to recursive relationships [wb-imarc, 2009-05-20]
 * @changes    1.0.0b15  Fixed an incorrect variable reference in ::store() [wb, 2009-05-06]
 * @changes    1.0.0b14  ::store() no longer tries to get an auto-incrementing ID from the database if a value was set [wb, 2009-05-02]
 * @changes    1.0.0b13  ::delete(), ::load(), ::populate() and ::store() now return the record to allow for method chaining [wb, 2009-03-23]
 * @changes    1.0.0b12  ::set() now removes commas from integers and floats to prevent validation issues [wb, 2009-03-22]
 * @changes    1.0.0b11  ::encode() no longer adds commas to floats [wb, 2009-03-22]
 * @changes    1.0.0b10  ::__wakeup() no longer registers the record as the definitive copy in the identity map [wb, 2009-03-22]
 * @changes    1.0.0b9   Changed ::__construct() to populate database default values when a non-existing record is instantiated [wb, 2009-01-12]
 * @changes    1.0.0b8   Fixed ::exists() to properly detect cases when an existing record has one or more NULL values in the primary key [wb, 2009-01-11]
 * @changes    1.0.0b7   Fixed ::__construct() to not trigger the post::__construct() hook when force-configured [wb, 2008-12-30]
 * @changes    1.0.0b6   ::__construct() now accepts an associative array matching any unique key or primary key, fixed the post::__construct() hook to be called once for each record [wb, 2008-12-26]
 * @changes    1.0.0b5   Fixed ::replicate() to use plural record names for related records [wb, 2008-12-12]
 * @changes    1.0.0b4   Added ::replicate() to allow cloning along with related records [wb, 2008-12-12]
 * @changes    1.0.0b3   Changed ::__clone() to clone objects contains in the values and cache arrays [wb, 2008-12-11]
 * @changes    1.0.0b2   Added the ::__clone() method to properly duplicate a record [wb, 2008-12-04]
 * @changes    1.0.0b    The initial implementation [wb, 2007-08-04]
 */
abstract class fActiveRecord
{
	// The following constants allow for nice looking callbacks to static methods
	const assign          = 'fActiveRecord::assign';
	const changed         = 'fActiveRecord::changed';
	const checkConditions = 'fActiveRecord::checkConditions';
	const forceConfigure  = 'fActiveRecord::forceConfigure';
	const hasOld          = 'fActiveRecord::hasOld';
	const reset           = 'fActiveRecord::reset';
	const retrieveOld     = 'fActiveRecord::retrieveOld';
	const validateClass   = 'fActiveRecord::validateClass';
	
	
	/**
	 * Caches callbacks for methods
	 * 
	 * @var array
	 */
	static protected $callback_cache = array();
	
	/**
	 * An array of flags indicating a class has been configured
	 * 
	 * @var array
	 */
	static protected $configured = array();
	
	
	/**
	 * Maps objects via their primary key
	 * 
	 * @var array
	 */
	static protected $identity_map = array();
	
	
	/**
	 * Caches method name parsings
	 * 
	 * @var array
	 */
	static protected $method_name_cache = array();
	
	
	/**
	 * Keeps track of the recursive call level of replication so we can clear the map
	 * 
	 * @var integer
	 */
	static protected $replicate_level = 0;
	
	
	/**
	 * Keeps a list of records that have been replicated
	 * 
	 * @var array
	 */
	static protected $replicate_map = array();
	
	/**
	 * Contains a list of what columns in each class need to be unescaped and what data type they are
	 * 
	 * @var array
	 */
	static protected $unescape_map = array();
	
	
	/**
	 * Sets a value to the `$values` array, preserving the old value in `$old_values`
	 *
	 * @internal
	 * 
	 * @param  array  &$values      The current values
	 * @param  array  &$old_values  The old values
	 * @param  string $column       The column to set
	 * @param  mixed  $value        The value to set
	 * @return void
	 */
	static public function assign(&$values, &$old_values, $column, $value)
	{
		if (!isset($old_values[$column])) {
			$old_values[$column] = array();
		}
		
		$old_values[$column][] = $values[$column];
		$values[$column]       = $value;	
	}
	
	
	/**
	 * Checks to see if a value has changed
	 *
	 * @internal
	 * 
	 * @param  array  &$values      The current values
	 * @param  array  &$old_values  The old values
	 * @param  string $column       The column to check
	 * @return boolean  If the value for the column specified has changed
	 */
	static public function changed(&$values, &$old_values, $column)
	{
		if (!isset($old_values[$column])) {
			return FALSE;
		}
		
		$oldest_value = $old_values[$column][0];
		$new_value    = $values[$column];
		
		// We do a strict comparison when one of the values is NULL since
		// NULL is almost always meant to be distinct from 0, FALSE, etc.
		// However, since we cast blank strings to NULL in ::set() but a blank
		// string could come out of the database, we consider them to be
		// equivalent, so we don't do a strict comparison
		if (($oldest_value === NULL && $new_value !== '') || ($new_value === NULL && $oldest_value !== '')) {
			return $oldest_value !== $new_value;	
		}
		
		return $oldest_value != $new_value;	
	}
	
	
	/**
	 * Ensures a class extends fActiveRecord
	 * 
	 * @internal
	 * 
	 * @param  string $class  The class to check
	 * @return boolean  If the class is an fActiveRecord descendant
	 */
	static public function checkClass($class)
	{
		if (isset(self::$configured[$class])) {
			return TRUE;
		}
		
		if (!is_string($class) || !$class || !class_exists($class) || !($class == 'fActiveRecord' || is_subclass_of($class, 'fActiveRecord'))) {
			return FALSE;
		}
		return TRUE;
	}
	
	
	/**
	 * Checks to see if a record matches a condition
	 * 
	 * @internal
	 * 
	 * @param  string $operator  The record to check
	 * @param  mixed  $value     The value to compare to
	 * @param  mixed $result     The result of the method call(s)
	 * @return boolean  If the comparison was successful
	 */
	static private function checkCondition($operator, $value, $result)
	{
		$was_array = is_array($value);
		if (!$was_array) { $value = array($value); }
		foreach ($value as $i => $_value) {
			if (is_object($_value)) {
				if ($_value instanceof fActiveRecord) {
					continue;
				}
				if (method_exists($_value, '__toString')) {
					$value[$i] = $_value->__toString();
				}	
			}	
		}
		if (!$was_array) { $value = $value[0]; }
		
		$was_array = is_array($result);
		if (!$was_array) { $result = array($result); }
		foreach ($result as $i => $_result) {
			if (is_object($_result)) {
				if ($_result instanceof fActiveRecord) {
					continue;
				}
				if (method_exists($_result, '__toString')) {
					$result[$i] = $_result->__toString();
				}	
			}	
		}
		if (!$was_array) { $result = $result[0]; }
		
		if ($operator == '~' && !is_array($value) && is_array($result)) {
			$value = fORMDatabase::parseSearchTerms($value, TRUE);
		}

		if (in_array($operator, array('~', '&~', '!~', '^~', '$~'))) {
			settype($value, 'array');
			settype($result, 'array');
		}

		switch ($operator) {
			case '&~':
				foreach ($value as $_value) {
					if (fUTF8::ipos($result[0], $_value) === FALSE) {
						return FALSE;
					}
				}
				break;

			case '~':
				
				// Handles fuzzy search on multiple method calls
				if (count($result) > 1) {
					foreach ($value as $_value) {
						$found = FALSE;
						foreach ($result as $_result) {
							if (fUTF8::ipos($_result, $_value) !== FALSE) {
								$found = TRUE;
							}
						}
						if (!$found) {
							return FALSE;
						}	
					}
					break;
				}

				// No break exists since a ~ on a single method call acts
				// similar to the other LIKE operators

			case '!~':
			case '^~':
			case '$~':
				if ($operator == '$~') {
					$result_len = fUTF8::len($result[0]);
				}

				foreach ($value as $_value) {
					$pos = fUTF8::ipos($result[0], $_value);
					if ($operator == '^~' && $pos === 0) {
						return TRUE;
					} elseif ($operator == '$~' && $pos === $result_len - fUTF8::len($_value)) {
						return TRUE;
					} elseif ($pos !== FALSE) {
						return $operator != '!~';
					}
				}

				if ($operator != '!~') {
					return FALSE;
				}
				break;
			
			case '=':
				if ($value instanceof fActiveRecord && $result instanceof fActiveRecord) {
					if (get_class($value) != get_class($result) || !$value->exists() || !$result->exists() || self::hash($value) != self::hash($result)) {
						return FALSE;
					}
					
				} elseif (is_array($value) && !in_array($result, $value)) {
					return FALSE;
						
				} elseif (!is_array($value) && $result != $value) {
					return FALSE;	
				}
				break;
				
			case '!':
				if ($value instanceof fActiveRecord && $result instanceof fActiveRecord) {
					if (get_class($value) == get_class($result) && $value->exists() && $result->exists() && self::hash($value) == self::hash($result)) {
						return FALSE;
					}
					
				} elseif (is_array($value) && in_array($result, $value)) {
					return FALSE;	
					
				} elseif (!is_array($value) && $result == $value) {
					return FALSE;	
				}
				break;
			
			case '<':
				if ($result >= $value) {
					return FALSE;	
				}
				break;
			
			case '<=':
				if ($result > $value) {
					return FALSE;	
				}
				break;
			
			case '>':
				if ($result <= $value) {
					return FALSE;	
				}
				break;
			
			case '>=':
				if ($result < $value) {
					return FALSE;	
				}
				break;
		}
		
		return TRUE;		
	}
	
	
	/**
	 * Checks to see if a record matches all of the conditions
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $record      The record to check
	 * @param  array         $conditions  The conditions to check - see fRecordSet::filter() for format details
	 * @return boolean  If the record meets all conditions
	 */
	static public function checkConditions($record, $conditions)
	{
		foreach ($conditions as $method => $value) {
			
			// Split the operator off of the end of the method name
			if (in_array(substr($method, -2), array('<=', '>=', '!=', '<>', '!~', '&~', '^~', '$~', '><'))) {
				$operator = strtr(
					substr($method, -2),
					array(
						'<>' => '!',
						'!=' => '!'
					)
				);
				$method   = substr($method, 0, -2);
			} else {
				$operator = substr($method, -1);
				$method   = substr($method, 0, -1);
			}
			
			if (preg_match('#(?<!\|)\|(?!\|)#', $method)) {
				
				$methods   = explode('|', $method);
				$values    = $value;
				$operators = array();
				
				foreach ($methods as &$_method) {
					if (in_array(substr($_method, -2), array('<=', '>=', '!=', '<>', '!~', '&~', '^~', '$~', '><'))) {
						$operators[] = strtr(
							substr($_method, -2),
							array(
								'<>' => '!',
								'!=' => '!'
							)
						);
						$_method     = substr($_method, 0, -2);
					} elseif (!ctype_alnum(substr($_method, -1))) {
						$operators[] = substr($_method, -1);
						$_method     = substr($_method, 0, -1);
					}
				}
				$operators[] = $operator;
				
				
				if (sizeof($operators) == 1) {
				
					// Handle fuzzy searches
					if ($operator == '~') {
					
						$results = array();
						foreach ($methods as $method) {
							$results[] = $record->$method();	
						}
						if (!self::checkCondition($operator, $value, $results)) {
							return FALSE;	
						}
					
					// Handle intersection
					} elseif ($operator == '><') {
						
						if (sizeof($methods) != 2 || sizeof($values) != 2) {
							throw new fProgrammerException(
								'The intersection operator, %s, requires exactly two methods and two values',
								$operator
							);	
						}
									
						$results    = array();
						$results[0] = $record->{$methods[0]}();
						$results[1] = $record->{$methods[1]}();
						
						if ($results[1] === NULL && $values[1] === NULL) {
							if (!self::checkCondition('=', $values[0], $results[0])) {
								return FALSE;
							}
							
							
						} else {
							
							$starts_between_values = FALSE;
							$overlaps_value_1      = FALSE;
							
							if ($values[1] !== NULL) {
								$start_lt_value_1      = self::checkCondition('<', $values[0], $results[0]);
								$start_gt_value_2      = self::checkCondition('>', $values[1], $results[0]);
								$starts_between_values = !$start_lt_value_1 && !$start_gt_value_2;
							}
							if ($results[1] !== NULL) {
								$start_gt_value_1 = self::checkCondition('>', $values[0], $results[0]);
								$end_lt_value_1   = self::checkCondition('<', $values[0], $results[1]);
								$overlaps_value_1 = !$start_gt_value_1 && !$end_lt_value_1;
							}
							
							if (!$starts_between_values && !$overlaps_value_1) {
								return FALSE;
							}
						}
					
					} else {
						throw new fProgrammerException(
							'An invalid comparison operator, %s, was specified for multiple columns',
							$operator
						);
					}
					
				// Handle OR conditions
				} else {
					
					if (sizeof($methods) != sizeof($values)) {
						throw new fProgrammerException(
							'When performing an %1$s comparison there must be an equal number of methods and values, however there are not',
							'OR',
							sizeof($methods),
							sizeof($values)
						);
					}
					
					if (sizeof($methods) != sizeof($operators)) {
						throw new fProgrammerException(
							'When performing an %s comparison there must be a comparison operator for each column, however one or more is missing',
							'OR'
						);
					}
					
					$results    = array();
					$iterations = sizeof($methods);
					for ($i=0; $i<$iterations; $i++) {
						$results[] = self::checkCondition($operators[$i], $values[$i], $record->{$methods[$i]}());
					}
					
					if (!array_filter($results)) {
						return FALSE;	
					}
					
				}
				
			// Single method comparisons	
			} else {
				$result = $record->$method();
				if (!self::checkCondition($operator, $value, $result)) {
					return FALSE;	
				}
			}	
		}
		
		return TRUE;
	}


	/**
	 * Clears the identity map
	 * 
	 * @return void
	 */
	static public function clearIdentityMap()
	{
		self::$identity_map = array();
	}
	
	
	/**
	 * Composes text using fText if loaded
	 * 
	 * @param  string  $message    The message to compose
	 * @param  mixed   $component  A string or number to insert into the message
	 * @param  mixed   ...
	 * @return string  The composed and possible translated message
	 */
	static protected function compose($message)
	{
		$args = array_slice(func_get_args(), 1);
		
		if (class_exists('fText', FALSE)) {
			return call_user_func_array(
				array('fText', 'compose'),
				array($message, $args)
			);
		} else {
			return vsprintf($message, $args);
		}
	}
	
	
	/**
	 * Takes information from a method call and determines the subject, route and if subject was plural
	 * 
	 * @param string $class    The class the method was called on
	 * @param string $subject  An underscore_notation subject - either a singular or plural class name
	 * @param string $route    The route to the subject
	 * @return array  An array with the structure: array(0 => $subject, 1 => $route, 2 => $plural)
	 */
	static private function determineSubject($class, $subject, $route)
	{
		$schema  = fORMSchema::retrieve($class);
		$table   = fORM::tablize($class);
		$type    = '*-to-many';
		$plural  = FALSE;
		
		// one-to-many relationships need to use plural forms
		$singular_form = fGrammar::singularize($subject, TRUE);
		if ($singular_form && fORM::isClassMappedToTable($singular_form)) {
			$subject = $singular_form;
			$plural  = TRUE;
			
		} elseif (!fORM::isClassMappedToTable($subject) && in_array(fGrammar::underscorize($subject), $schema->getTables())) {
			$subject = fGrammar::singularize($subject);
			$plural  = TRUE;
		}
		
		$related_table = fORM::tablize($subject);
		$one_to_one    = fORMSchema::isOneToOne($schema, $table, $related_table, $route);
		if ($one_to_one) {
			$type = 'one-to-one';
		}
		if (($one_to_one && $plural) || (!$plural && !$one_to_one)) {
			throw new fProgrammerException(
				'The table %1$s is not in a %2$srelationship with the table %3$s',
				$table,
				$type,
				$related_table
			); 
		}
		
		$route = fORMSchema::getRouteName($schema, $table, $related_table, $route, $type);
		
		return array($subject, $route, $plural);
	}
	
	
	/**
	 * Ensures that ::configure() has been called for the class
	 *
	 * @internal
	 * 
	 * @param  string $class  The class to configure
	 * @return void
	 */
	static public function forceConfigure($class)
	{
		if (isset(self::$configured[$class])) {
			return;	
		}
		new $class();
	}
	
	
	/**
	 * Takes a row of data or a primary key and makes a hash from the primary key
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord|array|string|int $record   An fActiveRecord object, an array of the records data, an array of primary key data or a scalar primary key value
	 * @param  string                         $class    The class name, if $record isn't an fActiveRecord
	 * @return string|NULL  A hash of the record's primary key value or NULL if the record doesn't exist yet
	 */
	static public function hash($record, $class=NULL)
	{
		if ($record instanceof fActiveRecord && !$record->exists()) {
			return NULL;	
		}
		
		if ($class === NULL) {
			if (!$record instanceof fActiveRecord) {
				throw new fProgrammerException(
					'The class of the record must be provided if the record specified is not an instance of fActiveRecord'
				);
			}
			$class = get_class($record);	
		}
		
		$schema     = fORMSchema::retrieve($class);
		$table      = fORM::tablize($class);
		$pk_columns = $schema->getKeys($table, 'primary');
		
		// Build an array of just the primary key data
		$pk_data = array();
		foreach ($pk_columns as $pk_column) {
			if ($record instanceof fActiveRecord) {
				$value = (self::hasOld($record->old_values, $pk_column)) ? self::retrieveOld($record->old_values, $pk_column) : $record->values[$pk_column];
			
			} elseif (is_array($record)) {
				$value = $record[$pk_column];
			
			} else {
				$value = $record;	
			}
			
			$pk_data[$pk_column] = fORM::scalarize(
				$class,
				$pk_column,
				$value
			);
			
			if (is_numeric($pk_data[$pk_column]) || is_object($pk_data[$pk_column])) {
				$pk_data[$pk_column] = (string) $pk_data[$pk_column];	
			}
		}
		
		return md5(serialize($pk_data));
	}
	
	
	/**
	 * Checks to see if an old value exists for a column 
	 *
	 * @internal
	 * 
	 * @param  array  &$old_values  The old values
	 * @param  string $column       The column to set
	 * @return boolean  If an old value for that column exists
	 */
	static public function hasOld(&$old_values, $column)
	{
		return array_key_exists($column, $old_values);
	}
	
	
	/**
	 * Resets the configuration of the class
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	static public function reset()
	{
		self::$callback_cache    = array();
		self::$configured        = array();
		self::$identity_map      = array();
		self::$method_name_cache = array();
		self::$unescape_map      = array();
	}
	
	
	/**
	 * Retrieves the oldest value for a column or all old values
	 *
	 * @internal
	 * 
	 * @param  array   &$old_values  The old values
	 * @param  string  $column       The column to get
	 * @param  mixed   $default      The default value to return if no value exists
	 * @param  boolean $return_all   Return the array of all old values for this column instead of just the oldest
	 * @return mixed  The old value for the column
	 */
	static public function retrieveOld(&$old_values, $column, $default=NULL, $return_all=FALSE)
	{
		if (!isset($old_values[$column])) {
			return $default;	
		}
		
		if ($return_all === TRUE) {
			return $old_values[$column];	
		}
		
		return $old_values[$column][0];
	}
	
	
	/**
	 * Ensures a class extends fActiveRecord
	 * 
	 * @internal
	 * 
	 * @param  string $class  The class to verify
	 * @return void
	 */
	static public function validateClass($class)
	{
		if (isset(self::$configured[$class])) {
			return TRUE;
		}
		
		if (!self::checkClass($class)) {
			throw new fProgrammerException(
				'The class specified, %1$s, does not appear to be a valid %2$s class',
				$class,
				'fActiveRecord'
			);
		}
	}
	
	
	/**
	 * A data store for caching data related to a record, the structure of this is completely up to the developer using it
	 * 
	 * @var array
	 */
	protected $cache = array();
	
	/**
	 * The old values for this record
	 * 
	 * Column names are the keys, but a column key will only be present if a
	 * value has changed. The value associated with each key is an array of
	 * old values with the first entry being the oldest value. The static 
	 * methods ::assign(), ::changed(), ::hasOld() and ::retrieveOld() are the
	 * best way to interact with this array.
	 * 
	 * @var array
	 */
	protected $old_values = array();
	
	/**
	 * Records that are related to the current record via some relationship
	 * 
	 * This array is used to cache related records so that a database query
	 * is not required each time related records are accessed. The fORMRelated
	 * class handles most of the interaction with this array.
	 * 
	 * @var array
	 */
	protected $related_records = array();
	
	/**
	 * The values for this record
	 * 
	 * This array always contains every column in the database table as a key
	 * with the value being the current value. 
	 * 
	 * @var array
	 */
	protected $values = array();
	
	
	/**
	 * Handles all method calls for columns, related records and hook callbacks
	 * 
	 * Dynamically handles `get`, `set`, `prepare`, `encode` and `inspect`
	 * methods for each column in this record. Method names are in the form
	 * `verbColumName()`.
	 * 
	 * This method also handles `associate`, `build`, `count`, `has`, and `link`
	 * verbs for records in many-to-many relationships; `build`, `count`, `has`
	 * and `populate` verbs for all related records in one-to-many relationships
	 * and `create`, `has` and `populate` verbs for all related records in
	 * one-to-one relationships, and the `create` verb for all related records
	 * in many-to-one relationships.
	 * 
	 * Method callbacks registered through fORM::registerActiveRecordMethod()
	 * will be delegated via this method.
	 * 
	 * @param  string $method_name  The name of the method called
	 * @param  array  $parameters   The parameters passed
	 * @return mixed  The value returned by the method called
	 */
	public function __call($method_name, $parameters)
	{
		$class = get_class($this);
		
		if (!isset(self::$callback_cache[$class][$method_name])) {
			if (!isset(self::$callback_cache[$class])) {
				self::$callback_cache[$class] = array();
			}	
			$callback = fORM::getActiveRecordMethod($class, $method_name);
			self::$callback_cache[$class][$method_name] = $callback ? $callback : FALSE;
		}
		
		if ($callback = self::$callback_cache[$class][$method_name]) {
			return call_user_func_array(
				$callback,
				array(
					$this,
					&$this->values,
					&$this->old_values,
					&$this->related_records,
					&$this->cache,
					$method_name,
					$parameters
				)
			);
		}
		
		if (!isset(self::$method_name_cache[$method_name])) {
			list ($action, $subject) = fORM::parseMethod($method_name);
			if (in_array($action, array('get', 'encode', 'prepare', 'inspect', 'set'))) {
				$subject = fGrammar::underscorize($subject);
			} else {
				if (in_array($action, array('build', 'count', 'inject', 'link', 'list', 'tally'))) {
					$subject = fGrammar::singularize($subject);
				}
				$subject = fORM::getRelatedClass($class, $subject);
			}
			self::$method_name_cache[$method_name] = array(
				'action'  => $action,
				'subject' => $subject
			);	
		} else {
			$action  = self::$method_name_cache[$method_name]['action'];
			$subject = self::$method_name_cache[$method_name]['subject'];	
		}
		
		switch ($action) {
			
			// Value methods
			case 'get':
				return $this->get($subject);
				
			case 'encode':
				if (isset($parameters[0])) {
					return $this->encode($subject, $parameters[0]);
				}
				return $this->encode($subject);
			
			case 'prepare':
				if (isset($parameters[0])) {
					return $this->prepare($subject, $parameters[0]);
				}
				return $this->prepare($subject);
			
			case 'inspect':
				if (isset($parameters[0])) {
					return $this->inspect($subject, $parameters[0]);
				}
				return $this->inspect($subject);
			
			case 'set':
				if (sizeof($parameters) < 1) {
					throw new fProgrammerException(
						'The method, %s(), requires at least one parameter',
						$method_name
					);
				}
				return $this->set($subject, $parameters[0]);
			
			// Related data methods
			case 'associate':
				if (sizeof($parameters) < 1) {
					throw new fProgrammerException(
						'The method, %s(), requires at least one parameter',
						$method_name
					);
				}
				
				$records = $parameters[0];
				$route   = isset($parameters[1]) ? $parameters[1] : NULL;
				
				list ($subject, $route, $plural) = self::determineSubject($class, $subject, $route);
				
				if ($plural) {
					fORMRelated::associateRecords($class, $this->related_records, $subject, $records, $route);
				} else {
					fORMRelated::associateRecord($class, $this->related_records, $subject, $records, $route);
				}
				return $this;
			
			case 'build':
				if (isset($parameters[0])) {
					return fORMRelated::buildRecords($class, $this->values, $this->related_records, $subject, $parameters[0]);
				}
				return fORMRelated::buildRecords($class, $this->values, $this->related_records, $subject);
			
			case 'count':
				if (isset($parameters[0])) {
					return fORMRelated::countRecords($class, $this->values, $this->related_records, $subject, $parameters[0]);
				}
				return fORMRelated::countRecords($class, $this->values, $this->related_records, $subject);
			
			case 'create':
				if (isset($parameters[0])) {
					return fORMRelated::createRecord($class, $this->values, $this->related_records, $subject, $parameters[0]);
				}
				return fORMRelated::createRecord($class, $this->values, $this->related_records, $subject);
				
			case 'has':
				$route = isset($parameters[0]) ? $parameters[0] : NULL;
				
				list ($subject, $route, ) = self::determineSubject($class, $subject, $route);
				
				return fORMRelated::hasRecords($class, $this->values, $this->related_records, $subject, $route);
			 
			case 'inject':
				if (sizeof($parameters) < 1) {
					throw new fProgrammerException(
						'The method, %s(), requires at least one parameter',
						$method_name
					);
				}
				
				if (isset($parameters[1])) {
					return fORMRelated::setRecordSet($class, $this->related_records, $subject, $parameters[0], $parameters[1]);
				}
				return fORMRelated::setRecordSet($class, $this->related_records, $subject, $parameters[0]);

			case 'link':
				if (isset($parameters[0])) {
					fORMRelated::linkRecords($class, $this->related_records, $subject, $parameters[0]);
				} else {
					fORMRelated::linkRecords($class, $this->related_records, $subject);
				}
				return $this;
			
			case 'list':
				if (isset($parameters[0])) {
					return fORMRelated::getPrimaryKeys($class, $this->values, $this->related_records, $subject, $parameters[0]);
				}
				return fORMRelated::getPrimaryKeys($class, $this->values, $this->related_records, $subject);
			
			case 'populate':
				$route = isset($parameters[0]) ? $parameters[0] : NULL;
				
				list ($subject, $route, ) = self::determineSubject($class, $subject, $route);
				
				fORMRelated::populateRecords($class, $this->related_records, $subject, $route);
				return $this;
			
			case 'tally':
				if (sizeof($parameters) < 1) {
					throw new fProgrammerException(
						'The method, %s(), requires at least one parameter',
						$method_name
					);
				}
				
				if (isset($parameters[1])) {
					return fORMRelated::setCount($class, $this->related_records, $subject, $parameters[0], $parameters[1]);
				}
				return fORMRelated::setCount($class, $this->related_records, $subject, $parameters[0]);
			
			// Error handler
			default:
				throw new fProgrammerException(
					'Unknown method, %s(), called',
					$method_name
				);
		}
	}
	
	
	/**
	 * Creates a clone of a record
	 * 
	 * If the record has an auto incrementing primary key, the primary key will
	 * be erased in the clone. If the primary key is not auto incrementing,
	 * the primary key will be left as-is in the clone. In either situation the
	 * clone will return `FALSE` from the ::exists() method until ::store() is
	 * called.
	 * 
	 * @internal
	 * 
	 * @return fActiveRecord
	 */
	public function __clone()
	{
		$class = get_class($this);
		
		// Copy values and cache, making sure objects are cloned to prevent reference issues
		$temp_values  = $this->values;
		$new_values   = array();
		$this->values =& $new_values;
		foreach ($temp_values as $column => $value) {
			$this->values[$column] = fORM::replicate($class, $column, $value);
		}
		
		$temp_cache  = $this->cache;
		$new_cache   = array();
		$this->cache =& $new_cache;
		foreach ($temp_cache as $key => $value) {
			if (is_object($value)) {
				$this->cache[$key] = clone $value;
			} else {
				$this->cache[$key] = $value;
			}		
		}
		
		// Related records are purged
		$new_related_records   = array();
		$this->related_records =& $new_related_records;
		
		// Old values are changed to look like the record is non-existant
		$new_old_values   = array();
		$this->old_values =& $new_old_values;
		
		foreach (array_keys($this->values) as $key) {
			$this->old_values[$key] = array(NULL);
		}
		
		// If we have a single auto incrementing primary key, remove the value
		$schema     = fORMSchema::retrieve($class);
		$table      = fORM::tablize($class);
		$pk_columns = $schema->getKeys($table, 'primary');
		
		if (sizeof($pk_columns) == 1 && $schema->getColumnInfo($table, $pk_columns[0], 'auto_increment')) {
			$this->values[$pk_columns[0]] = NULL;
			unset($this->old_values[$pk_columns[0]]);
		}		
	}
	
	
	/**
	 * Creates a new record or loads one from the database - if a primary key or unique key is provided the record will be loaded
	 * 
	 * @throws fNotFoundException  When the record specified by `$key` can not be found in the database
	 * 
	 * @param  mixed $key  The primary key or unique key value(s) - single column primary keys will accept a scalar value, all others must be an associative array of `(string) {column} => (mixed) {value}`
	 * @return fActiveRecord
	 */
	public function __construct($key=NULL)
	{
		$class  = get_class($this);
		$schema = fORMSchema::retrieve($class);
		
		// If the features of this class haven't been set yet, do it
		if (!isset(self::$configured[$class])) {
			self::$configured[$class] = TRUE;
			$this->configure();
			
			$table = fORM::tablize($class);
			if (!$schema->getKeys($table, 'primary')) {
				throw new fProgrammerException(
					'The database table %1$s (being modelled by the class %2$s) does not appear to have a primary key defined. %3$s and %4$s will not work properly without a primary key.',
					$table,
					$class,
					'fActiveRecord',
					'fRecordSet'
				);	
			}
			
			// If the configuration was forced, prevent the post::__construct() hook from
			// being triggered since it is not really a real record instantiation
			$trace = array_slice(debug_backtrace(), 0, 2);
			
			$is_forced = sizeof($trace) == 2;
			$is_forced = $is_forced && $trace[1]['function'] == 'forceConfigure';
			$is_forced = $is_forced && isset($trace[1]['class']);
			$is_forced = $is_forced && $trace[1]['type'] == '::';
			$is_forced = $is_forced && in_array($trace[1]['class'], array('fActiveRecord', $class));
			
			if ($is_forced) {
				return;	
			}
		}
		
		if (!isset(self::$callback_cache[$class]['__construct'])) {
			if (!isset(self::$callback_cache[$class])) {
				self::$callback_cache[$class] = array();
			}
			$callback = fORM::getActiveRecordMethod($class, '__construct');
			self::$callback_cache[$class]['__construct'] = $callback ? $callback : FALSE;	
		}
		if ($callback = self::$callback_cache[$class]['__construct']) {
			return $this->__call($callback);
		}
		
		// Handle loading by a result object passed via the fRecordSet class
		if ($key instanceof Iterator) {
			
			$this->loadFromResult($key);
		
		// Handle loading an object from the database
		} elseif ($key !== NULL) {
			
			$table      = fORM::tablize($class);
			$pk_columns = $schema->getKeys($table, 'primary');
			
			// If the primary key does not look properly formatted, check to see if it is a UNIQUE key
			$is_unique_key = FALSE;
			if (is_array($key) && (sizeof($pk_columns) == 1 || array_diff(array_keys($key), $pk_columns))) {
				$unique_keys = $schema->getKeys($table, 'unique');
				$key_keys    = array_keys($key);
				foreach ($unique_keys as $unique_key) {
					if (!array_diff($key_keys, $unique_key)) {
						$is_unique_key = TRUE;
					}
				}	
			}
			
			$wrong_keys = is_array($key) && (count($key) != count($pk_columns) || array_diff(array_keys($key), $pk_columns));
			$wrong_type = !is_array($key) && (sizeof($pk_columns) != 1 || !is_scalar($key));
			
			// If we didn't find a UNIQUE key and primary key doesn't look right we fail
			if (!$is_unique_key && ($wrong_keys || $wrong_type)) {
				throw new fProgrammerException(
					'An invalidly formatted primary or unique key was passed to this %s object',
					fORM::getRecordName($class)
				);
			}
			
			if ($is_unique_key) {
				
				$result = $this->fetchResultFromUniqueKey($key);
				$this->loadFromResult($result);
				
			} else {
				
				$hash = self::hash($key, $class);
				if (!$this->loadFromIdentityMap($key, $hash)) {
					// Assign the primary key values for loading
					if (is_array($key)) {
						foreach ($pk_columns as $pk_column) {
							$this->values[$pk_column] = $key[$pk_column];
						}
					} else {
						$this->values[$pk_columns[0]] = $key;
					}
					
					$this->load();
				}
			}
			
		// Create an empty array for new objects
		} else {
			$column_info = $schema->getColumnInfo(fORM::tablize($class));
			foreach ($column_info as $column => $info) {
				$this->values[$column] = NULL;
				if ($info['default'] !== NULL) {
					self::assign(
						$this->values,
						$this->old_values,
						$column,
						fORM::objectify($class, $column, $info['default'])
					);	
				}
			}
		}
		
		fORM::callHookCallbacks(
			$this,
			'post::__construct()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache
		);
	}
	
	
	/**
	 * All requests that hit this method should be requests for callbacks
	 * 
	 * @internal
	 * 
	 * @param  string $method  The method to create a callback for
	 * @return callback  The callback for the method requested
	 */
	public function __get($method)
	{
		return array($this, $method);		
	}
	
	
	/**
	 * Configure itself when coming out of the session. Records from the session are NOT hooked into the identity map.
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	public function __wakeup()
	{
		$class = get_class($this);
		
		if (!isset(self::$configured[$class])) {
			$this->configure();
			self::$configured[$class] = TRUE;
		}		
	}
	
	
	/**
	 * Allows the programmer to set features for the class
	 * 
	 * This method is only called once per page load for each class.
	 * 
	 * @return void
	 */
	protected function configure()
	{
	}
	
	
	/**
	 * Creates the fDatabase::translatedQuery() insert statement params
	 *
	 * @return array  The parameters for an fDatabase::translatedQuery() SQL insert statement
	 */
	protected function constructInsertParams()
	{
		$columns = array();
		$values  = array();
		
		$column_placeholders = array();
		$value_placeholders  = array();
		
		$class       = get_class($this);
		$schema      = fORMSchema::retrieve($class);
		$table       = fORM::tablize($class);
		$column_info = $schema->getColumnInfo($table);
		foreach ($column_info as $column => $info) {
			if ($schema->getColumnInfo($table, $column, 'auto_increment') && $schema->getColumnInfo($table, $column, 'not_null') && $this->values[$column] === NULL) {
				continue;
			}
			
			$value = fORM::scalarize($class, $column, $this->values[$column]);
			if ($value === NULL && $info['not_null'] && $info['default'] !== NULL) {
				$value = $info['default'];	
			}
			
			$columns[] = $column;
			$values[]  = $value;
			
			$column_placeholders[] = '%r';
			$value_placeholders[]  = $info['placeholder'];
		}
		
		$sql    = 'INSERT INTO %r (' . join(', ', $column_placeholders) . ') VALUES (' . join(', ', $value_placeholders) . ')';
		$params = array($sql, $table);
		$params = array_merge($params, $columns);
		$params = array_merge($params, $values);
		
		return $params;	
	}
	
	
	/**
	 * Creates the fDatabase::translatedQuery() update statement params
	 *
	 * @return array  The parameters for an fDatabase::translatedQuery() SQL update statement
	 */
	protected function constructUpdateParams()
	{
		$class       = get_class($this);
		$schema      = fORMSchema::retrieve($class);
		
		$table       = fORM::tablize($class);
		$column_info = $schema->getColumnInfo($table);
		
		$assignments = array();
		$params      = array($table);
			
		foreach ($column_info as $column => $info) {
			if ($info['auto_increment'] && !fActiveRecord::changed($this->values, $this->old_values, $column) && count($column_info) > 1) {
				continue;
			}
			
			$assignments[] = '%r = ' . $info['placeholder'];
			
			$value = fORM::scalarize($class, $column, $this->values[$column]);
			if ($value === NULL && $info['not_null'] && $info['default'] !== NULL) {
				$value = $info['default'];	
			}
			
			$params[] = $column;
			$params[] = $value;
		}
		
		$sql = 'UPDATE %r SET ' . join(', ', $assignments) . ' WHERE ';
		array_unshift($params, $sql);
		
		return fORMDatabase::addPrimaryKeyWhereParams($schema, $params, $table, $table, $this->values, $this->old_values);
	}
	
	
	/**
	 * Deletes a record from the database, but does not destroy the object
	 * 
	 * This method will start a database transaction if one is not already active.
	 * 
	 * @param  boolean $force_cascade  When TRUE, this will cause all child objects to be deleted, even if the ON DELETE clause is RESTRICT or NO ACTION
	 * @return fActiveRecord  The record object, to allow for method chaining
	 */
	public function delete($force_cascade=FALSE)
	{
		// This flag prevents recursive relationships, such as one-to-one
		// relationships, from creating infinite loops
		if (!empty($this->cache['fActiveRecord::delete()::being_deleted'])) {
			return;	
		}
		
		$class = get_class($this);
		
		if (fORM::getActiveRecordMethod($class, 'delete')) {
			return $this->__call('delete', array());
		}
		
		if (!$this->exists()) {
			throw new fProgrammerException(
				'This %s object does not yet exist in the database, and thus can not be deleted',
				fORM::getRecordName($class)
			);
		}
		
		$db     = fORMDatabase::retrieve($class, 'write');
		$schema = fORMSchema::retrieve($class);
		
		fORM::callHookCallbacks(
			$this,
			'pre::delete()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache
		);
		
		$table = fORM::tablize($class);
		
		$inside_db_transaction = $db->isInsideTransaction();
		
		try {
			
			if (!$inside_db_transaction) {
				$db->translatedQuery('BEGIN');
			}
			
			fORM::callHookCallbacks(
				$this,
				'post-begin::delete()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);
			
			// Check to ensure no foreign dependencies prevent deletion
			$one_to_one_relationships   = $schema->getRelationships($table, 'one-to-one');
			$one_to_many_relationships  = $schema->getRelationships($table, 'one-to-many');
			$many_to_many_relationships = $schema->getRelationships($table, 'many-to-many');
			
			$relationships = array_merge($one_to_one_relationships, $one_to_many_relationships, $many_to_many_relationships);
			$records_sets_to_delete = array();
			
			$restriction_messages = array();
			
			$this->cache['fActiveRecord::delete()::being_deleted'] = TRUE;
			
			foreach ($relationships as $relationship) {
				
				// Figure out how to check for related records
				if (isset($relationship['join_table'])) {
					$type = 'many-to-many';
				} else {
					$type = in_array($relationship, $one_to_one_relationships) ? 'one-to-one' : 'one-to-many';
				}
				$route = fORMSchema::getRouteNameFromRelationship($type, $relationship);
				
				$related_class = fORM::classize($relationship['related_table']);
				
				if ($type == 'one-to-one') {
					$method         = 'create' . $related_class;
					$related_record = $this->$method($route);
					if (!$related_record->exists()) {
						continue;
					} 
					
				} else {
					$method     = 'build' . fGrammar::pluralize($related_class);
					$record_set = $this->$method($route);
					if (!$record_set->count()) {
						continue;
					}
					
					if ($type == 'one-to-many' && $relationship['on_delete'] == 'cascade') {
						$records_sets_to_delete[] = $record_set;
					}
				}
				
				// If we are focing the cascade we have to delete child records and join table entries before this record
				if ($force_cascade) {
					
					if ($type == 'one-to-one') {
						$related_record->delete($force_cascade);
						
					// For one-to-many we explicitly delete all of the records
					} elseif ($type == 'one-to-many') {
						foreach ($record_set as $record) {
							if ($record->exists()) {
								$record->delete($force_cascade);
							}
						}
					
					// For many-to-many relationships we explicitly delete the join table entries
					} elseif ($type == 'many-to-many') {
						$join_column_placeholder = $schema->getColumnInfo($relationship['join_table'], $relationship['join_column'], 'placeholder');
						$column_get_method       = 'get' . fGrammar::camelize($relationship['column'], TRUE);
						
						$db->translatedQuery(
							$db->escape(
								'DELETE FROM %r WHERE %r = ',
								$relationship['join_table'],
								$relationship['join_column']
							) . $join_column_placeholder,
							$this->$column_get_method()
						);		
					}
				
				// Otherwise we have a restriction and we can to create a nice error message for the user
				} elseif ($relationship['on_delete'] == 'restrict' || $relationship['on_delete'] == 'no_action') {
					
					$related_class_name  = fORM::classize($relationship['related_table']);
					$related_class_name  = fORM::getRelatedClass($class, $related_class_name);
					$related_record_name = fORM::getRecordName($related_class_name);
					
					if ($type == 'one-to-one') {
						$restriction_messages[] = self::compose("A %s references it", $related_record_name);
					} else {
						$related_record_name = fGrammar::pluralize($related_record_name);
						$restriction_messages[] = self::compose("One or more %s references it", $related_record_name);
					}
				}
			}
			
			if ($restriction_messages) {
				throw new fValidationException(
					self::compose('This %s can not be deleted because:', fORM::getRecordName($class)),
					$restriction_messages
				);
			}
			
			
			// Delete this record
			$params = array('DELETE FROM %r WHERE ', $table);
			$params = fORMDatabase::addPrimaryKeyWhereParams($schema, $params, $table, $table, $this->values, $this->old_values);
			
			$result = call_user_func_array($db->translatedQuery, $params);
			
			
			// Delete related records to ensure any PHP-level cleanup is done
			foreach ($records_sets_to_delete as $record_set) {
				foreach ($record_set as $record) {
					if ($record->exists()) {
						$record->delete($force_cascade);
					}
				}
			}
			
			unset($this->cache['fActiveRecord::delete()::being_deleted']);
			
			fORM::callHookCallbacks(
				$this,
				'pre-commit::delete()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);
			
			if (!$inside_db_transaction) {
				$db->translatedQuery('COMMIT');
			}
			
			fORM::callHookCallbacks(
				$this,
				'post-commit::delete()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);
			
		} catch (fException $e) {
			
			if (!$inside_db_transaction) {
				$db->translatedQuery('ROLLBACK');
			}
			
			fORM::callHookCallbacks(
				$this,
				'post-rollback::delete()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);
			
			// Check to see if the validation exception came from a related record, and fix the message
			if ($e instanceof fValidationException) {
				$message = $e->getMessage();
				$search  = self::compose('This %s can not be deleted because:', fORM::getRecordName($class));
				if (stripos($message, $search) === FALSE) {
					$regex       = self::compose('This %s can not be deleted because:', '__');
					$regex_parts = explode('__', $regex);
					$regex       = '#(' . preg_quote($regex_parts[0], '#') . ').*?(' . preg_quote($regex_parts[0], '#') . ')#';
					
					$message = preg_replace($regex, '\1' . strtr(fORM::getRecordName($class), array('\\' => '\\\\', '$' => '\\$')) . '\2', $message);
					
					$find          = self::compose("One or more %s references it", '__');
					$find_parts    = explode('__', $find);
					$find_regex    = '#' . preg_quote($find_parts[0], '#') . '(.*?)' . preg_quote($find_parts[1], '#') . '#';
					
					$replace       = self::compose("One or more %s indirectly references it", '__');
					$replace_parts = explode('__', $replace);
					$replace_regex = strtr($replace_parts[0], array('\\' => '\\\\', '$' => '\\$')) . '\1' . strtr($replace_parts[1], array('\\' => '\\\\', '$' => '\\$'));
					
					$message = preg_replace($find_regex, $replace_regex, $regex);
					throw new fValidationException($message);
				}
			}
			
			throw $e;
		}
		
		fORM::callHookCallbacks(
			$this,
			'post::delete()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache
		);
		
		// If we just deleted an object that has an auto-incrementing primary key,
		// lets delete that value from the object since it is no longer valid
		$pk_columns  = $schema->getKeys($table, 'primary');
		if (sizeof($pk_columns) == 1 && $schema->getColumnInfo($table, $pk_columns[0], 'auto_increment')) {
			$this->values[$pk_columns[0]] = NULL;
			unset($this->old_values[$pk_columns[0]]);
		}
		
		return $this;
	}
	
	
	/**
	 * Retrieves a value from the record and prepares it for output into an HTML form element.
	 * 
	 * Below are the transformations performed:
	 *  
	 *  - **varchar, char, text**: will run through fHTML::encode(), if `TRUE` is passed the text will be run through fHTML::convertNewLinks() and fHTML::makeLinks()
	 *  - **float**: takes 1 parameter to specify the number of decimal places
	 *  - **date, time, timestamp**: `format()` will be called on the fDate/fTime/fTimestamp object with the 1 parameter specified
	 *  - **objects**: the object will be converted to a string by `__toString()` or a `(string)` cast and then will be run through fHTML::encode()
	 *  - **all other data types**: the value will be run through fHTML::encode()
	 * 
	 * @param  string $column      The name of the column to retrieve
	 * @param  string $formatting  The formatting string
	 * @return string  The encoded value for the column specified
	 */
	protected function encode($column, $formatting=NULL)
	{
		$column_exists = array_key_exists($column, $this->values);
		$method_name   = 'get' . fGrammar::camelize($column, TRUE);
		$method_exists = method_exists($this, $method_name);
		
		if (!$column_exists && !$method_exists) {
			throw new fProgrammerException(
				'The column specified, %s, does not exist',
				$column
			);
		}
		
		if ($column_exists) {
			$class       = get_class($this);
			$schema      = fORMSchema::retrieve($class);
			$table       = fORM::tablize($class);
			$column_type = $schema->getColumnInfo($table, $column, 'type');
			
			// Ensure the programmer is calling the function properly
			if ($column_type == 'blob') {
				throw new fProgrammerException(
					'The column specified, %s, does not support forming because it is a blob column',
					$column
				);
			}
			
			if ($formatting !== NULL && in_array($column_type, array('boolean', 'integer'))) {
				throw new fProgrammerException(
					'The column specified, %s, does not support any formatting options',
					$column
				);
			}
			
		// If the column doesn't exist, we are just pulling the
		// value from a get method, so treat it as text
		} else {
			$column_type = 'text';	
		}
		
		// Grab the value for empty value checking
		$value = $this->$method_name();
		
		// Date/time objects
		if (is_object($value) && in_array($column_type, array('date', 'time', 'timestamp'))) {
			if ($formatting === NULL) {
				throw new fProgrammerException(
					'The column specified, %s, requires one formatting parameter, a valid date() formatting string',
					$column
				);
			}
			$value = $value->format($formatting);
		}
		
		// Other objects
		if (is_object($value) && is_callable(array($value, '__toString'))) {
			$value = $value->__toString();
		} elseif (is_object($value)) {
			$value = (string) $value;	
		}
		
		// Make sure we don't mangle a non-float value
		if ($column_type == 'float' && is_numeric($value)) {
			$column_decimal_places = $schema->getColumnInfo($table, $column, 'decimal_places');
			
			// If the user passed in a formatting value, use it
			if ($formatting !== NULL && is_numeric($formatting)) {
				$decimal_places = (int) $formatting;
				
			// If the column has a pre-defined number of decimal places, use that
			} elseif ($column_decimal_places !== NULL) {
				$decimal_places = $column_decimal_places;
			
			// This figures out how many decimal places are part of the current value
			} else {
				$value_parts    = explode('.', $value);
				$decimal_places = (!isset($value_parts[1])) ? 0 : strlen($value_parts[1]);
			}
			
			return number_format($value, $decimal_places, '.', '');
		}
		
		// Turn line-breaks into breaks for text fields and add links
		if ($formatting === TRUE && in_array($column_type, array('varchar', 'char', 'text'))) {
			return fHTML::makeLinks(fHTML::convertNewlines(fHTML::encode($value)));
		}
		
		// Anything that has gotten to here is a string value or is not the proper data type for the column that contains it
		return fHTML::encode($value);
	}
	
	
	/**
	 * Checks to see if the record exists in the database
	 * 
	 * @return boolean  If the record exists in the database
	 */
	public function exists()
	{
		$class = get_class($this);
		
		if (fORM::getActiveRecordMethod($class, 'exists')) {
			return $this->__call('exists', array());
		}
		
		$schema     = fORMSchema::retrieve($class);
		$table      = fORM::tablize($class);
		$pk_columns = $schema->getKeys($table, 'primary');
		$exists     = FALSE;
		
		foreach ($pk_columns as $pk_column) {
			$has_old = self::hasOld($this->old_values, $pk_column);
			if (($has_old && self::retrieveOld($this->old_values, $pk_column) !== NULL) || (!$has_old && $this->values[$pk_column] !== NULL)) {
				$exists = TRUE;
			}
		}
		
		return $exists;
	}
	
	
	/**
	 * Loads a record from the database based on a UNIQUE key
	 * 
	 * @throws fNotFoundException
	 * 
	 * @param  array $values  The UNIQUE key values to try and load with
	 * @return void
	 */
	protected function fetchResultFromUniqueKey($values)
	{		
		$class = get_class($this);
		
		$db     = fORMDatabase::retrieve($class, 'read');
		$schema = fORMSchema::retrieve($class);
		
		try {
			if ($values === array_combine(array_keys($values), array_fill(0, sizeof($values), NULL))) {
				throw new fExpectedException('The values specified for the unique key are all NULL');	
			}
			
			$table  = fORM::tablize($class);
			$params = array('SELECT * FROM %r WHERE ', $table);
			
			$column_info = $schema->getColumnInfo($table);
			
			$conditions = array();
			foreach ($values as $column => $value) {
				
				// This makes sure the query performs the way an insert will
				if ($value === NULL && $column_info[$column]['not_null'] && $column_info[$column]['default'] !== NULL) {
					$value = $column_info[$column]['default'];
				}
				
				$conditions[] = fORMDatabase::makeCondition($schema, $table, $column, '=', $value);
				$params[] = $column;
				$params[] = $value;	
			}
			
			$params[0] .= join(' AND ', $conditions);
		
			$result = call_user_func_array($db->translatedQuery, $params);
			$result->tossIfNoRows();
			
		} catch (fExpectedException $e) {
			throw new fNotFoundException(
				'The %s requested could not be found',
				fORM::getRecordName($class)
			);
		}
		
		return $result;
	}
	
	
	/**
	 * Retrieves a value from the record
	 * 
	 * @param  string $column  The name of the column to retrieve
	 * @return mixed  The value for the column specified
	 */
	protected function get($column)
	{
		if (!isset($this->values[$column]) && !array_key_exists($column, $this->values)) {
			throw new fProgrammerException(
				'The column specified, %s, does not exist',
				$column
			);
		}
		return $this->values[$column];
	}
	
	
	/**
	 * Retrieves information about a column
	 * 
	 * @param  string $column   The name of the column to inspect
	 * @param  string $element  The metadata element to retrieve
	 * @return mixed  The metadata array for the column, or the metadata element specified
	 */
	protected function inspect($column, $element=NULL)
	{
		if (!array_key_exists($column, $this->values)) {
			throw new fProgrammerException(
				'The column specified, %s, does not exist',
				$column
			);
		}
		
		$class  = get_class($this);
		$table  = fORM::tablize($class);
		$schema = fORMSchema::retrieve($class);
		$info   = $schema->getColumnInfo($table, $column);
		
		if (!in_array($info['type'], array('varchar', 'char', 'text'))) {
			unset($info['valid_values']);
			unset($info['max_length']);
		}
		
		if ($info['type'] != 'float') {
			unset($info['decimal_places']);
		}
		
		if ($info['type'] != 'integer') {
			unset($info['auto_increment']);
		}
		
		if (!in_array($info['type'], array('integer', 'float'))) {
			unset($info['min_value']);
			unset($info['max_value']);
		}
		
		$info['feature'] = NULL;
		
		fORM::callInspectCallbacks(get_class($this), $column, $info);
		
		if ($element) {
			if (!isset($info[$element]) && !array_key_exists($element, $info)) {
				throw new fProgrammerException(
					'The element specified, %1$s, is invalid. Must be one of: %2$s.',
					$element,
					join(', ', array_keys($info))
				);
			}
			return $info[$element];
		}
		
		return $info;
	}
	
	
	/**
	 * Loads a record from the database
	 * 
	 * @throws fNotFoundException  When the record could not be found in the database
	 * 
	 * @return fActiveRecord  The record object, to allow for method chaining
	 */
	public function load()
	{
		$class  = get_class($this);
		$db     = fORMDatabase::retrieve($class, 'read');
		$schema = fORMSchema::retrieve($class);
		
		if (fORM::getActiveRecordMethod($class, 'load')) {
			return $this->__call('load', array());
		}
		
		try {
			$table = fORM::tablize($class);
			$params = array('SELECT * FROM %r WHERE ', $table);
			$params = fORMDatabase::addPrimaryKeyWhereParams($schema, $params, $table, $table, $this->values, $this->old_values);
		
			$result = call_user_func_array($db->translatedQuery, $params);
			$result->tossIfNoRows();
			
		} catch (fExpectedException $e) {
			throw new fNotFoundException(
				'The %s requested could not be found',
				fORM::getRecordName($class)
			);
		}
		
		$this->loadFromResult($result, TRUE);
		
		// Clears the cached related records so they get pulled from the database
		$this->related_records = array();
		
		return $this;
	}
	
	
	/**
	 * Loads a record from the database directly from a result object
	 * 
	 * @param  Iterator $result               The result object to use for loading the current object
	 * @param  boolean  $ignore_identity_map  If the identity map should be ignored and the values loaded no matter what
	 * @return boolean  If the record was loaded from the identity map
	 */
	protected function loadFromResult($result, $ignore_identity_map=FALSE)
	{
		$class  = get_class($this);
		$table  = fORM::tablize($class);
		$row    = $result->current();
		
		$db     = fORMDatabase::retrieve($class, 'read');
		$schema = fORMSchema::retrieve($class);
		
		if (!isset(self::$unescape_map[$class])) {
			self::$unescape_map[$class] = array();
			$column_info                = $schema->getColumnInfo($table);
			
			foreach ($column_info as $column => $info) {
				if (in_array($info['type'], array('blob', 'boolean', 'date', 'time', 'timestamp'))) {
					self::$unescape_map[$class][$column] = $info['type'];
				}
			}	
		}
		
		$pk_columns = $schema->getKeys($table, 'primary');
		foreach ($pk_columns as $column) {
			$value = $row[$column];
			if ($value !== NULL && isset(self::$unescape_map[$class][$column])) {
				$value = $db->unescape(self::$unescape_map[$class][$column], $value);
			}	
			
			$this->values[$column] = fORM::objectify($class, $column, $value);
			unset($row[$column]);
		}
		
		$hash = self::hash($this->values, $class);
		if (!$ignore_identity_map && $this->loadFromIdentityMap($this->values, $hash)) {
			return TRUE;
		}
		
		foreach ($row as $column => $value) {
			if ($value !== NULL && isset(self::$unescape_map[$class][$column])) {
				$value = $db->unescape(self::$unescape_map[$class][$column], $value);
			}
			
			$this->values[$column] = fORM::objectify($class, $column, $value);
		}
		
		// Save this object to the identity map
		if (!isset(self::$identity_map[$class])) {
			self::$identity_map[$class] = array(); 		
		}
		self::$identity_map[$class][$hash] = $this;
		
		fORM::callHookCallbacks(
			$this,
			'post::loadFromResult()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache
		);
		
		return FALSE;
	}
	
	
	/**
	 * Tries to load the object (via references to class vars) from the fORM identity map
	 * 
	 * @param  array  $row   The data source for the primary key values
	 * @param  string $hash  The unique hash for this record
	 * @return boolean  If the load was successful
	 */
	protected function loadFromIdentityMap($row, $hash)
	{
		$class = get_class($this);
		
		if (!isset(self::$identity_map[$class])) {
			return FALSE;
		}
		
		if (!isset(self::$identity_map[$class][$hash])) {
			return FALSE;
		}
		
		$object = self::$identity_map[$class][$hash];
		
		// If we got a result back, it is the object we are creating
		$this->cache           = &$object->cache;
		$this->values          = &$object->values;
		$this->old_values      = &$object->old_values;
		$this->related_records = &$object->related_records;
		
		fORM::callHookCallbacks(
			$this,
			'post::loadFromIdentityMap()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache
		);
		
		return TRUE;
	}
	
	
	/**
	 * Sets the values for this record by getting values from the request through the fRequest class
	 * 
	 * @return fActiveRecord  The record object, to allow for method chaining
	 */
	public function populate()
	{
		$class = get_class($this);
		
		if (fORM::getActiveRecordMethod($class, 'populate')) {
			return $this->__call('populate', array());
		}
		
		fORM::callHookCallbacks(
			$this,
			'pre::populate()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache
		);
		
		$schema = fORMSchema::retrieve($class);
		$table  = fORM::tablize($class);
		
		$column_info = $schema->getColumnInfo($table);
		foreach ($column_info as $column => $info) {
			if (fRequest::check($column)) {
				$method = 'set' . fGrammar::camelize($column, TRUE);
				$cast_to = ($info['type'] == 'blob') ? 'binary' : NULL;
				$this->$method(fRequest::get($column, $cast_to));
			}
		}
		
		fORM::callHookCallbacks(
			$this,
			'post::populate()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache
		);
		
		return $this;
	}
	
	
	/**
	 * Retrieves a value from the record and prepares it for output into html.
	 * 
	 * Below are the transformations performed:
	 * 
	 *  - **varchar, char, text**: will run through fHTML::prepare(), if `TRUE` is passed the text will be run through fHTML::convertNewLinks() and fHTML::makeLinks()
	 *  - **boolean**: will return `'Yes'` or `'No'`
	 *  - **integer**: will add thousands/millions/etc. separators
	 *  - **float**: will add thousands/millions/etc. separators and takes 1 parameter to specify the number of decimal places
	 *  - **date, time, timestamp**: `format()` will be called on the fDate/fTime/fTimestamp object with the 1 parameter specified
	 *  - **objects**: the object will be converted to a string by `__toString()` or a `(string)` cast and then will be run through fHTML::prepare()
	 * 
	 * @param  string $column      The name of the column to retrieve
	 * @param  mixed  $formatting  The formatting parameter, if applicable
	 * @return string  The formatted value for the column specified
	 */
	protected function prepare($column, $formatting=NULL)
	{
		$column_exists = array_key_exists($column, $this->values);
		$method_name   = 'get' . fGrammar::camelize($column, TRUE);
		$method_exists = method_exists($this, $method_name);
		
		if (!$column_exists && !$method_exists) {
			throw new fProgrammerException(
				'The column specified, %s, does not exist',
				$column
			);
		}
		
		if ($column_exists) {
			$class  = get_class($this);
			$table  = fORM::tablize($class);
			$schema = fORMSchema::retrieve($class);
			
			$column_info = $schema->getColumnInfo($table, $column);
			$column_type = $column_info['type'];
			
			// Ensure the programmer is calling the function properly
			if ($column_type == 'blob') {
				throw new fProgrammerException(
					'The column specified, %s, can not be prepared because it is a blob column',
					$column
				);
			}
			
			if ($formatting !== NULL && in_array($column_type, array('integer', 'boolean'))) {
				throw new fProgrammerException(
					'The column specified, %s, does not support any formatting options',
					$column
				);
			}
		
		// If the column doesn't exist, we are just pulling the
		// value from a get method, so treat it as text
		} else {
			$column_type = 'text';	
		}
		
		// Grab the value for empty value checking
		$value = $this->$method_name();
		
		// Date/time objects
		if (is_object($value) && in_array($column_type, array('date', 'time', 'timestamp'))) {
			if ($formatting === NULL) {
				throw new fProgrammerException(
					'The column specified, %s, requires one formatting parameter, a valid date() formatting string',
					$column
				);
			}
			return $value->format($formatting);
		}
		
		// Other objects
		if (is_object($value) && is_callable(array($value, '__toString'))) {
			$value = $value->__toString();
		} elseif (is_object($value)) {
			$value = (string) $value;	
		}
		
		// Ensure the value matches the data type specified to prevent mangling
		if ($column_type == 'boolean' && is_bool($value)) {
			return ($value) ? 'Yes' : 'No';
		}
		
		if ($column_type == 'integer' && is_numeric($value)) {
			return number_format($value, 0, '', ',');
		}
		
		if ($column_type == 'float' && is_numeric($value)) {
			// If the user passed in a formatting value, use it
			if ($formatting !== NULL && is_numeric($formatting)) {
				$decimal_places = (int) $formatting;
				
			// If the column has a pre-defined number of decimal places, use that
			} elseif ($column_info['decimal_places'] !== NULL) {
				$decimal_places = $column_info['decimal_places'];
			
			// This figures out how many decimal places are part of the current value
			} else {
				$value_parts    = explode('.', $value);
				$decimal_places = (!isset($value_parts[1])) ? 0 : strlen($value_parts[1]);
			}
			
			return number_format($value, $decimal_places, '.', ',');
		}
		
		// Turn line-breaks into breaks for text fields and add links
		if ($formatting === TRUE && in_array($column_type, array('varchar', 'char', 'text'))) {
			return fHTML::makeLinks(fHTML::convertNewlines(fHTML::prepare($value)));
		}
		
		// Anything that has gotten to here is a string value, or is not the
		// proper data type for the column, so we just make sure it is marked
		// up properly for display in HTML
		return fHTML::prepare($value);
	}
	
	
	/**
	 * Generates the method signatures for all methods (including dynamic ones)
	 * 
	 * @param  boolean $include_doc_comments  If the doc block comments for each method should be included
	 * @return array  An associative array of method name => method signature
	 */
	public function reflect($include_doc_comments=FALSE)
	{
		$signatures = array();
		
		$class        = get_class($this);
		$table        = fORM::tablize($class);
		$schema       = fORMSchema::retrieve($class);
		$columns_info = $schema->getColumnInfo($table);
		foreach ($columns_info as $column => $column_info) {
			$camelized_column = fGrammar::camelize($column, TRUE);
			
			// Get and set methods
			$signature = '';
			if ($include_doc_comments) {
				$fixed_type = $column_info['type'];
				if ($fixed_type == 'blob') {
					$fixed_type = 'string';
				}
				if ($fixed_type == 'date') {
					$fixed_type = 'fDate';
				}
				if ($fixed_type == 'timestamp') {
					$fixed_type = 'fTimestamp';
				}
				if ($fixed_type == 'time') {
					$fixed_type = 'fTime';
				}
				
				$signature .= "/**\n";
				$signature .= " * Gets the current value of " . $column . "\n";
				$signature .= " * \n";
				$signature .= " * @return " . $fixed_type . "  The current value\n";
				$signature .= " */\n";
			}
			$get_method = 'get' . $camelized_column;
			$signature .= 'public function ' . $get_method . '()';
			
			$signatures[$get_method] = $signature;
			
			
			$signature = '';
			if ($include_doc_comments) {
				$fixed_type = $column_info['type'];
				if ($fixed_type == 'blob') {
					$fixed_type = 'string';
				}
				if ($fixed_type == 'date') {
					$fixed_type = 'fDate|string';
				}
				if ($fixed_type == 'timestamp') {
					$fixed_type = 'fTimestamp|string';
				}
				if ($fixed_type == 'time') {
					$fixed_type = 'fTime|string';
				}
				
				$signature .= "/**\n";
				$signature .= " * Sets the value for " . $column . "\n";
				$signature .= " * \n";
				$signature .= " * @param  " . $fixed_type . " \$" . $column . "  The new value\n";
				$signature .= " * @return fActiveRecord  The record object, to allow for method chaining\n";
				$signature .= " */\n";
			}
			$set_method = 'set' . $camelized_column;
			$signature .= 'public function ' . $set_method . '($' . $column . ')';
			
			$signatures[$set_method] = $signature;
			
			
			// The encode method
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Encodes the value of " . $column . " for output into an HTML form\n";
				$signature .= " * \n";
				
				if (in_array($column_info['type'], array('time', 'timestamp', 'date'))) {
					$signature .= " * @param  string \$date_formatting_string  A date() compatible formatting string\n";
				}
				if (in_array($column_info['type'], array('float'))) {
					$signature .= " * @param  integer \$decimal_places  The number of decimal places to include - if not specified will default to the precision of the column or the current value\n";
				}
				
				$signature .= " * @return string  The HTML form-ready value\n";
				$signature .= " */\n";
			}
			$encode_method = 'encode' . $camelized_column;
			$signature .= 'public function ' . $encode_method . '(';
			if (in_array($column_info['type'], array('time', 'timestamp', 'date'))) {
				$signature .= '$date_formatting_string';
			}
			if (in_array($column_info['type'], array('float'))) {
				$signature .= '$decimal_places=NULL';
			}
			$signature .= ')';
			
			$signatures[$encode_method] = $signature;
			
			
			// The prepare method
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Prepares the value of " . $column . " for output into HTML\n";
				$signature .= " * \n";
				
				if (in_array($column_info['type'], array('time', 'timestamp', 'date'))) {
					$signature .= " * @param  string \$date_formatting_string  A date() compatible formatting string\n";
				}
				if (in_array($column_info['type'], array('float'))) {
					$signature .= " * @param  integer \$decimal_places  The number of decimal places to include - if not specified will default to the precision of the column or the current value\n";
				}
				if (in_array($column_info['type'], array('varchar', 'char', 'text'))) {
					$signature .= " * @param  boolean \$create_links_and_line_breaks  Will cause links to be automatically converted into [a] tags and line breaks into [br] tags \n";
				}
				
				$signature .= " * @return string  The HTML-ready value\n";
				$signature .= " */\n";
			}
			$prepare_method = 'prepare' . $camelized_column;
			$signature .= 'public function ' . $prepare_method . '(';
			if (in_array($column_info['type'], array('time', 'timestamp', 'date'))) {
				$signature .= '$date_formatting_string';
			}
			if (in_array($column_info['type'], array('float'))) {
				$signature .= '$decimal_places=NULL';
			}
			if (in_array($column_info['type'], array('varchar', 'char', 'text'))) {
				$signature .= '$create_links_and_line_breaks=FALSE';
			}
			$signature .= ')';
			
			$signatures[$prepare_method] = $signature;
			
			
			// The inspect method
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Returns metadata about " . $column . "\n";
				$signature .= " * \n";
				$elements = array('type', 'not_null', 'default', 'comment');
				if (in_array($column_info['type'], array('varchar', 'char', 'text'))) {
					$elements[] = 'valid_values';
					$elements[] = 'max_length';
				}
				if ($column_info['type'] == 'float') {
					$elements[] = 'decimal_places';
				}
				if ($column_info['type'] == 'integer') {
					$elements[] = 'auto_increment';
					$elements[] = 'min_value';
					$elements[] = 'max_value';
				}
				$signature .= " * @param  string \$element  The element to return. Must be one of: '" . join("', '", $elements) . "'.\n";
				$signature .= " * @return mixed  The metadata array or a single element\n";
				$signature .= " */\n";
			}
			$inspect_method = 'inspect' . $camelized_column;
			$signature .= 'public function ' . $inspect_method . '($element=NULL)';
			
			$signatures[$inspect_method] = $signature;
		}
		
		fORMRelated::reflect($class, $signatures, $include_doc_comments);
		
		fORM::callReflectCallbacks($class, $signatures, $include_doc_comments);
		
		$reflection = new ReflectionClass($class);
		$methods    = $reflection->getMethods();
		
		foreach ($methods as $method) {
			$signature = '';
			
			if (!$method->isPublic() || $method->getName() == '__call') {
				continue;
			}
			
			if ($method->isFinal()) {
				$signature .= 'final ';
			}
			
			if ($method->isAbstract()) {
				$signature .= 'abstract ';
			}
			
			if ($method->isStatic()) {
				$signature .= 'static ';
			}
			
			$signature .= 'public function ';
			
			if ($method->returnsReference()) {
				$signature .= '&';
			}
			
			$signature .= $method->getName();
			$signature .= '(';
			
			$parameters = $method->getParameters();
			foreach ($parameters as $parameter) {
				if (substr($signature, -1) == '(') {
					$signature .= '';
				} else {
					$signature .= ', ';
				}
				
				if ($parameter->isArray()) {
					$signature .= 'array ';	
				}
				if ($parameter->getClass()) {
					$signature .= $parameter->getClass()->getName() . ' ';	
				}
				if ($parameter->isPassedByReference()) {
					$signature .= '&';	
				}
				$signature .= '$' . $parameter->getName();
				
				if ($parameter->isDefaultValueAvailable()) {
					$val = var_export($parameter->getDefaultValue(), TRUE);
					if ($val == 'true') {
						$val = 'TRUE';
					}
					if ($val == 'false') {
						$val = 'FALSE';
					}
					if (is_array($parameter->getDefaultValue())) {
						$val = preg_replace('#array\s+\(\s+#', 'array(', $val);
						$val = preg_replace('#,(\r)?\n  #', ', ', $val);
						$val = preg_replace('#,(\r)?\n\)#', ')', $val);
					}
					$signature .= '=' . $val;
				}
			}
			
			$signature .= ')';
			
			if ($include_doc_comments) {
				$comment = $method->getDocComment();
				$comment = preg_replace('#^\t+#m', '', $comment);
				$signature = $comment . "\n" . $signature;
			}
			$signatures[$method->getName()] = $signature;
		}
		
		ksort($signatures);
		
		return $signatures;
	}
	
	
	/**
	 * Generates a clone of the current record, removing any auto incremented primary key value and allowing for replicating related records
	 * 
	 * This method will accept three different sets of parameters:
	 * 
	 *  - No parameters: this object will be cloned
	 *  - A single `TRUE` value: this object plus all many-to-many associations and all child records (recursively) will be cloned
	 *  - Any number of plural related record class names: the many-to-many associations or child records that correspond to the classes specified will be cloned
	 * 
	 * The class names specified can be a simple class name if there is only a
	 * single route between the two corresponding database tables. If there is 
	 * more than one route between the two tables, the class name should be
	 * substituted with a string in the format `'RelatedClass{route}'`.
	 * 
	 * @param  string $related_class  The plural related class to replicate - see method description for details
	 * @param  string ...
	 * @return fActiveRecord  The cloned record
	 */
	public function replicate($related_class=NULL)
	{
		fORM::callHookCallbacks(
			$this,
			'pre::replicate()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache,
			fActiveRecord::$replicate_level
		);
		
		fActiveRecord::$replicate_level++;
		
		$class  = get_class($this);
		$hash   = self::hash($this->values, $class);
		$schema = fORMSchema::retrieve($class);
		$table  = fORM::tablize($class);
			
		// If the object has not been replicated yet, do it now
		if (!isset(fActiveRecord::$replicate_map[$class])) {
			fActiveRecord::$replicate_map[$class] = array();
		}
		if (!isset(fActiveRecord::$replicate_map[$class][$hash])) {
			fActiveRecord::$replicate_map[$class][$hash] = clone $this;
			
			// We need the primary key to get a hash, otherwise certain recursive relationships end up losing members
			$pk_columns = $schema->getKeys($table, 'primary');
			if (sizeof($pk_columns) == 1 && $schema->getColumnInfo($table, $pk_columns[0], 'auto_increment')) {
				fActiveRecord::$replicate_map[$class][$hash]->values[$pk_columns[0]] = $this->values[$pk_columns[0]];
			}
			
		}
		$clone = fActiveRecord::$replicate_map[$class][$hash];
		
		$parameters = func_get_args();
		
		$recursive                  = FALSE;
		$many_to_many_relationships = $schema->getRelationships($table, 'many-to-many');
		$one_to_many_relationships  = $schema->getRelationships($table, 'one-to-many');
		
		
		// When just TRUE is passed we recursively replicate all related records
		if (sizeof($parameters) == 1 && $parameters[0] === TRUE) {
			$parameters = array();
			$recursive  = TRUE;
			
			foreach ($many_to_many_relationships as $relationship) {
				$parameters[] = fGrammar::pluralize(fORM::classize($relationship['related_table'])) . '{' . $relationship['join_table'] . '}'; 		
			}
			foreach ($one_to_many_relationships as $relationship) {
				$parameters[] = fGrammar::pluralize(fORM::classize($relationship['related_table'])) . '{' . $relationship['related_column'] . '}'; 		
			}			
		}
		
		$record_sets = array();
		
		foreach ($parameters as $parameter) {
			
			// Parse the Class{route} strings
			if (strpos($parameter, '{') !== FALSE) {
				$brace         = strpos($parameter, '{');
				$related_class = fGrammar::singularize(substr($parameter, 0, $brace));
				$related_class = fORM::getRelatedClass($class, $related_class);
				$related_table = fORM::tablize($related_class);
				$route         = substr($parameter, $brace+1, -1);
			} else {
				$related_class = fGrammar::singularize($parameter);
				$related_class = fORM::getRelatedClass($class, $related_class);
				$related_table = fORM::tablize($related_class);
				$route         = fORMSchema::getRouteName($schema, $table, $related_table);
			}
			
			// Determine the kind of relationship
			$many_to_many = FALSE;
			$one_to_many  = FALSE;
			
			foreach ($many_to_many_relationships as $relationship) {
				if ($relationship['related_table'] == $related_table && $relationship['join_table'] == $route) {
					$many_to_many = TRUE;	
					break;
				}
			}
			
			foreach ($one_to_many_relationships as $relationship) {
				if ($relationship['related_table'] == $related_table && $relationship['related_column'] == $route) {
					$one_to_many = TRUE;
					break;
				}	
			}
			
			if (!$many_to_many && !$one_to_many) {
				throw new fProgrammerException(
					'The related class specified, %1$s, does not appear to be in a many-to-many or one-to-many relationship with %$2s',
					$parameter,
					get_class($this)
				);	
			}
			
			// Get the related records
			$record_set = fORMRelated::buildRecords($class, $this->values, $this->related_records, $related_class, $route);
			
			// One-to-many records need to be replicated, possibly recursively
			if ($one_to_many) {
				if ($recursive) {
					$records = $record_set->call('replicate', TRUE);
				} else {
					$records = $record_set->call('replicate');
				}
				$record_set = fRecordSet::buildFromArray($related_class, $records);
				$record_set->call(
					'set' . fGrammar::camelize($route, TRUE),
					NULL
				);	
			}
			
			// Cause the related records to be associated with the new clone
			fORMRelated::associateRecords($class, $clone->related_records, $related_class, $record_set, $route);
		}
		
		fActiveRecord::$replicate_level--;
		if (!fActiveRecord::$replicate_level) {
			// This removes the primary keys we had added back in for proper duplicate detection
			foreach (fActiveRecord::$replicate_map as $class => $records) {
				$table      = fORM::tablize($class);
				$pk_columns = $schema->getKeys($table, 'primary');
				if (sizeof($pk_columns) != 1 || !$schema->getColumnInfo($table, $pk_columns[0], 'auto_increment')) {
					continue;
				}
				foreach ($records as $hash => $record) {
					$record->values[$pk_columns[0]] = NULL;		
				}	
			}
			fActiveRecord::$replicate_map = array();	
		}
		
		fORM::callHookCallbacks(
			$this,
			'post::replicate()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache,
			fActiveRecord::$replicate_level
		);
		
		fORM::callHookCallbacks(
			$clone,
			'cloned::replicate()',
			$clone->values,
			$clone->old_values,
			$clone->related_records,
			$clone->cache,
			fActiveRecord::$replicate_level
		);
		
		return $clone;
	}
	
	
	/**
	 * Sets a value to the record
	 * 
	 * @param  string $column  The column to set the value to
	 * @param  mixed  $value   The value to set
	 * @return fActiveRecord  This record, to allow for method chaining
	 */
	protected function set($column, $value)
	{
		if (!array_key_exists($column, $this->values)) {
			throw new fProgrammerException(
				'The column specified, %s, does not exist',
				$column
			);
		}
		
		// We consider an empty string or a string of spaces to be equivalent to NULL
		if ($value === '' || (is_string($value) && trim($value) === '')) {
			$value = NULL;
		}
		
		$class = get_class($this);
		$value = fORM::objectify($class, $column, $value);
		
		// Float and int columns that look like numbers with commas will have the commas removed
		if (is_string($value)) {
			$table  = fORM::tablize($class);
			$schema = fORMSchema::retrieve($class);
			$type   = $schema->getColumnInfo($table, $column, 'type');
			if (in_array($type, array('integer', 'float')) && preg_match('#^(\d+,)+\d+(\.\d+)?$#', $value)) {
				$value = str_replace(',', '', $value);
			}
		}
		
		self::assign($this->values, $this->old_values, $column, $value);
		
		return $this;
	}
	
	
	/**
	 * Stores a record in the database, whether existing or new
	 * 
	 * This method will start database and filesystem transactions if they have
	 * not already been started.
	 * 
	 * @throws fValidationException  When ::validate() throws an exception
	 * 
	 * @param  boolean $force_cascade  When storing related records, this will force deleting child records even if they have their own children in a relationship with an RESTRICT or NO ACTION for the ON DELETE clause
	 * @return fActiveRecord  The record object, to allow for method chaining
	 */
	public function store($force_cascade=FALSE)
	{
		$class = get_class($this);
		
		if (fORM::getActiveRecordMethod($class, 'store')) {
			return $this->__call('store', array());
		}
		
		fORM::callHookCallbacks(
			$this,
			'pre::store()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache
		);
		
		$db     = fORMDatabase::retrieve($class, 'write');
		$schema = fORMSchema::retrieve($class);
		
		try {
			$table = fORM::tablize($class);
			
			// New auto-incrementing records require lots of special stuff, so we'll detect them here
			$new_autoincrementing_record = FALSE;
			if (!$this->exists()) {
				$pk_columns           = $schema->getKeys($table, 'primary');
				$pk_column            = $pk_columns[0];
				$pk_auto_incrementing = $schema->getColumnInfo($table, $pk_column, 'auto_increment');
				
				if (sizeof($pk_columns) == 1 && $pk_auto_incrementing && !$this->values[$pk_column]) {
					$new_autoincrementing_record = TRUE;
				}
			}
			
			$inside_db_transaction = $db->isInsideTransaction();
			
			if (!$inside_db_transaction) {
				$db->translatedQuery('BEGIN');
			}
			
			fORM::callHookCallbacks(
				$this,
				'post-begin::store()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);
			
			$this->validate();
			
			fORM::callHookCallbacks(
				$this,
				'post-validate::store()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);
			
			// Storing main table
			
			if (!$this->exists()) {
				$params = $this->constructInsertParams();
			} else {
				$params = $this->constructUpdateParams();
			}
			$result = call_user_func_array($db->translatedQuery, $params);
			
			
			// If there is an auto-incrementing primary key, grab the value from the database
			if ($new_autoincrementing_record) {
				$this->set($pk_column, $result->getAutoIncrementedValue());
			}
			
			
			// Fix cascade updated columns for in-memory objects to prevent issues when saving
			$one_to_one_relationships  = $schema->getRelationships($table, 'one-to-one');
			$one_to_many_relationships = $schema->getRelationships($table, 'one-to-many');
			
			$relationships = array_merge($one_to_one_relationships, $one_to_many_relationships);
			
			foreach ($relationships as $relationship) {
				$type  = in_array($relationship, $one_to_one_relationships) ? 'one-to-one' : 'one-to-many';
				$route = fORMSchema::getRouteNameFromRelationship($type, $relationship);
				
				$related_table = $relationship['related_table'];
				$related_class = fORM::classize($related_table);
				$related_class = fORM::getRelatedClass($class, $related_class);
				
				if ($relationship['on_update'] != 'cascade') {
					continue;
				}
				
				$column = $relationship['column'];
				if (!fActiveRecord::changed($this->values, $this->old_values, $column)) {
					continue;
				}
				
				if (!isset($this->related_records[$related_table][$route]['record_set'])) {
					continue;
				}
				
				$record_set     = $this->related_records[$related_table][$route]['record_set'];
				$related_column = $relationship['related_column'];
				
				$old_value      = fActiveRecord::retrieveOld($this->old_values, $column);
				$value          = $this->values[$column];
				
				if ($old_value === NULL) {
					continue;
				}
				foreach ($record_set as $record) {
					if (isset($record->old_values[$related_column])) {
						foreach (array_keys($record->old_values[$related_column]) as $key) {
							if ($record->old_values[$related_column][$key] === $old_value) {
								$record->old_values[$related_column][$key] = $value;
							}
						}
					}
					if ($record->values[$related_column] === $old_value) {
						$record->values[$related_column] = $value;
					}
				}
			}
			
			// Storing *-to-many and one-to-one relationships
			fORMRelated::store($class, $this->values, $this->related_records, $force_cascade);
			
			
			fORM::callHookCallbacks(
				$this,
				'pre-commit::store()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);
			
			if (!$inside_db_transaction) {
				$db->translatedQuery('COMMIT');
			}
			
			fORM::callHookCallbacks(
				$this,
				'post-commit::store()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);
			
		} catch (fException $e) {
			
			if (!$inside_db_transaction) {
				$db->translatedQuery('ROLLBACK');
			}
			
			fORM::callHookCallbacks(
				$this,
				'post-rollback::store()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);
			
			if ($new_autoincrementing_record && self::hasOld($this->old_values, $pk_column)) {
				$this->values[$pk_column] = self::retrieveOld($this->old_values, $pk_column);
				unset($this->old_values[$pk_column]);
			}
			
			throw $e;
		}
		
		fORM::callHookCallbacks(
			$this,
			'post::store()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache
		);
		
		$was_new = !$this->exists();
		
		// If we got here we succefully stored, so update old values to make exists() work
		foreach ($this->values as $column => $value) {
			$this->old_values[$column] = array($value);
		}
		
		// If the object was just inserted into the database, save it to the identity map
		if ($was_new) {
			$hash = self::hash($this->values, $class);
			
			if (!isset(self::$identity_map[$class])) {
				self::$identity_map[$class] = array(); 		
			}
			self::$identity_map[$class][$hash] = $this;		
		}
		
		return $this;
	}
	
	
	/**
	 * Validates the values of the record against the database and any additional validation rules
	 * 
	 * @throws fValidationException  When the record, or one of the associated records, violates one of the validation rules for the class or can not be properly stored in the database
	 * 
	 * @param  boolean $return_messages      If an array of validation messages should be returned instead of an exception being thrown
	 * @param  boolean $remove_column_names  If column names should be removed from the returned messages, leaving just the message itself
	 * @return void|array  If $return_messages is TRUE, an array of validation messages will be returned
	 */
	public function validate($return_messages=FALSE, $remove_column_names=FALSE)
	{
		$class = get_class($this);
		
		if (fORM::getActiveRecordMethod($class, 'validate')) {
			return $this->__call('validate', array($return_messages));
		}
		
		$validation_messages = array();
		
		fORM::callHookCallbacks(
			$this,
			'pre::validate()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache,
			$validation_messages
		);
		
		// Validate the local values
		$local_validation_messages = fORMValidation::validate($this, $this->values, $this->old_values);
		
		// Validate related records
		$related_validation_messages = fORMValidation::validateRelated($this, $this->values, $this->related_records);
		
		$validation_messages = array_merge($validation_messages, $local_validation_messages, $related_validation_messages);
		
		fORM::callHookCallbacks(
			$this,
			'post::validate()',
			$this->values,
			$this->old_values,
			$this->related_records,
			$this->cache,
			$validation_messages
		);
		
		$validation_messages = fORMValidation::replaceMessages($class, $validation_messages);
		$validation_messages = fORMValidation::reorderMessages($class, $validation_messages);
		
		if ($return_messages) {
			if ($remove_column_names) {
				$validation_messages = fValidationException::removeFieldNames($validation_messages);
			}
			return $validation_messages;
		}
		
		if (!empty($validation_messages)) {
			throw new fValidationException(
				'The following problems were found:',
				$validation_messages
			);
		}
	}
}



/**
 * Copyright (c) 2007-2011 Will Bond <will@flourishlib.com>, others
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */