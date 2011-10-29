<?php
/**
 * Allows a column in an fActiveRecord class to be a relative sort order column
 * 
 * @copyright  Copyright (c) 2008-2010 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Dan Collins, iMarc LLC [dc-imarc] <dan@imarc.net>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fORMOrdering
 * 
 * @version    1.0.0b19
 * @changes    1.0.0b19  Updated code to work with the new fORM API [wb, 2010-08-06]
 * @changes    1.0.0b18  Changed ::configureOrderingColumn() to ensure the column specified can store negative values [wb, 2010-07-21]
 * @changes    1.0.0b17  Changed validation messages array to use column name keys [wb, 2010-05-26]
 * @changes    1.0.0b16  Updated the class to allow for multiple ordering columns per class [dc-imarc, 2010-05-10]
 * @changes    1.0.0b15  Fixed a bug with ordering columns that are part of a multi-column unique constraint [wb, 2009-11-13]
 * @changes    1.0.0b14  Fixed a bug affecting where conditions with columns that are not null but have a default value [wb, 2009-11-03]
 * @changes    1.0.0b13  Updated code for the new fORMDatabase and fORMSchema APIs [wb, 2009-10-28]
 * @changes    1.0.0b12  Changed SQL statements to use value placeholders, identifier escaping and schema support [wb, 2009-10-22]
 * @changes    1.0.0b11  Fixed another bug with deleting records in the middle of a set, added support for reordering multiple records at once [dc-imarc, 2009-07-17]
 * @changes    1.0.0b10  Fixed a bug with deleting multiple in-memory records in the same set [dc-imarc, 2009-07-15]
 * @changes    1.0.0b9   Fixed a bug with using fORM::registerInspectCallback() [wb, 2009-07-15]
 * @changes    1.0.0b8   Updated to use new fORM::registerInspectCallback() method [wb, 2009-07-13]
 * @changes    1.0.0b7   Fixed ::validate() so it properly ignores ordering columns in multi-column unique constraints [wb, 2009-06-17]
 * @changes    1.0.0b6   Updated code for new fORM API [wb, 2009-06-15]
 * @changes    1.0.0b5   Updated class to automatically correct ordering values that are too high [wb, 2009-06-14]
 * @changes    1.0.0b4   Updated code to use new fValidationException::formatField() method [wb, 2009-06-04]  
 * @changes    1.0.0b3   Fixed a bug with setting a new record to anywhere but the end of a set [wb, 2009-03-18]
 * @changes    1.0.0b2   Fixed a bug with ::inspect(), 'max_ordering_value' was being returned as 'max_ordering_index' [wb, 2009-03-02]
 * @changes    1.0.0b    The initial implementation [wb, 2008-06-25]
 */
