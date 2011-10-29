<?php
/**
 * Allows creating and sending a single email containing plaintext, HTML, attachments and S/MIME encryption
 * 
 * Please note that this class uses the [http://php.net/function.mail mail()]
 * function by default. Developers that are sending multiple emails, or need
 * SMTP support, should use fSMTP with this class.
 * 
 * This class is implemented to use the UTF-8 character encoding. Please see
 * http://flourishlib.com/docs/UTF-8 for more information.
 * 
 * @copyright  Copyright (c) 2008-2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Bill Bushee, iMarc LLC [bb-imarc] <bill@imarc.net>
 * @author     netcarver [n] <fContrib@netcarving.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fEmail
 * 
 * @version    1.0.0b30
 * @changes    1.0.0b30  Changed methods to return instance for method chaining [n, 2011-09-12]
 * @changes    1.0.0b29  Changed ::combineNameEmail() to be a static method and to be exposed publicly for use by other classes [wb, 2011-07-26]
 * @changes    1.0.0b28  Fixed ::addAttachment() and ::addRelatedFile() to properly handle duplicate filenames [wb, 2011-05-17]
 * @changes    1.0.0b27  Fixed a bug with generating FQDNs on some Windows machines [wb, 2011-02-24]
 * @changes    1.0.0b26  Added ::addCustomerHeader() [wb, 2011-02-02]
 * @changes    1.0.0b25  Fixed a bug with finding the FQDN on non-Windows machines [wb, 2011-01-19]
 * @changes    1.0.0b24  Backwards Compatibility Break - the `$contents` parameter of ::addAttachment() is now first instead of third, ::addAttachment() will now accept fFile objects for the `$contents` parameter, added ::addRelatedFile() [wb, 2010-12-01]
 * @changes    1.0.0b23  Fixed a bug on Windows where emails starting with a `.` would have the `.` removed [wb, 2010-09-11]
 * @changes    1.0.0b22  Revamped the FQDN code and added ::getFQDN() [wb, 2010-09-07]
 * @changes    1.0.0b21  Added a check to prevent permissions warnings when getting the FQDN on Windows machines [wb, 2010-09-02]
 * @changes    1.0.0b20  Fixed ::send() to only remove the name of a recipient when dealing with the `mail()` function on Windows and to leave it when using fSMTP [wb, 2010-06-22]
 * @changes    1.0.0b19  Changed ::send() to return the message id for the email, fixed the email regexes to require [] around IPs [wb, 2010-05-05]
 * @changes    1.0.0b18  Fixed the name of the static method ::unindentExpand() [wb, 2010-04-28]
 * @changes    1.0.0b17  Added the static method ::unindentExpand() [wb, 2010-04-26]
 * @changes    1.0.0b16  Added support for sending emails via fSMTP [wb, 2010-04-20]
 * @changes    1.0.0b15  Added the `$unindent_expand_constants` parameter to ::setBody(), added ::loadBody() and ::loadHTMLBody(), fixed HTML emails with attachments [wb, 2010-03-14]
 * @changes    1.0.0b14  Changed ::send() to not double `.`s at the beginning of lines on Windows since it seemed to break things rather than fix them [wb, 2010-03-05]
 * @changes    1.0.0b13  Fixed the class to work when safe mode is turned on [wb, 2009-10-23]
 * @changes    1.0.0b12  Removed duplicate MIME-Version headers that were being included in S/MIME encrypted emails [wb, 2009-10-05]
 * @changes    1.0.0b11  Updated to use the new fValidationException API [wb, 2009-09-17]
 * @changes    1.0.0b10  Fixed a bug with sending both an HTML and a plaintext body [bb-imarc, 2009-06-18]
 * @changes    1.0.0b9   Fixed a bug where the MIME headers were not being set for all emails [wb, 2009-06-12]
 * @changes    1.0.0b8   Added the method ::clearRecipients() [wb, 2009-05-29]
 * @changes    1.0.0b7   Email names with UTF-8 characters are now properly encoded [wb, 2009-05-08]
 * @changes    1.0.0b6   Fixed a bug where <> quoted email addresses in validation messages were not showing [wb, 2009-03-27]
 * @changes    1.0.0b5   Updated for new fCore API [wb, 2009-02-16]
 * @changes    1.0.0b4   The recipient error message in ::validate() no longer contains a typo [wb, 2009-02-09]
 * @changes    1.0.0b3   Fixed a bug with missing content in the fValidationException thrown by ::validate() [wb, 2009-01-14]
 * @changes    1.0.0b2   Fixed a few bugs with sending S/MIME encrypted/signed emails [wb, 2009-01-10]
 * @changes    1.0.0b    The initial implementation [wb, 2008-06-23]
 */
