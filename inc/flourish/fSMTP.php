<?php
/**
 * Creates a connection to an SMTP server to be used by fEmail
 * 
 * @copyright  Copyright (c) 2010-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fSMTP
 * 
 * @version    1.0.0b11
 * @changes    1.0.0b11  Enhanced the error checking for ::write() [wb, 2011-06-03]
 * @changes    1.0.0b10  Added code to work around PHP bug #42682 (http://bugs.php.net/bug.php?id=42682) where `stream_select()` doesn't work on 64bit machines from PHP 5.2.0 to 5.2.5, improved timeouts while reading data [wb, 2011-01-10]
 * @changes    1.0.0b9   Fixed a bug where lines starting with `.` and containing other content would have the `.` stripped [wb, 2010-09-11]
 * @changes    1.0.0b8   Updated the class to use fEmail::getFQDN() [wb, 2010-09-07]
 * @changes    1.0.0b7   Updated class to use new fCore::startErrorCapture() functionality [wb, 2010-08-09]
 * @changes    1.0.0b6   Updated the class to use new fCore functionality [wb, 2010-07-05]
 * @changes    1.0.0b5   Hacked around a bug in PHP 5.3 on Windows [wb, 2010-06-22]
 * @changes    1.0.0b4   Updated the class to not connect and authenticate until a message is sent, moved message id generation in fEmail [wb, 2010-05-05]
 * @changes    1.0.0b3   Fixed a bug with connecting to servers that send an initial response of `220-` and instead of `220 ` [wb, 2010-04-26]
 * @changes    1.0.0b2   Fixed a bug where `STARTTLS` would not be triggered if it was last in the SMTP server's list of supported extensions [wb, 2010-04-20]
 * @changes    1.0.0b    The initial implementation [wb, 2010-04-20]
 */
class fSMTP
{
	/**
	 * The authorization methods that are valid for this server
	 * 
	 * @var array
	 */
	private $auth_methods;
	
	/**
	 * The socket connection to the SMTP server
	 * 
	 * @var resource
	 */
	private $connection;
	
	/**
	 * If debugging has been enabled
	 * 
	 * @var boolean
	 */
	private $debug;
	
	/**
	 * The hostname or IP of the SMTP server
	 * 
	 * @var string
	 */
	private $host;
	
	/**
	 * The maximum size message the SMTP server supports
	 * 
	 * @var integer
	 */
	private $max_size;
	
	/**
	 * The password to authenticate with
	 * 
	 * @var string
	 */
	private $password;
	
	/**
	 * If the server supports pipelining
	 * 
	 * @var boolean
	 */
	private $pipelining;
	
	/**
	 * The port the SMTP server is on
	 * 
	 * @var integer
	 */
	private $port;
	
	/**
	 * If the connection to the SMTP server is secure
	 * 
	 * @var boolean
	 */
	private $secure;
	
	/**
	 * The timeout for the connection
	 * 
	 * @var integer
	 */
	private $timeout;
	
