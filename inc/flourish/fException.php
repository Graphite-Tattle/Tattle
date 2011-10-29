<?php
/**
 * An exception that allows for easy l10n, printing, tracing and hooking
 * 
 * @copyright  Copyright (c) 2007-2009 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fException
 * 
 * @version    1.0.0b8
 * @changes    1.0.0b8  Added a missing line of backtrace to ::formatTrace() [wb, 2009-06-28]
 * @changes    1.0.0b7  Updated ::__construct() to no longer require a message, like the Exception class, and allow for non-integer codes [wb, 2009-06-26]
 * @changes    1.0.0b6  Fixed ::splitMessage() so that the original message is returned if no list items are found, added ::reorderMessage() [wb, 2009-06-02]
 * @changes    1.0.0b5  Added ::splitMessage() to replace fCRUD::removeListItems() and fCRUD::reorderListItems() [wb, 2009-05-08]
 * @changes    1.0.0b4  Added a check to ::__construct() to ensure that the `$code` parameter is numeric [wb, 2009-05-04]
 * @changes    1.0.0b3  Fixed a bug with ::printMessage() messing up some HTML messages [wb, 2009-03-27]
 * @changes    1.0.0b2  ::compose() more robustly handles `$components` passed as an array, ::__construct() now detects stray `%` characters [wb, 2009-02-05]
 * @changes    1.0.0b   The initial implementation [wb, 2007-06-14]
 */
abstract class fException extends Exception
{
	/**
	 * Callbacks for when exceptions are created
	 * 
	 * @var array
	 */
	static private $callbacks = array();
	
	
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
		$components = array_slice(func_get_args(), 1);
		
		// Handles components passed as an array
		if (sizeof($components) == 1 && is_array($components[0])) {
			$components = $components[0];	
		}
		
