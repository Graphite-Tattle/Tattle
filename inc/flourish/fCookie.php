<?php
/**
 * Provides a consistent cookie API, HTTPOnly compatibility with older PHP versions and default parameters
 * 
 * @copyright  Copyright (c) 2008-2009 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Nick Trew [nt]
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fCookie
 * 
 * @version    1.0.0b3
 * @changes    1.0.0b3  Added the ::delete() method [nt+wb, 2009-09-30]
 * @changes    1.0.0b2  Updated for new fCore API [wb, 2009-02-16]
 * @changes    1.0.0b   The initial implementation [wb, 2008-09-01]
 */
class fCookie
{
	// The following constants allow for nice looking callbacks to static methods
	const delete             = 'fCookie::delete';
	const get                = 'fCookie::get';
	const reset              = 'fCookie::reset';
	const set                = 'fCookie::set';
	const setDefaultDomain   = 'fCookie::setDefaultDomain';
	const setDefaultExpires  = 'fCookie::setDefaultExpires';
	const setDefaultHTTPOnly = 'fCookie::setDefaultHTTPOnly';
	const setDefaultPath     = 'fCookie::setDefaultPath';
	const setDefaultSecure   = 'fCookie::setDefaultSecure';
	
	
	/**
	 * The default domain to set for cookies
	 * 
	 * @var string
	 */
	static private $default_domain = NULL;
	
	/**
	 * The default expiration date to set for cookies
	 * 
	 * @var string|integer
	 */
	static private $default_expires = NULL;
	
	/**
	 * If cookies should default to being http-only
	 * 
	 * @var boolean
	 */
	static private $default_httponly = FALSE;
	
	/**
	 * The default path to set for cookies
	 * 
	 * @var string
	 */
	static private $default_path = NULL;
	
