<?php
/**
 * Provides money functionality for fActiveRecord classes
 * 
 * @copyright  Copyright (c) 2008-2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Dan Collins, iMarc LLC [dc-imarc] <dan@imarc.net>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fORMMoney
 * 
 * @version    1.0.0b11
 * @changes    1.0.0b11  Fixed the generation of validation messages when a non-monetary value is supplied [wb, 2011-05-17]
 * @changes    1.0.0b10  Updated code to work with the new fORM API [wb, 2010-08-06]
 * @changes    1.0.0b9   Added the `$remove_zero_fraction` parameter to prepare methods [wb, 2010-06-09]
 * @changes    1.0.0b8   Changed validation messages array to use column name keys [wb, 2010-05-26]
 * @changes    1.0.0b7   Fixed the `set` methods to return the record object in order to be consistent with all other `set` methods [wb, 2010-03-15]
 * @changes    1.0.0b6   Fixed duplicate validation messages and fProgrammerException object being thrown when NULL is set [dc-imarc+wb, 2010-03-03]
 * @changes    1.0.0b5   Updated code for the new fORMDatabase and fORMSchema APIs [wb, 2009-10-28]
 * @changes    1.0.0b4   Updated to use new fORM::registerInspectCallback() method [wb, 2009-07-13]
 * @changes    1.0.0b3   Updated code to use new fValidationException::formatField() method [wb, 2009-06-04]  
 * @changes    1.0.0b2   Fixed bugs with objectifying money columns [wb, 2008-11-24]
 * @changes    1.0.0b    The initial implementation [wb, 2008-09-05]
 */
class fORMMoney
{
	// The following constants allow for nice looking callbacks to static methods
	const configureMoneyColumn       = 'fORMMoney::configureMoneyColumn';
	const encodeMoneyColumn          = 'fORMMoney::encodeMoneyColumn';
	const inspect                    = 'fORMMoney::inspect';
	const makeMoneyObjects           = 'fORMMoney::makeMoneyObjects';
	const objectifyMoney             = 'fORMMoney::objectifyMoney';
	const objectifyMoneyWithCurrency = 'fORMMoney::objectifyMoneyWithCurrency';
	const prepareMoneyColumn         = 'fORMMoney::prepareMoneyColumn';
	const reflect                    = 'fORMMoney::reflect';
	const reset                      = 'fORMMoney::reset';
	const setCurrencyColumn          = 'fORMMoney::setCurrencyColumn';
	const setMoneyColumn             = 'fORMMoney::setMoneyColumn';
	const validateMoneyColumns       = 'fORMMoney::validateMoneyColumns';
	
	
	/**
	 * Columns that store currency information for a money column
	 * 
	 * @var array
	 */
	static private $currency_columns = array();
	
