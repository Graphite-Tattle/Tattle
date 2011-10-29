<?php
/**
 * Handles related record tasks for fActiveRecord classes
 * 
 * The functionality of this class only works with single-field `FOREIGN KEY`
 * constraints.
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fORMRelated
 * 
 * @version    1.0.0b44
 * @changes    1.0.0b44  Added missing information for has and list methods to ::reflect() [wb, 2011-09-07]
 * @changes    1.0.0b43  Fixed some bugs in handling relationships between PHP 5.3 namespaced classes [wb, 2011-05-26]
 * @changes    1.0.0b42  Fixed a bug with ::associateRecords() not associating record set via primary key [wb, 2011-05-23]
 * @changes    1.0.0b41  Fixed a bug in generating errors messages for many-to-many relationships [wb, 2011-03-07]
 * @changes    1.0.0b40  Updated ::getRelatedRecordName() to use fText if loaded [wb, 2011-02-02]
 * @changes    1.0.0b39  Fixed a bug with ::validate() not properly removing validation messages about a related primary key value not being present yet, if the column and related column names were different [wb, 2010-11-24]
 * @changes    1.0.0b38  Updated ::overrideRelatedRecordName() to prefix any namespace from `$class` to `$related_class` if not already present [wb, 2010-11-24]
 * @changes    1.0.0b37  Fixed a documentation typo [wb, 2010-11-04]
 * @changes    1.0.0b36  Fixed ::getPrimaryKeys() to not throw SQL exceptions [wb, 2010-10-20]
 * @changes    1.0.0b35  Backwards Compatibility Break - changed the validation messages array to use nesting for child records [wb-imarc+wb, 2010-10-03]
 * @changes    1.0.0b35  Updated ::getPrimaryKeys() to always return primary keys in a consistent order when no order bys are specified [wb, 2010-07-26]
 * @changes    1.0.0b34  Updated the class to work with fixes in fORMRelated [wb, 2010-07-22]
 * @changes    1.0.0b33  Fixed the related table populate action to use the plural underscore_notation version of the related class name [wb, 2010-07-08]
 * @changes    1.0.0b32  Backwards Compatibility Break - related table populate action now use the underscore_notation version of the class name instead of the related table name, allowing for related tables in non-standard schemas [wb, 2010-06-23]
 * @changes    1.0.0b31  Fixed ::reflect() to properly show parameters for associate methods [wb, 2010-06-08]
 * @changes    1.0.0b30  Fixed a bug where related record error messages could be overwritten if there were multiple related records with the same error [wb, 2010-05-29]
 * @changes    1.0.0b29  Changed validation messages array to use column name keys [wb, 2010-05-26]
 * @changes    1.0.0b28  Updated ::associateRecords() to accept just a single fActiveRecord [wb, 2010-05-06]
 * @changes    1.0.0b27  Updated the class to force configure classes before peforming actions with them [wb, 2010-03-30]
 * @changes    1.0.0b26  Fixed ::reflect() to show the proper return values for `associate`, `link` and `populate` methods [wb, 2010-03-15]
 * @changes    1.0.0b25  Fixed a bug when storing a one-to-one related record with different column names on each end of the relationship [wb, 2010-03-04]
 * @changes    1.0.0b24  Added the ability to associate a single record via primary key [wb, 2010-03-03]
 * @changes    1.0.0b23  Fixed a column aliasing issue with SQLite [wb, 2010-01-25]
 * @changes    1.0.0b22  Fixed a bug with associating a non-contiguous array of fActiveRecord objects [wb, 2009-12-17]
 * @changes    1.0.0b21  Added support for the $force_cascade parameter of fActiveRecord::store(), added ::hasRecords() and fixed a bug with creating non-existent one-to-one related records [wb, 2009-12-16]
 * @changes    1.0.0b20  Updated code for the new fORMDatabase and fORMSchema APIs [wb, 2009-10-28]
 * @changes    1.0.0b19  Internal Backwards Compatibility Break - Added the `$class` parameter to ::storeManyToMany() - also fixed ::countRecords() to work across all databases, changed SQL statements to use value placeholders, identifier escaping and support schemas [wb, 2009-10-22]
 * @changes    1.0.0b18  Fixed a bug in ::countRecords() that would occur when multiple routes existed to the table being counted [wb, 2009-10-05]
 * @changes    1.0.0b17  Updated code for new fRecordSet API [wb, 2009-09-16]
 * @changes    1.0.0b16  Fixed a bug with ::createRecord() not creating non-existent record when the related value is NULL [wb, 2009-08-25]
 * @changes    1.0.0b15  Fixed a bug with ::createRecord() where foreign keys with a different column and related column name would not load properly [wb, 2009-08-17]
 * @changes    1.0.0b14  Fixed a bug with ::createRecord() when a foreign key constraint is on a column other than the primary key [wb, 2009-08-10]
 * @changes    1.0.0b13  ::setOrderBys() now (properly) only recognizes *-to-many relationships [wb, 2009-07-31] 
 * @changes    1.0.0b12  Changed how related record values are set and how related validation messages are ignored because of recursive relationships [wb, 2009-07-29]
 * @changes    1.0.0b11  Fixed some bugs with one-to-one relationships [wb, 2009-07-21]
 * @changes    1.0.0b10  Fixed a couple of bugs with validating related records [wb, 2009-06-26]
 * @changes    1.0.0b9   Fixed a bug where ::store() would not save associations with no related records [wb, 2009-06-23]
 * @changes    1.0.0b8   Changed ::associateRecords() to work for *-to-many instead of just many-to-many relationships [wb, 2009-06-17]
 * @changes    1.0.0b7   Updated code for new fORM API, fixed API documentation bugs [wb, 2009-06-15]
 * @changes    1.0.0b6   Updated code to use new fValidationException::formatField() method [wb, 2009-06-04]  
 * @changes    1.0.0b5   Added ::getPrimaryKeys() and ::setPrimaryKeys(), renamed ::setRecords() to ::setRecordSet() and ::tallyRecords() to ::setCount() [wb, 2009-06-02]
 * @changes    1.0.0b4   Updated code to handle new association method for related records and new `$related_records` structure, added ::store() and ::validate() [wb, 2009-06-02]
 * @changes    1.0.0b3   ::associateRecords() can now accept an array of records or primary keys instead of only an fRecordSet [wb, 2009-06-01]
 * @changes    1.0.0b2   ::populateRecords() now accepts any input field keys instead of sequential ones starting from 0 [wb, 2009-05-03]
 * @changes    1.0.0b    The initial implementation [wb, 2007-12-30]
 */
class fORMRelated
{
	// The following constants allow for nice looking callbacks to static methods
	const associateRecords             = 'fORMRelated::associateRecords';
	const buildRecords                 = 'fORMRelated::buildRecords';
	const countRecords                 = 'fORMRelated::countRecords';
	const createRecord                 = 'fORMRelated::createRecord';
	const determineRequestFilter       = 'fORMRelated::determineRequestFilter';
	const flagForAssociation           = 'fORMRelated::flagForAssociation';
	const getOrderBys                  = 'fORMRelated::getOrderBys';
	const getRelatedRecordName         = 'fORMRelated::getRelatedRecordName';
	const hasRecords                   = 'fORMRelated::hasRecords';
	const linkRecords                  = 'fORMRelated::linkRecords';
	const overrideRelatedRecordName    = 'fORMRelated::overrideRelatedRecordName';
	const populateRecords              = 'fORMRelated::populateRecords';
	const reflect                      = 'fORMRelated::reflect';
	const registerValidationNameMethod = 'fORMRelated::registerValidationNameMethod';
	const reset                        = 'fORMRelated::reset';
	const setOrderBys                  = 'fORMRelated::setOrderBys';
	const setCount                     = 'fORMRelated::setCount';
	const setPrimaryKeys               = 'fORMRelated::setPrimaryKeys';
	const setRecordSet                 = 'fORMRelated::setRecordSet';
	const store                        = 'fORMRelated::store';
	const storeManyToMany              = 'fORMRelated::storeManyToMany';
	const storeOneToMany               = 'fORMRelated::storeOneToMany';
	const validate                     = 'fORMRelated::validate';
	
	
	/**
	 * A generic cache for the class
	 * 
	 * @var array
	 */
	static private $cache = array();
	