	/**
	 * If cookies should default to being secure-only
	 * 
	 * @var boolean
	 */
	static private $default_secure = FALSE;
	
	
	/**
	 * Deletes a cookie - uses default parameters set by the other set methods of this class
	 * 
	 * @param  string  $name    The cookie name to delete
	 * @param  string  $path    The path of the cookie to delete
	 * @param  string  $domain  The domain of the cookie to delete
	 * @param  boolean $secure  If the cookie is a secure-only cookie
	 * @return void
	 */
	static public function delete($name, $path=NULL, $domain=NULL, $secure=NULL)
	{
		self::set($name, '', time() - 86400, $path, $domain, $secure);
	}
	
	
	/**
	 * Gets a cookie value from `$_COOKIE`, while allowing a default value to be provided
	 * 
	 * @param  string $name           The name of the cookie to retrieve
	 * @param  mixed  $default_value  If there is no cookie with the name provided, return this value instead
	 * @return mixed  The value
	 */
	static public function get($name, $default_value=NULL)
	{
		if (isset($_COOKIE[$name])) {
			$value = fUTF8::clean($_COOKIE[$name]);
			if (get_magic_quotes_gpc()) {
				$value = stripslashes($value);
			}
			return $value;
		}
		return $default_value;
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
		self::$default_domain   = NULL;
		self::$default_expires  = NULL;
		self::$default_httponly = FALSE;
		self::$default_path     = NULL;
		self::$default_secure   = FALSE;
	}
	
	
	/**
	 * Sets a cookie to be sent back to the browser - uses default parameters set by the other set methods of this class
	 * 
	 * The following methods allow for setting default parameters for this method:
	 *   
	 *  - ::setDefaultExpires():  Sets the default for the `$expires` parameter
	 *  - ::setDefaultPath():     Sets the default for the `$path` parameter
	 *  - ::setDefaultDomain():   Sets the default for the `$domain` parameter
	 *  - ::setDefaultSecure():   Sets the default for the `$secure` parameter
	 *  - ::setDefaultHTTPOnly(): Sets the default for the `$httponly` parameter
	 * 
	 * @param  string         $name      The name of the cookie to set
	 * @param  mixed          $value     The value of the cookie to set
	 * @param  string|integer $expires   A relative string to be interpreted by [http://php.net/strtotime strtotime()] or an integer unix timestamp
	 * @param  string         $path      The path this cookie applies to
	 * @param  string         $domain    The domain this cookie applies to
	 * @param  boolean        $secure    If the cookie should only be transmitted over a secure connection
	 * @param  boolean        $httponly  If the cookie should only be readable by HTTP connection, not javascript
	 * @return void
	 */
	static public function set($name, $value, $expires=NULL, $path=NULL, $domain=NULL, $secure=NULL, $httponly=NULL)
	{
		if ($expires === NULL && self::$default_expires !== NULL) {
			$expires = self::$default_expires;	
		}
		
		if ($path === NULL && self::$default_path !== NULL) {
			$path = self::$default_path;	
		}
		
		if ($domain === NULL && self::$default_domain !== NULL) {
			$domain = self::$default_domain;	
		}
		
		if ($secure === NULL && self::$default_secure !== NULL) {
			$secure = self::$default_secure;	
		}
		
		if ($httponly === NULL && self::$default_httponly !== NULL) {
			$httponly = self::$default_httponly;	
		}
		
		if ($expires && !is_numeric($expires)) {
			$expires = strtotime($expires);	
		}
		
		// Adds support for httponly cookies to PHP 5.0 and 5.1
		if (strlen($value) && $httponly && !fCore::checkVersion('5.2')) {
			$header_string = urlencode($name) . '=' . urlencode($value);
			if ($expires) {
				$header_string .= '; expires=' . gmdate('D, d-M-Y H:i:s T', $expires); 		
			}
			if ($path) {
				$header_string .= '; path=' . $path;	
			}
			if ($domain) {
				$header_string .= '; domain=' . $domain;	
			}
			if ($secure) {
				$header_string .= '; secure';	
			}
			$header_string .= '; httponly';
			header('Set-Cookie: ' . $header_string, FALSE);
			return;
			
		// Only pases the httponly parameter if we are on 5.2 since it causes error notices otherwise
		} elseif (strlen($value) && $httponly) {
			setcookie($name, $value, $expires, $path, $domain, $secure, TRUE);
			return; 		
		}
		
		setcookie($name, $value, $expires, $path, $domain, $secure);
	}
	
	
	/**
	 * Sets the default domain to use for cookies
	 * 
	 * This value will be used when the `$domain` parameter of the ::set()
	 * method is not specified or is set to `NULL`.
	 * 
	 * @param  string $domain  The default domain to use for cookies
	 * @return void
	 */
	static public function setDefaultDomain($domain)
	{
		self::$default_domain = $domain;	
	}
	
	
	/**
	 * Sets the default expiration date to use for cookies
	 * 
	 * This value will be used when the `$expires` parameter of the ::set()
	 * method is not specified or is set to `NULL`.
	 * 
	 * @param  string|integer $expires  The default expiration date to use for cookies
	 * @return void
	 */
	static public function setDefaultExpires($expires)
	{
		self::$default_expires = $expires;	
	}
	
	
	/**
	 * Sets the default httponly flag to use for cookies
	 * 
	 * This value will be used when the `$httponly` parameter of the ::set()
	 * method is not specified or is set to `NULL`.
	 * 
	 * @param  boolean $httponly  The default httponly flag to use for cookies
	 * @return void
	 */
	static public function setDefaultHTTPOnly($httponly)
	{
		self::$default_httponly = $httponly;	
	}
	
	
	/**
	 * Sets the default path to use for cookies
	 * 
	 * This value will be used when the `$path` parameter of the ::set()
	 * method is not specified or is set to `NULL`.
	 * 
	 * @param  string $path  The default path to use for cookies
	 * @return void
	 */
	static public function setDefaultPath($path)
	{
		self::$default_path = $path;	
	}
	
	
	/**
	 * Sets the default secure flag to use for cookies
	 * 
	 * This value will be used when the `$secure` parameter of the ::set()
	 * method is not specified or is set to `NULL`.
	 * 
	 * @param  boolean $secure  The default secure flag to use for cookies
	 * @return void
	 */
	static public function setDefaultSecure($secure)
	{
		self::$default_secure = $secure;	
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fCookie
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2008-2009 Will Bond <will@flourishlib.com>, others
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