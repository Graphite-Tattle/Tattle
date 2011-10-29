<?php
/**
 * Provides functionality to retrieve and manipulate URL information
 * 
 * This class uses `$_SERVER['REQUEST_URI']` for all operations, meaning that
 * the original URL entered by the user will be used, or that any rewrites
 * will **not** be reflected by this class.
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fURL
 * 
 * @version    1.0.0b10
 * @changes    1.0.0b10  Fixed some method signatures [wb, 2011-08-24]
 * @changes    1.0.0b9   Fixed ::redirect() to handle no parameters properly [wb, 2011-06-13]
 * @changes    1.0.0b8   Added the `$delimiter` parameter to ::makeFriendly() [wb, 2011-06-03]
 * @changes    1.0.0b7   Fixed ::redirect() to be able to handle unqualified and relative paths [wb, 2011-03-02]
 * @changes    1.0.0b6   Added the `$max_length` parameter to ::makeFriendly() [wb, 2010-09-19]
 * @changes    1.0.0b5   Updated ::redirect() to not require a URL, using the current URL as the default [wb, 2009-07-29]
 * @changes    1.0.0b4   ::getDomain() now includes the port number if non-standard [wb, 2009-05-02]
 * @changes    1.0.0b3   ::makeFriendly() now changes _-_ to - and multiple _ to a single _ [wb, 2009-03-24]
 * @changes    1.0.0b2   Fixed ::makeFriendly() so that _ doesn't appear at the beginning of URLs [wb, 2009-03-22]
 * @changes    1.0.0b    The initial implementation [wb, 2007-06-14]
 */