	/**
	 * Columns that should be formatted as money
	 * 
	 * @var array
	 */
	static private $money_columns = array();
	
	
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
	 * Sets a column to be formatted as an fMoney object
	 * 
	 * @param  mixed  $class            The class name or instance of the class to set the column format
	 * @param  string $column           The column to format as an fMoney object
	 * @param  string $currency_column  If specified, this column will store the currency of the fMoney object
	 * @return void
	 */
	static public function configureMoneyColumn($class, $column, $currency_column=NULL)
	{
		$class     = fORM::getClass($class);
		$table     = fORM::tablize($class);
		$schema    = fORMSchema::retrieve($class);
		$data_type = $schema->getColumnInfo($table, $column, 'type');
		
		$valid_data_types = array('float');
		if (!in_array($data_type, $valid_data_types)) {
			throw new fProgrammerException(
				'The column specified, %1$s, is a %2$s column. Must be %3$s to be set as a money column.',
				$column,
				$data_type,
				join(', ', $valid_data_types)
			);
		}
		
		if ($currency_column !== NULL) {
			$currency_column_data_type = $schema->getColumnInfo($table, $currency_column, 'type');
			$valid_currency_column_data_types = array('varchar', 'char', 'text');
			if (!in_array($currency_column_data_type, $valid_currency_column_data_types)) {
				throw new fProgrammerException(
					'The currency column specified, %1$s, is a %2$s column. Must be %3$s to be set as a currency column.',
					$currency_column,
					$currency_column_data_type,
					join(', ', $valid_currency_column_data_types)
				);
			}
		}
		
		$camelized_column = fGrammar::camelize($column, TRUE);
		
		fORM::registerActiveRecordMethod(
			$class,
			'encode' . $camelized_column,
			self::encodeMoneyColumn
		);
		
		fORM::registerActiveRecordMethod(
			$class,
			'prepare' . $camelized_column,
			self::prepareMoneyColumn
		);
		
		if (!fORM::checkHookCallback($class, 'post::validate()', self::validateMoneyColumns)) {
			fORM::registerHookCallback($class, 'post::validate()', self::validateMoneyColumns);
		}
		
		fORM::registerReflectCallback($class, self::reflect);
		fORM::registerInspectCallback($class, $column, self::inspect);
		
		$value = FALSE;
		
		if ($currency_column) {
			$value = $currency_column;	
			
			if (empty(self::$currency_columns[$class])) {
				self::$currency_columns[$class] = array();
			}
			self::$currency_columns[$class][$currency_column] = $column;
			
			if (!fORM::checkHookCallback($class, 'post::loadFromResult()', self::makeMoneyObjects)) {
				fORM::registerHookCallback($class, 'post::loadFromResult()', self::makeMoneyObjects);
			}
			
			if (!fORM::checkHookCallback($class, 'pre::validate()', self::makeMoneyObjects)) {
				fORM::registerHookCallback($class, 'pre::validate()', self::makeMoneyObjects);
			}
			
			fORM::registerActiveRecordMethod(
				$class,
				'set' . $camelized_column,
				self::setMoneyColumn
			);
			
			fORM::registerActiveRecordMethod(
				$class,
				'set' . fGrammar::camelize($currency_column, TRUE),
				self::setCurrencyColumn
			);
		
		} else {
			fORM::registerObjectifyCallback($class, $column, self::objectifyMoney);
		}
		
		if (empty(self::$money_columns[$class])) {
			self::$money_columns[$class] = array();
		}
		
		self::$money_columns[$class][$column] = $value;
	}
	
	
	/**
	 * Encodes a money column by calling fMoney::__toString()
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
	 * @return string  The encoded monetary value
	 */
	static public function encodeMoneyColumn($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		list ($action, $subject) = fORM::parseMethod($method_name);
		
		$column = fGrammar::underscorize($subject);
		$value  = $values[$column];
		
		if ($value instanceof fMoney) {
			$value = $value->__toString();
		}
		
		return fHTML::prepare($value);
	}
	
	
	/**
	 * Adds metadata about features added by this class
	 * 
	 * @internal
	 * 
	 * @param  string $class      The class being inspected
	 * @param  string $column     The column being inspected
	 * @param  array  &$metadata  The array of metadata about a column
	 * @return void
	 */
	static public function inspect($class, $column, &$metadata)
	{
		unset($metadata['auto_increment']);
		$metadata['feature'] = 'money';
	}
	
	
	/**
	 * Makes fMoney objects for all money columns in the object that also have a currency column
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
	static public function makeMoneyObjects($object, &$values, &$old_values, &$related_records, &$cache)
	{
		$class = get_class($object);
		
		if (!isset(self::$currency_columns[$class])) {
			return;	
		}
		
		foreach(self::$currency_columns[$class] as $currency_column => $value_column) {
			self::objectifyMoneyWithCurrency($values, $old_values, $value_column, $currency_column);
		}	
	}
	
	
	/**
	 * Turns a monetary value into an fMoney object
	 * 
	 * @internal
	 * 
	 * @param  string $class   The class this value is for
	 * @param  string $column  The column the value is in
	 * @param  mixed  $value   The value
	 * @return mixed  The fMoney object or raw value
	 */
	static public function objectifyMoney($class, $column, $value)
	{
		if ((!is_string($value) && !is_numeric($value) && !is_object($value)) || !strlen(trim($value))) {
			return $value;
		}
		
		try {
			return new fMoney($value);
			 
		// If there was some error creating the money object, just return the raw value
		} catch (fExpectedException $e) {
			return $value;
		}
	}
	
	
	/**
	 * Turns a monetary value into an fMoney object with a currency specified by another column
	 * 
	 * @internal
	 * 
	 * @param  array  &$values          The current values
	 * @param  array  &$old_values      The old values
	 * @param  string $value_column     The column holding the value
	 * @param  string $currency_column  The column holding the currency code
	 * @return void
	 */
	static public function objectifyMoneyWithCurrency(&$values, &$old_values, $value_column, $currency_column)
	{
		if ((!is_string($values[$value_column]) && !is_numeric($values[$value_column]) && !is_object($values[$value_column])) || !strlen(trim($values[$value_column]))) {
			return;
		}
			
		try {
			$value = $values[$value_column];
			if ($value instanceof fMoney) {
				$value = $value->__toString();	
			}
			
			$currency = $values[$currency_column];
			if (!$currency && $currency !== '0' && $currency !== 0) {
				$currency = NULL;	
			}
			
			$value = new fMoney($value, $currency);
			 
			if (fActiveRecord::hasOld($old_values, $currency_column) && !fActiveRecord::hasOld($old_values, $value_column)) {
				fActiveRecord::assign($values, $old_values, $value_column, $value);		
			} else {
				$values[$value_column] = $value;
			}
			
			if ($values[$currency_column] === NULL) {
				fActiveRecord::assign($values, $old_values, $currency_column, $value->getCurrency());
			}
			 
		// If there was some error creating the money object, we just leave all values alone
		} catch (fExpectedException $e) { }	
	}
	
	
	/**
	 * Prepares a money column by calling fMoney::format()
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
	 * @return string  The formatted monetary value
	 */
	static public function prepareMoneyColumn($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		list ($action, $subject) = fORM::parseMethod($method_name);
		
		$column = fGrammar::underscorize($subject);
		if (empty($values[$column])) {
			return $values[$column];
		}
		$value = $values[$column];
		
		$remove_zero_fraction = FALSE;
		if (count($parameters)) {
			$remove_zero_fraction = $parameters[0];
		}
		
		if ($value instanceof fMoney) {
			$value = $value->format($remove_zero_fraction);
		}
		
		return fHTML::prepare($value);
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
		if (!isset(self::$money_columns[$class])) {
			return;	
		}
		
		foreach(self::$money_columns[$class] as $column => $enabled) {
			$camelized_column = fGrammar::camelize($column, TRUE);
			
			// Get and set methods
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Gets the current value of " . $column . "\n";
				$signature .= " * \n";
				$signature .= " * @return fMoney  The current value\n";
				$signature .= " */\n";
			}
			$get_method = 'get' . $camelized_column;
			$signature .= 'public function ' . $get_method . '()';
			
			$signatures[$get_method] = $signature;
			
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Sets the value for " . $column . "\n";
				$signature .= " * \n";
				$signature .= " * @param  fMoney|string|integer \$" . $column . "  The new value - a string or integer will be converted to the default currency (if defined)\n";
				$signature .= " * @return fActiveRecord  The record object, to allow for method chaining\n";
				$signature .= " */\n";
			}
			$set_method = 'set' . $camelized_column;
			$signature .= 'public function ' . $set_method . '($' . $column . ')';
			