	/**
	 * The username to authenticate with
	 * 
	 * @var string
	 */
	private $username;
	
	
	/**
	 * Configures the SMTP connection
	 * 
	 * The SMTP connection is only made once authentication is attempted or
	 * an email is sent.
	 * 
	 * Please note that this class will upgrade the connection to TLS via the
	 * SMTP `STARTTLS` command if possible, even if a secure connection was not
	 * requested. This helps to keep authentication information secure.
	 * 
	 * @param string  $host     The hostname or IP address to connect to
	 * @param integer $port     The port number to use
	 * @param boolean $secure   If the connection should be secure - if `STARTTLS` is available, the connection will be upgraded even if this is `FALSE`
	 * @param integer $timeout  The timeout for the connection - defaults to the `default_socket_timeout` ini setting
	 * @return fSMTP
	 */
	public function __construct($host, $port=NULL, $secure=FALSE, $timeout=NULL)
	{
		if ($timeout === NULL) {
			$timeout = ini_get('default_socket_timeout');
		}
		if ($port === NULL) {
			$port = !$secure ? 25 : 465;
		}
		
		if ($secure && !extension_loaded('openssl')) {
			throw new fEnvironmentException(
				'A secure connection was requested, but the %s extension is not installed',
				'openssl'
			);
		}
		
		$this->host    = $host;
		$this->port    = $port;
		$this->secure  = $secure;
		$this->timeout = $timeout;
	}
	
	
	/**
	 * Closes the connection to the SMTP server
	 * 
	 * @return void
	 */
	public function __destruct()
	{
		$this->close();
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
	 * Authenticates with the SMTP server
	 * 
	 * This method supports the digest-md5, cram-md5, login and plain
	 * SMTP authentication methods. This method will try to use the more secure
	 * digest-md5 and cram-md5 methods first since they do not send information
	 * in the clear.
	 * 
	 * @throws fValidationException  When the `$username` and `$password` are not accepted
	 * 
	 * @param string $username  The username
	 * @param string $password  The password
	 * @return void
	 */
	public function authenticate($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}
	
	
	/**
	 * Closes the connection to the SMTP server
	 * 
	 * @return void
	 */
	public function close()
	{
		if (!$this->connection) {
			return;
		}
		
		$this->write('QUIT', 1);
		fclose($this->connection);
		$this->connection = NULL;
	}
	
	
	/**
	 * Initiates the connection to the server
	 * 
	 * @return void
	 */
	private function connect()
	{
		if ($this->connection) {
			return;
		}
		
		$fqdn = fEmail::getFQDN();
		
		fCore::startErrorCapture(E_WARNING);
		
		$host = ($this->secure) ? 'tls://' . $this->host : $this->host;
		$this->connection = fsockopen($host, $this->port, $error_int, $error_string, $this->timeout);
		
		foreach (fCore::stopErrorCapture('#ssl#i') as $error) {
			throw new fConnectivityException('There was an error connecting to the server. A secure connection was requested, but was not available. Try a non-secure connection instead.');
		}
		
		if (!$this->connection) {
			throw new fConnectivityException('There was an error connecting to the server');
		}
		
		stream_set_timeout($this->connection, $this->timeout);
		$response = $this->read('#^220 #');
		if (!$this->find($response, '#^220[ -]#')) {
			throw new fConnectivityException(
				'Unknown SMTP welcome message, %1$s, from server %2$s on port %3$s',
				join("\r\n", $response),
				$this->host,
				$this->port
			);		
		}
		
		// Try sending the ESMTP EHLO command, but fall back to normal SMTP HELO
		$response = $this->write('EHLO ' . $fqdn, '#^250 #m');
		if ($this->find($response, '#^500#')) {
			$response = $this->write('HELO ' . $fqdn, 1);	
		}
		
		// If STARTTLS is available, use it
		if (!$this->secure && extension_loaded('openssl') && $this->find($response, '#^250[ -]STARTTLS#')) {
			$response    = $this->write('STARTTLS', '#^220 #');
			$affirmative = $this->find($response, '#^220[ -]#');
			if ($affirmative) {
				do {
					if (isset($res)) {
						sleep(0.1);	
					}
					$res = stream_socket_enable_crypto($this->connection, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT);
				} while ($res === 0);
			}
			if (!$affirmative || $res === FALSE) {
				throw new fConnectivityException('Error establishing secure connection');
			}
			$response = $this->write('EHLO ' . $fqdn, '#^250 #m');
		}
		
		$this->max_size = 0;
		if ($match = $this->find($response, '#^250[ -]SIZE\s+(\d+)$#')) {
			$this->max_size = $match[0][1];	
		}
		
		$this->pipelining = (boolean) $this->find($response, '#^250[ -]PIPELINING$#');
		
		$auth_methods = array();
		if ($match = $this->find($response, '#^250[ -]AUTH[ =](.*)$#')) {
			$auth_methods = array_map('strtoupper', explode(' ', $match[0][1]));	
		}
		
		if (!$auth_methods || !$this->username) {
			return;
		}
		
		if (in_array('DIGEST-MD5', $auth_methods)) {
			$response = $this->write('AUTH DIGEST-MD5', 1);
			$this->handleErrors($response);
			
			$match     = $this->find($response, '#^334 (.*)$#');
			$challenge = base64_decode($match[0][1]);
			
			preg_match_all('#(?<=,|^)(\w+)=("[^"]+"|[^,]+)(?=,|$)#', $challenge, $matches, PREG_SET_ORDER);
			$request_params = array();
			foreach ($matches as $_match) {
				$request_params[$_match[1]] = ($_match[2][0] == '"') ? substr($_match[2], 1, -1) : $_match[2];
			}
			
			$missing_qop_auth = !isset($request_params['qop']) || !in_array('auth', explode(',', $request_params['qop']));
			$missing_nonce    = empty($request_params['nonce']);
			if ($missing_qop_auth || $missing_nonce) {
				throw new fUnexpectedException(
					'The SMTP server %1$s on port %2$s claims to support DIGEST-MD5, but does not seem to provide auth functionality',
					$this->host,
					$this->port
				);
			}
			if (!isset($request_params['realm'])) {
				$request_params['realm'] = '';
			}
			
			// Algorithm from http://www.ietf.org/rfc/rfc2831.txt
			$realm      = $request_params['realm'];
			$nonce      = $request_params['nonce'];
			$cnonce     = fCryptography::randomString('32', 'hexadecimal');
			$nc         = '00000001';
			$digest_uri = 'smtp/' . $this->host;
			
			$a1 = md5($this->username . ':' . $realm . ':' . $this->password, TRUE) . ':' . $nonce . ':' . $cnonce;	
			$a2 = 'AUTHENTICATE:' . $digest_uri;
			$response = md5(md5($a1) . ':' . $nonce . ':' . $nc . ':' . $cnonce . ':auth:' . md5($a2));
			
			$response_params = array(
				'charset=utf-8',
				'username="' . $this->username . '"',
				'realm="' . $realm . '"',
				'nonce="' . $nonce . '"',
				'nc=' . $nc,
				'cnonce="' . $cnonce . '"',
				'digest-uri="' . $digest_uri . '"',
				'response=' . $response,
				'qop=auth'
			);
			
			$response = $this->write(base64_encode(join(',', $response_params)), 2);
		
		} elseif (in_array('CRAM-MD5', $auth_methods)) {
			$response = $this->write('AUTH CRAM-MD5', 1);
			$match     = $this->find($response, '#^334 (.*)$#');
			$challenge = base64_decode($match[0][1]);
			$response  = $this->write(base64_encode($this->username . ' ' . fCryptography::hashHMAC('md5', $challenge, $this->password)), 1);
		
		} elseif (in_array('LOGIN', $auth_methods)) {
			$response = $this->write('AUTH LOGIN', 1);
			$this->write(base64_encode($this->username), 1);
			$response = $this->write(base64_encode($this->password), 1);
			
		} elseif (in_array('PLAIN', $auth_methods)) {
			$response = $this->write('AUTH PLAIN ' . base64_encode($this->username . "\0" . $this->username . "\0" . $this->password), 1);
		}
		
		if ($this->find($response, '#^535[ -]#')) {
			throw new fValidationException(
				'The username and password provided were not accepted for the SMTP server %1$s on port %2$s',
				$this->host,
				$this->port
			);
		}
		if (!array_filter($response)) {
			throw new fConnectivityException('No response was received for the authorization request');
		}
	}
	
	
	/**
	 * Sets if debug messages should be shown
	 * 
	 * @param  boolean $flag  If debugging messages should be shown
	 * @return void
	 */
	public function enableDebugging($flag)
	{
		$this->debug = (boolean) $flag;
	}
	
	
	/**
	 * Searches the response array for the the regex and returns any matches
	 * 
	 * @param array  $response  The lines of data to search through
	 * @param string $regex     The regex to search with
	 * @return array  The regex matches
	 */
	private function find($response, $regex)
	{
		$matches = array();
		foreach ($response as $line) {
			if (preg_match($regex, $line, $match)) {
				$matches[] = $match;
			}	
		}
		return $matches;
	}
	
	
	/**
	 * Searches the response array for SMTP error codes
	 * 
	 * @param array $response  The response array to search through
	 * @return void
	 */
	private function handleErrors($response)
	{
		$codes = array(
			450, 451, 452, 500, 501, 502, 503, 504, 521, 530, 550, 551, 552, 553
		);
		
		$regex  = '#^(' . join('|', $codes) . ')#';
		$errors = array();
		foreach ($response as $line) {
			if (preg_match($regex, $line)) {
				$errors[] = $line;
			}
		}
		if ($errors) {
			throw new fUnexpectedException(
				"The following unexpected SMTP errors occurred for the server %1\$s on port %2\$s:\n%3\$s",
				$this->host,
				$this->port,
				join("\n", $errors)
			);		
		}
	}
	
	
	/**
	 * Reads lines from the SMTP server
	 * 
	 * @param  integer|string $expect  The expected number of lines of response or a regex of the last line
	 * @return array  The lines of response from the server
	 */
	private function read($expect)
	{
		$response = array();
		if ($result = $this->select($this->timeout, 0)) {
			while (!feof($this->connection)) {
				$line = fgets($this->connection);
				if ($line === FALSE) {
					break;
				}
				$line = substr($line, 0, -2);
				if (is_string($result)) {
					$line = $result . $line;
				}
				$response[] = $line;
				if ($expect !== NULL) {
					$result = NULL;
					$matched_number = is_int($expect) && sizeof($response) == $expect;
					$matched_regex  = is_string($expect) && preg_match($expect, $response[sizeof($response)-1]);
					if ($matched_number || $matched_regex) {
						break;
					}
				} elseif (!($result = $this->select(0, 200000))) {
					break;
				}
			}
		}
		if (fCore::getDebug($this->debug)) {
			fCore::debug("Received:\n" . join("\r\n", $response), $this->debug);
		}
		$this->handleErrors($response);
		return $response;
	}
	
	
	/**
	 * Performs a "fixed" stream_select() for the connection
	 * 
	 * @param integer $timeout   The number of seconds in the timeout
	 * @param integer $utimeout  The number of microseconds in the timeout
	 * @return boolean|string  TRUE (or a character) is the connection is ready to be read from, FALSE if not
	 */
	private function select($timeout, $utimeout)
	{
		$read     = array($this->connection);
		$write    = NULL;
		$except   = NULL;
		
		// PHP 5.2.0 to 5.2.5 had a bug on amd64 linux where stream_select()
		// fails, so we have to fake it - http://bugs.php.net/bug.php?id=42682
		static $broken_select = NULL;
		if ($broken_select === NULL) {
			$broken_select = strpos(php_uname('m'), '64') !== FALSE && fCore::checkVersion('5.2.0') && !fCore::checkVersion('5.2.6');
		}
		
		// Fixes an issue with stream_select throwing a warning on PHP 5.3 on Windows
		if (fCore::checkOS('windows') && fCore::checkVersion('5.3.0')) {
			$select = @stream_select($read, $write, $except, $timeout, $utimeout);
		
		} elseif ($broken_select) {
			$broken_select_buffer = NULL;
			$start_time = microtime(TRUE);
			$i = 0;
			do {
				if ($i) {
					usleep(50000);
				}
				$char = fgetc($this->connection);
				if ($char != "\x00" && $char !== FALSE) {
					$broken_select_buffer = $char;
				}
				$i++;
				if ($i > 2) {
					break;
				}
			} while ($broken_select_buffer === NULL && microtime(TRUE) - $start_time < ($timeout + ($utimeout/1000000)));
			$select = $broken_select_buffer === NULL ? FALSE : $broken_select_buffer;
			
		} else {
			$select = stream_select($read, $write, $except, $timeout, $utimeout);
		}
		
		return $select;
	}
	
	
	/**
	 * Sends a message via the SMTP server
	 * 
	 * @internal
	 * 
	 * @throws fValidationException  When the message is too large for the server
	 * 
	 * @param string $from     The email address being sent from - this will be used as the `Return-Path` header
	 * @param array  $to       All of the To, Cc and Bcc email addresses to send the message to - this does not affect the message headers in any way
	 * @param string $headers  The message headers - the Bcc header will be removed if present
	 * @param string $body     The mail body
	 * @return void
	 */
	public function send($from, $to, $headers, $body)
	{
		$this->connect();
		
		// Lines starting with . need to start with two .s because the leading
		// . will be stripped
		$body = preg_replace('#^\.#m', '..', $body);
		
		// Removed the Bcc header incase the SMTP server doesn't
		$headers = preg_replace('#^Bcc:(.*?)\r\n([^ ])#mi', '\2', $headers);
		
		// Add the Date header
		$headers = "Date: " . date('D, j M Y H:i:s O') . "\r\n" . $headers;
		
		$data = $headers . "\r\n\r\n" . $body;
		if ($this->max_size && strlen($data) > $this->max_size) {
			throw new fValidationException(
				'The email provided is %1$s, which is larger than the maximum size of %2$s that the server supports',
				fFilesystem::formatFilesize(strlen($data)),
				fFilesystem::formatFilesize($this->max_size)
			);
		}
		
		$mail_from = "MAIL FROM:<" . $from . ">";
		
		if ($this->pipelining) {
			$expect = 2;
			$rcpt_to = '';
			foreach ($to as $email) {
				$rcpt_to .= "RCPT TO:<" . $email . ">\r\n";
				$expect++;
			}
			$rcpt_to = trim($rcpt_to);
			
			$this->write($mail_from . "\r\n" . $rcpt_to . "\r\nDATA\r\n", $expect);
			
		} else {
			$this->write($mail_from, 1);
			foreach ($to as $email) {
				$this->write("RCPT TO:<" . $email . ">", 1);
			}
			$this->write('DATA', 1);
		}
		
		$this->write($data . "\r\n.\r\n", 1);
		$this->write('RSET', 1);
	}
	
	
	/**
	 * Sends raw text/commands to the SMTP server
	 * 
	 * @param  string         $data    The data or commands to send
	 * @param  integer|string $expect  The expected number of lines of response or a regex of the last line
	 * @return array  The response from the server
	 */
	private function write($data, $expect)
	{
		if (!$this->connection) {
			throw new fProgrammerException('Unable to send data since the connection has already been closed');
		}
		
		if (substr($data, -2) != "\r\n") {
			$data .= "\r\n";
		}
		if (fCore::getDebug($this->debug)) {
			fCore::debug("Sending:\n" . trim($data), $this->debug);
		}
		
		$res = fwrite($this->connection, $data);
		
		if ($res === FALSE || $res === 0) {
			throw new fConnectivityException('Unable to write data to SMTP server %1$s on port %2$s', $this->host, $this->port);	
		}
		$response = $this->read($expect);
		return $response;
	}
}


/**
 * Copyright (c) 2010-2011 Will Bond <will@flourishlib.com>
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
