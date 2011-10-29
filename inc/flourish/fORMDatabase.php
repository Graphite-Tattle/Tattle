<?php
/**
 * Holds a single instance of the fDatabase class and provides database manipulation functionality for ORM code
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Craig Ruksznis, iMarc LLC [cr-imarc] <craigruk@imarc.net>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fORMDatabase
 * 
 * @version    1.0.0b32
 * @changes    1.0.0b32  Added support to ::addWhereClause() for the `^~` and `$~` operators [wb, 2011-06-20]
 * @changes    1.0.0b31  Fixed a bug with ::addWhereClause() generating invalid SQL [wb, 2011-05-10]
 * @changes    1.0.0b30  Fixed ::insertFromAndGroupByClauses() to insert `MAX()` around columns in related tables in the `ORDER BY` clause when a `GROUP BY` is used [wb, 2011-02-03]
 * @changes    1.0.0b29  Added code to handle old PCRE engines that don't support unicode character properties [wb, 2010-12-06]
 * @changes    1.0.0b28  Fixed a bug in the fProgrammerException that is thrown when an improperly formatted OR condition is provided [wb, 2010-11-24]
 * @changes    1.0.0b27  Fixed ::addWhereClause() to ignore fuzzy search clauses with no values to match [wb, 2010-10-19]
 * @changes    1.0.0b26  Fixed ::insertFromAndGroupByClauses() to handle SQL where a table is references in more than one capitalization [wb, 2010-07-26]
 * @changes    1.0.0b25  Fixed ::insertFromAndGroupByClauses() to properly handle recursive relationships [wb, 2010-07-22]
 * @changes    1.0.0b24  Fixed ::parseSearchTerms() to work with non-ascii terms [wb, 2010-06-30]
 * @changes    1.0.0b23  Fixed error messages in ::retrieve() [wb, 2010-04-23]
 * @changes    1.0.0b22  Added support for IBM DB2, fixed an issue with building record sets or records that have recursive relationships [wb, 2010-04-13]
 * @changes    1.0.0b21  Changed ::injectFromAndGroupByClauses() to be able to handle table aliases that contain other aliases inside of them [wb, 2010-03-03]
 * @changes    1.0.0b20  Fixed a bug where joining to a table two separate ways could cause table alias issues and incorrect SQL to be generated [wb, 2009-12-16]
 * @changes    1.0.0b19  Added the ability to compare columns with the `=:`, `!:`, `<:`, `<=:`, `>:` and `>=:` operators [wb, 2009-12-08]
 * @changes    1.0.0b18  Fixed a bug affecting where conditions with columns that are not null but have a default value [wb, 2009-11-03]
 * @changes    1.0.0b17  Added support for multiple databases [wb, 2009-10-28]
 * @changes    1.0.0b16  Internal Backwards Compatibility Break - Renamed methods and significantly changed parameters and functionality for SQL statements to use value placeholders, identifier escaping and to handle schemas [wb, 2009-10-22]
 * @changes    1.0.0b15  Streamlined intersection operator SQL and added support for the second value being NULL [wb, 2009-09-21]
 * @changes    1.0.0b14  Added support for the intersection operator `><` to ::createWhereClause() [wb, 2009-07-13]
 * @changes    1.0.0b13  Added support for the `AND LIKE` operator `&~` to ::createWhereClause() [wb, 2009-07-09]
 * @changes    1.0.0b12  Added support for the `NOT LIKE` operator `!~` to ::createWhereClause() [wb, 2009-07-08]
 * @changes    1.0.0b11  Added support for concatenated columns to ::escapeBySchema() [cr-imarc, 2009-06-19]
 * @changes    1.0.0b10  Updated ::createWhereClause() to properly handle NULLs for arrays of values when doing = and != comparisons [wb, 2009-06-17]
 * @changes    1.0.0b9   Changed replacement values in preg_replace() calls to be properly escaped [wb, 2009-06-11]
 * @changes    1.0.0b8   Fixed a bug with ::creatingWhereClause() where a null value would not be escaped property [wb, 2009-05-12]
 * @changes    1.0.0b7   Fixed a bug where an OR condition in ::createWhereClause() could not have one of the values be an array [wb, 2009-04-22]
 * @changes    1.0.0b6   ::insertFromAndGroupByClauses() will no longer wrap ungrouped columns if in a CAST or CASE statement for ORDER BY clauses of queries with a GROUP BY clause [wb, 2009-03-23]
 * @changes    1.0.0b5   Fixed ::parseSearchTerms() to include stop words when they are the only thing in the search string [wb, 2008-12-31]
 * @changes    1.0.0b4   Fixed a bug where loading a related record in the same table through a one-to-many relationship caused recursion [wb, 2008-12-24]
 * @changes    1.0.0b3   Fixed a bug from 1.0.0b2 [wb, 2008-12-05]
 * @changes    1.0.0b2   Added support for != and <> to ::createWhereClause() and ::createHavingClause() [wb, 2008-12-04]
 * @changes    1.0.0b    The initial implementation [wb, 2007-08-04]
 */
class fORMDatabase
{
	// The following constants allow for nice looking callbacks to static methods
	const addHavingClause             = 'fORMDatabase::addHavingClause';
	const addOrderByClause            = 'fORMDatabase::addOrderByClause';
	const addPrimaryKeyWhereParams    = 'fORMDatabase::addPrimaryKeyWhereParams';
	const addWhereClause              = 'fORMDatabase::addWhereClause';
	const attach                      = 'fORMDatabase::attach';
	const injectFromAndGroupByClauses = 'fORMDatabase::injectFromAndGroupByClauses';
	const makeCondition               = 'fORMDatabase::makeCondition';
	const parseSearchTerms            = 'fORMDatabase::parseSearchTerms';
	const reset                       = 'fORMDatabase::reset';
	const retrieve                    = 'fORMDatabase::retrieve';
	const splitHavingConditions       = 'fORMDatabase::splitHavingConditions';
	
	
	/**
	 * An array of fDatabase objects
	 * 
	 * @var array
	 */
	static private $database_objects = array();
	