			$signatures[$set_method] = $signature;
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Encodes the value of " . $column . " for output into an HTML form\n";
				$signature .= " * \n";
				$signature .= " * If the value is an fMoney object, the ->__toString() method will be called\n";
				$signature .= " * resulting in the value minus the currency symbol and thousands separators\n";
				$signature .= " * \n";
				$signature .= " * @return string  The HTML form-ready value\n";
				$signature .= " */\n";
			}
			$encode_method = 'encode' . $camelized_column;
			$signature .= 'public function ' . $encode_method . '()';
			
			$signatures[$encode_method] = $signature;
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Prepares the value of " . $column . " for output into HTML\n";
				$signature .= " * \n";
				$signature .= " * If the value is an fMoney object, the ->format() method will be called\n";
				$signature .= " * resulting in the value including the currency symbol and thousands separators\n";
				$signature .= " * \n";
				$signature .= " * @param  boolean \$remove_zero_fraction  If a fraction of all zeros should be removed\n";
				$signature .= " * @return string  The HTML-ready value\n";
				$signature .= " */\n";
			}
			$prepare_method = 'prepare' . $camelized_column;
			$signature .= 'public function ' . $prepare_method . '($remove_zero_fraction=FALSE)';
			
			$signatures[$prepare_method] = $signature;
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
		self::$currency_columns = array();
		self::$money_columns    = array();
	}
	
	
	/**
	 * Sets the currency column and then tries to objectify the related money column
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
	 * @return fActiveRecord  The record object, to allow for method chaining
	 */
	static public function setCurrencyColumn($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		list ($action, $subject) = fORM::parseMethod($method_name);
		
		$column = fGrammar::underscorize($subject);
		$class  = get_class($object);
		
		if (count($parameters) < 1) {
			throw new fProgrammerException(
				'The method, %s(), requires at least one parameter',
				$method_name
			);	
		}
		
		fActiveRecord::assign($values, $old_values, $column, $parameters[0]);
		
		// See if we can make an fMoney object out of the values
		self::objectifyMoneyWithCurrency(
			$values,
			$old_values,
			self::$currency_columns[$class][$column],
			$column
		);
		
		return $object;
	}
	
	
	/**
	 * Sets the money column and then tries to objectify it with an related currency column
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
	 * @return fActiveRecord  The record object, to allow for method chaining
	 */
	static public function setMoneyColumn($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		list ($action, $subject) = fORM::parseMethod($method_name);
		
		$column = fGrammar::underscorize($subject);
		$class  = get_class($object);
		
		if (count($parameters) < 1) {
			throw new fProgrammerException(
				'The method, %s(), requires at least one parameter',
				$method_name
			);	
		}
		
		$value = $parameters[0];
		
		fActiveRecord::assign($values, $old_values, $column, $value);
		
		$currency_column = self::$money_columns[$class][$column];
		
		// See if we can make an fMoney object out of the values
		self::objectifyMoneyWithCurrency($values, $old_values, $column, $currency_column);
		
		if ($currency_column) {
			if ($value instanceof fMoney) {
				fActiveRecord::assign($values, $old_values, $currency_column, $value->getCurrency());
			}	
		}
		
		return $object;
	}
	
	
	/**
	 * Validates all money columns
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
	static public function validateMoneyColumns($object, &$values, &$old_values, &$related_records, &$cache, &$validation_messages)
	{
		$class = get_class($object);
		
		if (empty(self::$money_columns[$class])) {
			return;
		}
		
		foreach (self::$money_columns[$class] as $column => $currency_column) {
			if ($values[$column] instanceof fMoney || $values[$column] === NULL) {
				continue;
			}
			
			// Remove any previous validation warnings
			unset($validation_messages[$column]);
			
			if ($currency_column && !in_array($values[$currency_column], fMoney::getCurrencies())) {
				$validation_messages[$currency_column] = self::compose(
					'%sThe currency specified is invalid',
					fValidationException::formatField(fORM::getColumnName($class, $currency_column))
				);	
				
			} else {
				$validation_messages[$column] = self::compose(
					'%sPlease enter a monetary value',
					fValidationException::formatField(fORM::getColumnName($class, $column))
				);
			}
		}
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fORMMoney
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2008-2011 Will Bond <will@flourishlib.com>, others
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
