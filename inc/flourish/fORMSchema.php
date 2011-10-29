<?php
/**
 * Provides fSchema class related functions for ORM code
 * 
 * @copyright  Copyright (c) 2007-2010 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fORMSchema
 * 
 * @version    1.0.0b9
 * @changes    1.0.0b9  Enhanced various exception messages [wb, 2010-09-19]
 * @changes    1.0.0b8  Added 'one-to-one' support to ::getRouteNameFromRelationship(), '!many-to-one' to ::getRoute() [wb, 2010-03-03]
 * @changes    1.0.0b7  Added support for multiple databases [wb, 2009-10-28]
 * @changes    1.0.0b6  Internal Backwards Compatibility Break - Added the `$schema` parameter to the beginning of ::getRoute(), ::getRouteName(), ::getRoutes() and ::isOneToOne() - added '!many-to-one' relationship type handling [wb, 2009-10-22]
 * @changes    1.0.0b5  Fixed some error messaging to not include {empty_string} in some situations [wb, 2009-07-31]
 * @changes    1.0.0b4  Added ::isOneToOne() [wb, 2009-07-21]
 * @changes    1.0.0b3  Added routes caching for performance [wb, 2009-06-15]
 * @changes    1.0.0b2  Backwards Compatiblity Break - removed ::enableSmartCaching(), fORM::enableSchemaCaching() now provides equivalent functionality [wb, 2009-05-04]
 * @changes    1.0.0b   The initial implementation [wb, 2007-06-14]
 */
