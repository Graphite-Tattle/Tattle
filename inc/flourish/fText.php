<?php
/**
 * Provides internationlization support for strings
 * 
 * @copyright  Copyright (c) 2008-2009 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fText
 * 
 * @version    1.0.0b2
 * @changes    1.0.0b2  Updated ::compose() to more handle `$components` passed as an array [wb, 2009-02-05]
 * @changes    1.0.0b   The initial implementation [wb, 2008-11-12]
 */
class fText
{
	// The following constants allow for nice looking callbacks to static methods
	const compose                 = 'fText::compose';
	const registerComposeCallback = 'fText::registerComposeCallback';
	const reset                   = 'fText::reset';
	
	
	/**
	 * Callbacks for when messages are composed
	 * 
	 * @var array
	 */
	static private $compose_callbacks = array(
		'pre'  => array(),
		'post' => array()
	);
	
	
	/**
	 * Performs an [http://php.net/sprintf sprintf()] on a string and provides a hook for modifications such as internationalization
	 * 
	 * @param  string  $message    A message to compose
	 * @param  mixed   $component  A string or number to insert into the message
	 * @param  mixed   ...
	 * @return string  The composed message
	 */
	static public function compose($message)
	{
		if (self::$compose_callbacks) {
			foreach (self::$compose_callbacks['pre'] as $callback) {
				$message = call_user_func($callback, $message);
			}
		}
		
		$components = array_slice(func_get_args(), 1);
		
		// Handles components passed as an array
		if (sizeof($components) == 1 && is_array($components[0])) {
			$components = $components[0];	
		}
		
		$message = vsprintf($message, $components);
		
		if (self::$compose_callbacks) {
			foreach (self::$compose_callbacks['post'] as $callback) {
				$message = call_user_func($callback, $message);
			}
		}
		
		return $message;
	}
	
	
	/**
	 * Adds a callback for when a message is created using ::compose()
	 * 
	 * The primary purpose of these callbacks is for internationalization of
	 * error messaging in Flourish. The callback should accept a single
	 * parameter, the message being composed and should return the message
	 * with any modifications.
	 * 
	 * The timing parameter controls if the callback happens before or after
	 * the actual composition takes place, which is simply a call to
	 * [http://php.net/sprintf sprintf()]. Thus the message passed `'pre'`
	 * will always be exactly the same, while the message `'post'` will include
	 * the interpolated variables. Because of this, most of the time the `'pre'`
	 * timing should be chosen.
	 * 
	 * @param  string   $timing    When the callback should be executed - `'pre'` or `'post'` performing the actual composition
	 * @param  callback $callback  The callback
	 * @return void
	 */
	static public function registerComposeCallback($timing, $callback)
	{
		$valid_timings = array('pre', 'post');
		if (!in_array($timing, $valid_timings)) {
			throw new fProgrammerException(
				'The timing specified, %1$s, is not a valid timing. Must be one of: %2$s.',
				$timing,
				join(', ', $valid_timings)	
			);
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		self::$compose_callbacks[$timing][] = $callback;
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
		self::$compose_callbacks = array(
			'pre'  => array(),
			'post' => array()
		);
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fText
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