	/**
	 * Rules that control what order related data is returned in
	 * 
	 * @var array
	 */
	static private $order_bys = array();
	
	/**
	 * Names for related records
	 * 
	 * @var array
	 */
	static private $related_record_names = array();
	
	/**
	 * Methods to use for getting the name of related records when performing validation
	 * 
	 * @var array
	 */
	static private $validation_name_methods = array();
	
	
	/**
	 * Creates associations for one-to-one relationships
	 * 
	 * @internal
	 * 
	 * @param  string                             $class             The class to get the related values for
	 * @param  array                              &$related_records  The related records existing for the fActiveRecord class
	 * @param  string                             $related_class     The class we are associating with the current record
	 * @param  fActiveRecord|array|string|integer $record            The record (or primary key of the record) to be associated
	 * @param  string                             $route             The route to use between the current class and the related class
	 * @return void
	 */
	static public function associateRecord($class, &$related_records, $related_class, $record, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		if ($record !== NULL) {
			if (!$record instanceof fActiveRecord) {
				$record = new $related_class($record);	
			}
			$records = array($record);
		} else {
			$records = array();
		}
		
		$schema  = fORMSchema::retrieve($class);
		$records = fRecordSet::buildFromArray($related_class, $records);
		$route   = fORMSchema::getRouteName($schema, $table, $related_table, $route, 'one-to-one');
		
		self::setRecordSet($class, $related_records, $related_class, $records, $route);
		self::flagForAssociation($class, $related_records, $related_class, $route);
	}
	
	
	/**
	 * Creates associations for *-to-many relationships
	 * 
	 * @internal
	 * 
	 * @param  string            $class                 The class to get the related values for
	 * @param  array             &$related_records      The related records existing for the fActiveRecord class
	 * @param  string            $related_class         The class we are associating with the current record
	 * @param  fRecordSet|array  $records_to_associate  An fRecordSet, an array or records, or an array of primary keys of the records to be associated
	 * @param  string            $route                 The route to use between the current class and the related class
	 * @return void
	 */
	static public function associateRecords($class, &$related_records, $related_class, $records_to_associate, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$primary_keys = FALSE;
		
		if ($records_to_associate instanceof fActiveRecord) {
			$records = fRecordSet::buildFromArray($related_class, array($records_to_associate));
		
		} elseif ($records_to_associate instanceof fRecordSet) {
			$records = clone $records_to_associate;
		
		} elseif (!sizeof($records_to_associate)) {
			$records = fRecordSet::buildFromArray($related_class, array());
		
		} elseif (reset($records_to_associate) instanceof fActiveRecord) {
			$records = fRecordSet::buildFromArray($related_class, $records_to_associate);
		
		// This indicates we are working with just primary keys, so we have to call a different method
		} else {
			$primary_keys = TRUE;	
		}
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route, '*-to-many');
		