	/**
	 * If the PCRE engine supports unicode character properties
	 * 
	 * @var boolean
	 */
	static private $pcre_supports_unicode_character_properties = NULL;
	
	
	/**
	 * Allows attaching an fDatabase-compatible objects for by ORM code
	 * 
	 * If a `$name` other than `default` is used, any fActiveRecord classes
	 * that should use it will need to be configured by passing the class name
	 * and `$name` to ::mapClassToDatabase(). The `$name` parameter should be
	 * unique per database or database master/slave setup.
	 * 
	 * The `$role` is used by code to allow for master/slave database setups.
	 * There can only be one database object attached for either of the roles,
	 * `'read'` or `'write'`. If the role `'both'` is specified, it will
	 * be applied to both the `'read'` and `'write'` roles. Any sort of logic
	 * for picking one out of multiple databases should be done before this
	 * method is called.
	 * 
	 * @param  fDatabase $database  An object that is compatible with fDatabase
	 * @param  string    $name      The name for the database instance
	 * @param  string    $role      If the database should be used for `'read'`, `'write'` or `'both'` operations
	 * @return void
	 */
	static public function attach($database, $name='default', $role='both')
	{
		$valid_roles = array('both', 'write', 'read');
		if (!in_array($role, $valid_roles)) {
			throw new fProgrammerException(
				'The role specified, %1$s, is invalid. Must be one of: %2$s.',
				$role,
				join(', ', $valid_roles)
			);
		}
		
		if (!isset(self::$database_objects[$name])) {
			self::$database_objects[$name] = array();
		}
		
		settype($role, 'array');
		if ($role == array('both')) {
			$role = array('write', 'read');
		}
		
		foreach ($role as $_role) {
			self::$database_objects[$name][$_role] = $database;
		}
	}
	
	
	/**
	 * Translated the where condition for a single column into a SQL clause
	 * 
	 * @param  fDatabase $db              The database the query will be run on
	 * @param  fSchema   $schema          The schema for the database
	 * @param  array     $params          The parameters for the fDatabase::query() call
	 * @param  string    $table           The table to create the condition for
	 * @param  string    $column          The column to store the value in, may also be shorthand column name like `table.column` or `table=>related_table.column`
	 * @param  string    $operator        Should be `'='`, `'!='`, `'!'`, `'<>'`, `'<'`, `'<='`, `'>'`, `'>='`, `'IN'`, `'NOT IN'`
	 * @param  mixed     $values          The value(s) to escape
	 * @param  string    $escaped_column  The escaped column to use in the SQL
	 * @param  string    $placeholder     This allows overriding the placeholder
	 * @return array  The modified parameters
	 */
	static private function addColumnCondition($db, $schema, $params, $table, $column, $operator, $values, $escaped_column=NULL, $placeholder=NULL)
	{
		// Some objects when cast to an array turn the members into array keys
		if (!is_object($values)) {
			settype($values, 'array');
		} else {
			$values = array($values);	
		}
		// Make sure we have an array with something in it to compare to
		if (!$values) { $values = array(NULL); }
		
		// If the table and column specified are real and not some combination
		$real_column = $escaped_column === NULL && $placeholder === NULL;
		
		if ($escaped_column === NULL) {
			$escaped_column = $db->escape('%r', (strpos($column, '.') === FALSE) ? $table . '.' . $column : $column);
		}
		
		list($table, $column) = self::getTableAndColumn($schema, $table, $column);
								 
		if ($placeholder === NULL && !in_array($operator, array('=:', '!=:', '<:', '<=:', '>:', '>=:'))) {
			$placeholder = $schema->getColumnInfo($table, $column, 'placeholder');
		}
		
		// More than one value
		if (sizeof($values) > 1) {
			switch ($operator) {
				case '=':
					$non_null_values = array();
					$has_null  = FALSE;
					foreach ($values as $value) {
						if ($value === NULL) {
							$has_null = TRUE;
							continue;	
						}
						$non_null_values[] = $value;
					}
					if ($has_null) {
						$params[0] .= '(' . $escaped_column . ' IS NULL OR ';	
					}
					$params[0] .= $escaped_column . ' IN (' . $placeholder . ')';
					$params[]   = $non_null_values;
					if ($has_null) {
						$params[0] .= ')';	
					}
					break;
					
				case '!':
					$non_null_values = array();
					$has_null  = FALSE;
					foreach ($values as $value) {
						if ($value === NULL) {
							$has_null = TRUE;
							continue;	
						}
						$non_null_values[] = $value;
					}
					if ($has_null) {
						$params[0] .= '(' . $escaped_column . ' IS NOT NULL AND ';		
					}
					$params[0] .= $escaped_column . ' NOT IN (' . $placeholder . ')';
					$params[]   = $non_null_values;
					if ($has_null) {
						$params[0] .= ')';	
					}
					break;
					
				case '~':
					$condition = array();
					foreach ($values as $value) {
						$condition[] = $escaped_column . ' LIKE %s';
						$params[] = '%' . $value . '%';
					}
					$params[0] .= '(' . join(' OR ', $condition) . ')';
					break;

				case '^~':
					$condition = array();
					foreach ($values as $value) {
						$condition[] = $escaped_column . ' LIKE %s';
						$params[] = $value . '%';
					}
					$params[0] .= '(' . join(' OR ', $condition) . ')';
					break;
				
				case '$~':
					$condition = array();
					foreach ($values as $value) {
						$condition[] = $escaped_column . ' LIKE %s';
						$params[] = '%' . $value;
					}
					$params[0] .= '(' . join(' OR ', $condition) . ')';
					break;
				
				case '&~':
					$condition = array();
					foreach ($values as $value) {
						$condition[] = $escaped_column . ' LIKE %s';
						$params[] = '%' . $value . '%';
					}
					$params[0] .= '(' . join(' AND ', $condition) . ')';
					break;
				
				case '!~':
					$condition = array();
					foreach ($values as $value) {
						$condition[] = $escaped_column . ' NOT LIKE %s';
						$params[] = '%' . $value . '%';
					}
					$params[0] .= '(' . join(' AND ', $condition) . ')';
					break;
					
				default:
					throw new fProgrammerException(
						'An invalid array comparison operator, %s, was specified for an array of values',
						$operator
					);
					break;
			}
			
		// A single value
		} else {
			if ($values === array()) {
				$value = NULL;
			} else {
				$value = current($values);
			}
								  
			switch ($operator) {
				case '!:':
					$operator = '<>:';
				case '=:':
				case '<:':
				case '<=:':
				case '>:':
				case '>=:':
					$params[0] .= $escaped_column . ' ';
					$params[0] .= substr($operator, 0, -1) . ' ';
					
					// If the column to match is a function, split the function
					// name off so we can escape the column name
					$prefix = '';
					$suffix = '';
					if (preg_match('#^([^(]+\()\s*([^\s]+)\s*(\))$#D', $value, $parts)) {
						 $prefix = $parts[1];
						 $value  = $parts[2];
						 $suffix = $parts[3];
					}
					
					$params[0] .= $prefix . $db->escape('%r', (strpos($value, '.') === FALSE) ? $table . '.' . $value : $value) . $suffix;
					break;
				
				case '=':
					if ($value === NULL) {
						$operator = 'IS';	
					}
					$params[0] .= $escaped_column . ' ' . $operator . ' ' . $placeholder;
					$params[] = $value;
					break;
					
				case '!':
					$operator = '<>';
					if ($value !== NULL) {
						$params[0] .= '(';
					} else {
						$operator = 'IS NOT';	
					}
					$params[0] .= $escaped_column . ' ' . $operator . ' ' . $placeholder;
					$params[] = $value;
					if ($value !== NULL) {
						$params[0] .= ' OR ' . $escaped_column . ' IS NULL)';	
					}
					break;
					
				case '<':
				case '<=':
				case '>':
				case '>=':
					$params[0] .= $escaped_column . ' ' . $operator . ' ' . $placeholder;
					$params[]   = $value;
					break;
					
				case '~':
					$params[0] .= $escaped_column . ' LIKE %s';
					$params[]   = '%' . $value . '%';
					break;
				
				case '^~':
					$params[0] .= $escaped_column . ' LIKE %s';
					$params[]   = $value . '%';
					break;
				
				case '$~':
					$params[0] .= $escaped_column . ' LIKE %s';
					$params[]   = '%' . $value;
					break;
				
				case '!~':
					$params[0] .= $escaped_column . ' NOT LIKE %s';
					$params[]   = '%' . $value . '%';
					break;
					
				default:
					throw new fProgrammerException(
						'An invalid comparison operator, %s, was specified for a single value',
						$operator
					);
					break;
			}
		}
		
		return $params;	
	}
	
	
	/**
	 * Creates a `HAVING` clause from an array of conditions
	 * 
	 * @internal
	 *                                             
	 * @param  fDatabase $db          The database the query will be executed on
	 * @param  fSchema   $schema      The schema for the database  
	 * @param  array     $params      The params for the fDatabase::query() call
	 * @param  string    $table       The table the query is being executed on
	 * @param  array     $conditions  The array of conditions - see fRecordSet::build() for format
	 * @return array  The params with the `HAVING` clause added
	 */
	static public function addHavingClause($db, $schema, $params, $table, $conditions)
	{
		$i = 0;
		foreach ($conditions as $expression => $value) {
			if ($i) {
				$params[0] .= ' AND ';	
			}
			
			// Splits the operator off of the end of the expression
			if (in_array(substr($expression, -3), array('!=:', '>=:', '<=:', '<>:'))) {
				$operator = strtr(
					substr($expression, -3),
					array('<>:' => '!:', '!=:' => '!:')
				);
				$expression = substr($expression, 0, -3);
			} elseif (in_array(substr($expression, -2), array('<=', '>=', '!=', '<>', '=:', '!:', '<:', '>:'))) {
				$operator   = strtr(
					substr($expression, -2),
					array('<>' => '!', '!=' => '!')
				);
				$expression = substr($expression, 0, -2);
			} else {
				$operator   = substr($expression, -1);
				$expression = substr($expression, 0, -1);
			}
			
			// Quotes the identifier in the expression
			preg_match('#^([^(]+\()\s*([^\s]+)\s*(\))$#D', $expression, $parts);
			$expression = $parts[1] . $db->escape('%r', $parts[2]) . $parts[3];
			
			// The AVG, SUM and COUNT functions all return a number
			$function    = strtolower(substr($parts[2], 0, -1));
			$placeholder = (in_array($function, array('avg', 'sum', 'count'))) ? '%f' : NULL;
			
			// This removes stray quoting inside of {route} specified for shorthand column names
			$expression  = preg_replace('#(\{\w+)"\."(\w+\})#', '\1.\2', $expression);
			
			$params = self::addColumnCondition($db, $schema, $params, $table, $parts[2], $operator, $value, $expression, $placeholder);
		
			$i++;
		}
		
		return $params;
	}
	
	
	/**
	 * Adds an `ORDER BY` clause to an array of params for an fDatabase::query() call
	 * 
	 * @internal
	 * 
	 * @param  fDatabase $db         The database the query will be executed on
	 * @param  fSchema   $schema     The schema object for the database the query will be executed on
	 * @param  array     $params     The parameters for the fDatabase::query() call
	 * @param  string    $table      The table any ambigious column references will refer to
	 * @param  array     $order_bys  The array of order bys to use - see fRecordSet::build() for format
	 * @return array  The params with a SQL `ORDER BY` clause added
	 */
	static public function addOrderByClause($db, $schema, $params, $table, $order_bys)
	{
		$expressions = array();
		
		foreach ($order_bys as $column => $direction) {
			if ((!is_string($column) && !is_object($column) && !is_numeric($column)) || !strlen(trim($column))) {
				throw new fProgrammerException(
					'An invalid sort column, %s, was specified',
					$column
				);
			}
			
			$direction = strtoupper($direction);
			if (!in_array($direction, array('ASC', 'DESC'))) {
				throw new fProgrammerException(
					'An invalid direction, %s, was specified',
					$direction
				);
			}
			
			if (preg_match('#^((?:max|min|avg|sum|count)\()?((?:(?:(?:"?\w+"?\.)?"?\w+(?:\{[\w.]+\})?"?=>)?"?(?:(?:\w+"?\."?)?\w+)(?:\{[\w.]+\})?"?\.)?"?(?:\w+)"?)(?:\))?$#D', $column, $matches)) {
				
				// Parse the expression and get a table and column to determine the data type
				list ($clause_table, $clause_column) = self::getTableAndColumn($schema, $table, $matches[2]);
				$column_type = $schema->getColumnInfo($clause_table, $clause_column, 'type');
				
				// Make sure each column is qualified with a table name
				if (strpos($matches[2], '.') === FALSE) {
					$matches[2] = $table . '.' . $matches[2];	
				}
				
				$matches[2] = $db->escape('%r', $matches[2]);
				
				// Text columns are converted to lowercase for more accurate sorting
				if (in_array($column_type, array('varchar', 'char', 'text'))) {
					$expression = 'LOWER(' . $matches[2] . ')';
				} else {
					$expression = $matches[2];
				}
				
				// If the column is in an aggregate function, add the function back in
				if ($matches[1]) {
					$expression = $matches[1] . $expression . ')';	
				}
				
				$expressions[] = $expression . ' ' . $direction;
				
			} else {
				$expressions[] = $column . ' ' . $direction;
			}
		}
		
		$params[0] .= join(', ', $expressions);
		
		return $params;
	}
	
	
	/**
	 * Add the appropriate SQL and params for a `WHERE` clause condition for primary keys of the table specified
	 * 
	 * @internal
	 * 
	 * @param  fSchema $schema        The schema for the database the query will be run on
	 * @param  array   $params        The currently constructed params for fDatabase::query() - the first param should be a SQL statement
	 * @param  string  $table         The table to build the where clause for
	 * @param  string  $table_alias   The alias for the table
	 * @param  array   &$values       The values array for the fActiveRecord object
	 * @param  array   &$old_values   The old values array for the fActiveRecord object
	 * @return array  The params to pass to fDatabase::query(), including the new primary key where condition
	 */
	static public function addPrimaryKeyWhereParams($schema, $params, $table, $table_alias, &$values, &$old_values)
	{
		$pk_columns = $schema->getKeys($table, 'primary');
		
		$column_info = $schema->getColumnInfo($table);
		
		$conditions = array();
		foreach ($pk_columns as $pk_column) {
			$value = fActiveRecord::retrieveOld($old_values, $pk_column, $values[$pk_column]);
			
			// This makes sure the query performs the way an insert will
			if ($value === NULL && $column_info[$pk_column]['not_null'] && $column_info[$pk_column]['default'] !== NULL) {
				$value = $column_info[$pk_column]['default'];
			}
			
			$params[] = $table_alias . '.' . $pk_column;
			$params[] = $value;
			
			$conditions[] = self::makeCondition($schema, $table, $pk_column, '=', $value);
		}
		
		$params[0] .= join(' AND ', $conditions);
		
		return $params;
	}
	
	
	/**
	 * Adds a `WHERE` clause, from an array of conditions, to the parameters for an fDatabase::query() call
	 * 
	 * @internal
	 * 
	 * @param  fDatabase $db          The database the query will be executed on
	 * @param  fSchema   $schema      The schema for the database
	 * @param  array     $params      The parameters for the fDatabase::query() call
	 * @param  string    $table       The table any ambigious column references will refer to
	 * @param  array     $conditions  The array of conditions - see fRecordSet::build() for format
	 * @return array  The params with the SQL `WHERE` clause added
	 */
	static public function addWhereClause($db, $schema, $params, $table, $conditions)
	{
		$i = 0;
		foreach ($conditions as $column => $values) {
			if ($i) {
				$params[0] .= ' AND ';	
			}
			
			if (in_array(substr($column, -3), array('!=:', '>=:', '<=:', '<>:'))) {
				$operator = strtr(
					substr($column, -3),
					array('<>:' => '!:', '!=:' => '!:')
				);
				$column   = substr($column, 0, -3);
			} elseif (in_array(substr($column, -2), array('<=', '>=', '!=', '<>', '!~', '&~', '^~', '$~', '><', '=:', '!:', '<:', '>:'))) {
				$operator = strtr(
					substr($column, -2),
					array('<>' => '!', '!=' => '!')
				);
				$column   = substr($column, 0, -2);
			} else {
				$operator = substr($column, -1);
				$column   = substr($column, 0, -1);
			}
			
			if (!is_object($values)) {
				settype($values, 'array');
			} else {
				$values = array($values);	
			}
			if (!$values) { $values = array(NULL); }
			
			$new_values = array();
			foreach ($values as $value) {
				if (is_object($value) && is_callable(array($value, '__toString'))) {
					$value = $value->__toString();
				} elseif (is_object($value)) {
					$value = (string) $value;	
				}
				$new_values[] = $value;
			}
			$values = $new_values;
			
			// Multi-column condition
			if (preg_match('#(?<!\|)\|(?!\|)#', $column)) {
				$columns   = explode('|', $column);
				$operators = array();
				
				foreach ($columns as &$_column) {
					if (in_array(substr($_column, -3), array('!=:', '>=:', '<=:', '<>:'))) {
						$operators[] = strtr(
							substr($_column, -3),
							array('<>:' => '!:', '!=:' => '!:')
						);
						$_column     = substr($_column, 0, -3);
					} elseif (in_array(substr($_column, -2), array('<=', '>=', '!=', '<>', '!~', '&~', '^~', '$~', '=:', '!:', '<:', '>:'))) {
						$operators[] = strtr(
							substr($_column, -2),
							array('<>' => '!', '!=' => '!')
						);
						$_column     = substr($_column, 0, -2);
					} elseif (!ctype_alnum(substr($_column, -1))) {
						$operators[] = substr($_column, -1);
						$_column     = substr($_column, 0, -1);
					}
				}
				$operators[] = $operator;
				
				if (sizeof($operators) == 1) {
					
					// Make sure every column is qualified by a table name
					$new_columns = array();
					foreach ($columns as $column) {
						if (strpos($column, '.') === FALSE) {
							$column = $table . '.' . $column;
						}
						$new_columns[] = $column;	
					}
					$columns = $new_columns;
					
					// Handle fuzzy searches
					if ($operator == '~') {
					
						// If the value to search is a single string value, parse it for search terms
						if (sizeof($values) == 1 && is_string($values[0])) {
							$values = self::parseSearchTerms($values[0], TRUE);	
						}
						
						// Skip fuzzy matches with no values to match
						if ($values === array()) {
							$params[0] .= ' 1 = 1 ';
							$i++;
							continue;
						}
						
						$condition = array();
						foreach ($values as $value) {
							$sub_condition = array();
							foreach ($columns as $column) {
								$sub_condition[] = $db->escape('%r', $column) . ' LIKE %s';
								$params[] = '%' . $value . '%';
							}
							$condition[] = '(' . join(' OR ', $sub_condition) . ')';
						}
						$params[0] .= ' (' . join(' AND ', $condition) . ') ';
					
					// Handle intersection
					} elseif ($operator == '><') {
						
						if (sizeof($columns) != 2 || sizeof($values) != 2) {
							throw new fProgrammerException(
								'The intersection operator, %s, requires exactly two columns and two values',
								$operator
							);	
						}
						
						$escaped_columns = array(
							$db->escape('%r', $columns[0]),
							$db->escape('%r', $columns[1])
						);
						
						list($column_1_table, $column_1) = self::getTableAndColumn($schema, $table, $columns[0]);
						list($column_2_table, $column_2) = self::getTableAndColumn($schema, $table, $columns[1]);
						$placeholders = array(
							$schema->getColumnInfo($column_1_table, $column_1, 'placeholder'),
							$schema->getColumnInfo($column_2_table, $column_2, 'placeholder')
						);
															 
						if ($values[1] === NULL) {
							$part_1 = '(' . $escaped_columns[1] . ' IS NULL AND ' . $escaped_columns[0] . ' = ' . $placeholders[0] . ')';
							$part_2 = '(' . $escaped_columns[1] . ' IS NOT NULL AND ' . $escaped_columns[0] . ' <= ' . $placeholders[0] . ' AND ' . $escaped_columns[1] . ' >= ' . $placeholders[1] . ')';
							$params[] = $values[0];
							$params[] = $values[0];
							$params[] = $values[0];
							
						} else {
							$part_1 = '(' . $escaped_columns[0] . ' <= ' . $placeholders[0] . ' AND ' . $escaped_columns[1] . ' >= ' . $placeholders[1] . ')';
							$part_2 = '(' . $escaped_columns[0] . ' >= ' . $placeholders[0] . ' AND ' . $escaped_columns[0] . ' <= ' . $placeholders[0] . ')';
							$params[] = $values[0];
							$params[] = $values[0];
							$params[] = $values[0];
							$params[] = $values[1];
						}
						
						$params[0] .= ' (' . $part_1 . ' OR ' . $part_2 . ') ';
					
					} else {
						throw new fProgrammerException(
							'An invalid comparison operator, %s, was specified for multiple columns',
							$operator
						);
					}
				
				// Handle OR combos
				} else {
					if (sizeof($columns) != sizeof($values)) {
						throw new fProgrammerException(
							'When creating an %1$s where clause there must be an equal number of columns and values, however %2$s column(s) and %3$s value(s) were provided',
							'OR',
							sizeof($columns),
							sizeof($values)
						);
					}
					
					if (sizeof($columns) != sizeof($operators)) {
						throw new fProgrammerException(
							'When creating an %s where clause there must be a comparison operator for each column, however one or more is missing',
							'OR'
						);
					}
					
					$params[0] .= ' (';
					$iterations = sizeof($columns);
					for ($j=0; $j<$iterations; $j++) {
						if ($j) {
							$params[0] .= ' OR ';	
						}
						$params = self::addColumnCondition($db, $schema, $params, $table, $columns[$j], $operators[$j], $values[$j]);
					}
					$params[0] .= ') ';
				}

				
			// Concatenated columns
			} elseif (strpos($column, '||') !== FALSE) {
				
				$parts     = explode('||', $column);
				$new_parts = array();
				foreach ($parts as $part) {
					$part = trim($part);
					if ($part[0] != "'") {
						$new_parts[] = $db->escape('%r', $part);
					} else {
						$new_parts[] = $part;
					}	
				}
				$escaped_column = join('||', $new_parts);
				$params = self::addColumnCondition($db, $schema, $params, $table, $column, $operator, $values, $escaped_column, '%s');
				
			// Single column condition	
			} else {
				
				$params = self::addColumnCondition($db, $schema, $params, $table, $column, $operator, $values);
				
			}
			
			$i++;
		}
		
		return $params;
	}
	
	
	/**
	 * Takes a table name, cleans off quoting and removes the schema name if unambiguous
	 * 
	 * @param fSchema $schema  The schema object for the database being inspected
	 * @param string  $table   The table name to be made cleaned
	 * @return string  The cleaned table name
	 */
	static private function cleanTableName($schema, $table)
	{
		$table = str_replace('"', '', $table);
		$tables = array_flip($schema->getTables());
		if (!isset($tables[$table])) {
			$short_table = preg_replace('#^\w\.#', '', $table);
			if (isset($tables[$short_table])) {
				$table = $short_table;
			}	
		}
		return strtolower($table);
	}
	
	
	/**
	 * Creates a `FROM` clause from a join array
	 * 
	 * @internal
	 * 
	 * @param  fDatabase $db     The database the query will be run on
	 * @param  array     $joins  The joins to create the `FROM` clause out of
	 * @return string  The from clause (does not include the word `FROM`)
	 */
	static private function createFromClauseFromJoins($db, $joins)
	{
		$sql = '';
		
		foreach ($joins as $join) {
			// Here we handle the first table in a join
			if ($join['join_type'] == 'none') {
				$sql .= $db->escape('%r', $join['table_name']);
				if ($join['table_alias'] != $join['table_name']) {
					$sql .= ' ' . $db->escape('%r', $join['table_alias']);
				}
			
			// Here we handle all other joins
			} else {
				$sql .= ' ' . strtoupper($join['join_type']) . ' ' . $db->escape('%r', $join['table_name']);
				if ($join['table_alias'] != $join['table_name']) {
					$sql .= ' ' . $db->escape('%r', $join['table_alias']);
				}
				if (!empty($join['on_clause_fields'])) {
					$sql .= ' ON ' . $db->escape('%r', $join['on_clause_fields'][0]) . ' = ' . $db->escape('%r', $join['on_clause_fields'][1]);
				}
			}
		}
		
		return $sql;
	}	
	
	
	/**
	 * Creates join information for the table shortcut provided
	 * 
	 * @internal
	 * 
	 * @param  fSchema $schema         The schema object for the tables/joins
	 * @param  string  $table          The primary table
	 * @param  string  $table_alias    The primary table alias
	 * @param  string  $related_table  The related table
	 * @param  string  $route          The route to the related table
	 * @param  array   &$joins         The names of the joins that have been created
	 * @param  array   &$used_aliases  The aliases that have been used
	 * @return string  The name of the significant join created
	 */
	static private function createJoin($schema, $table, $table_alias, $related_table, $route, &$joins, &$used_aliases)
	{
		$routes = fORMSchema::getRoutes($schema, $table, $related_table);
						
		if (!isset($routes[$route])) {
			throw new fProgrammerException(
				'An invalid route, %1$s, was specified for the relationship from %2$s to %3$s',
				$route,
				$table,
				$related_table
			);
		}
		
		if (isset($joins[$table . '_' . $related_table . '{' . $route . '}'])) {
			return  $table . '_' . $related_table . '{' . $route . '}';
		}
		
		// If the route uses a join table
		if (isset($routes[$route]['join_table'])) {
			$join = array(
				'join_type'        => 'LEFT JOIN',
				'table_name'       => $routes[$route]['join_table'],
				'table_alias'      => self::createNewAlias($routes[$route]['join_table'], $used_aliases),
				'on_clause_fields' => array()
			);
			
			$join2 = array(
				'join_type'        => 'LEFT JOIN',
				'table_name'       => $related_table,
				'table_alias'      => self::createNewAlias($related_table, $used_aliases),
				'on_clause_fields' => array()
			);
			
			if ($table != $related_table) {
				$join['on_clause_fields'][]  = $table_alias . '.' . $routes[$route]['column'];
				$join['on_clause_fields'][]  = $join['table_alias'] . '.' . $routes[$route]['join_column'];
				$join2['on_clause_fields'][] = $join['table_alias'] . '.' . $routes[$route]['join_related_column'];
				$join2['on_clause_fields'][] = $join2['table_alias'] . '.' . $routes[$route]['related_column'];
			} else {
				$join['on_clause_fields'][]  = $table_alias . '.' . $routes[$route]['column'];
				$join['on_clause_fields'][]  = $join['table_alias'] . '.' . $routes[$route]['join_related_column'];
				$join2['on_clause_fields'][] = $join['table_alias'] . '.' . $routes[$route]['join_column'];
				$join2['on_clause_fields'][] = $join2['table_alias'] . '.' . $routes[$route]['related_column'];
			}
			
			$joins[$table . '_' . $related_table . '{' . $route . '}_join'] = $join;
			$joins[$table . '_' . $related_table . '{' . $route . '}'] = $join2;
				
		// If the route is a direct join
		} else {
			
			$join = array(
				'join_type'        => 'LEFT JOIN',
				'table_name'       => $related_table,
				'table_alias'      => self::createNewAlias($related_table, $used_aliases),
				'on_clause_fields' => array()
			);
			
			if ($table != $related_table) {
				$join['on_clause_fields'][] = $table_alias . '.' . $routes[$route]['column'];
				$join['on_clause_fields'][] = $join['table_alias'] . '.' . $routes[$route]['related_column'];
			} else {
				$join['on_clause_fields'][] = $table_alias . '.' . $routes[$route]['related_column'];
				$join['on_clause_fields'][] = $join['table_alias'] . '.' . $routes[$route]['column'];
			}
		
			$joins[$table . '_' . $related_table . '{' . $route . '}'] = $join;
		
		}
		
		return $table . '_' . $related_table . '{' . $route . '}';
	}
	
	
	/**
	 * Creates a new table alias
	 * 
	 * @internal
	 * 
	 * @param  string $table          The table to create an alias for
	 * @param  array  &$used_aliases  The aliases that have been used
	 * @return string  The alias to use for the table
	 */
	static private function createNewAlias($table, &$used_aliases)
	{
		if (!in_array($table, $used_aliases)) {
			$used_aliases[] = $table;
			return $table;
		}
		
		// This will strip any schema name off the beginning
		$table = preg_replace('#^\w+\.#', '', $table);
		
		$i = 1;
		while(in_array($table . $i, $used_aliases)) {
			$i++;
		}
		$used_aliases[] = $table . $i;
		return $table . $i;
	}
	
	
	/**
	 * Gets the table and column name from a shorthand column name
	 * 
	 * @param  fSchema $schema  The schema for the database
	 * @param  string  $table   The table to use when no table is specified in the shorthand
	 * @param  string  $column  The shorthand column definition - see fRecordSet::build() for possible syntaxes
	 * @return array  The $table and $column, suitable for use with fSchema
	 */
	static private function getTableAndColumn($schema, $table, $column)
	{
		// Handle shorthand column names like table.column and table=>related_table.column
		if (preg_match('#((?:"?\w+"?\.)?"?\w+)(?:\{[\w.]+\})?"?\."?(\w+)"?$#D', $column, $match)) {
			$table  = $match[1];
			$column = $match[2];
		}	
		$table  = self::cleanTableName($schema, $table);
		$column = str_replace('"', '', $column);
		
		return array($table, $column);
	}
	
	
	/**
	 * Finds all of the table names in the SQL and creates the appropriate `FROM` and `GROUP BY` clauses with all necessary joins
	 * 
	 * The SQL string should contain two placeholders, `:from_clause` and
	 * `:group_by_clause`, although the later may be omitted if necessary. All
	 * columns should be qualified with their full table name.
	 * 
	 * Here is an example SQL string to pass in presumming that the tables
	 * users and groups are in a relationship:
	 * 
	 * {{{
	 * SELECT users.* FROM :from_clause WHERE groups.group_id = 5 :group_by_clause ORDER BY lower(users.first_name) ASC
	 * }}}
	 * 
	 * @internal
	 * 
	 * @param  fDatabase $db      The database the query is to be executed on
	 * @param  fSchema   $schema  The schema for the database
	 * @param  array     $params  The parameters for the fDatabase::query() call
	 * @param  string    $table   The main table to be queried
	 * @return array  The params with the SQL `FROM` and `GROUP BY` clauses injected
	 */
	static public function injectFromAndGroupByClauses($db, $schema, $params, $table)
	{
		$table_with_schema = $table;
		$table = self::cleanTableName($schema, $table);
		$joins = array();
		
		if (strpos($params[0], ':from_clause') === FALSE) {
			throw new fProgrammerException(
				'No %1$s placeholder was found in:%2$s',
				':from_clause',
				"\n" . $params[0]
			);
		}
		
		$has_group_by_placeholder = (strpos($params[0], ':group_by_clause') !== FALSE) ? TRUE : FALSE;
		
		// Separate the SQL from quoted values
		preg_match_all("#(?:'(?:''|\\\\'|\\\\[^']|[^'\\\\])*')|(?:[^']+)#", $params[0], $matches);
		
		$table_alias = $table;
		
		$used_aliases  = array();
		$table_map     = array();
		
		// If we are not passing in existing joins, start with the specified table
		if (!$joins) {
			$joins[] = array(
				'join_type'   => 'none',
				'table_name'  => $table,
				'table_alias' => $table_alias
			);
		}
		
		$used_aliases[] = $table_alias;
		
		foreach ($matches[0] as $match) {
			if ($match[0] != "'") {
				// This removes quotes from around . in the {route} specified of a shorthand column name
				$match = preg_replace('#(\{\w+)"\."(\w+\})#', '\1.\2', $match);
				
				preg_match_all('#(?<!\w|"|=>)((?:"?((?:\w+"?\."?)?\w+)(?:\{([\w.]+)\})?"?=>)?("?(?:\w+"?\."?)?\w+)(?:\{([\w.]+)\})?"?)\."?\w+"?(?=[^\w".{])#m', $match, $table_matches, PREG_SET_ORDER);
				foreach ($table_matches as $table_match) {
					
					if (!isset($table_match[5])) {
						$table_match[5] = NULL;
					}
					
					if (!empty($table_match[2])) {
						$table_match[2] = self::cleanTableName($schema, $table_match[2]);	
					}
					$table_match[4] = self::cleanTableName($schema, $table_match[4]);
					
					if (in_array($db->getType(), array('oracle', 'db2'))) {
						foreach (array(2, 3, 4, 5) as $subpattern) {
							if (isset($table_match[$subpattern])) {
								$table_match[$subpattern] = strtolower($table_match[$subpattern]);	
							}
						}	
					}
					
					// This is a related table that is going to join to a once-removed table
					if (!empty($table_match[2])) {
						
						$related_table = $table_match[2];
						$route = fORMSchema::getRouteName($schema, $table, $related_table, $table_match[3]);
						
						$join_name = $table . '_' . $related_table . '{' . $route . '}';
						
						$once_removed_table = $table_match[4];
						
						// Add the once removed table to the aliases in case we also join directly to it
						// which may cause the replacements later in this method to convert first to the
						// real table name and then from the real table to the real table's alias
						if (!in_array($once_removed_table, $used_aliases)) {
							$used_aliases[] = $once_removed_table;
						}

						self::createJoin($schema, $table, $table_alias, $related_table, $route, $joins, $used_aliases);
						
						$route = fORMSchema::getRouteName($schema, $related_table, $once_removed_table, $table_match[5]);
						
						$join_name = self::createJoin($schema, $related_table, $joins[$join_name]['table_alias'], $once_removed_table, $route, $joins, $used_aliases);
						
						$table_map[$table_match[1]] = $db->escape('%r', $joins[$join_name]['table_alias']);
						
						// Remove the once removed table from the aliases so we also join directly to it without an alias
						unset($used_aliases[array_search($once_removed_table, $used_aliases)]);
					
					// This is a related table
					} elseif (($table_match[4] != $table || fORMSchema::getRoutes($schema, $table, $table_match[4])) && self::cleanTableName($schema, $table_match[1]) != $table) {
					
						$related_table = $table_match[4];
						$route = fORMSchema::getRouteName($schema, $table, $related_table, $table_match[5]);
						
						$join_name = self::createJoin($schema, $table, $table_alias, $related_table, $route, $joins, $used_aliases);
						
						$table_map[$table_match[1]] = $db->escape('%r', $joins[$join_name]['table_alias']);
					}
				}
			}
		}
		
		// Determine if we joined a *-to-many relationship
		$joined_to_many = FALSE;
		foreach ($joins as $name => $join) {
			if (is_numeric($name)) {
				continue;
			}
			
			// Many-to-many uses a join table
			if (substr($name, -5) == '_join') {
				$joined_to_many = TRUE;
				break;
			}
			
			$main_table   = preg_replace('#_' . $join['table_name'] . '{\w+}$#iD', '', $name);
			$second_table = $join['table_name'];
			$route        = preg_replace('#^[^{]+{([\w.]+)}$#D', '\1', $name);
			$routes       = fORMSchema::getRoutes($schema, $main_table, $second_table, '*-to-many');
			if (isset($routes[$route])) {
				$joined_to_many = TRUE;
				break;
			}
		}
		
		$from_clause     = self::createFromClauseFromJoins($db, $joins);
		
		// If we are joining on a *-to-many relationship we need to group by the
		// columns in the main table to prevent duplicate entries
		if ($joined_to_many) {
			$column_info     = $schema->getColumnInfo($table);
			$columns         = array();
			foreach ($column_info as $column => $info) {
				$columns[] = $table . '.' . $column;
			}
			$group_by_columns = $db->escape('%r ', $columns);
			$group_by_clause  = ' GROUP BY ' . $group_by_columns;
		} else {
			$group_by_clause = ' ';
			$group_by_columns = '';
		}
		
		// Put the SQL back together
		$new_sql = '';
		
		$preg_table_pattern = preg_quote($table_with_schema, '#') . '\.|' . preg_quote('"' . trim($table_with_schema, '"') . '"', '#') . '\.';
		foreach ($matches[0] as $match) {
			$temp_sql = $match;
			
			// Get rid of the => notation and the :from_clause placeholder
			if ($match[0] !== "'") {
				// This removes quotes from around . in the {route} specified of a shorthand column name
				$temp_sql = preg_replace('#(\{\w+)"\."(\w+\})#', '\1.\2', $match);
				
				foreach ($table_map as $arrow_table => $alias) {
					$temp_sql = preg_replace('#(?<![\w"])' . preg_quote($arrow_table, '#') . '(?!=[\w"])#', $alias, $temp_sql);
				}
				
				// This automatically adds max() around column from other tables when a group by is used
				if ($joined_to_many && preg_match('#order\s+by#i', $temp_sql)) {
					$order_by_found = TRUE;
					
					$parts = preg_split('#(order\s+by)#i', $temp_sql, -1, PREG_SPLIT_DELIM_CAPTURE);
					$parts[2] = preg_replace('#(?<!avg\(|count\(|max\(|min\(|sum\(|cast\(|case |when |"|avg\("|count\("|max\("|min\("|sum\("|cast\("|case "|when "|\{|\.)((?!' . $preg_table_pattern . ')((?:"|\b)\w+"?\.)?(?:"|\b)\w+"?\."?\w+"?)(?![\w."])#i', 'max(\1)', $parts[2]);
					
					$temp_sql = join('', $parts);
				}
				
				$temp_sql = str_replace(':from_clause', $from_clause, $temp_sql);
				if ($has_group_by_placeholder) {
					$temp_sql = preg_replace('#\s:group_by_clause(\s|$)#', strtr($group_by_clause, array('\\' => '\\\\', '$' => '\\$')), $temp_sql);
				} elseif ($group_by_columns) {
					$temp_sql = preg_replace('#(\sGROUP\s+BY\s((?!HAVING|ORDER\s+BY).)*)\s#i', '\1, ' . strtr($group_by_columns, array('\\' => '\\\\', '$' => '\\$')), $temp_sql);
				}
			}
			
			$new_sql .= $temp_sql;
		}
		
		$params[0] = $new_sql;
		
		return $params;
	}
	
	
	/**
	 * Makes a condition for a SQL statement out of fDatabase::escape() placeholders
	 * 
	 * @internal
	 * 
	 * @param  fSchema $schema               The schema object for the database the query will be executed on
	 * @param  string  $table                The table to create the condition for
	 * @param  string  $column               The column to make the condition for
	 * @param  string  $comparison_operator  The comparison operator for the condition
	 * @param  mixed   $value                The value for the condition, which allows the $comparison_operator to be tweaked for NULL values
	 * @return string  A SQL condition using fDatabase::escape() placeholders
	 */
	static public function makeCondition($schema, $table, $column, $comparison_operator, $value)
	{
		list($table, $column) = self::getTableAndColumn($schema, $table, $column);
		
		$co = strtr($comparison_operator, array('!' => '<>', '!=' => '<>'));
		
		if ($value === NULL) {
			if (in_array(trim($co), array('=', 'IN'))) {
				$co = 'IS';
			} elseif (in_array(trim($co), array('<>', 'NOT IN'))) {
				$co = 'IS NOT';
			}
		}
		
		return '%r ' . $co . ' ' . $schema->getColumnInfo($table, $column, 'placeholder');
	}
	
	
	/**
	 * Parses a search string into search terms, supports quoted phrases and removes extra punctuation
	 * 
	 * @internal
	 * 
	 * @param  string  $terms              A text string from a form input to parse into search terms
	 * @param  boolean $ignore_stop_words  If stop words should be ignored, this setting will be ignored if all words are stop words
	 * @return void
	 */
	static public function parseSearchTerms($terms, $ignore_stop_words=FALSE)
	{
		$stop_words = array(
			'i',     'a',     'an',    'are',   'as',    'at',    'be',    
			'by',    'de',    'en',    'en',    'for',   'from',  'how',   
			'in',    'is',    'it',    'la',    'of',    'on',    'or',    
			'that',  'the',   'this',  'to',    'was',   'what',  'when',  
			'where', 'who',   'will'
		);
		
		preg_match_all('#(?:"[^"]+"|[^\s]+)#', $terms, $matches);
		
		$good_terms    = array();
		$ignored_terms = array();
		foreach ($matches[0] as $match) {
			// Remove phrases from quotes
			if ($match[0] == '"' && substr($match, -1)) {
				$match = substr($match, 1, -1);	
			
			// Trim any punctuation off of the beginning and end of terms
			} else {
				if (self::$pcre_supports_unicode_character_properties === NULL) {
					fCore::startErrorCapture();
					preg_match('#\pC#u', 'test');
					self::$pcre_supports_unicode_character_properties = !((boolean) fCore::stopErrorCapture());
				}
				if (self::$pcre_supports_unicode_character_properties) {
					$match = preg_replace('#(^[\pC\pC\pM\pP\pS\pZ]+|[\pC\pC\pM\pP\pS\pZ]+$)#iDu', '', $match);
				} else {
					// This just removes ascii non-alphanumeric characters, plus the unicode punctuation and supplemental punctuation blocks
					$match = preg_replace('#(^[\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7F\x{2000}-\x{206F}\x{2E00}-\x{2E7F}\x{00A1}-\x{00A9}\x{00AB}-\x{00B1}\x{00B4}\x{00B6}-\x{00B8}\x{00BB}\x{00BF}\x{00D7}\x{00F7}]+|[\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7F\x{2000}-\x{206F}\x{2E00}-\x{2E7F}\x{00A1}-\x{00A9}\x{00AB}-\x{00B1}\x{00B4}\x{00B6}-\x{00B8}\x{00BB}\x{00BF}\x{00D7}\x{00F7}]+$)#iDu', '', $match);
				}
			}
			
			if ($ignore_stop_words && in_array(strtolower($match), $stop_words)) {
				$ignored_terms[] = $match;
				continue;	
			}
			$good_terms[] = $match;
		}
		
		// If no terms were parsed, that means all words were stop words
		if ($ignored_terms && !$good_terms) {
			$good_terms = $ignored_terms;
		}	
		
		return $good_terms;
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
		self::$database_objects = array();
	}
	
	
	/**
	 * Return the instance of the fDatabase class
	 * 
	 * @param  string $class  The class to retrieve the database for - if not specified, the default database will be returned
	 * @param  string $role   If the database will be used for `'write'`, `'read'` or `'either'` operations
	 * @return fDatabase  The database instance
	 */
	static public function retrieve($class='fActiveRecord', $role='either')
	{
		if (substr($class, 0, 5) == 'name:') {
			$database_name = substr($class, 5);
		} else {
			$database_name = fORM::getDatabaseName($class);
		}
		
		if (!isset(self::$database_objects[$database_name])) {
			throw new fProgrammerException(
				'The database object named "%1$s" has not been attached via %2$s yet',
				$database_name,
				__CLASS__ . '::attach()'
			);
		}
		
		if ($role == 'write' || $role == 'read') {
			// If the user wants a read database but we are in a transaction on the write database, return
			// the write database to allow for comparing data changed since the transaction started
			if ($role == 'read' && isset(self::$database_objects[$database_name]['write']) && self::$database_objects[$database_name]['write']->isInsideTransaction()) {
				$role = 'write';	
			}
			
			if (!isset(self::$database_objects[$database_name][$role])) {
				throw new fProgrammerException(
					'The database object named "%1$s" for the %s$2 role has not been attached via %3$s yet',
					$database_name,
					$role,
					__CLASS__ . '::attach()'
				);			
			}
			
			return self::$database_objects[$database_name][$role];
		}
			
		if (isset(self::$database_objects[$database_name]['write'])) {
			return self::$database_objects[$database_name]['write'];
				
		} elseif (isset(self::$database_objects[$database_name]['read'])) {
			return self::$database_objects[$database_name]['read'];
		}
	}
	
	
	/**
	 * Removed aggregate function calls from where conditions array and puts them in a having conditions array
	 * 
	 * @internal
	 * 
	 * @param  array &$where_conditions  The where conditions to look through for aggregate functions
	 * @return array  The conditions to be put in a `HAVING` clause
	 */
	static public function splitHavingConditions(&$where_conditions)
	{
		$having_conditions = array();
		
		foreach ($where_conditions as $column => $value) {
			$column_has_aggregate             = preg_match('#^(count\(|max\(|avg\(|min\(|sum\()#i', $column);
			$is_column_compare_with_aggregate = substr($column, -1) == ':' && preg_match('#^(count\(|max\(|avg\(|min\(|sum\()#i', $value);
			if ($column_has_aggregate || $is_column_compare_with_aggregate) {
				$having_conditions[$column] = $value;
				unset($where_conditions[$column]);
			}	
		}
		
		return $having_conditions;	
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fORMDatabase
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