class fORMOrdering
{
	// The following constants allow for nice looking callbacks to static methods
	const configureOrderingColumn = 'fORMOrdering::configureOrderingColumn';
	const delete                  = 'fORMOrdering::delete';
	const inspect                 = 'fORMOrdering::inspect';
	const reflect                 = 'fORMOrdering::reflect';
	const reorder                 = 'fORMOrdering::reorder';
	const reset                   = 'fORMOrdering::reset';
	const validate                = 'fORMOrdering::validate';
	
	
	/**
	 * The columns configured as ordering columns
	 * 
	 * @var array
	 */
	static private $ordering_columns = array();
	
	
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
	 * Sets a column to be an ordering column
	 * 
	 * There can only be one ordering column per class/table and it must be
	 * part of a single or multi-column `UNIQUE` constraint.
	 * 
	 * @param  mixed  $class   The class name or instance of the class
	 * @param  string $column  The column to set as an ordering column
	 * @return void
	 */
	static public function configureOrderingColumn($class, $column)
	{
		$class       = fORM::getClass($class);
		$table       = fORM::tablize($class);
		$schema      = fORMSchema::retrieve($class);
		$info        = $schema->getColumnInfo($table, $column);
		$unique_keys = $schema->getKeys($table, 'unique');
		
		if ($info['type'] != 'integer') {
			throw new fProgrammerException(
				'The column specified, %1$s, is a %2$s column. It must be an integer column to be set as an ordering column.',
				$column,
				$data_type
			);
		}
		
		if ($info['min_value'] && $info['min_value']->eq(0)) {
			throw new fProgrammerException(
				'The column specified, %1$s, does not allow for negative values. Please adjust the data type to an integer type that allows for negative values.',
				$column
			);
		}
		
		$found = FALSE;
		foreach ($unique_keys as $unique_key) {
			settype($unique_key, 'array');
			if (in_array($column, $unique_key)) {
				$other_columns = array_diff($unique_key, array($column));
				$found = TRUE;
				break;
			}
		}
		
		if (!$found) {
			throw new fProgrammerException(
				'The column specified, %s, does not appear to be part of a unique key. It must be part of a unique key to be set as an ordering column.',
				$column
			);
		}
		
		if (!fORM::checkHookCallback($class, 'post::validate()', self::validate)) {
			fORM::registerHookCallback($class, 'post::validate()', self::validate);
		}
		if (!fORM::checkHookCallback($class, 'post-validate::store()', self::reorder)) {
			fORM::registerHookCallback($class, 'post-validate::store()', self::reorder);
		}
		if (!fORM::checkHookCallback($class, 'pre-commit::delete()', self::delete)) {
			fORM::registerHookCallback($class, 'pre-commit::delete()', self::delete);
		}
		
		fORM::registerReflectCallback($class, self::reflect);
		
		fORM::registerActiveRecordMethod($class, 'inspect' . fGrammar::camelize($column, TRUE), self::inspect);
		
		self::$ordering_columns[$class][$column] = $other_columns;
	}
	
	
	/**
	 * Add params for a `WHERE` clause for the //old// multi-column set a record was part of
	 * 
	 * @param  fSchema $schema         The schema of the database the query will be executed on
	 * @param  array   $params         The params for the fDatabase::query() call
	 * @param  string  $table          The table the `WHERE` clause is for
	 * @param  array   $other_columns  The other columns in the multi-column unique constraint
	 * @param  array   &$values        The record's current values
	 * @param  array   &$old_values    The record's old values
	 * @return array  The updated params for fDatabase::query()
	 */
	static private function addOldOtherFieldsWhereParams($schema, $params, $table, $other_columns, &$values, &$old_values)
	{
		$column_info = $schema->getColumnInfo($table);
		
		$conditions = array();
		foreach ($other_columns as $other_column) {
			$value = fActiveRecord::retrieveOld($old_values, $other_column, $values[$other_column]);
			
			// This makes sure the query performs the way an insert will
			if ($value === NULL && $column_info[$other_column]['not_null'] && $column_info[$other_column]['default'] !== NULL) {
				$value = $column_info[$other_column]['default'];
			}
			
			$params[] = $table . '.' . $other_column;
			$params[] = $value;
			
			$conditions[] = fORMDatabase::makeCondition($schema, $table, $other_column, '=', $value);
		}
		
		$params[0] .= join(' AND ', $conditions);
		
		return $params;
	}
	
	
	/**
	 * Adds params for a `WHERE` clause to ensure a database call is only selecting from rows that are part of the same set when an ordering field is in multi-column `UNIQUE` constraint.
	 * 
	 * @param  fSchema $schema         The schema of the database the query will be executed on
	 * @param  array   $params         The parameters for the fDatabase::query() call
	 * @param  string  $table          The table the `WHERE` clause is for
	 * @param  array   $other_columns  The other columns in the multi-column unique constraint
	 * @param  array   &$values        The values to match with
	 * @return array  The updated params for fDatabase::query()
	 */
	static private function addOtherFieldsWhereParams($schema, $params, $table, $other_columns, &$values)
	{
		$column_info = $schema->getColumnInfo($table);
		
		$conditions = array();
		foreach ($other_columns as $other_column) {
			$value = $values[$other_column];
			
			// This makes sure the query performs the way an insert will
			if ($value === NULL && $column_info[$other_column]['not_null'] && $column_info[$other_column]['default'] !== NULL) {
				$value = $column_info[$other_column]['default'];
			}
			
			$params[] = $table . '.' . $other_column;
			$params[] = $value;
			
			$conditions[] = fORMDatabase::makeCondition($schema, $table, $other_column, '=', $value);
		}
		
		$params[0] .= join(' AND ', $conditions);
		
		return $params;
	}
	
	
	/**
	 * Re-orders other records in the set when the record specified is deleted
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @return void
	 */
	static public function delete($object, &$values, &$old_values, &$related_records, &$cache)
	{
		$class = get_class($object);
		$table = fORM::tablize($class);
		
		$db     = fORMDatabase::retrieve($class, 'write');
		$schema = fORMSchema::retrieve($class);
		
		foreach (self::$ordering_columns[$class] as $column => $other_columns) {
		
			$current_value = $values[$column];
			$old_value     = fActiveRecord::retrieveOld($old_values, $column, $current_value);
			
			// Figure out the range we are dealing with
			$params = array("SELECT MAX(%r) FROM %r", $column, $table);
			if ($other_columns) {
				$params[0] .= " WHERE ";
				$params     = self::addOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values);
			}
			
			$current_max_value = (integer) call_user_func_array($db->translatedQuery, $params)->fetchScalar();
			
			$shift_down = $current_max_value + 10;
			$shift_up   = $current_max_value + 9;
			
			$params = array(
				"SELECT %r FROM %r LEFT JOIN %r t2 ON %r = t2.%r + 1",
				$table . '.' . $column,
				$table,
				$table,
				$table . '.' . $column,
				$column
			);
			
			foreach ($other_columns as $other_column) {
				$params[0] .= " AND %r = t2.%r";
				$params[] = $table . '.' . $other_column;
				$params[] = $other_column;	
			} 
			
			$params[0] .= " WHERE t2.%r IS NULL AND %r != 1";
			$params[] = $column;
			$params[] = $table . '.' . $column;
			
			if ($other_columns) {
				$params[0] .= " AND ";
				$params = self::addOldOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values, $old_values);
			}
			