		if ($primary_keys) {
			self::setPrimaryKeys($class, $related_records, $related_class, $records_to_associate, $route);
		} else {
			self::setRecordSet($class, $related_records, $related_class, $records, $route);
		}
		self::flagForAssociation($class, $related_records, $related_class, $route);
	}
	
	
	/**
	 * Builds a set of related records along a one-to-many or many-to-many relationship
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to get the related values for
	 * @param  array  &$values           The values for the fActiveRecord class
	 * @param  array  &$related_records  The related records existing for the fActiveRecord class
	 * @param  string $related_class     The class that is related to the current record
	 * @param  string $route             The route to follow for the class specified
	 * @return fRecordSet  A record set of the related records
	 */
	static public function buildRecords($class, &$values, &$related_records, $related_class, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route, '*-to-many');
		
		// If we already have the sequence, we can stop here
		if (isset($related_records[$related_table][$route]['record_set'])) {
			return $related_records[$related_table][$route]['record_set'];
		}
		
		$relationship = fORMSchema::getRoute($schema, $table, $related_table, $route, '*-to-many');
		
		// Determine how we are going to build the sequence
		if (isset($related_records[$related_table][$route]['primary_keys'])) {
			$primary_key_column = current($schema->getKeys($related_table, 'primary'));
			$where_conditions   = array($primary_key_column . '=' => $related_records[$related_table][$route]['primary_keys']);
			$order_bys          = self::getOrderBys($class, $related_class, $route);
			$record_set         = fRecordSet::build($related_class, $where_conditions, $order_bys);
			$related_records[$related_table][$route]['record_set'] = $record_set;
			return $record_set;

		} elseif ($values[$relationship['column']] === NULL) {
			$record_set = fRecordSet::buildFromArray($related_class, array());
		
		} else {
			$column = $table . '{' . $route . '}.' . $relationship['column'];
			
			$where_conditions = array($column . '=' => $values[$relationship['column']]);
			$order_bys        = self::getOrderBys($class, $related_class, $route);
			$record_set       = fRecordSet::build($related_class, $where_conditions, $order_bys);
		}
		
		self::setRecordSet($class, $related_records, $related_class, $record_set, $route);

		return $record_set;
	}
	
	
	/**
	 * Composes text using fText if loaded
	 * 
	 * @param  string  $message    The message to compose
	 * @param  mixed   $component  A string or number to insert into the message
	 * @param  mixed   ...
	 * @return string  The composed and possible translated message
	 */
	static private function compose($message)
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
	 * Counts the number of related one-to-many or many-to-many records
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to get the related values for
	 * @param  array  &$values           The values for the fActiveRecord class
	 * @param  array  &$related_records  The related records existing for the fActiveRecord class
	 * @param  string $related_class     The class that is related to the current record
	 * @param  string $route             The route to follow for the class specified
	 * @return integer  The number of related records
	 */
	static public function countRecords($class, &$values, &$related_records, $related_class, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$db     = fORMDatabase::retrieve($class, 'read');
		$schema = fORMSchema::retrieve($class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$route = fORMSchema::getRouteName($schema, $table, $related_table, $route, '*-to-many');
		
		// If we already have the sequence, we can stop here
		if (isset($related_records[$related_table][$route]['count'])) {
			return $related_records[$related_table][$route]['count'];
		}
		
		$relationship = fORMSchema::getRoute($schema, $table, $related_table, $route, '*-to-many');
		
		// Determine how we are going to build the sequence
		if ($values[$relationship['column']] === NULL) {
			$count = 0;
		} else {
			$column = $relationship['column'];
			$value  = $values[$column];
			
			$pk_columns = $schema->getKeys($related_table, 'primary');
			
			// One-to-many relationships require joins
			if (!isset($relationship['join_table'])) {
				$table_with_route = $table . '{' . $relationship['related_column'] . '}';
				
				$params = array("SELECT count(*) AS flourish__count FROM :from_clause WHERE ");
				
				$params[0] .= str_replace(
					'%r',
					$db->escape('%r', $table_with_route . '.' . $column),
					fORMDatabase::makeCondition($schema, $table, $column, '=', $value)
				);
				$params[] = $value;
				
				$params = fORMDatabase::injectFromAndGroupByClauses($db, $schema, $params, $related_table);
				
			// Many-to-many relationships allow counting just from the join table
			} else {
				
				$params = array($db->escape(
					"SELECT count(*) FROM %r WHERE %r = ",
					$relationship['join_table'],
					$relationship['join_column']
				));
				
				$params[0] .= $schema->getColumnInfo($table, $column, 'placeholder');
				$params[] = $value;
			}
			
			$result = call_user_func_array($db->translatedQuery, $params);
			
			$count = ($result->valid()) ? (int) $result->fetchScalar() : 0;
		}
		
		self::setCount($class, $related_records, $related_class, $count, $route);
		
		return $count;
	}
	
	
	/**
	 * Builds the object for the related class specified
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to create the related record for
	 * @param  array  $values            The values existing in the fActiveRecord class
	 * @param  array  &$related_records  The related records for the record
	 * @param  string $related_class     The related class name
	 * @param  string $route             The route to the related class
	 * @return fActiveRecord  An instance of the class specified
	 */
	static public function createRecord($class, $values, &$related_records, $related_class, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$schema        = fORMSchema::retrieve($class);
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$relationship = fORMSchema::getRoute($schema, $table, $related_table, $route, '*-to-one');
		$route        = $relationship['column'];
		
		// Determine if the relationship is one-to-one
		if (isset(self::$cache['one-to-one::' . $table . '::' . $related_table . '::' . $route])) {
			$one_to_one = self::$cache['one-to-one::' . $table . '::' . $related_table . '::' . $route];	
		
		} else {
			$one_to_one = FALSE;
			$one_to_one_relationships = fORMSchema::getRoutes($schema, $table, $related_table, 'one-to-one');
			foreach ($one_to_one_relationships as $one_to_one_relationship) {
				if ($relationship['column'] == $one_to_one_relationship['column']) {
					$one_to_one = TRUE;
					break;	
				}
			}	
			
			self::$cache['one-to-one::' . $table . '::' . $related_table . '::' . $route] = $one_to_one;
		}
		
		// One-to-one records are stored in the related records array to support populating
		if ($one_to_one) {
			if (isset($related_records[$related_table][$route]['record_set'])) {
				if ($related_records[$related_table][$route]['record_set']->count()) {
					return $related_records[$related_table][$route]['record_set'][0];
				}
				return new $related_class();
			}
			
			// If the value is NULL, don't pass it to the constructor because an fNotFoundException will be thrown
			if ($values[$relationship['column']] !== NULL) {
				try {
					$records = array(new $related_class(array($relationship['related_column'] => $values[$relationship['column']])));
				} catch (fNotFoundException $e) {
					$records = array();	
				}
			} else {
				$records = array();
			}	
			$record_set = fRecordSet::buildFromArray($related_class, $records);
			self::setRecordSet($class, $related_records, $related_class, $record_set, $route);
			
			if ($record_set->count()) {
				return $record_set[0];		
			}
			return new $related_class();	
		}
		
		// This allows records without a related record to return a non-existent one
		if ($values[$relationship['column']] === NULL) {
			return new $related_class();
		}
		
		return new $related_class(array($relationship['related_column'] => $values[$relationship['column']]));
	}
	
	
	
	/**
	 * Figures out the first primary key column for a related class that is not the related column
	 *
	 * @internal
	 * 
	 * @param  string $class          The class name of the main class
	 * @param  string $related_class  The related class being filtered for
	 * @param  string $route          The route to the related class
	 * @return string  The first primary key column in the related class
	 */
	static public function determineFirstPKColumn($class, $related_class, $route)
	{
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema        = fORMSchema::retrieve($class);
		$pk_columns    = $schema->getKeys($related_table, 'primary');
		
		// If there is a multi-fiend primary key we want to populate based on any field BUT the foreign key to the current class
		if (sizeof($pk_columns) > 1) {
		
			$first_pk_column = NULL;
			$relationships   = fORMSchema::getRoutes($schema, $related_table, $table, '*-to-one');
			foreach ($pk_columns as $pk_column) {
				foreach ($relationships as $relationship) {
					if ($pk_column == $relationship['column']) {
						continue;
					}
					$first_pk_column = $pk_column;
					break 2;
				}	
			}
			
			if (!$first_pk_column) {
				$first_pk_column = $pk_columns[0];
			}
			
		} else {
			$first_pk_column = $pk_columns[0];
		}
		
		return $first_pk_column;	
	}
	
	
	/**
	 * Figures out what filter to pass to fRequest::filter() for the specified related class
	 *
	 * @internal
	 * 
	 * @param  string $class          The class name of the main class
	 * @param  string $related_class  The related class being filtered for
	 * @param  string $route          The route to the related class
	 * @return string  The prefix to filter the request fields by
	 */
	static public function determineRequestFilter($class, $related_class, $route)
	{
		$table           = fORM::tablize($class);
		$schema          = fORMSchema::retrieve($class);
		
		$related_table   = fORM::tablize($related_class);
		$relationship    = fORMSchema::getRoute($schema, $table, $related_table, $route);
		
		$route_name    	 = fORMSchema::getRouteNameFromRelationship('one-to-many', $relationship);
		
		$primary_keys    = $schema->getKeys($related_table, 'primary');
		$first_pk_column = $primary_keys[0];
		
		$filter_class            = fGrammar::pluralize(fGrammar::underscorize($related_class));
		$filter_class_with_route = $filter_class . '{' . $route_name . '}';
		
		$pk_field            = $filter_class . '::' . $first_pk_column;
		$pk_field_with_route = $filter_class_with_route . '::' . $first_pk_column;
		
		if (!fRequest::check($pk_field) && fRequest::check($pk_field_with_route)) {
			$filter_class = $filter_class_with_route;
		}
		
		return $filter_class . '::';
	}
	
	
	/**
	 * Sets the related records for a *-to-many relationship to be associated upon fActiveRecord::store()
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to associate the related records to
	 * @param  array  &$related_records  The related records existing for the fActiveRecord class
	 * @param  string $related_class     The class we are associating with the current record
	 * @param  string $route             The route to use between the current class and the related class
	 * @return void
	 */
	static public function flagForAssociation($class, &$related_records, $related_class, $route=NULL)
	{
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route, '!many-to-one');
		
		if (!isset($related_records[$related_table][$route]['record_set']) && !isset($related_records[$related_table][$route]['primary_keys'])) {
			throw new fProgrammerException(
				'%1$s can only be called after %2$s or %3$s',
				__CLASS__ . '::flagForAssociation()',
				__CLASS__ . '::setRecordSet()',
				__CLASS__ . '::setPrimaryKeys()'
			);
		}
		
		$related_records[$related_table][$route]['associate'] = TRUE;
	}
	
	
	/**
	 * Gets the ordering to use when returning an fRecordSet of related objects
	 *
	 * @internal
	 * 
	 * @param  string $class          The class to get the order bys for
	 * @param  string $related_class  The related class the ordering rules apply to
	 * @param  string $route          The route to the related table, should be a column name in the current table or a join table name
	 * @return array  An array of the order bys - see fRecordSet::build() for format
	 */
	static public function getOrderBys($class, $related_class, $route)
	{
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route);
		
		if (!isset(self::$order_bys[$table][$related_table]) || !isset(self::$order_bys[$table][$related_table][$route])) {
			return array();
		}
		
		return self::$order_bys[$table][$related_table][$route];
	}
	
	
	/**
	 * Gets the primary keys of the related records for *-to-many relationships
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to get the related primary keys for
	 * @param  array  &$values           The values for the fActiveRecord class
	 * @param  array  &$related_records  The related records existing for the fActiveRecord class
	 * @param  string $related_class     The class that is related to the current record
	 * @param  string $route             The route to follow for the class specified
	 * @return array  The primary keys of the related records
	 */
	static public function getPrimaryKeys($class, &$values, &$related_records, $related_class, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$db     = fORMDatabase::retrieve($class, 'read');
		$schema = fORMSchema::retrieve($class);
		
		$route = fORMSchema::getRouteName($schema, $table, $related_table, $route, '*-to-many');
		
		if (!isset($related_records[$related_table])) {
			$related_records[$related_table] = array();
		}
		if (!isset($related_records[$related_table][$route])) {
			$related_records[$related_table][$route] = array();
		}
		
		$related_info =& $related_records[$related_table][$route];
		if (!isset($related_info['primary_keys'])) {
			if (isset($related_info['record_set'])) {
				$related_info['primary_keys'] = $related_info['record_set']->getPrimaryKeys();
				
			// If we don't have a record set yet we want to use a single SQL query to just get the primary keys
			} else {
				$relationship       = fORMSchema::getRoute($schema, $table, $related_table, $route, '*-to-many');
				$related_pk_columns = $schema->getKeys($related_table, 'primary');
				$column_info        = $schema->getColumnInfo($related_table);
				$column             = $relationship['column'];
				
				$aliased_related_pk_columns = array();
				foreach ($related_pk_columns as $related_pk_column) {
					// We explicitly alias the columns due to SQLite issues
					$aliased_related_pk_columns[] = $db->escape("%r AS %r", $related_table . '.' . $related_pk_column, $related_pk_column);	
				}
				
				if (isset($relationship['join_table'])) {
					$table_with_route = $table . '{' . $relationship['join_table'] . '}';	
				} else {
					$table_with_route = $table . '{' . $relationship['related_column'] . '}';
				}
				
				$column         = $relationship['column'];
				$related_column = $relationship['related_column'];
				
				$params = array(
					$db->escape(
						sprintf(
							"SELECT %s FROM :from_clause WHERE",
							join(', ', $aliased_related_pk_columns)
						) . " %r = ",
						$table_with_route . '.' . $column
					),
				);
				$params[0] .= $schema->getColumnInfo($table, $column, 'placeholder');
				$params[] = $values[$column];
				
				$params[0] .= " :group_by_clause ";
				
				if (!$order_bys = self::getOrderBys($class, $related_class, $route)) {
					$order_bys = array();
					foreach ($related_pk_columns as $related_pk_column) {
						$order_bys[$related_pk_column] = 'ASC';
					}
				}
				$params[0] .= " ORDER BY ";
				$params = fORMDatabase::addOrderByClause($db, $schema, $params, $related_table, $order_bys);
				
				$params = fORMDatabase::injectFromAndGroupByClauses($db, $schema, $params, $related_table);
				
				$result = call_user_func_array($db->translatedQuery, $params);
				
				$primary_keys = array();
				
				foreach ($result as $row) {
					if (sizeof($row) > 1) {
						$primary_key = array();
						foreach ($row as $column => $value) {
							$value = $db->unescape($column_info[$column]['type'], $value);
							$primary_key[$column] = $value;
						}	
						$primary_keys[] = $primary_key;
					} else {
						$column = key($row);
						$primary_keys[] = $db->unescape($column_info[$column]['type'], $row[$column]);
					}	
				}
				
				$related_info['record_set']   = NULL;
				$related_info['count']        = sizeof($primary_keys);
				$related_info['associate']    = FALSE;
				$related_info['primary_keys'] = $primary_keys;
			}	
		}
		
		return $related_info['primary_keys'];
	}
	
	
	/**
	 * Returns the record name for a related class
	 * 
	 * The default record name of a related class is the result of
	 * fGrammar::humanize() called on the class.
	 * 
	 * @internal
	 * 
	 * @param  string $class          The class to get the related class name for
	 * @param  string $related_class  The related class to get the record name of
	 * @return string  The record name for the related class specified
	 */
	static public function getRelatedRecordName($class, $related_class, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route);
		
		if (!isset(self::$related_record_names[$table]) ||
			  !isset(self::$related_record_names[$table][$related_class]) ||
			  !isset(self::$related_record_names[$table][$related_class][$route])) {
			return fORM::getRecordName($related_class);
		}
		
		// If fText is loaded, use it
		if (class_exists('fText', FALSE)) {
			return call_user_func(
				array('fText', 'compose'),
				str_replace('%', '%%', self::$related_record_names[$table][$related_class][$route])
			);
		}
		
		return self::$related_record_names[$table][$related_class][$route];
	}
	
	
	/**
	 * Indicates if a record has a one-to-one or any *-to-many related records
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to check related records for
	 * @param  array  &$values           The values for the record we are checking 
	 * @param  array  &$related_records  The related records for the record we are checking
	 * @param  string $related_class     The related class we are checking for
	 * @param  string $route             The route to the related class
	 * @return void
	 */
	static public function hasRecords($class, &$values, &$related_records, $related_class, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route, '!many-to-one');
		
		if (!isset($related_records[$related_table][$route]['count'])) {
			if (fORMSchema::isOneToOne($schema, $table, $related_table, $route)) {
				self::createRecord($class, $values, $related_records, $related_class, $route);
			} else {
				self::countRecords($class, $values, $related_records, $related_class, $route);
			}	
		}
		
		return (boolean) $related_records[$related_table][$route]['count'];	
	}
	
	
	/**
	 * Parses associations for many-to-many relationships from the page request
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to get link the related records to
	 * @param  array  &$related_records  The related records existing for the fActiveRecord class
	 * @param  string $related_class     The related class to populate
	 * @param  string $route             The route to the related class
	 * @return void
	 */
	static public function linkRecords($class, &$related_records, $related_class, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema       = fORMSchema::retrieve($class);
		$route_name   = fORMSchema::getRouteName($schema, $table, $related_table, $route, 'many-to-many');
		$relationship = fORMSchema::getRoute($schema, $table, $related_table, $route, 'many-to-many');
		
		$field_table      = $relationship['related_table'];
		$field_column     = '::' . $relationship['related_column'];
		
		$field            = $field_table . $field_column;
		$field_with_route = $field_table . '{' . $route_name . '}' . $field_column;
		
		// If there is only one route and they specified the route instead of leaving it off, use that
		if ($route === NULL && !fRequest::check($field) && fRequest::check($field_with_route)) {
			$field = $field_with_route;
		}
		
		$record_set = fRecordSet::build(
			$related_class,
			array(
				$relationship['related_column'] . '=' => fRequest::get($field, 'array', array())
			)
		);
		
		self::associateRecords($class, $related_records, $related_class, $record_set, $route_name);
	}
	
	
	/**
	 * Does an [http://php.net/array_diff array_diff()] for two arrays that have arrays as values
	 * 
	 * @param  array $array1  The array to remove items from
	 * @param  array $array2  The array of items to remove
	 * @return array  The items in `$array1` that were not also in `$array2`
	 */
	static private function multidimensionArrayDiff($array1, $array2)
	{
		$output = array();
		foreach ($array1 as $sub_array1) {
			$remove = FALSE;
			foreach ($array2 as $sub_array2) {
				if ($sub_array1 == $sub_array2) {
					$remove = TRUE;
				}
			}
			if (!$remove) {
				$output[] = $sub_array1;
			}
		}
		return $output;
	}
	
	
	/**
	 * Allows overriding of default record names or related records
	 * 
	 * The default record name of a related record is the result of
	 * fGrammar::humanize() called on the class name.
	 * 
	 * @param  mixed  $class          The class name or instance of the class to set the related record name for
	 * @param  mixed  $related_class  The name of the related class, or an instance of it
	 * @param  string $record_name    The human version of the related record
	 * @param  string $route          The route to the related class
	 * @return void
	 */
	static public function overrideRelatedRecordName($class, $related_class, $record_name, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$class         = fORM::getClass($class);
		$table         = fORM::tablize($class);
		
		$related_class = fORM::getClass($related_class);
		$related_class = fORM::getRelatedClass($class, $related_class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route, '*-to-many');
		
		if (!isset(self::$related_record_names[$table])) {
			self::$related_record_names[$table] = array();
		}
		
		if (!isset(self::$related_record_names[$table][$related_class])) {
			self::$related_record_names[$table][$related_class] = array();
		}
		
		self::$related_record_names[$table][$related_class][$route] = $record_name;
	}
	
	
	/**
	 * Sets the values for records in a one-to-many relationship with this record
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to populate the related records of
	 * @param  array  &$related_records  The related records existing for the fActiveRecord class
	 * @param  string $related_class     The related class to populate
	 * @param  string $route             The route to the related class
	 * @return void
	 */
	static public function populateRecords($class, &$related_records, $related_class, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table           = fORM::tablize($class);
		$related_table   = fORM::tablize($related_class);
		$schema          = fORMSchema::retrieve($class);
		$pk_columns      = $schema->getKeys($related_table, 'primary');
		
		$first_pk_column = self::determineFirstPKColumn($class, $related_class, $route);
		
		$filter          = self::determineRequestFilter($class, $related_class, $route);
		$pk_field        = $filter . $first_pk_column;
		
		$input_keys = array_keys(fRequest::get($pk_field, 'array', array()));
		$records    = array();
		
		foreach ($input_keys as $input_key) {
			fRequest::filter($filter, $input_key);
			
			// Try to load the value from the database first
			try {
				if (sizeof($pk_columns) == 1) {
					$primary_key_values = fRequest::get($first_pk_column);
				} else {
					$primary_key_values = array();
					foreach ($pk_columns as $pk_column) {
						$primary_key_values[$pk_column] = fRequest::get($pk_column);
					}
				}
				
				$record = new $related_class($primary_key_values);
				
			} catch (fNotFoundException $e) {
				$record = new $related_class();
			}
			
			$record->populate();
			$records[] = $record;
			
			fRequest::unfilter();
		}
		
		$record_set = fRecordSet::buildFromArray($related_class, $records);
		self::setRecordSet($class, $related_records, $related_class, $record_set, $route);
		self::flagForAssociation($class, $related_records, $related_class, $route);
	}
	
	
	/**
	 * Adds information about methods provided by this class to fActiveRecord
	 * 
	 * @internal
	 * 
	 * @param  string  $class                 The class to reflect the related record methods for
	 * @param  array   &$signatures           The associative array of `{method_name} => {signature}`
	 * @param  boolean $include_doc_comments  If the doc block comments for each method should be included
	 * @return void
	 */
	static public function reflect($class, &$signatures, $include_doc_comments)
	{
		$table  = fORM::tablize($class);
		$schema = fORMSchema::retrieve($class);
		
		$one_to_one_relationships   = $schema->getRelationships($table, 'one-to-one');
		$one_to_many_relationships  = $schema->getRelationships($table, 'one-to-many');
		$many_to_one_relationships  = $schema->getRelationships($table, 'many-to-one');
		$many_to_many_relationships = $schema->getRelationships($table, 'many-to-many');
		
		$to_one_relationships  = array_merge($one_to_one_relationships, $many_to_one_relationships);
		$to_many_relationships = array_merge($one_to_many_relationships, $many_to_many_relationships);
		
		$to_one_created = array();
		
		foreach ($to_one_relationships as $relationship) {
			$related_class = fORM::classize($relationship['related_table']);
			$related_class = fORM::getRelatedClass($class, $related_class);
			
			if (isset($to_one_created[$related_class])) {
				continue;
			}
			
			$routes = fORMSchema::getRoutes($schema, $table, $relationship['related_table'], '*-to-one');
			$route_names = array();
			
			foreach ($routes as $route) {
				$route_names[] = fORMSchema::getRouteNameFromRelationship('*-to-one', $route);
			}
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Creates the related " . $related_class . "\n";
				$signature .= " * \n";
				if (sizeof($route_names) > 1) {
					$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $route_names) . "'.\n";
				}
				$signature .= " * @return " . $related_class . "  The related object\n";
				$signature .= " */\n";
			}
			$create_method = 'create' . $related_class;
			$signature .= 'public function ' . $create_method . '(';
			if (sizeof($route_names) > 1) {
				$signature .= '$route';
			}
			$signature .= ')';
			
			$signatures[$create_method] = $signature;
			
			$to_one_created[$related_class] = TRUE;
		}
		
		$one_to_one_created = array();
		
		foreach ($one_to_one_relationships as $relationship) {
			$related_class = fORM::classize($relationship['related_table']);
			$related_class = fORM::getRelatedClass($class, $related_class);
			
			if (isset($one_to_one_created[$related_class])) {
				continue;
			}
			
			$routes = fORMSchema::getRoutes($schema, $table, $relationship['related_table'], 'one-to-one');
			$route_names = array();
			
			foreach ($routes as $route) {
				$route_names[] = fORMSchema::getRouteNameFromRelationship('one-to-one', $route);
			}
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Populates the related " . $related_class . "\n";
				$signature .= " * \n";
				if (sizeof($route_names) > 1) {
					$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $route_names) . "'.\n";
				}
				$signature .= " * @return fActiveRecord  The record object, to allow for method chaining\n";
				$signature .= " */\n";
			}
			$populate_method = 'populate' . $related_class;
			$signature .= 'public function ' . $populate_method . '(';
			if (sizeof($route_names) > 1) {
				$signature .= '$route';
			}
			$signature .= ')';
			
			$signatures[$populate_method] = $signature;
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Associates the related " . $related_class . " to this record\n";
				$signature .= " * \n";
				$signature .= " * @param  fActiveRecord|array|string|integer \$record  The record, or the primary key of the record, to associate\n";
				if (sizeof($route_names) > 1) {
					$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $route_names) . "'.\n";
				}
				$signature .= " * @return fActiveRecord  The record object, to allow for method chaining\n";
				$signature .= " */\n";
			}
			$associate_method = 'associate' . $related_class;
			$signature .= 'public function ' . $associate_method . '($record';
			if (sizeof($route_names) > 1) {
				$signature .= ', $route';
			}
			$signature .= ')';
			
			$signatures[$associate_method] = $signature;

			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Indicates if a related " . $related_class . " exists\n";
				$signature .= " * \n";
				if (sizeof($route_names) > 1) {
					$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $route_names) . "'.\n";
				}
				$signature .= " * @return boolean  If a related record exists\n";
				$signature .= " */\n";
			}
			$has_method = 'has' . $related_class;
			$signature .= 'public function ' . $has_method . '($record';
			if (sizeof($route_names) > 1) {
				$signature .= ', $route';
			}
			$signature .= ')';
			
			$signatures[$has_method] = $signature;
			
			$one_to_one_created[$related_class] = TRUE;		
		}
		
		$to_many_created = array();
		
		foreach ($to_many_relationships as $relationship) {
			$related_class = fORM::classize($relationship['related_table']);
			$related_class = fORM::getRelatedClass($class, $related_class);
			
			if (isset($to_many_created[$related_class])) {
				continue;
			}
			
			$routes = fORMSchema::getRoutes($schema, $table, $relationship['related_table'], '*-to-many');
			$route_names = array();
			
			$many_to_many_route_names = array();
			$one_to_many_route_names  = array();
			
			foreach ($routes as $route) {
				if (isset($route['join_table'])) {
					$route_name = fORMSchema::getRouteNameFromRelationship('many-to-many', $route);
					$route_names[]              = $route_name;
					$many_to_many_route_names[] = $route_name;
					
				} else {
					$route_name = fORMSchema::getRouteNameFromRelationship('one-to-many', $route);
					$route_names[]             = $route_name;
					$one_to_many_route_names[] = $route_name;
				}
			}
			
			if ($one_to_many_route_names) {
				$signature = '';
				if ($include_doc_comments) {
					$related_table = fORM::tablize($related_class);
				
					$signature .= "/**\n";
					$signature .= " * Calls the ::populate() method for multiple child " . $related_class . " records. Uses request value arrays in the form " . $related_table . "::{column_name}[].\n";
					$signature .= " * \n";
					if (sizeof($one_to_many_route_names) > 1) {
						$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $one_to_many_route_names) . "'.\n";
					}
					$signature .= " * @return fActiveRecord  The record object, to allow for method chaining\n";
					$signature .= " */\n";
				}
				$populate_related_method = 'populate' . fGrammar::pluralize($related_class);
				$signature .= 'public function ' . $populate_related_method . '(';
				if (sizeof($one_to_many_route_names) > 1) {
					$signature .= '$route';
				}
				$signature .= ')';
				
				$signatures[$populate_related_method] = $signature;
			}
			
			
			if ($many_to_many_route_names) {
				$signature = '';
				if ($include_doc_comments) {
					$related_table = fORM::tablize($related_class);
				
					$signature .= "/**\n";
					$signature .= " * Creates entries in the appropriate joining table to create associations with the specified " . $related_class . " records. Uses request value array(s) in the form " . $related_table . "::{primary_key_column_name(s)}[].\n";
					$signature .= " * \n";
					if (sizeof($many_to_many_route_names) > 1) {
						$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $many_to_many_route_names) . "'.\n";
					}
					$signature .= " * @return fActiveRecord  The record object, to allow for method chaining\n";
					$signature .= " */\n";
				}
				$link_related_method = 'link' . fGrammar::pluralize($related_class);
				$signature .= 'public function ' . $link_related_method . '(';
				if (sizeof($many_to_many_route_names) > 1) {
					$signature .= '$route';
				}
				$signature .= ')';
				
				$signatures[$link_related_method] = $signature;
				
				
				$signature = '';
				if ($include_doc_comments) {
					$related_table = fORM::tablize($related_class);
				
					$signature .= "/**\n";
					$signature .= " * Creates entries in the appropriate joining table to create associations with the specified " . $related_class . " records\n";
					$signature .= " * \n";
					$signature .= " * @param  fRecordSet|array \$records_to_associate  The records to associate - should be an fRecords, an array of records or an array of primary keys\n";
					if (sizeof($many_to_many_route_names) > 1) {
						$signature .= " * @param  string           \$route  The route to the related class. Must be one of: '" . join("', '", $many_to_many_route_names) . "'.\n";
					}
					$signature .= " * @return fActiveRecord  The record object, to allow for method chaining\n";
					$signature .= " */\n";
				}
				$associate_related_method = 'associate' . fGrammar::pluralize($related_class);
				$signature .= 'public function ' . $associate_related_method . '($records_to_associate';
				if (sizeof($many_to_many_route_names) > 1) {
					$signature .= ', $route';
				}
				$signature .= ')';
				
				$signatures[$associate_related_method] = $signature;
			}
			
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Builds an fRecordSet of the related " . $related_class . " objects\n";
				$signature .= " * \n";
				if (sizeof($route_names) > 1) {
					$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $route_names) . "'.\n";
				}
				$signature .= " * @return fRecordSet  A record set of the related " . $related_class . " objects\n";
				$signature .= " */\n";
			}
			$build_method = 'build' . fGrammar::pluralize($related_class);
			$signature .= 'public function ' . $build_method . '(';
			if (sizeof($route_names) > 1) {
				$signature .= '$route';
			}
			$signature .= ')';
			
			$signatures[$build_method] = $signature;

			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Indicates if related " . $related_class . " objects exist\n";
				$signature .= " * \n";
				if (sizeof($route_names) > 1) {
					$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $route_names) . "'.\n";
				}
				$signature .= " * @return boolean  If related " . $related_class . " objects exist\n";
				$signature .= " */\n";
			}
			$has_method = 'has' . fGrammar::pluralize($related_class);
			$signature .= 'public function ' . $has_method . '(';
			if (sizeof($route_names) > 1) {
				$signature .= '$route';
			}
			$signature .= ')';
			
			$signatures[$has_method] = $signature;

			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Returns an array of the primary keys for the related " . $related_class . " objects\n";
				$signature .= " * \n";
				if (sizeof($route_names) > 1) {
					$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $route_names) . "'.\n";
				}
				$signature .= " * @return array  The primary keys of the related " . $related_class . " objects\n";
				$signature .= " */\n";
			}
			$list_method = 'list' . fGrammar::pluralize($related_class);
			$signature .= 'public function ' . $list_method . '(';
			if (sizeof($route_names) > 1) {
				$signature .= '$route';
			}
			$signature .= ')';
			
			$signatures[$list_method] = $signature;
			
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Counts the number of related " . $related_class . " objects\n";
				$signature .= " * \n";
				if (sizeof($route_names) > 1) {
					$signature .= " * @param  string \$route  The route to the related class. Must be one of: '" . join("', '", $route_names) . "'.\n";
				}
				$signature .= " * @return integer  The number related " . $related_class . " objects\n";
				$signature .= " */\n";
			}
			$count_method = 'count' . fGrammar::pluralize($related_class);
			$signature .= 'public function ' . $count_method . '(';
			if (sizeof($route_names) > 1) {
				$signature .= '$route';
			}
			$signature .= ')';
			
			$signatures[$count_method] = $signature;
			
			
			$to_many_created[$related_class] = TRUE;
		}
	}
	
	
	/**
	 * Registers a method to use to get a name for a related record when doing validation
	 * 
	 * @param string|fActiveRecord $class          The class to register the method for
	 * @param string               $related_class  The related class to register the method for
	 * @param string               $method         The method to be called on the related class that will return the name
	 * @param string               $route          The route to the related class
	 */
	static public function registerValidationNameMethod($class, $related_class, $method, $route=NULL)
	{
		$class         = fORM::getClass($class);
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		$schema        = fORMSchema::retrieve($class);
		$route         = fORMSchema::getRouteName($schema, $table, $related_table, $route, 'one-to-many');
		
		if (!isset(self::$validation_name_methods[$class])) {
			self::$validation_name_methods[$class] = array();
		}
		if (!isset(self::$validation_name_methods[$class][$related_class])) {
			self::$validation_name_methods[$class][$related_class] = array();
		}
		
		self::$validation_name_methods[$class][$related_class][$route] = $method;
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
		self::$cache                = array();
		self::$order_bys            = array();
		self::$related_record_names = array();
	}
	
	
	/**
	 * Sets the ordering to use when returning an fRecordSet of related objects
	 *
	 * @param  mixed  $class           The class name or instance of the class this ordering rule applies to
	 * @param  string $related_class   The related class we are getting info from
	 * @param  array  $order_bys       An array of the order bys for this table.column combination - see fRecordSet::build() for format
	 * @param  string $route           The route to the related table, this should be a column name in the current table or a join table name
	 * @return void
	 */
	static public function setOrderBys($class, $related_class, $order_bys, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$class         = fORM::getClass($class);
		$table         = fORM::tablize($class);
		
		$related_class = fORM::getRelatedClass($class, $related_class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route, '*-to-many');
		
		if (!isset(self::$order_bys[$table])) {
			self::$order_bys[$table] = array();
		}
		
		if (!isset(self::$order_bys[$table][$related_table])) {
			self::$order_bys[$table][$related_table] = array();
		}
		
		self::$order_bys[$table][$related_table][$route] = $order_bys;
	}
	
	
	/**
	 * Records the number of related one-to-many or many-to-many records
	 * 
	 * @internal
	 * 
	 * @param  string  $class             The class to set the related records count for
	 * @param  array   &$values           The values for the fActiveRecord class
	 * @param  array   &$related_records  The related records existing for the fActiveRecord class
	 * @param  string  $related_class     The class that is related to the current record
	 * @param  integer $count             The number of records
	 * @param  string  $route             The route to follow for the class specified
	 * @return void
	 */
	static public function setCount($class, &$related_records, $related_class, $count, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route, '*-to-many');
		
		// Cache the results for subsequent calls
		if (!isset($related_records[$related_table])) {
			$related_records[$related_table] = array();
		}
		if (!isset($related_records[$related_table][$route])) {
			$related_records[$related_table][$route] = array();
		}
		
		if (!isset($related_records[$related_table][$route]['record_set'])) {
			$related_records[$related_table][$route]['record_set']   = NULL;
			$related_records[$related_table][$route]['associate']    = FALSE;
			$related_records[$related_table][$route]['primary_keys'] = NULL;
		}
		
		$related_records[$related_table][$route]['count'] = $count;
	}
	
	
	/**
	 * Sets the related records for *-to-many relationships, providing only primary keys
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to set the related primary keys for
	 * @param  array  &$related_records  The related records existing for the fActiveRecord class
	 * @param  string $related_class     The class we are setting the records for
	 * @param  array  $primary_keys      The records to set
	 * @param  string $route             The route to use between the current class and the related class
	 * @return void
	 */
	static public function setPrimaryKeys($class, &$related_records, $related_class, $primary_keys, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route, '*-to-many');
		
		if (!isset($related_records[$related_table])) {
			$related_records[$related_table] = array();
		}
		if (!isset($related_records[$related_table][$route])) {
			$related_records[$related_table][$route] = array();
		}
		
		$related_records[$related_table][$route]['record_set']   = NULL;
		$related_records[$related_table][$route]['count']        = sizeof($primary_keys);
		$related_records[$related_table][$route]['associate']    = FALSE;
		$related_records[$related_table][$route]['primary_keys'] = $primary_keys;
	}
	
	
	/**
	 * Sets the related records for *-to-many relationships
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to set the related records for
	 * @param  array  &$related_records  The related records existing for the fActiveRecord class
	 * @param  string $related_class     The class we are associating with the current record
	 * @param  fRecordSet $records       The records are associating
	 * @param  string $route             The route to use between the current class and the related class
	 * @return void
	 */
	static public function setRecordSet($class, &$related_records, $related_class, fRecordSet $records, $route=NULL)
	{
		fActiveRecord::validateClass($related_class);
		fActiveRecord::forceConfigure($related_class);
		
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema = fORMSchema::retrieve($class);
		$route  = fORMSchema::getRouteName($schema, $table, $related_table, $route, '!many-to-one');
		
		if (!isset($related_records[$related_table])) {
			$related_records[$related_table] = array();
		}
		if (!isset($related_records[$related_table][$route])) {
			$related_records[$related_table][$route] = array();
		}
		
		$related_records[$related_table][$route]['record_set']   = $records;
		$related_records[$related_table][$route]['count']        = $records->count();
		$related_records[$related_table][$route]['associate']    = FALSE;
		$related_records[$related_table][$route]['primary_keys'] = NULL;
	}
	
	
	/**
	 * Stores any many-to-many associations or any one-to-many records that have been flagged for association
	 * 
	 * @internal
	 * 
	 * @param  string  $class             The class to store the related records for
	 * @param  array   &$values           The current values for the main record being stored
	 * @param  array   &$related_records  The related records array
	 * @param  boolean $force_cascade     This flag will be passed to the fActiveRecord::delete() method on related records that are being deleted
	 * @return void
	 */
	static public function store($class, &$values, &$related_records, $force_cascade)
	{
		$table  = fORM::tablize($class);
		$schema = fORMSchema::retrieve($class);
		
		foreach ($related_records as $related_table => $relationships) {
			foreach ($relationships as $route => $related_info) {
				if (!$related_info['associate']) {
					continue;
				}
				
				$relationship = fORMSchema::getRoute($schema, $table, $related_table, $route);
				if (isset($relationship['join_table'])) {
					fORMRelated::storeManyToMany($class, $values, $relationship, $related_info);
				} else {
					$related_class = fORM::classize($related_table);
					$related_class = fORM::getRelatedClass($class, $related_class);
					fORMRelated::storeOneToStar($class, $values, $related_records, $related_class, $route, $force_cascade);
				}
			}
		}
	}
	
	
	/**
	 * Associates a set of many-to-many related records with the current record
	 * 
	 * @internal
	 * 
	 * @param  string $class         The class the relationship is being stored for
	 * @param  array  &$values       The current values for the main record being stored
	 * @param  array  $relationship  The information about the relationship between this object and the records in the record set
	 * @param  array  $related_info  An array containing the keys `'record_set'`, `'count'`, `'primary_keys'` and `'associate'` 
	 * @return void
	 */
	static public function storeManyToMany($class, &$values, $relationship, $related_info)
	{
		$db     = fORMDatabase::retrieve($class, 'write');
		$schema = fORMSchema::retrieve($class);
		
		$column_value      = $values[$relationship['column']];
		
		// First, we remove all existing relationships between the two tables
		$join_table        = $relationship['join_table'];
		$join_column       = $relationship['join_column'];
		
		$params = array(
			"DELETE FROM %r WHERE " . fORMDatabase::makeCondition($schema, $join_table, $join_column, '=', $column_value),
			$join_table,
			$join_column,
			$column_value
		);
		call_user_func_array($db->translatedQuery, $params);
		
		// Then we add back the ones in the record set
		$join_related_column = $relationship['join_related_column'];
		
		$related_pk_columns  = $schema->getKeys($relationship['related_table'], 'primary');
		
		$related_column_values = array();
		
		// If the related column is the primary key, we can just use the primary keys if we have them
		if ($related_pk_columns[0] == $relationship['related_column'] && $related_info['primary_keys']) {
			$related_column_values = $related_info['primary_keys'];
		
		// Otherwise we need to pull the related values out of the record set
		} else {
			// If there is no record set, build it from the primary keys
			if (!$related_info['record_set']) {
				$related_class = fORM::classize($relationship['related_table']);
				$related_class = fORM::getRelatedClass($class, $related_class);
				$related_info['record_set'] = fRecordSet::build($related_class, array($related_pk_columns[0] . '=' => $related_info['primary_keys']));
			}
			
			$get_related_method_name = 'get' . fGrammar::camelize($relationship['related_column'], TRUE);
			
			foreach ($related_info['record_set'] as $record) {
				$related_column_values[] = $record->$get_related_method_name();
			}	
		}
		
		// Ensure we aren't storing duplicates
		$related_column_values = array_unique($related_column_values);
		
		$join_column_placeholder    = $schema->getColumnInfo($join_table, $join_column, 'placeholder');
		$related_column_placeholder = $schema->getColumnInfo($join_table, $join_related_column, 'placeholder');
		
		foreach ($related_column_values as $related_column_value) {
			$params = array(
				"INSERT INTO %r (%r, %r) VALUES (" . $join_column_placeholder . ", " . $related_column_placeholder . ")",
				$join_table,
				$join_column,
				$join_related_column,
				$column_value,
				$related_column_value
			);
			call_user_func_array($db->translatedQuery, $params);
		}
	}
	
	
	/**
	 * Stores a set of one-to-many related records in the database
	 * 
	 * @throws fValidationException  When one of the "many" records throws an exception from fActiveRecord::store()
	 * @internal
	 * 
	 * @param  string  $class             The class to store the related records for
	 * @param  array   &$values           The current values for the main record being stored
	 * @param  array   &$related_records  The related records array
	 * @param  string  $related_class     The related class being stored
	 * @param  string  $route             The route to the related class
	 * @param  boolean $force_cascade     This flag will be passed to the fActiveRecord::delete() method on related records that are being deleted
	 * @return void
	 */
	static public function storeOneToStar($class, &$values, &$related_records, $related_class, $route, $force_cascade)
	{
		$table         = fORM::tablize($class);
		$related_table = fORM::tablize($related_class);
		
		$schema       = fORMSchema::retrieve($class);
		$relationship = fORMSchema::getRoute($schema, $table, $related_table, $route);
		$column_value = $values[$relationship['column']];
		
		if (!empty($related_records[$related_table][$route]['record_set'])) {
			$record_set = $related_records[$related_table][$route]['record_set'];
		} else {
			$record_set = self::buildRecords($class, $values, $related_records, $related_class, $route);	
		}

		$where_conditions = array(
			$relationship['related_column'] . '=' => $column_value
		);
		
		
		$existing_records = fRecordSet::build($related_class, $where_conditions);
		
		$existing_primary_keys  = $existing_records->getPrimaryKeys();
		$new_primary_keys       = $record_set->getPrimaryKeys();
		
		$primary_keys_to_delete = self::multidimensionArrayDiff($existing_primary_keys, $new_primary_keys);
		
		foreach ($primary_keys_to_delete as $primary_key_to_delete) {
			$object_to_delete = new $related_class($primary_key_to_delete);
			$object_to_delete->delete($force_cascade);
		}
		
		$set_method_name = 'set' . fGrammar::camelize($relationship['related_column'], TRUE);
		
		$first_pk_column = self::determineFirstPKColumn($class, $related_class, $route);
		$filter          = self::determineRequestFilter(fORM::classize($relationship['table']), $related_class, $route);
		$pk_field        = $filter . $first_pk_column;
		$input_keys      = array_keys(fRequest::get($pk_field, 'array', array()));
		
		// Set all of the values first to prevent issues with recursive relationships
		foreach ($record_set as $i => $record) {
			$record->$set_method_name($column_value);	
		}
		
		foreach ($record_set as $i => $record) {
			fRequest::filter($filter, isset($input_keys[$i]) ? $input_keys[$i] : $i);
			$record->store();
			fRequest::unfilter();
		}
	}
	
	
	/**
	 * Validates any many-to-many associations or any one-to-many records that have been flagged for association
	 * 
	 * @internal
	 * 
	 * @param  string $class             The class to validate the related records for
	 * @param  array  &$values           The values for the object
	 * @param  array  &$related_records  The related records for the object
	 * @return void
	 */
	static public function validate($class, &$values, &$related_records)
	{
		$table  = fORM::tablize($class);
		$schema = fORMSchema::retrieve($class);
		
		$validation_messages = array();
		
		// Find the record sets to validate
		foreach ($related_records as $related_table => $routes) {
			foreach ($routes as $route => $related_info) {
				if (!$related_info['count'] || !$related_info['associate']) {
					continue;
				}
				
				$related_class = fORM::classize($related_table);
				$related_class = fORM::getRelatedClass($class, $related_class);
				$relationship  = fORMSchema::getRoute($schema, $table, $related_table, $route);
																												
				if (isset($relationship['join_table'])) {
					$related_messages = self::validateManyToMany($class, $related_class, $route, $related_info);
				} else {
					$related_messages = self::validateOneToStar($class, $values, $related_records, $related_class, $route);
				}
				
				$validation_messages = array_merge($validation_messages, $related_messages);
			}
		}	
		
		return $validation_messages;
	}
	
	
	/**
	 * Validates one-to-* related records
	 *
	 * @param  string $class             The class to validate the related records for
	 * @param  array  &$values           The values for the object
	 * @param  array  &$related_records  The related records for the object
	 * @param  string $related_class     The name of the class for this record set
	 * @param  string $route             The route between the table and related table
	 * @return array  An array of validation messages
	 */
	static private function validateOneToStar($class, &$values, &$related_records, $related_class, $route)
	{
		$schema              = fORMSchema::retrieve($class);
		$table               = fORM::tablize($class);
		$related_table       = fORM::tablize($related_class);
		$relationship        = fORMSchema::getRoute($schema, $table, $related_table, $route);
		
		$first_pk_column     = self::determineFirstPKColumn($class, $related_class, $route);
		$filter              = self::determineRequestFilter($class, $related_class, $route);
		$pk_field            = $filter . $first_pk_column;
		$input_keys          = array_keys(fRequest::get($pk_field, 'array', array()));
		
		$related_record_name = self::getRelatedRecordName($class, $related_class, $route);
		
		$messages = array();
		
		$one_to_one = fORMSchema::isOneToOne($schema, $table, $related_table, $route);
		if ($one_to_one) {
			$records = array(self::createRecord($class, $values, $related_records, $related_class, $route));

		} else {
			$records = self::buildRecords($class, $values, $related_records, $related_class, $route);
		}
		
		foreach ($records as $i => $record) {
			fRequest::filter($filter, isset($input_keys[$i]) ? $input_keys[$i] : $i);
			$record_messages = $record->validate(TRUE);
			
			foreach ($record_messages as $column => $record_message) {
				// Ignore validation messages about the primary key since it will be added
				if ($column == $relationship['related_column']) {
				    continue;
				}
				
				if ($one_to_one) {
					$token_field           = fValidationException::formatField('__TOKEN__');
					$extract_message_regex = '#' . str_replace('__TOKEN__', '(.*?)', preg_quote($token_field, '#')) . '(.*)$#D';
					preg_match($extract_message_regex, $record_message, $matches);
				
					$column_name = self::compose(
						'%1$s %2$s',
						$related_record_name,
						$matches[1]
					);
					
					$messages[$related_table . '::' . $column] = self::compose(
						'%1$s%2$s',
						fValidationException::formatField($column_name),
						$matches[2]
					);
					
				} else {
					$main_key = $related_table . '[' . $i . ']';
					if (!isset($messages[$main_key])) {
						if (isset(self::$validation_name_methods[$class][$related_class][$route])) {
							$name = $record->{self::$validation_name_methods[$class][$related_class][$route]}($i+1);
						} else {
							$name = $related_record_name . ' #' . ($i+1);
						}
						$messages[$main_key] = array(
							'name'   => $name,
							'errors' => array()
						);
					}
					
					$messages[$main_key]['errors'][$column] = $record_message;
				}
				
				
			}
			fRequest::unfilter();
		}
		
		return $messages;
	}
	
	
	/**
	 * Validates many-to-many related records
	 *
	 * @param  string $class          The class to validate the related records for
	 * @param  string $related_class  The name of the class for this record set
	 * @param  string $route          The route between the table and related table
	 * @param  array  $related_info   The related info to validate
	 * @return array  An array of validation messages
	 */
	static private function validateManyToMany($class, $related_class, $route, $related_info)
	{
		$related_record_name = self::getRelatedRecordName($class, $related_class, $route);
		$record_number = 1;
		
		$messages = array();
		
		$related_records = $related_info['record_set'] ? $related_info['record_set'] : $related_info['primary_keys'];
		
		foreach ($related_records as $record) {
			if ((is_object($record) && !$record->exists()) || !$record) {
				$messages[fORM::tablize($related_class)] = self::compose(
					'%1$sPlease select a %2$s',
					fValidationException::formatField(
						self::compose(
							'%1$s #%2$s',
							$related_record_name,
							$record_number
						)
					),
					$related_record_name
				);
			}
			$record_number++;
		}
		
		return $messages;
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fORMRelated
	 */
	private function __construct() { }
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