		// If fText is loaded, use it
		if (class_exists('fText', FALSE)) {
			return call_user_func_array(
				array('fText', 'compose'),
				array($message, $components)
			);
			
		} else {
			return vsprintf($message, $components);
		}
	}
	
	
	/**
	 * Creates a string representation of any variable using predefined strings for booleans, `NULL` and empty strings
	 * 
	 * The string output format of this method is very similar to the output of
	 * [http://php.net/print_r print_r()] except that the following values
	 * are represented as special strings:
	 *   
	 *  - `TRUE`: `'{true}'`
	 *  - `FALSE`: `'{false}'`
	 *  - `NULL`: `'{null}'`
	 *  - `''`: `'{empty_string}'`
	 * 
	 * @param  mixed $data  The value to dump
	 * @return string  The string representation of the value
	 */
	static protected function dump($data)
	{
		if (is_bool($data)) {
			return ($data) ? '{true}' : '{false}';
		
		} elseif (is_null($data)) {
			return '{null}';
		
		} elseif ($data === '') {
			return '{empty_string}';
		
		} elseif (is_array($data) || is_object($data)) {
			
			ob_start();
			var_dump($data);
			$output = ob_get_contents();
			ob_end_clean();
			
			// Make the var dump more like a print_r
			$output = preg_replace('#=>\n(  )+(?=[a-zA-Z]|&)#m', ' => ', $output);
			$output = str_replace('string(0) ""', '{empty_string}', $output);
			$output = preg_replace('#=> (&)?NULL#', '=> \1{null}', $output);
			$output = preg_replace('#=> (&)?bool\((false|true)\)#', '=> \1{\2}', $output);
			$output = preg_replace('#string\(\d+\) "#', '', $output);
			$output = preg_replace('#"(\n(  )*)(?=\[|\})#', '\1', $output);
			$output = preg_replace('#(?:float|int)\((-?\d+(?:.\d+)?)\)#', '\1', $output);
			$output = preg_replace('#((?:  )+)\["(.*?)"\]#', '\1[\2]', $output);
			$output = preg_replace('#(?:&)?array\(\d+\) \{\n((?:  )*)((?:  )(?=\[)|(?=\}))#', "Array\n\\1(\n\\1\\2", $output);
			$output = preg_replace('/object\((\w+)\)#\d+ \(\d+\) {\n((?:  )*)((?:  )(?=\[)|(?=\}))/', "\\1 Object\n\\2(\n\\2\\3", $output);
			$output = preg_replace('#^((?:  )+)}(?=\n|$)#m', "\\1)\n", $output);
			$output = substr($output, 0, -2) . ')';
			
			// Fix indenting issues with the var dump output
			$output_lines = explode("\n", $output);
			$new_output = array();
			$stack = 0;
			foreach ($output_lines as $line) {
				if (preg_match('#^((?:  )*)([^ ])#', $line, $match)) {
					$spaces = strlen($match[1]);
					if ($spaces && $match[2] == '(') {
						$stack += 1;
					}
					$new_output[] = str_pad('', ($spaces)+(4*$stack)) . $line;
					if ($spaces && $match[2] == ')') {
						$stack -= 1;
					}
				} else {
					$new_output[] = str_pad('', ($spaces)+(4*$stack)) . $line;
				}
			}
			
			return join("\n", $new_output);
			
		} else {
			return (string) $data;
		}
	}
	
	
	/**
	 * Adds a callback for when certain types of exceptions are created 
	 * 
	 * The callback will be called when any exception of this class, or any
	 * child class, specified is tossed. A single parameter will be passed
	 * to the callback, which will be the exception object.
	 * 
	 * @param  callback $callback        The callback
	 * @param  string   $exception_type  The type of exception to call the callback for
	 * @return void
	 */
	static public function registerCallback($callback, $exception_type=NULL)
	{
		if ($exception_type === NULL) {
			$exception_type = 'fException';	
		}
		
		if (!isset(self::$callbacks[$exception_type])) {
			self::$callbacks[$exception_type] = array();
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		self::$callbacks[$exception_type][] = $callback;
	}
	
	
	/**
	 * Compares the message matching strings by longest first so that the longest matches are made first
	 *
	 * @param  string $a  The first string to compare
	 * @param  string $b  The second string to compare
	 * @return integer  `-1` if `$a` is longer than `$b`, `0` if they are equal length, `1` if `$a` is shorter than `$b`
	 */
	static private function sortMatchingArray($a, $b)
	{
		return -1 * strnatcmp(strlen($a), strlen($b));
	}
	
	
	/**
	 * Sets the message for the exception, allowing for string interpolation and internationalization
	 * 
	 * The `$message` can contain any number of formatting placeholders for
	 * string and number interpolation via [http://php.net/sprintf `sprintf()`].
	 * Any `%` signs that do not appear to be part of a valid formatting
	 * placeholder will be automatically escaped with a second `%`.
	 * 
	 * The following aspects of valid `sprintf()` formatting codes are not
	 * accepted since they are redundant and restrict the non-formatting use of
	 * the `%` sign in exception messages:
	 *  - `% 2d`: Using a literal space as a padding character - a space will be used if no padding character is specified
	 *  - `%'.d`: Providing a padding character but no width - no padding will be applied without a width
	 * 
	 * @param  string $message    The message for the exception. This accepts a subset of [http://php.net/sprintf `sprintf()`] strings - see method description for more details.
	 * @param  mixed  $component  A string or number to insert into the message
	 * @param  mixed  ...
	 * @param  mixed  $code       The exception code to set
	 * @return fException
	 */
	public function __construct($message='')
	{
		$args          = array_slice(func_get_args(), 1);
		$required_args = preg_match_all(
			'/
				(?<!%)                       # Ensure this is not an escaped %
				%(                           # The leading %
				  (?:\d+\$)?                 # Position
				  \+?                        # Sign specifier
				  (?:(?:0|\'.)?-?\d+|-?)     # Padding, alignment and width or just alignment
				  (?:\.\d+)?				 # Precision
				  [bcdeufFosxX]              # Type
				)/x',
			$message,
			$matches
		);
		
		// Handle %s that weren't properly escaped
		$formats    = $matches[1];
		$delimeters = ($formats) ? array_fill(0, sizeof($formats), '#') : array();
		$lookahead  = join(
			'|',
			array_map(
				'preg_quote',
				$formats,
				$delimeters 
			)
		);
		$lookahead  = ($lookahead) ? '|' . $lookahead : '';
		$message    = preg_replace('#(?<!%)%(?!%' . $lookahead . ')#', '%%', $message);	
		
		// If we have an extra argument, it is the exception code
		$code = NULL;
		if ($required_args == sizeof($args) - 1) {
			$code = array_pop($args);		
		}
		
		if (sizeof($args) != $required_args) {
			$message = self::compose(
				'%1$d components were passed to the %2$s constructor, while %3$d were specified in the message',
				sizeof($args),
				get_class($this),
				$required_args
			);
			throw new Exception($message);	
		}
		
		$args = array_map(array('fException', 'dump'), $args);
		
		parent::__construct(self::compose($message, $args));
		$this->code = $code;
		
		foreach (self::$callbacks as $class => $callbacks) {
			foreach ($callbacks as $callback) {
				if ($this instanceof $class) {
					call_user_func($callback, $this);
				}
			}
		}		
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
	 * Gets the backtrace to currently called exception
	 * 
	 * @return string  A nicely formatted backtrace to this exception
	 */
	public function formatTrace()
	{
		$doc_root  = realpath($_SERVER['DOCUMENT_ROOT']);
		$doc_root .= (substr($doc_root, -1) != DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';
		
		$backtrace = explode("\n", $this->getTraceAsString());
		array_unshift($backtrace, $this->file . '(' . $this->line . ')');
		$backtrace = preg_replace('/^#\d+\s+/', '', $backtrace);
		$backtrace = str_replace($doc_root, '{doc_root}' . DIRECTORY_SEPARATOR, $backtrace);
		$backtrace = array_diff($backtrace, array('{main}'));
		$backtrace = array_reverse($backtrace);
		
		return join("\n", $backtrace);
	}
	
	
	/**
	 * Returns the CSS class name for printing information about the exception
	 * 
	 * @return void
	 */
	protected function getCSSClass()
	{
		$string = preg_replace('#^f#', '', get_class($this));
		
		do {
			$old_string = $string;
			$string = preg_replace('/([a-zA-Z])([0-9])/', '\1_\2', $string);
			$string = preg_replace('/([a-z0-9A-Z])([A-Z])/', '\1_\2', $string);
		} while ($old_string != $string);
		
		return strtolower($string);
	}
	
	
	/**
	 * Prepares content for output into HTML
	 * 
	 * @return string  The prepared content
	 */
	protected function prepare($content)
	{
		// See if the message has newline characters but not br tags, extracted from fHTML to reduce dependencies
		static $inline_tags_minus_br = '<a><abbr><acronym><b><big><button><cite><code><del><dfn><em><font><i><img><input><ins><kbd><label><q><s><samp><select><small><span><strike><strong><sub><sup><textarea><tt><u><var>';
		$content_with_newlines = (strip_tags($content, $inline_tags_minus_br)) ? $content : nl2br($content);
		
		// Check to see if we have any block-level html, extracted from fHTML to reduce dependencies
		$inline_tags = $inline_tags_minus_br . '<br>';
		$no_block_html = strip_tags($content, $inline_tags) == $content;
		
		// This code ensures the output is properly encoded for display in (X)HTML, extracted from fHTML to reduce dependencies
		$reg_exp = "/<\s*\/?\s*[\w:]+(?:\s+[\w:]+(?:\s*=\s*(?:\"[^\"]*?\"|'[^']*?'|[^'\">\s]+))?)*\s*\/?\s*>|&(?:#\d+|\w+);|<\!--.*?-->/";
		preg_match_all($reg_exp, $content, $html_matches, PREG_SET_ORDER);
		$text_matches = preg_split($reg_exp, $content_with_newlines);
		
		foreach($text_matches as $key => $value) {
			$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		}
		
		for ($i = 0; $i < sizeof($html_matches); $i++) {
			$text_matches[$i] .= $html_matches[$i][0];
		}
		
		$content_with_newlines = implode($text_matches);
		
		$output  = ($no_block_html) ? '<p>' : '';
		$output .= $content_with_newlines;
		$output .= ($no_block_html) ? '</p>' : '';
		
		return $output;
	}
	
	
	/**
	 * Prints the message inside of a div with the class being 'exception %THIS_EXCEPTION_CLASS_NAME%'
	 * 
	 * @return void
	 */
	public function printMessage()
	{
		echo '<div class="exception ' . $this->getCSSClass() . '">';
		echo $this->prepare($this->message);
		echo '</div>';
	}
	
	
	/**
	 * Prints the backtrace to currently called exception inside of a pre tag with the class being 'exception %THIS_EXCEPTION_CLASS_NAME% trace'
	 * 
	 * @return void
	 */
	public function printTrace()
	{
		echo '<pre class="exception ' . $this->getCSSClass() . ' trace">';
		echo $this->formatTrace();
		echo '</pre>';
	}
	
	
	/**
	 * Reorders list items in the message based on simple string matching
	 * 
	 * @param  string $match  This should be a string to match to one of the list items - whatever the order this is in the parameter list will be the order of the list item in the adjusted message
	 * @param  string ...
	 * @return fException  The exception object, to allow for method chaining
	 */
	public function reorderMessage($match)
	{
		// If we can't find a list, don't bother continuing
		if (!preg_match('#^(.*<(?:ul|ol)[^>]*?>)(.*?)(</(?:ul|ol)>.*)$#isD', $this->message, $message_parts)) {
			return $this;
		}
		
		$matching_array = func_get_args();
		// This ensures that we match on the longest string first
		uasort($matching_array, array('self', 'sortMatchingArray'));
		
		$beginning     = $message_parts[1];
		$list_contents = $message_parts[2];
		$ending        = $message_parts[3];
		
		preg_match_all('#<li(.*?)</li>#i', $list_contents, $list_items, PREG_SET_ORDER);
		
		$ordered_items = array_fill(0, sizeof($matching_array), array());
		$other_items   = array();
		
		foreach ($list_items as $list_item) {
			foreach ($matching_array as $num => $match_string) {
				if (strpos($list_item[1], $match_string) !== FALSE) {
					$ordered_items[$num][] = $list_item[0];
					continue 2;
				}
			}
			
			$other_items[] = $list_item[0];
		}
		
		$final_list = array();
		foreach ($ordered_items as $ordered_item) {
			$final_list = array_merge($final_list, $ordered_item);
		}
		$final_list = array_merge($final_list, $other_items);
		
		$this->message = $beginning . join("\n", $final_list) . $ending;
		
		return $this;
	}
	
	
	/**
	 * Allows the message to be overwriten
	 * 
	 * @param  string $new_message  The new message for the exception
	 * @return void
	 */
	public function setMessage($new_message)
	{
		$this->message = $new_message;
	}
	
	
	/**
	 * Splits an exception with an HTML list into multiple strings each containing part of the original message
	 * 
	 * This method should be called with two or more parameters of arrays of
	 * string to match. If any of the provided strings are matching in a list
	 * item in the exception message, a new copy of the message will be created
	 * containing just the matching list items.
	 * 
	 * Here is an exception message to be split:
	 * 
	 * {{{
	 * #!html
	 * <p>The following problems were found:</p>
	 * <ul>
	 *     <li>First Name: Please enter a value</li>
	 *     <li>Last Name: Please enter a value</li>
	 *     <li>Email: Please enter a value</li>
	 *     <li>Address: Please enter a value</li>
	 *     <li>City: Please enter a value</li>
	 *     <li>State: Please enter a value</li>
	 *     <li>Zip Code: Please enter a value</li>
	 * </ul>
	 * }}}
	 * 
	 * The following PHP would split the exception into two messages:
	 * 
	 * {{{
	 * #!php
	 * list ($name_exception, $address_exception) = $exception->splitMessage(
	 *     array('First Name', 'Last Name', 'Email'),
	 *     array('Address', 'City', 'State', 'Zip Code')
	 * );
	 * }}}
	 * 
	 * The resulting messages would be:
	 * 
	 * {{{
	 * #!html
	 * <p>The following problems were found:</p>
	 * <ul>
	 *     <li>First Name: Please enter a value</li>
	 *     <li>Last Name: Please enter a value</li>
	 *     <li>Email: Please enter a value</li>
	 * </ul>
	 * }}}
	 * 
	 * and
	 * 
	 * {{{
	 * #!html
	 * <p>The following problems were found:</p>
	 * <ul>
	 *     <li>Address: Please enter a value</li>
	 *     <li>City: Please enter a value</li>
	 *     <li>State: Please enter a value</li>
	 *     <li>Zip Code: Please enter a value</li>
	 * </ul>
	 * }}}
	 * 
	 * If no list items match the strings in a parameter, the result will be
	 * an empty string, allowing for simple display:
	 * 
	 * {{{
	 * #!php
	 * fHTML::show($name_exception, 'error');
	 * }}}
	 * 
	 * An empty string is returned when none of the list items matched the
	 * strings in the parameter. If no list items are found, the first value in
	 * the returned array will be the existing message and all other array
	 * values will be an empty string.
	 * 
	 * @param  array $list_item_matches  An array of strings to filter the list items by, list items will be ordered in the same order as this array
	 * @param  array ...
	 * @return array  This will contain an array of strings corresponding to the parameters passed - see method description for details
	 */
	public function splitMessage($list_item_matches)
	{
		$class = get_class($this);
		
		$matching_arrays = func_get_args();
		
		if (!preg_match('#^(.*<(?:ul|ol)[^>]*?>)(.*?)(</(?:ul|ol)>.*)$#isD', $this->message, $matches)) {
			return array_merge(array($this->message), array_fill(0, sizeof($matching_arrays)-1, ''));
		}
		
		$beginning_html  = $matches[1];
		$list_items_html = $matches[2];
		$ending_html     = $matches[3];
		
		preg_match_all('#<li(.*?)</li>#i', $list_items_html, $list_items, PREG_SET_ORDER);
		
		$output = array();
		
		foreach ($matching_arrays as $matching_array) {
			
			// This ensures that we match on the longest string first
			uasort($matching_array, array('self', 'sortMatchingArray'));
			
			// We may match more than one list item per matching string, so we need a multi-dimensional array to hold them
			$matched_list_items = array_fill(0, sizeof($matching_array), array());
			$found              = FALSE;
			
			foreach ($list_items as $list_item) {
				foreach ($matching_array as $match_num => $matching_string) {
					if (strpos($list_item[1], $matching_string) !== FALSE) {
						$matched_list_items[$match_num][] = $list_item[0];
						$found = TRUE;
						continue 2;
					}
				}
			}
			
			if (!$found) {
				$output[] = '';
				continue;
			}
			
			// This merges all of the multi-dimensional arrays back to one so we can do a simple join
			$merged_list_items = array();
			foreach ($matched_list_items as $match_num => $matched_items) {
				$merged_list_items = array_merge($merged_list_items, $matched_items);
			}
			
			$output[] = $beginning_html . join("\n", $merged_list_items) . $ending_html;
		}
		
		return $output;
	}
}



/**
 * Copyright (c) 2007-2009 Will Bond <will@flourishlib.com>
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