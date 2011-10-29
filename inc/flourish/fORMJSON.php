<?php
/**
 * Adds JSON functionality to fActiveRecord and fRecordSet
 * 
 * @copyright  Copyright (c) 2008-2009 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fORMJSON
 * 
 * @version    1.0.0b3
 * @changes    1.0.0b3  Removed the `$pointer` parameter from ::toJSONRecordSet() since fRecordSet no longer has a pointer [wb, 2010-09-28]
 * @changes    1.0.0b2  Updated the code to remove the `$associate` parameter for the record set method callback [wb, 2009-06-02]
 * @changes    1.0.0b   The initial implementation [wb, 2008-06-25]
 */
class fORMJSON
{
	// The following constants allow for nice looking callbacks to static methods
	const extend          = 'fORMJSON::extend';
	const reflect         = 'fORMJSON::reflect';
	const toJSON          = 'fORMJSON::toJSON';
	const toJSONRecordSet = 'fORMJSON::toJSONRecordSet';
	
	
	/**
	 * Adds the method `toJSON()` to fActiveRecord and fRecordSet instances
	 * 
	 * @return void
	 */
	static public function extend()
	{
		fORM::registerReflectCallback(
			'*',
			self::reflect
		);
		
		fORM::registerActiveRecordMethod(
			'*',
			'toJSON',
			self::toJSON
		);
		
		fORM::registerRecordSetMethod(
			'toJSON',
			self::toJSONRecordSet
		);
	}
	
	
	/**
	 * Adjusts the fActiveRecord::reflect() signatures of columns that have been added by this class
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
		$signature = '';
		if ($include_doc_comments) {
			$signature .= "/**\n";
			$signature .= " * Converts the values from the record into a JSON object\n";
			$signature .= " * \n";
			$signature .= " * @return string  The JSON object representation of this record\n";
			$signature .= " */\n";
		}
		$signature .= 'public function toJSON()';
		
		$signatures['toJSON'] = $signature;
	}
	
	
	/**
	 * Returns a JSON object representation of the record
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
	 * @return string  The JSON object that represents the values of this record
	 */
	static public function toJSON($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		$output = array();
		foreach ($values as $column => $value) {
			if (is_object($value) && is_callable(array($value, '__toString'))) {
				$value = $value->__toString();
			} elseif (is_object($value)) {
				$value = (string) $value;	
			}
			$output[$column] = $value;
		}
		
		return fJSON::encode($output);
	}
	
	
	/**
	 * Returns a JSON object representation of a record set
	 * 
	 * @internal
	 * 
	 * @param  fRecordSet $record_set  The fRecordSet instance
	 * @param  string     $class       The class of the records
	 * @param  array      &$records    The fActiveRecord objects
	 * @return string  The JSON object that represents an array of all of the fActiveRecord objects
	 */
	static public function toJSONRecordSet($record_set, $class, &$records)
	{
		return '[' . join(',', $record_set->call('toJSON')) . ']';	
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fORMJSON
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2008-2009 Will Bond <will@flourishlib.com>
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