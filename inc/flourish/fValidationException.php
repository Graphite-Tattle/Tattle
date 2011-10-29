<?php
/**
 * An exception caused by a data not matching a rule or set of rules
 * 
 * @copyright  Copyright (c) 2007-2010 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fValidationException
 * 
 * @version    1.0.0b4
 * @changes    1.0.0b4  Added support for nested error arrays [wb-imarc, 2010-10-03]
 * @changes    1.0.0b3  Added ::removeFieldNames() [wb, 2010-05-26]
 * @changes    1.0.0b2  Added a custom ::__construct() to handle arrays of messages [wb, 2009-09-17]
 * @changes    1.0.0b   The initial implementation [wb, 2007-06-14]
 */
class fValidationException extends fExpectedException
{
	const formatField      = 'fValidationException::formatField';
	const removeFieldNames = 'fValidationException::removeFieldNames';
	const setFieldFormat   = 'fValidationException::setFieldFormat';
	
	/**
	 * The formatting string to use for field names
	 * 
	 * @var string
	 */
	static protected $field_format = '%s: ';
	
	
	/**
	 * Accepts a field name and formats it based on the formatting string set via ::setFieldFormat()
	 * 
	 * @param string $field  The name of the field to format
	 * @return string  The formatted field name
	 */
	static public function formatField($field)
	{
		return sprintf(self::$field_format, $field);	
	}
	
	
	/**
	 * Removes the field names from normal validation messages, leaving just the message part
	 * 
	 * @param array $messages  The messages to remove the field names from
	 * @return array  The messages without field names
	 */
	static public function removeFieldNames($messages)
	{
		$token_field   = self::formatField('__TOKEN__');
		$replace_regex = '#^' . str_replace('__TOKEN__', '(.*?)', preg_quote($token_field, '#')) . '#';
		
		$output = array();
		foreach ($messages as $column => $message) {
			if (is_array($message)) {
				$message['errors'] = self::removeFieldNames($message['errors']);
				$output[$column] = $message;
			} else {
				$output[$column] = preg_replace($replace_regex, '', $message);
			}
		}
		
		return $output;
	}
	
	
	/**
	* Set the format to be applied to all field names used in fValidationExceptions
	* 
	* The format should contain exactly one `%s`
	* [http://php.net/sprintf sprintf()] conversion specification, which will
	* be replaced with the field name. Any literal `%` characters should be
	* written as `%%`.
	* 
	* The default format is just `%s: `, which simply inserts a `:` and space
	* after the field name.
	* 
	* @param string $format  A string to format the field name with - `%s` will be replaced with the field name
	* @return void
	*/
	static public function setFieldFormat($format)
	{
		if (substr_count(str_replace('%%', '', $format), '%') != 1 || strpos($format, '%s') === FALSE) {
			throw new fProgrammerException(
				'The format, %s, has more or less than exactly one %%s sprintf() conversion specification',
				$format
			);	
		}
		self::$field_format = $format;
	}
	
	
	/**
	 * Sets the message for the exception, allowing for custom formatting beyond fException
	 * 
	 * If this method receives exactly two parameters, a string and an array,
	 * the string will be used as a message in a HTML `<p>` tag and the array
	 * will be turned into an unorder list `<ul>` tag with each element in the
	 * array being an `<li>` tag. It is possible to pass an optional exception
	 * code as a third parameter.
	 * 
	 * The following PHP:
	 * 
	 * {{{
	 * #!php
	 * throw new fValidationException(
	 *     'The following problems were found:',
	 *     array(
	 *         'Please provide your name',
	 *         'Please provide your email address'
	 *     )
	 * );
	 * }}}
	 * 
	 * Would create the message:
	 * 
	 * {{{
	 * #!text/html
	 * <p>The following problems were found:</p>
	 * <ul>
	 *     <li>Please provide your name</li>
	 *     <li>Please provide your email address</li>
	 * </ul>
	 * }}}
	 * 
	 * If the parameters are anything else, they will be passed to
	 * fException::__construct().
	 * 
	 * @param  string $message       The beginning message for the exception. This will be placed in a `<p>` tag.
	 * @param  array  $sub_messages  An array of strings to place in a `<ul>` tag
	 * @param  mixed  $code          The optional exception code
	 * @return fException
	 */
	public function __construct($message='')
	{
		$params = func_get_args();
		
		if ((count($params) == 2 || count($params) == 3) && is_string($params[0]) && is_array($params[1])) {
			
			
			$message = sprintf(
				"<p>%1\$s</p>\n<ul>\n<li>%2\$s</li>\n</ul>",
				self::compose($params[0]),
				join("</li>\n<li>", $this->formatErrorArray($params[1]))
			);
			
			$params = array_merge(
				// This escapes % signs since fException is going to look for sprintf formatting codes
				array(str_replace('%', '%%', $message)),
				// This grabs the exception code if one is defined
				array_slice($params, 2)
			);		
		}
		
		call_user_func_array(
			array($this, 'fException::__construct'),
			$params
		);		
	}
	
	
	/**
	 * Takes an error array that may or may not be nested and returns a HTML string representation 
	 * 
	 * @param  array $errors  An array of (possibly nested) child record errors
	 * @return array  An array of string error messages
	 */
	private function formatErrorArray($errors)
	{
		$new_errors = array();
		foreach ($errors as $error) {
			if (!is_array($error)) {
				$new_errors[] = $error;	
			} else {
				$new_errors[] = sprintf(
					"<span>%1\$s</span>\n<ul>\n<li>%2\$s</li>\n</ul>",
					$error['name'],
					join("</li>\n<li>", $this->formatErrorArray($error['errors']))
				);
			}
		}
		return $new_errors;
	}
}



/**
 * Copyright (c) 2007-2010 Will Bond <will@flourishlib.com>, others
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