class fORMSchema
{
	// The following constants allow for nice looking callbacks to static methods
	const attach                       = 'fORMSchema::attach';
	const getRoute                     = 'fORMSchema::getRoute';
	const getRouteName                 = 'fORMSchema::getRouteName';
	const getRouteNameFromRelationship = 'fORMSchema::getRouteNameFromRelationship';
	const getRoutes                    = 'fORMSchema::getRoutes';
	const isOneToOne                   = 'fORMSchema::isOneToOne';
	const reset                        = 'fORMSchema::reset';
	const retrieve                     = 'fORMSchema::retrieve';
	
	
	/**
	 * A cache for computed information
	 * 
	 * @var array
	 */
	static private $cache = array(
		'getRoutes' => array()
	);
	
	
	/**
	 * The schema objects to use for all ORM functionality
	 * 
	 * @var array
	 */
	static private $schema_objects = array();
	
	
	/**
	 * Allows attaching an fSchema-compatible object as the schema singleton for ORM code
	 * 
	 * @param  fSchema $schema  An object that is compatible with fSchema
	 * @param  string  $name    The name of the database this schema is for
	 * @return void
	 */
	static public function attach($schema, $name='default')
	{
		self::$schema_objects[$name] = $schema;
	}
	
	
	/**
	 * Returns information about the specified route
	 * 
	 * @internal
	 * 
	 * @param  fSchema $schema             The schema object to get the route from
	 * @param  string  $table              The main table we are searching on behalf of
	 * @param  string  $related_table      The related table we are searching under
	 * @param  string  $route              The route to get info about
	 * @param  string  $relationship_type  The relationship type: `NULL`, `'*-to-many'`, `'*-to-one'`, `'!many-to-one'`, `'one-to-one'`, `'one-to-meny'`, `'many-to-one'`, `'many-to-many'`
	 * @return void
	 */
	static public function getRoute($schema, $table, $related_table, $route, $relationship_type=NULL)
	{
		$valid_relationship_types = array(
			NULL,
			'*-to-many',
			'*-to-one',
			'!many-to-one',
			'many-to-many',
			'many-to-one',
			'one-to-many',
			'one-to-one'
		);
		if (!in_array($relationship_type, $valid_relationship_types)) {
			$valid_relationship_types[0] = '{null}';
			throw new fProgrammerException(
				'The relationship type specified, %1$s, is invalid. Must be one of: %2$s.',
				$relationship_type,
				join(', ', $valid_relationship_types)
			);
		}
		
		if ($route === NULL) {
			$route = self::getRouteName($schema, $table, $related_table, $route, $relationship_type);
		}
		
		$routes = self::getRoutes($schema, $table, $related_table, $relationship_type);
		
		if (!isset($routes[$route])) {
			throw new fProgrammerException(
				'The route specified, %1$s, for the%2$srelationship between %3$s and %4$s does not exist. Must be one of: %5$s.',
				$route,
				($relationship_type) ? ' ' . $relationship_type . ' ' : ' ',
				$table,
				$related_table,
				join(', ', array_keys($routes))
			);
		}
		
		return $routes[$route];
	}
	
	
	/**
	 * Returns the name of the only route from the specified table to one of its related tables
	 * 
	 * @internal
	 * 
	 * @param  fSchema $schema             The schema object to get the route name from
	 * @param  string  $table              The main table we are searching on behalf of
	 * @param  string  $related_table      The related table we are trying to find the routes for
	 * @param  string  $route              The route that was preselected, will be verified if present
	 * @param  string  $relationship_type  The relationship type: `NULL`, `'*-to-many'`, `'*-to-one'`, `'!many-to-one'`, `'one-to-one'`, `'one-to-many'`, `'many-to-one'`, `'many-to-many'`
	 * @return string  The only route from the main table to the related table
	 */
	static public function getRouteName($schema, $table, $related_table, $route=NULL, $relationship_type=NULL)
	{
		$valid_relationship_types = array(
			NULL,
			'*-to-many',
			'*-to-one',
			'!many-to-one',
			'many-to-many',
			'many-to-one',
			'one-to-many',
			'one-to-one'
		);
		if (!in_array($relationship_type, $valid_relationship_types)) {
			$valid_relationship_types[0] = '{null}';
			throw new fProgrammerException(
				'The relationship type specified, %1$s, is invalid. Must be one of: %2$s.',
				$relationship_type,
				join(', ', $valid_relationship_types)
			);
		}
		
		$routes = self::getRoutes($schema, $table, $related_table, $relationship_type);
		
		if (!empty($route)) {
			if (isset($routes[$route])) {
				return $route;
			}
			throw new fProgrammerException(
				'The route specified, %1$s, is not a valid route between %2$s and %3$s. Must be one of: %4$s.',
				$route,
				$table,
				$related_table,
				join(', ', array_keys($routes))
			);
		}
		
		$keys = array_keys($routes);
		
		if (sizeof($keys) > 1) {
			throw new fProgrammerException(
				'There is more than one route for the%1$srelationship between %2$s and %3$s. Please specify one of the following: %4$s.',
				($relationship_type) ? ' ' . $relationship_type . ' ' : ' ',
				$table,
				$related_table,
				join(', ', array_keys($routes))
			);
		}
		if (sizeof($keys) == 0) {
			throw new fProgrammerException(
				'The table %1$s is not in a%2$srelationship with the table %3$s',
				$table,
				($relationship_type) ? ' ' . $relationship_type . ' ' : ' ',
				$related_table
			);
		}
		
		return $keys[0];
	}
	
	
	/**
	 * Returns the name of the route specified by the relationship
	 * 
	 * @internal
	 * 
	 * @param  string $type          The type of relationship: `'*-to-one'`, `'one-to-one'`, `'one-to-many'`, `'many-to-one'`, `'many-to-many'`
	 * @param  array  $relationship  The relationship array from fSchema::getKeys()
	 * @return string  The name of the route
	 */
	static public function getRouteNameFromRelationship($type, $relationship)
	{
		$valid_types = array('*-to-one', 'one-to-one', 'one-to-many', 'many-to-one', 'many-to-many');
		if (!in_array($type, $valid_types)) {
			throw new fProgrammerException(
				'The relationship type specified, %1$s, is invalid. Must be one of: %2$s.',
				$type,
				join(', ', $valid_types)
			);
		}
		
		if (isset($relationship['join_table']) || $type == 'many-to-many') {
			return $relationship['join_table'];
		}
		
		if ($type == 'one-to-many') {
			return $relationship['related_column'];
		}
		
		return $relationship['column'];
	}
	
	
	/**
	 * Returns an array of all routes from a table to one of its related tables
	 * 
	 * @internal
	 * 
	 * @param  fSchema $schema             The schema object to get the routes for
	 * @param  string  $table              The main table we are searching on behalf of
	 * @param  string  $related_table      The related table we are trying to find the routes for
	 * @param  string  $relationship_type  The relationship type: `NULL`, `'*-to-many'`, `'*-to-one'`, `'!many-to-one'`, `'one-to-one'`, `'one-to-many'`, `'many-to-one'`, `'many-to-many'`
	 * @return array  All of the routes from the main table to the related table
	 */
	static public function getRoutes($schema, $table, $related_table, $relationship_type=NULL)
	{
		$key = $table . '::' . $related_table . '::' . $relationship_type;
		if (isset(self::$cache['getRoutes'][$key])) {
			return self::$cache['getRoutes'][$key];	
		}
		
		$valid_relationship_types = array(
			NULL,
			'*-to-many',
			'*-to-one',
			'!many-to-one',
			'many-to-many',
			'many-to-one',
			'one-to-many',
			'one-to-one'
		);
		if (!in_array($relationship_type, $valid_relationship_types)) {
			$valid_relationship_types[0] = '{null}';
			throw new fProgrammerException(
				'The relationship type specified, %1$s, is invalid. Must be one of: %2$s.',
				$relationship_type,
				join(', ', $valid_relationship_types)
			);
		}
		
		$all_relationships = $schema->getRelationships($table);
		
		if (!in_array($related_table, $schema->getTables())) {
			throw new fProgrammerException(
				'The related table specified, %1$s, does not exist in the database',
				$related_table
			);
		}
		
		$routes = array();
		
		foreach ($all_relationships as $type => $relationships) {
			
			// Filter the relationships by the relationship type
			if ($relationship_type !== NULL) {
				if ($relationship_type == '!many-to-one') {
					if ($type == 'many-to-one') {
						continue;
					}
				} else {
					if (strpos($type, str_replace('*', '', $relationship_type)) === FALSE) {
						continue;
					}
				}
			}
			
			foreach ($relationships as $relationship) {
				if ($relationship['related_table'] == $related_table) {
					if ($type == 'many-to-many') {
						$routes[$relationship['join_table']] = $relationship;
					} elseif ($type == 'one-to-many') {
						$routes[$relationship['related_column']] = $relationship;
					} else {
						$routes[$relationship['column']] = $relationship;
					}
				}
			}
		}
		
		self::$cache['getRoutes'][$key] = $routes;
		
		return $routes;
	}
	
	
	/**
	 * Indicates if the relationship specified is a one-to-one relationship
	 * 
	 * @internal
	 * 
	 * @param  fSchema $schema         The schema object the tables are from
	 * @param  string  $table          The main table we are searching on behalf of
	 * @param  string  $related_table  The related table we are trying to find the routes for
	 * @param  string  $route          The route between the two tables
	 * @return boolean  If the table is in a one-to-one relationship with the related table over the route specified
	 */
	static public function isOneToOne($schema, $table, $related_table, $route=NULL)
	{
		$relationships = self::getRoutes($schema, $table, $related_table, 'one-to-one', $route);
		
		if ($route === NULL && sizeof($relationships) > 1) {
			throw new fProgrammerException(
				'There is more than one route for the%1$srelationship between %2$s and %3$s. Please specify one of the following: %4$s.',
				' one-to-one ',
				$table,
				$related_table,
				join(', ', array_keys($relationships))
			);
		}
		if (!$relationships) {
			return FALSE;	
		}
		
		foreach ($relationships as $relationship) {
			if ($route === NULL || $route == $relationship['column']) {
				return TRUE;
			} 		
		}
		
		return FALSE;
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
		self::$schema_objects = array();
	}
	
	
	/**
	 * Return the instance of the fSchema class
	 * 
	 * @param  string $class  The class the object will be used with
	 * @return fSchema  The schema instance
	 */
	static public function retrieve($class='fActiveRecord')
	{
		if (substr($class, 0, 5) == 'name:') {
			$database_name = substr($class, 5);
		} else {
			$database_name = fORM::getDatabaseName($class);
		}
		
		if (!isset(self::$schema_objects[$database_name])) {
			self::$schema_objects[$database_name] = new fSchema(fORMDatabase::retrieve($class));
		}
		
		return self::$schema_objects[$database_name];
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fORMSchema
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2007-2010 Will Bond <will@flourishlib.com>
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