class fEmail
{
	// The following constants allow for nice looking callbacks to static methods
	const combineNameEmail = 'fEmail::combineNameEmail';
	const fixQmail         = 'fEmail::fixQmail';
	const getFQDN          = 'fEmail::getFQDN';
	const reset            = 'fEmail::reset';
	const unindentExpand   = 'fEmail::unindentExpand';
	
	
	/**
	 * A regular expression to match an email address, exluding those with comments and folding whitespace
	 * 
	 * The matches will be:
	 *  
	 *  - `[0]`: The whole email address
	 *  - `[1]`: The name before the `@`
	 *  - `[2]`: The domain/ip after the `@`
	 * 
	 * @var string
	 */
	const EMAIL_REGEX = '~^[ \t]*(                                                                      # Allow leading whitespace
						   (?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+|"[^"\\\\\n\r]+")                       # An "atom" or a quoted string
						   (?:\.[ \t]*(?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+|"[^"\\\\\n\r]+"[ \t]*))*    # A . plus another "atom" or a quoted string, any number of times
						 )@(                                                                            # The @ symbol
						   (?:[a-z0-9\\-]+\.)+[a-z]{2,}|                                                # Domain name
						   \[(?:(?:[01]?\d?\d|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d?\d|2[0-4]\d|25[0-5])\]  # (or) IP addresses
						 )[ \t]*$~ixD';                                                                 # Allow Trailing whitespace
	
	/**
	 * A regular expression to match a `name <email>` string, exluding those with comments and folding whitespace
	 * 
	 * The matches will be:
	 * 
	 *  - `[0]`: The whole name and email address
	 *  - `[1]`: The name
	 *  - `[2]`: The whole email address
	 *  - `[3]`: The email username before the `@`
	 *  - `[4]`: The email domain/ip after the `@`
	 * 
	 * @var string
	 */
	const NAME_EMAIL_REGEX = '~^[ \t]*(                                                                            # Allow leading whitespace
								(?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+[ \t]*|"[^"\\\\\n\r]+"[ \t]*)                 # An "atom" or a quoted string
								(?:\.?[ \t]*(?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+[ \t]*|"[^"\\\\\n\r]+"[ \t]*))*)  # Another "atom" or a quoted string or a . followed by one of those, any number of times
							  [ \t]*<[ \t]*((                                                                      # The < encapsulating the email address
								(?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+|"[^"\\\\\n\r]+")                             # An "atom" or a quoted string
								(?:\.[ \t]*(?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+|"[^"\\\\\n\r]+"[ \t]*))*          # A . plus another "atom" or a quoted string, any number of times
							  )@(                                                                                  # The @ symbol
								(?:[a-z0-9\\-]+\.)+[a-z]{2,}|                                                      # Domain nam
								\[(?:(?:[01]?\d?\d|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d?\d|2[0-4]\d|25[0-5])\]        # (or) IP addresses
							  ))[ \t]*>[ \t]*$~ixD';                                                               # Closing > and trailing whitespace
	
	
	/**
	 * Flags if the class should convert `\r\n` to `\n` for qmail. This makes invalid email headers that may work.
	 * 
	 * @var boolean
	 */
	static private $convert_crlf  = FALSE;
	
	/**
	 * The local fully-qualified domain name
	 */
	static private $fqdn;
	
	/**
	 * Flags if the class should use [http://php.net/popen popen()] to send mail via sendmail
	 * 
	 * @var boolean
	 */
	static private $popen_sendmail = FALSE;


	/**
	 * Turns a name and email into a `"name" <email>` string, or just `email` if no name is provided
	 * 
	 * This method will remove newline characters from the name and email, and
	 * will remove any backslash (`\`) and double quote (`"`) characters from
	 * the name.
	 * 
	 * @internal
	 *
	 * @param  string $name   The name associated with the email address
	 * @param  string $email  The email address
	 * @return string  The '"name" <email>' or 'email' string
	 */
	static public function combineNameEmail($name, $email)
	{
		// Strip lower ascii character since they aren't useful in email addresses
		$email = preg_replace('#[\x0-\x19]+#', '', $email);
		$name  = preg_replace('#[\x0-\x19]+#', '', $name);
		
		if (!$name) {
			return $email;
		}
		
		// If the name contains any non-ascii bytes or stuff not allowed
		// in quoted strings we just make an encoded word out of it
		if (preg_replace('#[\x80-\xff\x5C\x22]#', '', $name) != $name) {
			// The longest header name that will contain email addresses is
			// "Bcc: ", which is 5 characters long
			$name = self::makeEncodedWord($name, 5);
		} else {
			$name = '"' . $name . '"';	
		}
		
		return $name . ' <' . $email . '>';
	}
	
	
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
	 * Sets the class to try and fix broken qmail implementations that add `\r` to `\r\n`
	 * 
	 * Before trying to fix qmail with this method, please try using fSMTP
	 * to connect to `localhost` and pass the fSMTP object to ::send().
	 * 
	 * @return void
	 */
	static public function fixQmail()
	{
		if (fCore::checkOS('windows')) {
			return;
		}
		
		$sendmail_command = ini_get('sendmail_path');
		
		if (!$sendmail_command) {
			self::$convert_crlf = TRUE;
			trigger_error(
				self::compose('The proper fix for sending through qmail is not possible since the sendmail path is not set'),
				E_USER_WARNING
			);
			trigger_error(
				self::compose('Trying to fix qmail by converting all \r\n to \n. This will cause invalid (but possibly functioning) email headers to be generated.'),
				E_USER_WARNING
			);
		}
		
		$sendmail_command_parts = explode(' ', $sendmail_command, 2);
		
		$sendmail_path   = $sendmail_command_parts[0];
		$sendmail_dir    = pathinfo($sendmail_path, PATHINFO_DIRNAME);
		$sendmail_params = (isset($sendmail_command_parts[1])) ? $sendmail_command_parts[1] : '';
		
		// Check to see if we can run sendmail via popen
		$executable = FALSE;
		$safe_mode  = FALSE;
		
		if (!in_array(strtolower(ini_get('safe_mode')), array('0', '', 'off'))) {
			$safe_mode = TRUE;
			$exec_dirs = explode(';', ini_get('safe_mode_exec_dir'));
			foreach ($exec_dirs as $exec_dir) {
				if (stripos($sendmail_dir, $exec_dir) !== 0) {
					continue;
				}
				if (file_exists($sendmail_path) && is_executable($sendmail_path)) {
					$executable = TRUE;
				}
			}
			
		} else {
			if (file_exists($sendmail_path) && is_executable($sendmail_path)) {
				$executable = TRUE;
			}
		}
		
		if ($executable) {
			self::$popen_sendmail = TRUE;
		} else {
			self::$convert_crlf   = TRUE;
			if ($safe_mode) {
				trigger_error(
					self::compose('The proper fix for sending through qmail is not possible since safe mode is turned on and the sendmail binary is not in one of the paths defined by the safe_mode_exec_dir ini setting'),
					E_USER_WARNING
				);
				trigger_error(
					self::compose('Trying to fix qmail by converting all \r\n to \n. This will cause invalid (but possibly functioning) email headers to be generated.'),
					E_USER_WARNING
				);
			} else {
				trigger_error(
					self::compose('The proper fix for sending through qmail is not possible since the sendmail binary could not be found or is not executable'),
					E_USER_WARNING
				);
				trigger_error(
					self::compose('Trying to fix qmail by converting all \r\n to \n. This will cause invalid (but possibly functioning) email headers to be generated.'),
					E_USER_WARNING
				);
			}
		}
	}
	
	
	/**
	 * Returns the fully-qualified domain name of the server
	 * 
	 * @internal
	 * 
	 * @return string  The fully-qualified domain name of the server
	 */
	static public function getFQDN()
	{
		if (self::$fqdn !== NULL) {
			return self::$fqdn;
		}
		
		if (isset($_ENV['HOST'])) {
			self::$fqdn = $_ENV['HOST'];
		}
		if (strpos(self::$fqdn, '.') === FALSE && isset($_ENV['HOSTNAME'])) {
			self::$fqdn = $_ENV['HOSTNAME'];
		}
		if (strpos(self::$fqdn, '.') === FALSE) {
			self::$fqdn = php_uname('n');
		}
		
		if (strpos(self::$fqdn, '.') === FALSE) {
			
			$can_exec = !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions')))) && !ini_get('safe_mode');
			if (fCore::checkOS('linux') && $can_exec) {
				self::$fqdn = trim(shell_exec('hostname --fqdn'));
				
			} elseif (fCore::checkOS('windows')) {
				$shell = new COM('WScript.Shell');
				$tcpip_key = 'HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\Tcpip';
				try {
					$domain = $shell->RegRead($tcpip_key . '\Parameters\NV Domain');
				} catch (com_exception $e) {
					try {
						$domain = $shell->RegRead($tcpip_key . '\Parameters\DhcpDomain');
					} catch (com_exception $e) {
						try {
							$adapters = $shell->RegRead($tcpip_key . '\Linkage\Route');
							foreach ($adapters as $adapter) {
								if ($adapter[0] != '{') { continue; }
								try {
									$domain = $shell->RegRead($tcpip_key . '\Interfaces\\' . $adapter . '\Domain');
								} catch (com_exception $e) {
									try {
										$domain = $shell->RegRead($tcpip_key . '\Interfaces\\' . $adapter . '\DhcpDomain');
									} catch (com_exception $e) { }
								}
							}
						} catch (com_exception $e) { }
					}
				}
				if (!empty($domain)) {
					self::$fqdn .= '.' . $domain;
				} 
				
			} elseif (!fCore::checkOS('windows') && !ini_get('open_basedir') && file_exists('/etc/resolv.conf')) {
				$output = file_get_contents('/etc/resolv.conf');
				if (preg_match('#^domain ([a-z0-9_.-]+)#im', $output, $match)) {
					self::$fqdn .= '.' . $match[1];
				}
			}
		}
		
		return self::$fqdn;
	}


	/**
	 * Encodes a string to UTF-8 encoded-word
	 * 
	 * @param  string  $content                   The content to encode
	 * @param  integer $first_line_prefix_length  The length of any prefix applied to the first line of the encoded word - this allows properly accounting for a header name
	 * @return string  The encoded string
	 */
	static private function makeEncodedWord($content, $first_line_prefix_length)
	{
		// Homogenize the line-endings to CRLF
		$content = str_replace("\r\n", "\n", $content);
		$content = str_replace("\r", "\n", $content);
		$content = str_replace("\n", "\r\n", $content);
		
		// Encoded word is not required if all characters are ascii
		if (!preg_match('#[\x80-\xFF]#', $content)) {
			return $content;
		}
		
		// A quick a dirty hex encoding
		$content = rawurlencode($content);
		$content = str_replace('=', '%3D', $content);
		$content = str_replace('%', '=', $content);
		
		// Decode characters that don't have to be coded
		$decodings = array(
			'=20' => '_', '=21' => '!', '=22' => '"',  '=23' => '#',
			'=24' => '$', '=25' => '%', '=26' => '&',  '=27' => "'",
			'=28' => '(', '=29' => ')', '=2A' => '*',  '=2B' => '+',
			'=2C' => ',', '=2D' => '-', '=2E' => '.',  '=2F' => '/',
			'=3A' => ':', '=3B' => ';', '=3C' => '<',  '=3E' => '>',
			'=40' => '@', '=5B' => '[', '=5C' => '\\', '=5D' => ']',
			'=5E' => '^', '=60' => '`', '=7B' => '{',  '=7C' => '|',
			'=7D' => '}', '=7E' => '~', ' '   => '_'
		);
		
		$content = strtr($content, $decodings);
		
		$length = strlen($content);
		
		$prefix = '=?utf-8?Q?';
		$suffix = '?=';
		
		$prefix_length = 10;
		$suffix_length = 2;
		
		// This loop goes through and ensures we are wrapping by 75 chars
		// including the encoded word delimiters
		$output = $prefix;
		$line_length = $prefix_length + $first_line_prefix_length;
		
		for ($i=0; $i<$length; $i++) {
			
			// Get info about the next character
			$char_length = ($content[$i] == '=') ? 3 : 1;
			$char        = $content[$i];
			if ($char_length == 3) {
				$char .= $content[$i+1] . $content[$i+2];
			}
			
			// If we have too long a line, wrap it
			if ($line_length + $suffix_length + $char_length > 75) {
				$output .= $suffix . "\r\n " . $prefix;
				$line_length = $prefix_length + 2;
			}
			
			// Add the character
			$output .= $char;
			
			// Figure out how much longer the line is
			$line_length += $char_length;
			
			// Skip characters if we have an encoded character
			$i += $char_length-1;
		}
		
		if (substr($output, -2) != $suffix) {
			$output .= $suffix;
		}
		
		return $output;
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
		self::$convert_crlf   = FALSE;
		self::$fqdn           = NULL;
		self::$popen_sendmail = FALSE;
	}
	
	
	/**
	 * Returns `TRUE` for non-empty strings, numbers, objects, empty numbers and string-like numbers (such as `0`, `0.0`, `'0'`)
	 * 
	 * @param  mixed $value  The value to check
	 * @return boolean  If the value is string-like
	 */
	static protected function stringlike($value)
	{
		if ((!is_string($value) && !is_object($value) && !is_numeric($value)) || !strlen(trim($value))) {
			return FALSE;	
		}
		
		return TRUE;
	}
	
	
	/**
	 * Takes a block of text, unindents it and replaces {CONSTANT} tokens with the constant's value
	 * 
	 * @param string $text  The text to unindent and replace constants in
	 * @return string  The unindented text
	 */
	static public function unindentExpand($text)
	{
		$text = preg_replace('#^[ \t]*\n|\n[ \t]*$#D', '', $text);
			
		if (preg_match('#^[ \t]+(?=\S)#m', $text, $match)) {
			$text = preg_replace('#^' . preg_quote($match[0]) . '#m', '', $text);
		}
		
		preg_match_all('#\{([a-z][a-z0-9_]*)\}#i', $text, $constants, PREG_SET_ORDER);
		foreach ($constants as $constant) {
			if (!defined($constant[1])) { continue; }
			$text = preg_replace('#' . preg_quote($constant[0], '#') . '#', constant($constant[1]), $text, 1);
		}
		
		return $text;
	}
	
	
	/**
	 * The file contents to attach
	 * 
	 * @var array
	 */
	private $attachments = array();
	
	/**
	 * The email address(es) to BCC to
	 * 
	 * @var array
	 */
	private $bcc_emails = array();
	
	/**
	 * The email address to bounce to
	 * 
	 * @var string
	 */
	private $bounce_to_email = NULL;
	
	/**
	 * The email address(es) to CC to
	 * 
	 * @var array
	 */
	private $cc_emails = array();
	
	/**
	 * Custom headers
	 * 
	 * @var array
	 */
	private $custom_headers = array();
	
	/**
	 * The email address being sent from
	 * 
	 * @var string
	 */
	private $from_email = NULL;
	
	/**
	 * The HTML body of the email
	 * 
	 * @var string
	 */
	private $html_body = NULL;
	
	/**
	 * The Message-ID header for the email
	 * 
	 * @var string
	 */
	private $message_id = NULL;
	
	/**
	 * The plaintext body of the email
	 * 
	 * @var string
	 */
	private $plaintext_body = NULL;
	
	/**
	 * The recipient's S/MIME PEM certificate filename, used for encryption of the message
	 * 
	 * @var string
	 */
	private $recipients_smime_cert_file = NULL;
	
	/**
	 * The files to include as multipart/related
	 * 
	 * @var array
	 */
	private $related_files = array();
	
	/**
	 * The email address to reply to
	 * 
	 * @var string
	 */
	private $reply_to_email = NULL;
	
	/**
	 * The email address actually sending the email
	 * 
	 * @var string
	 */
	private $sender_email = NULL;
	
	/**
	 * The senders's S/MIME PEM certificate filename, used for singing the message
	 * 
	 * @var string
	 */
	private $senders_smime_cert_file = NULL;
	
	/**
	 * The senders's S/MIME private key filename, used for singing the message
	 * 
	 * @var string
	 */
	private $senders_smime_pk_file = NULL;
	
	/**
	 * The senders's S/MIME private key password, used for singing the message
	 * 
	 * @var string
	 */
	private $senders_smime_pk_password = NULL;
	
	/**
	 * If the message should be encrypted using the recipient's S/MIME certificate
	 * 
	 * @var boolean
	 */
	private $smime_encrypt = FALSE;
	
	/**
	 * If the message should be signed using the senders's S/MIME private key
	 * 
	 * @var boolean
	 */
	private $smime_sign = FALSE;
	
	/**
	 * The subject of the email
	 * 
	 * @var string
	 */
	private $subject = NULL;
	
	/**
	 * The email address(es) to send to
	 * 
	 * @var array
	 */
	private $to_emails = array();
	
	
	/**
	 * Initializes fEmail for creating message ids
	 * 
	 * @return fEmail
	 */
	public function __construct()
	{
		$this->message_id = '<' . fCryptography::randomString(10, 'hexadecimal') . '.' . time() . '@' . self::getFQDN() . '>';
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
	 * Adds an attachment to the email
	 * 
	 * If a duplicate filename is detected, it will be changed to be unique.
	 * 
	 * @param  string|fFile $contents   The contents of the file
	 * @param  string       $filename   The name to give the attachement - optional if `$contents` is an fFile object
	 * @param  string       $mime_type  The mime type of the file - this allows overriding the mime type of the file if incorrectly detected
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function addAttachment($contents, $filename=NULL, $mime_type=NULL)
	{
		$this->extrapolateFileInfo($contents, $filename, $mime_type);
		
		while (isset($this->attachments[$filename])) {
			$filename = $this->generateNewFilename($filename);
		}
		
		$this->attachments[$filename] = array(
			'mime-type' => $mime_type,
			'contents'  => $contents
		);

		return $this;
	}
	
	
	/**
	 * Adds a “related” file to the email, returning the `Content-ID` for use in HTML
	 * 
	 * The purpose of a related file is to be able to reference it in part of
	 * the HTML body. Image `src` URLs can reference a related file by starting
	 * the URL with `cid:` and then inserting the `Content-ID`.
	 * 
	 * If a duplicate filename is detected, it will be changed to be unique.
	 * 
	 * @param  string|fFile $contents   The contents of the file
	 * @param  string       $filename   The name to give the attachement - optional if `$contents` is an fFile object
	 * @param  string       $mime_type  The mime type of the file - this allows overriding the mime type of the file if incorrectly detected
	 * @return string  The fully-formed `cid:` URL for use in HTML `src` attributes
	 */
	public function addRelatedFile($contents, $filename=NULL, $mime_type=NULL)
	{
		$this->extrapolateFileInfo($contents, $filename, $mime_type);
		
		while (isset($this->related_files[$filename])) {
			$filename = $this->generateNewFilename($filename);
		}
		
		$cid = count($this->related_files) . '.' . substr($this->message_id, 1, -1);
		
		$this->related_files[$filename] = array(
			'mime-type'  => $mime_type,
			'contents'   => $contents,
			'content-id' => '<' . $cid . '>'
		);
		
		return 'cid:' . $cid;
	}
	
	
	/**
	 * Adds a blind carbon copy (BCC) email recipient
	 * 
	 * @param  string $email  The email address to BCC
	 * @param  string $name   The recipient's name
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function addBCCRecipient($email, $name=NULL)
	{
		if (!$email) {
			return;
		}
		
		$this->bcc_emails[] = self::combineNameEmail($name, $email);

		return $this;
	}
	
	
	/**
	 * Adds a carbon copy (CC) email recipient
	 * 
	 * @param  string $email  The email address to BCC
	 * @param  string $name   The recipient's name
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function addCCRecipient($email, $name=NULL)
	{
		if (!$email) {
			return;
		}
		
		$this->cc_emails[] = self::combineNameEmail($name, $email);

		return $this;
	}
	
	
	/**
	 * Allows adding a custom header to the email
	 * 
	 * If the method is called multiple times with the same name, the last
	 * value will be used.
	 * 
	 * Please note that this class will properly format the header, including
	 * adding the `:` between the name and value and wrapping values that are
	 * too long for a single line.
	 * 
	 * @param  string $name      The name of the header
	 * @param  string $value     The value of the header
	 * @param  array  :$headers  An associative array of `{name} => {value}`
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function addCustomHeader($name, $value=NULL)
	{
		if ($value === NULL && is_array($name)) {
			foreach ($name as $key => $value) {
				$this->addCustomHeader($key, $value);
			}
			return;	
		}
		
		$lower_name = fUTF8::lower($name);
		$this->custom_headers[$lower_name] = array($name, $value);

		return $this;
	}
	
	
	/**
	 * Adds an email recipient
	 * 
	 * @param  string $email  The email address to send to
	 * @param  string $name   The recipient's name
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function addRecipient($email, $name=NULL)
	{
		if (!$email) {
			return;
		}
		
		$this->to_emails[] = self::combineNameEmail($name, $email);

		return $this;
	}
	
	
	/**
	 * Takes a multi-address email header and builds it out using an array of emails
	 * 
	 * @param  string $header  The header name without `': '`, the header is non-blank, `': '` will be added
	 * @param  array  $emails  The email addresses for the header
	 * @return string  The email header with a trailing `\r\n`
	 */
	private function buildMultiAddressHeader($header, $emails)
	{
		$header .= ': ';
		
		$first = TRUE;
		$line  = 1;
		foreach ($emails as $email) {
			if ($first) { $first = FALSE; } else { $header .= ', '; }
			
			// Try to stay within the recommended 78 character line limit
			$last_crlf_pos = (integer) strrpos($header, "\r\n");
			if (strlen($header . $email) - $last_crlf_pos > 78) {
				$header .= "\r\n ";
				$line++;
			}
			
			$header .= trim($email);
		}
		
		return $header . "\r\n";
	}
	
	
	/**
	 * Removes all To, CC and BCC recipients from the email
	 * 
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function clearRecipients()
	{
		$this->to_emails  = array();
		$this->cc_emails  = array();
		$this->bcc_emails = array();

		return $this;
	}
	
	
	/**
	 * Creates a 32-character boundary for a multipart message
	 * 
	 * @return string  A multipart boundary
	 */
	private function createBoundary()
	{
		$chars      = 'ancdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ:-_';
		$last_index = strlen($chars) - 1;
		$output     = '';
		
		for ($i = 0; $i < 28; $i++) {
			$output .= $chars[rand(0, $last_index)];
		}
		return $output;
	}
	
	
	/**
	 * Builds the body of the email
	 * 
	 * @param  string $boundary  The boundary to use for the top level mime block
	 * @return string  The message body to be sent to the mail() function
	 */
	private function createBody($boundary)
	{
		$boundary_stack = array($boundary);
		
		$mime_notice = self::compose(
			"This message has been formatted using MIME. It does not appear that your\r\nemail client supports MIME."
		);
		
		$body = '';
		
		if ($this->html_body || $this->attachments) {
			$body .= $mime_notice . "\r\n\r\n";
		}
		
		if ($this->html_body && $this->related_files && $this->attachments) {
			$body    .= '--' . end($boundary_stack) . "\r\n";
			$boundary_stack[] = $this->createBoundary();
			$body .= 'Content-Type: multipart/related; boundary="' . end($boundary_stack) . "\"\r\n\r\n";
		}
		
		if ($this->html_body && ($this->attachments || $this->related_files)) {
			$body    .= '--' . end($boundary_stack) . "\r\n";
			$boundary_stack[] = $this->createBoundary();
			$body    .= 'Content-Type: multipart/alternative; boundary="' . end($boundary_stack) . "\"\r\n\r\n";
		}
		
		if ($this->html_body || $this->attachments) {
			$body .= '--' . end($boundary_stack) . "\r\n";
			$body .= "Content-Type: text/plain; charset=utf-8\r\n";
			$body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
		}
		
		$body .= $this->makeQuotedPrintable($this->plaintext_body) . "\r\n";
		
		if ($this->html_body) {
			$body .= '--' . end($boundary_stack) . "\r\n";
			$body .= "Content-Type: text/html; charset=utf-8\r\n";
			$body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
			$body .= $this->makeQuotedPrintable($this->html_body) . "\r\n";
		}
		
		if ($this->related_files) {
			$body .= '--' . end($boundary_stack) . "--\r\n";
			array_pop($boundary_stack);
			
			foreach ($this->related_files as $filename => $file_info) {
				$body .= '--' . end($boundary_stack) . "\r\n";
				$body .= 'Content-Type: ' . $file_info['mime-type'] . '; name="' . $filename . "\"\r\n";
				$body .= "Content-Transfer-Encoding: base64\r\n";
				$body .= 'Content-ID: ' . $file_info['content-id'] . "\r\n\r\n";
				$body .= $this->makeBase64($file_info['contents']) . "\r\n";
			}
		}
		
		if ($this->attachments) {
			
			if ($this->html_body) {
				$body .= '--' . end($boundary_stack) . "--\r\n";
				array_pop($boundary_stack);
			}
			
			foreach ($this->attachments as $filename => $file_info) {
				$body .= '--' . end($boundary_stack) . "\r\n";
				$body .= 'Content-Type: ' . $file_info['mime-type'] . "\r\n";
				$body .= "Content-Transfer-Encoding: base64\r\n";
				$body .= 'Content-Disposition: attachment; filename="' . $filename . "\";\r\n\r\n";
				$body .= $this->makeBase64($file_info['contents']) . "\r\n";
			}
		}
		
		if ($this->html_body || $this->attachments) {
			$body .= '--' . end($boundary_stack) . "--\r\n";
			array_pop($boundary_stack);
		}
		
		return $body;
	}
	
	
	/**
	 * Builds the headers for the email
	 * 
	 * @param  string $boundary    The boundary to use for the top level mime block
	 * @param  string $message_id  The message id for the message
	 * @return string  The headers to be sent to the [http://php.net/function.mail mail()] function
	 */
	private function createHeaders($boundary, $message_id)
	{
		$headers = '';
		
		if ($this->cc_emails) {
			$headers .= $this->buildMultiAddressHeader("Cc", $this->cc_emails);
		}
		
		if ($this->bcc_emails) {
			$headers .= $this->buildMultiAddressHeader("Bcc", $this->bcc_emails);
		}
		
		$headers .= "From: " . trim($this->from_email) . "\r\n";
		
		if ($this->reply_to_email) {
			$headers .= "Reply-To: " . trim($this->reply_to_email) . "\r\n";
		}
		
		if ($this->sender_email) {
			$headers .= "Sender: " . trim($this->sender_email) . "\r\n";
		}
		
		foreach ($this->custom_headers as $header_info) {
			$header_prefix = $header_info[0] . ': ';
			$headers .= $header_prefix . self::makeEncodedWord($header_info[1], strlen($header_prefix)) . "\r\n";  
		}
		
		$headers .= "Message-ID: " . $message_id . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		
		if (!$this->html_body && !$this->attachments) {
			$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
			$headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
		
		} elseif ($this->html_body && !$this->attachments) {
			if ($this->related_files) {
				$headers .= 'Content-Type: multipart/related; boundary="' . $boundary . "\"\r\n";
			} else {
				$headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n";
			}
		
		} elseif ($this->attachments) {
			$headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . "\"\r\n";
		}
		
		return $headers . "\r\n";
	}
	
	
	/**
	 * Takes the body of the message and processes it with S/MIME
	 * 
	 * @param  string $to       The recipients being sent to
	 * @param  string $subject  The subject of the email
	 * @param  string $headers  The headers for the message
	 * @param  string $body     The message body
	 * @return array  `0` => The message headers, `1` => The message body
	 */
	private function createSMIMEBody($to, $subject, $headers, $body)
	{
		if (!$this->smime_encrypt && !$this->smime_sign) {
			return array($headers, $body);
		}
		
		$plaintext_file  = tempnam('', '__fEmail_');
		$ciphertext_file = tempnam('', '__fEmail_');
		
		$headers_array = array(
			'To'      => $to,
			'Subject' => $subject
		);
		
		preg_match_all('#^([\w\-]+):\s+([^\n]+\n( [^\n]+\n)*)#im', $headers, $header_matches, PREG_SET_ORDER);
		foreach ($header_matches as $header_match) {
			$headers_array[$header_match[1]] = trim($header_match[2]);
		}
		
		$body_headers = "";
		if (isset($headers_array['Content-Type'])) {
			$body_headers .= 'Content-Type: ' . $headers_array['Content-Type'] . "\r\n";
		}
		if (isset($headers_array['Content-Transfer-Encoding'])) {
			$body_headers .= 'Content-Transfer-Encoding: ' . $headers_array['Content-Transfer-Encoding'] . "\r\n";
		}
		
		if ($body_headers) {
			$body = $body_headers . "\r\n" . $body;
		}
		
		file_put_contents($plaintext_file, $body);
		file_put_contents($ciphertext_file, '');
		
		// Set up the neccessary S/MIME resources
		if ($this->smime_sign) {
			$senders_smime_cert  = file_get_contents($this->senders_smime_cert_file);
			$senders_private_key = openssl_pkey_get_private(
				file_get_contents($this->senders_smime_pk_file),
				$this->senders_smime_pk_password
			);
			
			if ($senders_private_key === FALSE) {
				throw new fValidationException(
					"The sender's S/MIME private key password specified does not appear to be valid for the private key"
				);
			}
		}
		
		if ($this->smime_encrypt) {
			$recipients_smime_cert = file_get_contents($this->recipients_smime_cert_file);
		}
		
		
		// If we are going to sign and encrypt, the best way is to sign, encrypt and then sign again
		if ($this->smime_encrypt && $this->smime_sign) {
			openssl_pkcs7_sign($plaintext_file, $ciphertext_file, $senders_smime_cert, $senders_private_key, array());
			openssl_pkcs7_encrypt($ciphertext_file, $plaintext_file, $recipients_smime_cert, array(), NULL, OPENSSL_CIPHER_RC2_128);
			openssl_pkcs7_sign($plaintext_file, $ciphertext_file, $senders_smime_cert, $senders_private_key, $headers_array);
		
		} elseif ($this->smime_sign) {
			openssl_pkcs7_sign($plaintext_file, $ciphertext_file, $senders_smime_cert, $senders_private_key, $headers_array);
		  
		} elseif ($this->smime_encrypt) {
			openssl_pkcs7_encrypt($plaintext_file, $ciphertext_file, $recipients_smime_cert, $headers_array, NULL, OPENSSL_CIPHER_RC2_128);
		}
		
		// It seems that the contents of the ciphertext is not always \r\n line breaks
		$message = file_get_contents($ciphertext_file);
		$message = str_replace("\r\n", "\n", $message);
		$message = str_replace("\r", "\n", $message);
		$message = str_replace("\n", "\r\n", $message);
		
		list($new_headers, $new_body) = explode("\r\n\r\n", $message, 2);
		
		$new_headers = preg_replace('#^To:[^\n]+\n( [^\n]+\n)*#mi', '', $new_headers);
		$new_headers = preg_replace('#^Subject:[^\n]+\n( [^\n]+\n)*#mi', '', $new_headers);
		$new_headers = preg_replace("#^MIME-Version: 1.0\r?\n#mi", '', $new_headers, 1);
		$new_headers = preg_replace('#^Content-Type:\s+' . preg_quote($headers_array['Content-Type'], '#') . "\r?\n#mi", '', $new_headers);
		$new_headers = preg_replace('#^Content-Transfer-Encoding:\s+' . preg_quote($headers_array['Content-Transfer-Encoding'], '#') . "\r?\n#mi", '', $new_headers);
		
		unlink($plaintext_file);
		unlink($ciphertext_file);
		
		if ($this->smime_sign) {
			openssl_pkey_free($senders_private_key);
		}
								  
		return array($new_headers, $new_body);
	}
	
	
	/**
	 * Sets the email to be encrypted with S/MIME
	 * 
	 * @param  string $recipients_smime_cert_file  The file path to the PEM-encoded S/MIME certificate for the recipient
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function encrypt($recipients_smime_cert_file)
	{
		if (!extension_loaded('openssl')) {
			throw new fEnvironmentException(
				'S/MIME encryption was requested for an email, but the %s extension is not installed',
				'openssl'
			);
		}
		
		if (!self::stringlike($recipients_smime_cert_file)) {
			throw new fProgrammerException(
				"The recipient's S/MIME certificate filename specified, %s, does not appear to be a valid filename",
				$recipients_smime_cert_file
			);
		}
		
		$this->smime_encrypt              = TRUE;
		$this->recipients_smime_cert_file = $recipients_smime_cert_file;

		return $this;
	}
	
	
	/**
	 * Extracts just the email addresses from an array of strings containing an
	 * <email@address.com> or "Name" <email@address.com> combination.
	 * 
	 * @param array $list  The list of email or name/email to extract from
	 * @return array  The email addresses
	 */
	private function extractEmails($list)
	{
		$output = array();
		foreach ($list as $email) {
			if (preg_match(self::NAME_EMAIL_REGEX, $email, $match)) {
				$output[] = $match[2];
			} else {
				preg_match(self::EMAIL_REGEX, $email, $match);
				$output[] = $match[0];
			}
		}
		return $output;
	}
	
	
	/**
	 * Extracts the filename and mime-type from an fFile object
	 * 
	 * @param  string|fFile &$contents   The file to extrapolate the info from
	 * @param  string       &$filename   The filename to use for the file
	 * @param  string       &$mime_type  The mime type of the file
	 * @return void
	 */
	private function extrapolateFileInfo(&$contents, &$filename, &$mime_type)
	{
		if ($contents instanceof fFile) {
			if ($filename === NULL) {
				$filename = $contents->getName();
			}
			if ($mime_type === NULL) {
				$mime_type = $contents->getMimeType();
			}
			$contents = $contents->read();
			
		} else {
			if (!self::stringlike($filename)) {
				throw new fProgrammerException(
					'The filename specified, %s, does not appear to be a valid filename',
					$filename
				);
			}
			
			$filename = (string) $filename;
			
			if ($mime_type === NULL) {
				$mime_type = fFile::determineMimeType($filename, $contents);
			}
		}
	}
	
	
	/**
	 * Generates a new filename in an attempt to create a unique name
	 * 
	 * @param  string $filename  The filename to generate another name for
	 * @return string  The newly generated filename
	 */
	private function generateNewFilename($filename)
	{
		$filename_info = fFilesystem::getPathInfo($filename);
		if (preg_match('#_copy(\d+)($|\.)#D', $filename_info['filename'], $match)) {
			$i = $match[1] + 1;
		} else {
			$i = 1;
		}
		$extension = ($filename_info['extension']) ? '.' . $filename_info['extension'] : '';
		return preg_replace('#_copy\d+$#D', '', $filename_info['filename']) . '_copy' . $i . $extension;
	}
	
	
	/**
	 * Loads the plaintext version of the email body from a file and applies replacements
	 * 
	 * The should contain either ASCII or UTF-8 encoded text. Please see
	 * http://flourishlib.com/docs/UTF-8 for more information.
	 * 
	 * @throws fValidationException  When no file was specified, the file does not exist or the path specified is not a file
	 * 
	 * @param  string|fFile $file          The plaintext version of the email body
	 * @param  array        $replacements  The method will search the contents of the file for each key and replace it with the corresponding value
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function loadBody($file, $replacements=array())
	{
		if (!$file instanceof fFile) {
			$file = new fFile($file);	
		}
		
		$plaintext = $file->read();
		if ($replacements) {
			$plaintext = strtr($plaintext, $replacements);	
		}
		
		$this->plaintext_body = $plaintext;

		return $this;
	}
	
	
	/**
	 * Loads the plaintext version of the email body from a file and applies replacements
	 * 
	 * The should contain either ASCII or UTF-8 encoded text. Please see
	 * http://flourishlib.com/docs/UTF-8 for more information.
	 * 
	 * @throws fValidationException  When no file was specified, the file does not exist or the path specified is not a file
	 * 
	 * @param  string|fFile $file          The plaintext version of the email body
	 * @param  array        $replacements  The method will search the contents of the file for each key and replace it with the corresponding value
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function loadHTMLBody($file, $replacements=array())
	{
		if (!$file instanceof fFile) {
			$file = new fFile($file);	
		}
		
		$html = $file->read();
		if ($replacements) {
			$html = strtr($html, $replacements);	
		}
		
		$this->html_body = $html;

		return $this;
	}
	
	
	/**
	 * Encodes a string to base64
	 * 
	 * @param  string  $content  The content to encode
	 * @return string  The encoded string
	 */
	private function makeBase64($content)
	{
		return chunk_split(base64_encode($content));
	}
	
	
	/**
	 * Encodes a string to quoted-printable, properly handles UTF-8
	 * 
	 * @param  string  $content  The content to encode
	 * @return string  The encoded string
	 */
	private function makeQuotedPrintable($content)
	{
		// Homogenize the line-endings to CRLF
		$content = str_replace("\r\n", "\n", $content);
		$content = str_replace("\r", "\n", $content);
		$content = str_replace("\n", "\r\n", $content);
		
		// A quick a dirty hex encoding
		$content = rawurlencode($content);
		$content = str_replace('=', '%3D', $content);
		$content = str_replace('%', '=', $content);
		
		// Decode characters that don't have to be coded
		$decodings = array(
			'=20' => ' ', '=21' => '!', '=22' => '"', '=23' => '#',
			'=24' => '$', '=25' => '%', '=26' => '&', '=27' => "'",
			'=28' => '(', '=29' => ')', '=2A' => '*', '=2B' => '+',
			'=2C' => ',', '=2D' => '-', '=2E' => '.', '=2F' => '/',
			'=3A' => ':', '=3B' => ';', '=3C' => '<', '=3E' => '>',
			'=3F' => '?', '=40' => '@', '=5B' => '[', '=5C' => '\\',
			'=5D' => ']', '=5E' => '^', '=5F' => '_', '=60' => '`',
			'=7B' => '{', '=7C' => '|', '=7D' => '}', '=7E' => '~'
		);
		
		$content = strtr($content, $decodings);
		
		$output = '';
		
		$length = strlen($content);
		
		// This loop goes through and ensures we are wrapping by 76 chars
		$line_length = 0;
		for ($i=0; $i<$length; $i++) {
			
			// Get info about the next character
			$char_length = ($content[$i] == '=') ? 3 : 1;
			$char        = $content[$i];
			if ($char_length == 3) {
				$char .= $content[$i+1] . $content[$i+2];
			}
			
			// Skip characters if we have an encoded character, this must be
			// done before checking for whitespace at the beginning and end of
			// lines or else characters in the content will be skipped
			$i += $char_length-1;
			
			// Spaces and tabs at the beginning and ending of lines have to be encoded
			$begining_or_end = $line_length > 69 || $line_length == 0;
			$tab_or_space    = $char == ' ' || $char == "\t";
			if ($begining_or_end && $tab_or_space) {
				$char_length = 3;
				$char        = ($char == ' ') ? '=20' : '=09';
			}
			
			// If we have too long a line, wrap it
			if ($char != "\r" && $char != "\n" && $line_length + $char_length > 75) {
				$output .= "=\r\n";
				$line_length = 0;
			}
			
			// Add the character
			$output .= $char;
			
			// Figure out how much longer the line is now
			if ($char == "\r" || $char == "\n") {
				$line_length = 0;
			} else {
				$line_length += $char_length;
			}
		}
		
		return $output;
	}
	
	
	/**
	 * Sends the email
	 * 
	 * The return value is the message id, which should be included as the
	 * `Message-ID` header of the email. While almost all SMTP servers will not
	 * modify this value, testing has indicated at least one (smtp.live.com
	 * for Windows Live Mail) does.
	 * 
	 * @throws fValidationException  When ::validate() throws an exception
	 * 
	 * @param  fSMTP $connection  The SMTP connection to send the message over
	 * @return string  The message id for the message - see method description for details
	 */
	public function send($connection=NULL)
	{
		$this->validate();
		
		// The mail() function on Windows doesn't support names in headers so
		// we must strip them down to just the email address
		if ($connection === NULL && fCore::checkOS('windows')) {
			$vars = array('bcc_emails', 'bounce_to_email', 'cc_emails', 'from_email', 'reply_to_email', 'sender_email', 'to_emails');
			foreach ($vars as $var) {
				if (!is_array($this->$var)) {
					if (preg_match(self::NAME_EMAIL_REGEX, $this->$var, $match)) {
						$this->$var = $match[2];
					}
				} else {
					$new_emails = array();
					foreach ($this->$var as $email) {
						if (preg_match(self::NAME_EMAIL_REGEX, $email, $match)) {
							$email = $match[2];
						}
						$new_emails[] = $email;
					}
					$this->$var = $new_emails;
				}
			}
		}
		
		$to = substr(trim($this->buildMultiAddressHeader("To", $this->to_emails)), 4);
		
		$top_level_boundary = $this->createBoundary();
		$headers            = $this->createHeaders($top_level_boundary, $this->message_id);
		
		$subject = str_replace(array("\r", "\n"), '', $this->subject);
		$subject = self::makeEncodedWord($subject, 9);
		
		$body = $this->createBody($top_level_boundary);
		
		if ($this->smime_encrypt || $this->smime_sign) {
			list($headers, $body) = $this->createSMIMEBody($to, $subject, $headers, $body);
		}
		
		// Remove extra line breaks
		$headers = trim($headers);
		$body    = trim($body);
		
		if ($connection) {
			$to_emails = $this->extractEmails($this->to_emails);
			$to_emails = array_merge($to_emails, $this->extractEmails($this->cc_emails));
			$to_emails = array_merge($to_emails, $this->extractEmails($this->bcc_emails));
			$from = $this->bounce_to_email ? $this->bounce_to_email : current($this->extractEmails(array($this->from_email)));
			$connection->send($from, $to_emails, "To: " . $to . "\r\nSubject: " . $subject . "\r\n" . $headers, $body);
			return $this->message_id;
		}
		
		// Sendmail when not in safe mode will allow you to set the envelope from address via the -f parameter
		$parameters = NULL;
		if (!fCore::checkOS('windows') && $this->bounce_to_email) {
			preg_match(self::EMAIL_REGEX, $this->bounce_to_email, $matches);
			$parameters = '-f ' . $matches[0];
		
		// Windows takes the Return-Path email from the sendmail_from ini setting
		} elseif (fCore::checkOS('windows') && $this->bounce_to_email) {
			$old_sendmail_from = ini_get('sendmail_from');
			preg_match(self::EMAIL_REGEX, $this->bounce_to_email, $matches);
			ini_set('sendmail_from', $matches[0]);
		}
		
		// This is a gross qmail fix that is a last resort
		if (self::$popen_sendmail || self::$convert_crlf) {
			$to      = str_replace("\r\n", "\n", $to);
			$subject = str_replace("\r\n", "\n", $subject);
			$body    = str_replace("\r\n", "\n", $body);
			$headers = str_replace("\r\n", "\n", $headers);
		}
		
		// If the user is using qmail and wants to try to fix the \r\r\n line break issue
		if (self::$popen_sendmail) {
			$sendmail_command = ini_get('sendmail_path');
			if ($parameters) {
				$sendmail_command .= ' ' . $parameters;
			}
			
			$sendmail_process = popen($sendmail_command, 'w');
			fprintf($sendmail_process, "To: %s\n", $to);
			fprintf($sendmail_process, "Subject: %s\n", $subject);
			if ($headers) {
				fprintf($sendmail_process, "%s\n", $headers);
			}
			fprintf($sendmail_process, "\n%s\n", $body);
			$error = pclose($sendmail_process);
			
		// This is the normal way to send mail
		} else {
			// On Windows, mail() sends directly to an SMTP server and will
			// strip a leading . from the body
			if (fCore::checkOS('windows')) {
				$body = preg_replace('#^\.#', '..', $body);
			}
			
			if ($parameters) {
				$error = !mail($to, $subject, $body, $headers, $parameters);
			} else {
				$error = !mail($to, $subject, $body, $headers);
			}
		}
		
		if (fCore::checkOS('windows') && $this->bounce_to_email) {
			ini_set('sendmail_from', $old_sendmail_from);
		}
		
		if ($error) {
			throw new fConnectivityException(
				'An error occured while trying to send the email entitled %s',
				$this->subject
			);
		}
		
		return $this->message_id;
	}
	
	
	/**
	 * Sets the plaintext version of the email body
	 * 
	 * This method accepts either ASCII or UTF-8 encoded text. Please see
	 * http://flourishlib.com/docs/UTF-8 for more information.
	 * 
	 * @param  string  $plaintext                  The plaintext version of the email body
	 * @param  boolean $unindent_expand_constants  If this is `TRUE`, the body will be unindented as much as possible and {CONSTANT_NAME} will be replaced with the value of the constant
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function setBody($plaintext, $unindent_expand_constants=FALSE)
	{
		if ($unindent_expand_constants) {
			$plaintext = self::unindentExpand($plaintext);
		}
		
		$this->plaintext_body = $plaintext;

		return $this;
	}
	
	
	/**
	 * Adds the email address the email will be bounced to
	 * 
	 * This email address will be set to the `Return-Path` header.
	 * 
	 * @param  string $email  The email address to bounce to
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function setBounceToEmail($email)
	{
		if (ini_get('safe_mode') && !fCore::checkOS('windows')) {
			throw new fProgrammerException('It is not possible to set a Bounce-To Email address when safe mode is enabled on a non-Windows server');
		}
		if (!$email) {
			return;
		}
		
		$this->bounce_to_email = self::combineNameEmail('', $email);

		return $this;
	}
	
	
	/**
	 * Adds the `From:` email address to the email
	 * 
	 * @param  string $email  The email address being sent from
	 * @param  string $name   The from email user's name - unfortunately on windows this is ignored
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function setFromEmail($email, $name=NULL)
	{
		if (!$email) {
			return;
		}
		
		$this->from_email = self::combineNameEmail($name, $email);

		return $this;
	}
	
	
	/**
	 * Sets the HTML version of the email body
	 * 
	 * This method accepts either ASCII or UTF-8 encoded text. Please see
	 * http://flourishlib.com/docs/UTF-8 for more information.
	 * 
	 * @param  string $html  The HTML version of the email body
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function setHTMLBody($html)
	{
		$this->html_body = $html;

		return $this;
	}
	
	
	/**
	 * Adds the `Reply-To:` email address to the email
	 * 
	 * @param  string $email  The email address to reply to
	 * @param  string $name   The reply-to email user's name
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function setReplyToEmail($email, $name=NULL)
	{
		if (!$email) {
			return;
		}
		
		$this->reply_to_email = self::combineNameEmail($name, $email);

		return $this;
	}
	
	
	/**
	 * Adds the `Sender:` email address to the email
	 * 
	 * The `Sender:` header is used to indicate someone other than the `From:`
	 * address is actually submitting the message to the network.
	 * 
	 * @param  string $email  The email address the message is actually being sent from
	 * @param  string $name   The sender email user's name
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function setSenderEmail($email, $name=NULL)
	{
		if (!$email) {
			return;
		}
		
		$this->sender_email = self::combineNameEmail($name, $email);

		return $this;
	}
	
	
	/**
	 * Sets the subject of the email
	 * 
	 * This method accepts either ASCII or UTF-8 encoded text. Please see
	 * http://flourishlib.com/docs/UTF-8 for more information.
	 * 
	 * @param  string $subject  The subject of the email
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function setSubject($subject)
	{
		$this->subject = $subject;

		return $this;
	}
	
	
	/**
	 * Sets the email to be signed with S/MIME
	 * 
	 * @param  string $senders_smime_cert_file    The file path to the sender's PEM-encoded S/MIME certificate
	 * @param  string $senders_smime_pk_file      The file path to the sender's S/MIME private key
	 * @param  string $senders_smime_pk_password  The password for the sender's S/MIME private key
	 * @return fEmail  The email object, to allow for method chaining
	 */
	public function sign($senders_smime_cert_file, $senders_smime_pk_file, $senders_smime_pk_password)
	{
		if (!extension_loaded('openssl')) {
			throw new fEnvironmentException(
				'An S/MIME signature was requested for an email, but the %s extension is not installed',
				'openssl'
			);
		}
		
		if (!self::stringlike($senders_smime_cert_file)) {
			throw new fProgrammerException(
				"The sender's S/MIME certificate file specified, %s, does not appear to be a valid filename",
				$senders_smime_cert_file
			);
		}
		if (!file_exists($senders_smime_cert_file) || !is_readable($senders_smime_cert_file)) {
			throw new fEnvironmentException(
				"The sender's S/MIME certificate file specified, %s, does not exist or could not be read",
				$senders_smime_cert_file
			);
		}
		
		if (!self::stringlike($senders_smime_pk_file)) {
			throw new fProgrammerException(
				"The sender's S/MIME primary key file specified, %s, does not appear to be a valid filename",
				$senders_smime_pk_file
			);
		}
		if (!file_exists($senders_smime_pk_file) || !is_readable($senders_smime_pk_file)) {
			throw new fEnvironmentException(
				"The sender's S/MIME primary key file specified, %s, does not exist or could not be read",
				$senders_smime_pk_file
			);
		}
		
		$this->smime_sign                = TRUE;
		$this->senders_smime_cert_file   = $senders_smime_cert_file;
		$this->senders_smime_pk_file     = $senders_smime_pk_file;
		$this->senders_smime_pk_password = $senders_smime_pk_password;

		return $this;
	}
	
	
	/**
	 * Validates that all of the parts of the email are valid
	 * 
	 * @throws fValidationException  When part of the email is missing or formatted incorrectly
	 * 
	 * @return void
	 */
	private function validate()
	{
		$validation_messages = array();
		
		// Check all multi-address email field
		$multi_address_field_list = array(
			'to_emails'  => self::compose('recipient'),
			'cc_emails'  => self::compose('CC recipient'),
			'bcc_emails' => self::compose('BCC recipient')
		);
		
		foreach ($multi_address_field_list as $field => $name) {
			foreach ($this->$field as $email) {
				if ($email && !preg_match(self::NAME_EMAIL_REGEX, $email) && !preg_match(self::EMAIL_REGEX, $email)) {
					$validation_messages[] = htmlspecialchars(self::compose(
						'The %1$s %2$s is not a valid email address. Should be like "John Smith" <name@example.com> or name@example.com.',
						$name,
						$email
					), ENT_QUOTES, 'UTF-8');
				}
			}
		}
		
		// Check all single-address email fields
		$single_address_field_list = array(
			'from_email'      => self::compose('From email address'),
			'reply_to_email'  => self::compose('Reply-To email address'),
			'sender_email'    => self::compose('Sender email address'),
			'bounce_to_email' => self::compose('Bounce-To email address')
		);
		
		foreach ($single_address_field_list as $field => $name) {
			if ($this->$field && !preg_match(self::NAME_EMAIL_REGEX, $this->$field) && !preg_match(self::EMAIL_REGEX, $this->$field)) {
				$validation_messages[] = htmlspecialchars(self::compose(
					'The %1$s %2$s is not a valid email address. Should be like "John Smith" <name@example.com> or name@example.com.',
					$name,
					$this->$field
				), ENT_QUOTES, 'UTF-8');
			}
		}
		
		// Make sure the required fields are all set
		if (!$this->to_emails) {
			$validation_messages[] = self::compose(
				"Please provide at least one recipient"
			);
		}
		
		if (!$this->from_email) {
			$validation_messages[] = self::compose(
				"Please provide the from email address"
			);
		}
		
		if (!self::stringlike($this->subject)) {
			$validation_messages[] = self::compose(
				"Please provide an email subject"
			);
		}
		
		if (strpos($this->subject, "\n") !== FALSE) {
			$validation_messages[] = self::compose(
				"The subject contains one or more newline characters"
			);	
		}
		
		if (!self::stringlike($this->plaintext_body)) {
			$validation_messages[] = self::compose(
				"Please provide a plaintext email body"
			);
		}
		
		// Make sure the attachments look good
		foreach ($this->attachments as $filename => $file_info) {
			if (!self::stringlike($file_info['mime-type'])) {
				$validation_messages[] = self::compose(
					"No mime-type was specified for the attachment %s",
					$filename
				);
			}
			if (!self::stringlike($file_info['contents'])) {
				$validation_messages[] = self::compose(
					"The attachment %s appears to be a blank file",
					$filename
				);
			}
		}
		
		if ($validation_messages) {
			throw new fValidationException(
				'The email could not be sent because:',
				$validation_messages
			);	
		}
	}
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