			$res = call_user_func_array($db->translatedQuery, $params);
			
			if (!$res->countReturnedRows()) {
				continue;		
			}
			
			$old_value = $res->fetchScalar() - 1;
			
			// Close the gap for all records after this one in the set
			$params = array(
				'UPDATE %r SET %r = %r - %i WHERE %r > %i',
				$table,
				$column,
				$column,
				$shift_down,
				$column,
				$old_value
			);
			if ($other_columns) {
				$params[0] .= " AND ";
				$params = self::addOldOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values, $old_values);
			}
			
			call_user_func_array($db->translatedQuery, $params);
			
			// Close the gap for all records after this one in the set
			$params = array(
				'UPDATE %r SET %r = %r + %i WHERE %r < 0',
				$table,
				$column,
				$column,
				$shift_up,
				$column
			);
			if ($other_columns) {
				$params[0] .= " AND ";
				$params = self::addOldOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values, $old_values);
			}
			
			call_user_func_array($db->translatedQuery, $params);
		}
	}
	
	/**
	 * Returns the metadata about a column including features added by this class
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance 
	 * @param  array         &$values           The current values 
	 * @param  array         &$old_values       The old values 
	 * @param  array         &$related_records  Any records related to this record 
	 * @param  array         &$cache            The cache array for the record 
	 * @param  string        $method_name       The method that was called 
	 * @param  array         $parameters        The parameters passed to the method 
	 * @return mixed  The metadata array or element specified 
	 */ 
	static public function inspect($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters) 
	{ 
		list ($action, $subject) = fORM::parseMethod($method_name); 
		
		$column = fGrammar::underscorize($subject);
		$class  = get_class($object);
		$table  = fORM::tablize($class);
		
		$db     = fORMDatabase::retrieve($class, 'read');
		$schema = fORMSchema::retrieve($class);
		
		$info       = $schema->getColumnInfo($table, $column); 
		$element    = (isset($parameters[0])) ? $parameters[0] : NULL;
		
		$other_columns = self::$ordering_columns[$class][$column];
		
		// Retrieve the current max ordering index from the database
		$params = array("SELECT MAX(%r) FROM %r", $column, $table);
		if ($other_columns) {
			$params[0] .= " WHERE ";
			$params = self::addOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values);
		}
		$max_value = (integer) call_user_func_array($db->translatedQuery, $params)->fetchScalar();
		
		// If this is a new record, or in a new set, we need one more space in the ordering index
		if (self::isInNewSet($column, $other_columns, $values, $old_values)) {
			$max_value += 1;
		}
		
		$info['max_ordering_value'] = $max_value; 
		$info['feature']            = 'ordering'; 
		
		fORM::callInspectCallbacks($class, $column, $info);
		
		if ($element) { 
			return (isset($info[$element])) ? $info[$element] : NULL; 
		} 
		 
		return $info;
	}
	
	
	/**
	 * Checks to see if the values specified are part of a record that is new to its order set
	 * 
	 * @param  string $ordering_column  The column being ordered by
	 * @param  array  $other_columns    The other columns in the multi-column unique constraint
	 * @param  array  &$values          The values of the record
	 * @param  array  &$old_values      The old values of the record
	 * @return boolean  If the record is part of a new ordering set
	 */
	static private function isInNewSet($ordering_column, $other_columns, &$values, &$old_values)
	{
		$value_empty      = !$values[$ordering_column];
		$old_value_empty  = !fActiveRecord::retrieveOld($old_values, $ordering_column, TRUE);
		$no_old_value_set = !fActiveRecord::hasOld($old_values, $ordering_column);
		
		// If the value appears to be new, the record must be new to the order
		if ($old_value_empty || ($value_empty && $no_old_value_set)) {
			return TRUE;
		}
		
		// If there aren't any other columns to check, there is
		// only a single order, so it must have already existed
		if (!$other_columns) {
			return FALSE;
		}
		
		// Check through each of the other columns to see if the set could have
		// changed because of a new value in one of those columns
		foreach ($other_columns as $other_column) {
			if (fActiveRecord::changed($values, $old_values, $other_column)) {
				return TRUE;
			}
		}
		
		// If none of the multi-column values changed, the record must be part
		// of the same set it was
		return FALSE;
	}
	
	
	/**
	 * Adjusts the fActiveRecord::reflect() signatures of columns that have been configured in this class
	 * 
	 * @internal
	 * 
	 * @param  string  $class                 The class to reflect
	 * @param  array   &$signatures           The associative array of `{method name} => {signature}`
	 * @param  boolean $include_doc_comments  If doc comments should be included with the signature
	 * @return void
	 */
	static public function reflect($class, &$signatures, $include_doc_comments)
	{
		if (!isset(self::$ordering_columns[$class])) {
			return;
		}
		
		foreach(self::$ordering_columns[$class] as $column => $enabled) {
			$camelized_column = fGrammar::camelize($column, TRUE);
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Returns metadata about " . $column . "\n";
				$signature .= " * \n";
				$signature .= " * @param  string \$element  The element to return. Must be one of: 'type', 'not_null', 'default', 'feature', 'max_ordering_value'.\n";
				$signature .= " * @return mixed  The metadata array or a single element\n";
				$signature .= " */\n";
			}
			$inspect_method = 'inspect' . $camelized_column;
			$signature .= 'public function ' . $inspect_method . '($element=NULL)';
			
			$signatures[$inspect_method] = $signature;
		}
	}
	
	
	/**
	 * Re-orders the object based on it's current state and new position
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @return void
	 */
	static public function reorder($object, &$values, &$old_values, &$related_records, &$cache)
	{
		$class = get_class($object);
		$table = fORM::tablize($class);
		
		$db     = fORMDatabase::retrieve($class, 'write');
		$schema = fORMSchema::retrieve($class);
		
		foreach (self::$ordering_columns[$class] as $column => $other_columns) {
			
			$current_value = $values[$column];
			if (!$object->exists()) {
				$old_value = fActiveRecord::retrieveOld($old_values, $column);
			} else {
				$params = array("SELECT %r FROM %r WHERE ", $column, $table);
				$params = fORMDatabase::addPrimaryKeyWhereParams($schema, $params, $table, $table, $values, $old_values);
				$old_value = call_user_func_array($db->translatedQuery, $params)->fetchScalar();	
			}
			
			// Figure out the range we are dealing with
			$params = array("SELECT MAX(%r) FROM %r", $column, $table);
			if ($other_columns) {
				$params[0] .= ' WHERE ';
				$params = self::addOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values);
			}
			
			$current_max_value = (integer) call_user_func_array($db->translatedQuery, $params)->fetchScalar();
			$new_max_value     = $current_max_value;
			
			if ($new_set = self::isInNewSet($column, $other_columns, $values, $old_values)) {
				$new_max_value = $current_max_value + 1;
			}
			
			$changed = FALSE;
			
			// If a blank value was set, correct it to the old value (if there
			// was one), or a new value at the end of the set
			if ($current_value === '' || $current_value === NULL) {
				if ($old_value) {
					$current_value = $old_value;
				} else {
					$current_value = $new_max_value;
				}
				$changed = TRUE;
			}
			
			// When we move an object into a new set and the value didn't change then move it to the end of the new set
			if ($new_set && $object->exists() && ($old_value === NULL || $old_value == $current_value)) {
				$current_value = $new_max_value;
				$changed = TRUE;		
			}
			
			// If the value is too high, then set it to the last value
			if ($current_value > $new_max_value) {
				$current_value = $new_max_value;
				$changed = TRUE;
			}
			
			if ($changed) {
				fActiveRecord::assign($values, $old_values, $column, $current_value);
			}
			
			// If the value didn't change, we can exit
			$value_didnt_change = ($old_value && $current_value == $old_value) || !$old_value;
			if (!$new_set && $value_didnt_change) {
				continue;
			}
			
			// If we are entering a new record at the end of the set we don't need to shuffle anything either
			if (!$object->exists() && $new_set && $current_value == $new_max_value) {
				continue;
			}
			
			
			// If the object already exists in the database, grab the ordering value
			// right now in case some other object reordered it since it was loaded
			if ($object->exists()) {
				$params = array("SELECT %r FROM %r WHERE ", $column, $table);
				$params = fORMDatabase::addPrimaryKeyWhereParams($schema, $params, $table, $table, $values, $old_values);
				$db_value = (integer) call_user_func_array($db->translatedQuery, $params)->fetchScalar();
			}
			
			
			// We only need to move things in the new set around if we are inserting into the middle
			// of a new set, or if we are moving around in the current set
			if (!$new_set || ($new_set && $current_value != $new_max_value)) {
				$shift_down = $new_max_value + 10;
				
				// To prevent issues with the unique constraint, we move everything below 0
				$params = array(
					"UPDATE %r SET %r = %r - %i WHERE ",
					$table,
					$column,
					$column,
					$shift_down
				);
				$conditions = array();
				
				// If we are moving into the middle of a new set we just push everything up one value
				if ($new_set) {
					$shift_up     = $new_max_value + 11;
					$conditions[] = fORMDatabase::makeCondition($schema, $table, $column, '>=', $current_value);
					$params[]     = $table . '.' . $column;
					$params[]     = $current_value;
					
				// If we are moving a value down in a set, we push values in the difference zone up one
				} elseif ($current_value < $db_value) {
					$shift_up     = $new_max_value + 11;
					$conditions[] = fORMDatabase::makeCondition($schema, $table, $column, '<', $db_value);
					$params[]     = $table . '.' . $column;
					$params[]     = $db_value;
					$conditions[] = fORMDatabase::makeCondition($schema, $table, $column, '>=', $current_value);
					$params[]     = $table . '.' . $column;
					$params[]     = $current_value;
						
				// If we are moving a value up in a set, we push values in the difference zone down one
				} else {
					$shift_up     = $new_max_value + 9;
					$conditions[] = fORMDatabase::makeCondition($schema, $table, $column, '>', $db_value);
					$params[]     = $table . '.' . $column;
					$params[]     = $db_value;
					$conditions[] = fORMDatabase::makeCondition($schema, $table, $column, '<=', $current_value);
					$params[]     = $table . '.' . $column;
					$params[]     = $current_value;
				}
				
				$params[0] .= join(' AND ', $conditions);
				if ($other_columns) {
					$params[0] .= " AND ";
					$params = self::addOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values);
				}
				call_user_func_array($db->translatedQuery, $params);
				
				if ($object->exists()) {
					// Put the actual record we are changing in limbo to be updated when the actual update happens
					$params = array(
						"UPDATE %r SET %r = 0 WHERE %r = %i",
						$table,
						$column,
						$column,
						$db_value
					);
					if ($other_columns) {
						$params[0] .= " AND ";
						$params = self::addOldOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values, $old_values);
					}
					call_user_func_array($db->translatedQuery, $params);
				}
				
				// Anything below zero needs to be moved back up into its new position
				$params = array(
					"UPDATE %r SET %r = %r + %i WHERE %r < 0",
					$table,
					$column,
					$column,
					$shift_up,
					$column
				);
				if ($other_columns) {
					$params[0] .= " AND ";
					$params = self::addOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values);
				}
				call_user_func_array($db->translatedQuery, $params);
			}
			
			
			// If there was an old set, we need to close the gap
			if ($object->exists() && $new_set) {
				
				$params = array("SELECT MAX(%r) FROM %r WHERE ", $column, $table);
				$params = self::addOldOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values, $old_values);
				
				$old_set_max = (integer) call_user_func_array($db->translatedQuery, $params)->fetchScalar();
				
				// We only need to close the gap if the record was not at the end
				if ($db_value < $old_set_max) {
					$shift_down = $old_set_max + 10;
					$shift_up   = $old_set_max + 9;
					
					// To prevent issues with the unique constraint, we move everything below 0 and then back up above
					
					$params = array(
						"UPDATE %r SET %r = %r - %i WHERE %r > %i AND ",
						$table,
						$column,
						$column,
						$shift_down,
						$column,
						$db_value
					);
					$params = self::addOldOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values, $old_values);
					call_user_func_array($db->translatedQuery, $params);
					
					if ($current_value == $new_max_value) {
						// Put the actual record we are changing in limbo to be updated when the actual update happens
						$params = array(
							"UPDATE %r SET %r = 0 WHERE %r = %i",
							$table,
							$column,
							$column,
							$db_value
						);
						if ($other_columns) {
							$params[0] .= " AND ";
							$params = self::addOldOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values, $old_values);
						}
						call_user_func_array($db->translatedQuery, $params);
					}
					
					$params = array(
						"UPDATE %r SET %r = %r + %i WHERE %r < 0 AND ",
						$table,
						$column,
						$column,
						$shift_up,
						$column
					);
					$params = self::addOldOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values, $old_values);
					call_user_func_array($db->translatedQuery, $params);
				}
			}
		}
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
		self::$ordering_columns = array();
	}
	
	
	/**
	 * Makes sure the ordering value is sane, removes error messages about missing values
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object                The fActiveRecord instance
	 * @param  array         &$values               The current values
	 * @param  array         &$old_values           The old values
	 * @param  array         &$related_records      Any records related to this record
	 * @param  array         &$cache                The cache array for the record
	 * @param  array         &$validation_messages  An array of ordered validation messages
	 * @return void
	 */
	static public function validate($object, &$values, &$old_values, &$related_records, &$cache, &$validation_messages)
	{
		$class = get_class($object);
		$table = fORM::tablize($class);
		
		$db     = fORMDatabase::retrieve($class, 'read');
		$schema = fORMSchema::retrieve($class);
		
		foreach (self::$ordering_columns[$class] as $column => $other_columns) {
		
			$current_value = $values[$column];
			$old_value     = fActiveRecord::retrieveOld($old_values, $column);
			
			$params = array("SELECT MAX(%r) FROM %r", $column, $table);
			if ($other_columns) {
				$params[0] .= " WHERE ";
				$params = self::addOtherFieldsWhereParams($schema, $params, $table, $other_columns, $values);
			}
			
			$current_max_value = (integer) call_user_func_array($db->translatedQuery, $params)->fetchScalar();
			$new_max_value     = $current_max_value;
			
			if ($new_set = self::isInNewSet($column, $other_columns, $values, $old_values)) {
				$new_max_value     = $current_max_value + 1;
				$new_set_new_value = fActiveRecord::changed($values, $old_values, $column);
			}
			
			$column_name = fORM::getColumnName($class, $column);
			
			// Remove any previous validation warnings
			$filtered_messages = array();
			foreach ($validation_messages as $validation_column => $validation_message) {
				if (!preg_match('#(^|,)' . preg_quote($column, '#') . '(,|$)#D', $validation_column)) {
					$filtered_messages[$validation_column] = $validation_message;
				}
			}
			$validation_messages = $filtered_messages;
			
			// If we have a completely empty value, we don't need to validate since a valid value will be generated
			if ($current_value === '' || $current_value === NULL) {
				continue;
			}
			
			if (!is_numeric($current_value) || strlen((int) $current_value) != strlen($current_value)) {
				$validation_messages[$column] = self::compose('%sPlease enter an integer', fValidationException::formatField($column_name));
			
			} elseif ($current_value < 1) {
				$validation_messages[$column] = self::compose('%sThe value can not be less than 1', fValidationException::formatField($column_name));
				
			}
		}
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fORMOrdering
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2008-2010 Will Bond <will@flourishlib.com>, others
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