class fURL
{
	// The following constants allow for nice looking callbacks to static methods
	const get                   = 'fURL::get';
	const getDomain             = 'fURL::getDomain';
	const getQueryString        = 'fURL::getQueryString';
	const getWithQueryString    = 'fURL::getWithQueryString';
	const makeFriendly          = 'fURL::makeFriendly';
	const redirect              = 'fURL::redirect';
	const removeFromQueryString = 'fURL::removeFromQueryString';
	const replaceInQueryString  = 'fURL::replaceInQueryString';
	
	
	/**
	 * Returns the requested URL, does no include the domain name or query string
	 * 
	 * This will return the original URL requested by the user - ignores all
	 * rewrites.
	 * 
	 * @return string  The requested URL without the query string
	 */
	static public function get()
	{
		return preg_replace('#\?.*$#D', '', $_SERVER['REQUEST_URI']);
	}
	
	
	/**
	 * Returns the current domain name, with protcol prefix. Port will be included if not 80 for HTTP or 443 for HTTPS.
	 * 
	 * @return string  The current domain name, prefixed by `http://` or `https://`
	 */
	static public function getDomain()
	{
		$port = (isset($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : NULL;
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			return 'https://' . $_SERVER['SERVER_NAME'] . ($port && $port != 443 ? ':' . $port : '');
		} else {
			return 'http://' . $_SERVER['SERVER_NAME'] . ($port && $port != 80 ? ':' . $port : '');
		}
	}
	
	
	/**
	 * Returns the current query string, does not include parameters added by rewrites
	 * 
	 * @return string  The query string
	 */
	static public function getQueryString()
	{
		return preg_replace('#^[^?]*\??#', '', $_SERVER['REQUEST_URI']);
	}
	
	
	/**
	 * Returns the current URL including query string, but without domain name - does not include query string parameters from rewrites
	 * 
	 * @return string  The URL with query string
	 */
	static public function getWithQueryString()
	{
		return $_SERVER['REQUEST_URI'];
	}
	
	
	/**
	 * Changes a string into a URL-friendly string
	 * 
	 * @param  string   $string      The string to convert
	 * @param  integer  $max_length  The maximum length of the friendly URL
	 * @param  string   $delimiter   The delimiter to use between words, defaults to `_`
	 * @param  string   |$string
	 * @param  string   |$delimiter
	 * @return string  The URL-friendly version of the string
	 */
	static public function makeFriendly($string, $max_length=NULL, $delimiter=NULL)
	{
		// This allows omitting the max length, but including a delimiter
		if ($max_length && !is_numeric($max_length)) {
			$delimiter  = $max_length;
			$max_length = NULL;
		}

		$string = fHTML::decode(fUTF8::ascii($string));
		$string = strtolower(trim($string));
		$string = str_replace("'", '', $string);

		if (!strlen($delimiter)) {
			$delimiter = '_';
		}

		$delimiter_replacement = strtr($delimiter, array('\\' => '\\\\', '$' => '\\$'));
		$delimiter_regex       = preg_quote($delimiter, '#');

		$string = preg_replace('#[^a-z0-9\-_]+#', $delimiter_replacement, $string);
		$string = preg_replace('#' . $delimiter_regex . '{2,}#', $delimiter_replacement, $string);
		$string = preg_replace('#_-_#', '-', $string);
		$string = preg_replace('#(^' . $delimiter_regex . '+|' . $delimiter_regex . '+$)#D', '', $string);
		
		$length = strlen($string);
		if ($max_length && $length > $max_length) {
			$last_pos = strrpos($string, $delimiter, ($length - $max_length - 1) * -1);
			if ($last_pos < ceil($max_length / 2)) {
				$last_pos = $max_length;
			}
			$string = substr($string, 0, $last_pos);
		}
		
		return $string;
	}
	
	
	/**
	 * Redirects to the URL specified, without requiring a full-qualified URL
	 * 
	 *  - If the URL starts with `/`, it is treated as an absolute path on the current site
	 *  - If the URL starts with `http://` or `https://`, it is treated as a fully-qualified URL
	 *  - If the URL starts with anything else, including a `?`, it is appended to the current URL
	 *  - If the URL is ommitted, it is treated as the current URL
	 * 
	 * @param  string $url  The url to redirect to
	 * @return void
	 */
	static public function redirect($url=NULL)
	{
		if (strpos($url, '/') === 0) {
			$url = self::getDomain() . $url;

		} elseif (!preg_match('#^https?://#i', $url)) {
			
			$prefix = self::getDomain() . self::get();
			
			if (strlen($url)) {
				// All URLs that have more than the query string need to
				// be appended to the current directory name
				if ($url[0] != '?') {
					$prefix = preg_replace('#(?<=/)[^/]+$#D', '', $prefix);
				}

				// Clean up ./ relative URLS
				if (substr($url, 0, 2) == './') {
					$url = substr($url, 2);
				}

				// Resolve ../ relative paths as far as possible
				while (substr($url, 0, 3) == '../') {
					if ($prefix == self::getDomain() . '/') { break; }
					$prefix = preg_replace('#(?<=/)[^/]+/?$#D', '', $prefix);
					$url    = substr($url, 3);
				}
			}

			$url = $prefix . $url;
		}
		
		// Strip the ? if there are no query string parameters
		if (substr($url, -1) == '?') {
			$url = substr($url, 0, -1);
		}
		
		header('Location: ' . $url);
		exit($url);
	}
	
	
	/**
	 * Removes one or more parameters from the query string
	 * 
	 * This method uses the query string from the original URL and will not
	 * contain any parameters that are from rewrites.
	 * 
	 * @param  string $parameter  A parameter to remove from the query string
	 * @param  string ...
	 * @return string  The query string with the parameter(s) specified removed, first character is `?`
	 */
	static public function removeFromQueryString($parameter)
	{
		$parameters = func_get_args();
		
		parse_str(self::getQueryString(), $qs_array);
		if (get_magic_quotes_gpc()) {
			$qs_array = array_map('stripslashes', $qs_array);
		}
		
		foreach ($parameters as $parameter) {
			unset($qs_array[$parameter]);
		}
		
		return '?' . http_build_query($qs_array, '', '&');
	}
	
	
	/**
	 * Replaces a value in the query string
	 * 
	 * This method uses the query string from the original URL and will not
	 * contain any parameters that are from rewrites.
	 * 
	 * @param  string|array  $parameter  The query string parameter
	 * @param  string|array  $value      The value to set the parameter to
	 * @return string  The full query string with the parameter replaced, first char is `?`
	 */
	static public function replaceInQueryString($parameter, $value)
	{
		parse_str(self::getQueryString(), $qs_array);
		if (get_magic_quotes_gpc()) {
			$qs_array = array_map('stripslashes', $qs_array);
		}
		
		settype($parameter, 'array');
		settype($value, 'array');
		
		if (sizeof($parameter) != sizeof($value)) {
			throw new fProgrammerException(
				"There are a different number of parameters and values.\nParameters:\n%1\$s\nValues\n%2\$s",
				$parameter,
				$value
			);
		}
		
		for ($i=0; $i<sizeof($parameter); $i++) {
			$qs_array[$parameter[$i]] = $value[$i];
		}
		
		return '?' . http_build_query($qs_array, '', '&');
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fURL
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2007-2011 Will Bond <will@flourishlib.com>
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