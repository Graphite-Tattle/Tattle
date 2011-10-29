<?php
/**
 * Provides a single, simplified interface for [http://php.net/outcontrol output buffering] to prevent nested buffering issues and provide a more logical API
 * 
 * @copyright  Copyright (c) 2008-2010 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fBuffer
 * 
 * @version    1.0.0b3
 * @changes    1.0.0b3  Added a check to ensure the zlib extension is installd when doing gzipped buffering [wb, 2010-05-20]
 * @changes    1.0.0b2  Added the `$gzip` parameter to ::start() [wb, 2010-05-19]
 * @changes    1.0.0b   The initial implementation [wb, 2008-03-16]
 */
class fBuffer
{
	// The following constants allow for nice looking callbacks to static methods
	const erase        = 'fBuffer::erase';
	const get          = 'fBuffer::get';
	const isStarted    = 'fBuffer::isStarted';
	const replace      = 'fBuffer::replace';
	const reset        = 'fBuffer::reset';
	const start        = 'fBuffer::start';
	const startCapture = 'fBuffer::startCapture';
	const stop         = 'fBuffer::stop';
	const stopCapture  = 'fBuffer::stopCapture';
	
	
	/**
	 * If output capturing is currently active
	 * 
	 * @var boolean
	 */
	static private $capturing = FALSE;
	
	/**
	 * If output buffering has been started
	 * 
	 * @var integer
	 */
	static private $started = FALSE;
	
	
	/**
	 * Erases the output buffer
	 * 
	 * @return void
	 */
	static public function erase()
	{
		if (!self::$started) {
			throw new fProgrammerException(
				'The output buffer can not be erased since output buffering has not been started'
			);
		}
		if (self::$capturing) {
			throw new fProgrammerException(
				'Output capturing is currently active and it must be stopped before the buffer can be erased'
			);
		}
		ob_clean();
	}
	
	
	/**
	 * Returns the contents of output buffer
	 * 
	 * @return string  The contents of the output buffer
	 */
	static public function get()
	{
		if (!self::$started) {
			throw new fProgrammerException(
				'The output buffer can not be retrieved because it has not been started'
			);
		}
		if (self::$capturing) {
			throw new fProgrammerException(
				'Output capturing is currently active and it must be stopped before the buffer can be retrieved'
			);
		}
		return ob_get_contents();
	}
	
	
	/**
	 * Checks if buffering has been started
	 * 
	 * @return boolean  If buffering has been started
	 */
	static public function isStarted()
	{
		return self::$started;
	}
	
	
	/**
	 * Replaces a value in the output buffer
	 * 
	 * @param  string $find     The string to find
	 * @param  string $replace  The string to replace
	 * @return void
	 */
	static public function replace($find, $replace)
	{
		if (!self::$started) {
			throw new fProgrammerException(
				'A replacement can not be made since output buffering has not been started'
			);
		}
		if (self::$capturing) {
			throw new fProgrammerException(
				'Output capturing is currently active and it must be stopped before a replacement can be made'
			);
		}
		
		// ob_get_clean() actually turns off output buffering, so we do it the long way
		$contents = ob_get_contents();
		ob_clean();
		
		echo str_replace($find, $replace, $contents);
	}
	
	
	/**
	 * Resets the configuration and buffer of the class
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	static public function reset()
	{
		if (self::$capturing) {
			self::stopCapture();	
		}
		if (self::$started) {
			self::erase();
			self::stop();	
		}
	}
	
	
	/**
	 * Starts output buffering
	 * 
	 * @param  boolean $gzip  If the buffered output should be gzipped using [http://php.net/ob_gzhandler `ob_gzhandler()`]
	 * @return void
	 */
	static public function start($gzip=FALSE)
	{
		if (self::$started) {
			throw new fProgrammerException(
				'Output buffering has already been started'
			);
		}
		if (self::$capturing) {
			throw new fProgrammerException(
				'Output capturing is currently active and it must be stopped before the buffering can be started'
			);
		}
		if ($gzip && !extension_loaded('zlib')) {
			throw new fEnvironmentException(
				'The PHP %s extension is required for gzipped buffering, however is does not appear to be loaded',
				'zlib'
			);
		}
		ob_start($gzip ? 'ob_gzhandler' : NULL);
		self::$started = TRUE;
	}
	
	
	/**
	 * Starts capturing output, should be used with ::stopCapture() to grab output from code that does not offer an option of returning a value instead of outputting it
	 * 
	 * @return void
	 */
	static public function startCapture()
	{
		if (self::$capturing) {
			throw new fProgrammerException(
				'Output capturing has already been started'
			);
		}
		ob_start();
		self::$capturing = TRUE;
	}
	
	
	/**
	 * Stops output buffering, flushing everything to the browser
	 * 
	 * @return void
	 */
	static public function stop()
	{
		if (!self::$started) {
			throw new fProgrammerException(
				'Output buffering can not be stopped since it has not been started'
			);
		}
		if (self::$capturing) {
			throw new fProgrammerException(
				'Output capturing is currently active and it must be stopped before buffering can be stopped'
			);
		}
		
		// Only flush if there is content to push out, otherwise
		// we might prevent headers from being sent
		if (ob_get_contents()) {
			ob_end_flush();
		} else {
			ob_end_clean();
		}
		
		self::$started = FALSE;
	}
	
	
	/**
	 * Stops capturing output, returning what was captured
	 * 
	 * @return string  The captured output
	 */
	static public function stopCapture()
	{
		if (!self::$capturing) {
			throw new fProgrammerException(
				'Output capturing can not be stopped since it has not been started'
			);
		}
		self::$capturing = FALSE;
		return ob_get_clean();
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fBuffer
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2008-2010 Will Bond <will@flourishlib.com>
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