<?php
/**
 * Retrieves and deletes messages from a email account via IMAP or POP3
 * 
 * All headers, text and html content returned by this class are encoded in
 * UTF-8. Please see http://flourishlib.com/docs/UTF-8 for more information.
 * 
 * @copyright  Copyright (c) 2010-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fMailbox
 * 
 * @version    1.0.0b14
 * @changes    1.0.0b14  Added a workaround for iconv having issues in MAMP 1.9.4+ [wb, 2011-07-26]
 * @changes    1.0.0b13  Fixed handling of headers in relation to encoded-words being embedded inside of quoted strings [wb, 2011-07-26]
 * @changes    1.0.0b12  Enhanced the error checking in ::write() [wb, 2011-06-03]
 * @changes    1.0.0b11  Added code to work around PHP bug #42682 (http://bugs.php.net/bug.php?id=42682) where `stream_select()` doesn't work on 64bit machines from PHP 5.2.0 to 5.2.5, improved connectivity error handling and timeouts while reading data [wb, 2011-01-10]
 * @changes    1.0.0b10  Fixed ::parseMessage() to properly handle a header format edge case and properly set the `text` and `html` keys even when the email has an explicit `Content-disposition: inline` header [wb, 2010-11-25]
 * @changes    1.0.0b9   Fixed a bug in ::parseMessage() that could cause HTML alternate content to be included in the `inline` content array instead of the `html` element [wb, 2010-09-20]
 * @changes    1.0.0b8   Fixed ::parseMessage() to be able to handle non-text/non-html multipart parts that do not have a `Content-disposition` header [wb, 2010-09-18]
 * @changes    1.0.0b7   Fixed a typo in ::read() [wb, 2010-09-07]
 * @changes    1.0.0b6   Fixed a typo from 1.0.0b4 [wb, 2010-07-21]
 * @changes    1.0.0b5   Fixes for increased compatibility with various IMAP and POP3 servers, hacked around a bug in PHP 5.3 on Windows [wb, 2010-06-22]
 * @changes    1.0.0b4   Added code to handle emails without an explicit `Content-type` header [wb, 2010-06-04]
 * @changes    1.0.0b3   Added missing static method callback constants [wb, 2010-05-11]
 * @changes    1.0.0b2   Added the missing ::enableDebugging() [wb, 2010-05-05]
 * @changes    1.0.0b    The initial implementation [wb, 2010-05-05]
 */
class fMailbox
{
	const addSMIMEPair = 'fMailbox::addSMIMEPair';
	const parseMessage = 'fMailbox::parseMessage';
	const reset        = 'fMailbox::reset';
	
	
	/**
	 * S/MIME certificates and private keys for verification and decryption
	 * 
	 * @var array
	 */
	static private $smime_pairs = array();
	
	
	/**
	 * Adds an S/MIME certificate, or certificate + private key pair for verification and decryption of S/MIME messages
	 * 
	 * @param string       $email_address         The email address the certificate or private key is for
	 * @param fFile|string $certificate_file      The file the S/MIME certificate is stored in - required for verification and decryption
	 * @param fFile        $private_key_file      The file the S/MIME private key is stored in - required for decryption only
	 * @param string       $private_key_password  The password for the private key
	 * @return void
	 */
	static public function addSMIMEPair($email_address, $certificate_file, $private_key_file=NULL, $private_key_password=NULL)
	{
		if ($private_key_file !== NULL && !$private_key_file instanceof fFile) {
			$private_key_file = new fFile($private_key_file);
		}
		if (!$certificate_file instanceof fFile) {
			$certificate_file = new fFile($certificate_file);
		}
		self::$smime_pairs[strtolower($email_address)] = array(
			'certificate' => $certificate_file,
			'private_key' => $private_key_file,
			'password'    => $private_key_password
		);
	}
	
	
	/**
	 * Takes a date, removes comments and cleans up some common formatting inconsistencies
	 * 
	 * @param string $date  The date to clean
	 * @return string  The cleaned date
	 */
	static private function cleanDate($date)
	{
		$date = preg_replace('#\([^)]+\)#', ' ', trim($date));
		$date = preg_replace('#\s+#', ' ', $date);
		$date = preg_replace('#(\d+)-([a-z]+)-(\d{4})#i', '\1 \2 \3', $date);
		$date = preg_replace('#^[a-z]+\s*,\s*#i', '', trim($date));
		return trim($date);
	}
	
	
	/**
	 * Decodes encoded-word headers of any encoding into raw UTF-8
	 * 
	 * @param string $text  The header value to decode
	 * @return string  The decoded UTF-8
	 */
	static private function decodeHeader($text)
	{
		$parts = preg_split('#(=\?[^\?]+\?[QB]\?[^\?]+\?=)#i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		$part_with_encoding = array();
		$output = '';
		foreach ($parts as $part) {
			if ($part === '') {
				continue;
			}
			
			if (preg_match_all('#=\?([^\?]+)\?([QB])\?([^\?]+)\?=#i', $part, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					if (strtoupper($match[2]) == 'Q') {
						$part_string = rawurldecode(strtr(
							$match[3],
							array(
								'=' => '%',
								'_' => ' '
							)
						));
					} else {
						$part_string = base64_decode($match[3]);
					}
					$lower_encoding = strtolower($match[1]);
					$last_key = count($part_with_encoding) - 1;
					if (isset($part_with_encoding[$last_key]) && $part_with_encoding[$last_key]['encoding'] == $lower_encoding) {
						$part_with_encoding[$last_key]['string'] .= $part_string;
					} else {
						$part_with_encoding[] = array('encoding' => $lower_encoding, 'string' => $part_string);
					}
				}
				
			} else {
				$last_key = count($part_with_encoding) - 1;
				if (isset($part_with_encoding[$last_key]) && $part_with_encoding[$last_key]['encoding'] == 'iso-8859-1') {
					$part_with_encoding[$last_key]['string'] .= $part;
				} else {
					$part_with_encoding[] = array('encoding' => 'iso-8859-1', 'string' => $part);
				}
			}
		}
		
		foreach ($part_with_encoding as $part) {
			$output .= self::iconv($part['encoding'], 'UTF-8', $part['string']);
		}
		
		return $output;
	}
	
	
	/**
	 * Handles an individual part of a multipart message
	 * 
	 * @param array  $info       An array of information about the message
	 * @param array  $structure  An array describing the structure of the message
	 * @return array  The modified $info array
	 */
	static private function handlePart($info, $structure)
	{
		if ($structure['type'] == 'multipart') {
			foreach ($structure['parts'] as $part) {
				$info = self::handlePart($info, $part);
			}
			return $info;
		}
		
		if ($structure['type'] == 'application' && in_array($structure['subtype'], array('pkcs7-mime', 'x-pkcs7-mime'))) {
			$to = NULL;
			if (isset($info['headers']['to'][0])) {
				$to = $info['headers']['to'][0]['mailbox'];
				if (!empty($info['headers']['to'][0]['host'])) {
					$to .= '@' . $info['headers']['to'][0]['host'];
				}
			}
			if ($to && !empty(self::$smime_pairs[$to]['private_key'])) {
				if (self::handleSMIMEDecryption($info, $structure, self::$smime_pairs[$to])) {
					return $info;
				}
			}
		}
		
		if ($structure['type'] == 'application' && in_array($structure['subtype'], array('pkcs7-signature', 'x-pkcs7-signature'))) {
			$from = NULL;
			if (isset($info['headers']['from'])) {
				$from = $info['headers']['from']['mailbox'];
				if (!empty($info['headers']['from']['host'])) {
					$from .= '@' . $info['headers']['from']['host'];
				}
			}
			if ($from && !empty(self::$smime_pairs[$from]['certificate'])) {
				if (self::handleSMIMEVerification($info, $structure, self::$smime_pairs[$from])) {
					return $info;
				}
			}
		}
		
		$data = $structure['data'];
		
		if ($structure['encoding'] == 'base64') {
			$content = '';
			foreach (explode("\r\n", $data) as $line) {
				$content .= base64_decode($line);
			}
		} elseif ($structure['encoding'] == 'quoted-printable') {
			$content = quoted_printable_decode($data);
		} else {
			$content = $data;
		}
		
		if ($structure['type'] == 'text') {
			$charset = 'iso-8859-1';
			foreach ($structure['type_fields'] as $field => $value) {
				if (strtolower($field) == 'charset') {
					$charset = $value;
					break;
				}
			}
			$content = self::iconv($charset, 'UTF-8', $content);
			if ($structure['subtype'] == 'html') {
				$content = preg_replace('#(content=(["\'])text/html\s*;\s*charset=(["\']?))' . preg_quote($charset, '#') . '(\3\2)#i', '\1utf-8\4', $content);
			}
		}
		
		// This indicates a content-id which is used for multipart/related
		if ($structure['content_id']) {
			if (!isset($info['related'])) {
				$info['related'] = array();
			}
			$cid = $structure['content_id'][0] == '<' ? substr($structure['content_id'], 1, -1) : $structure['content_id'];
			$info['related']['cid:' . $cid] = array(
				'mimetype' => $structure['type'] . '/' . $structure['subtype'],
				'data'     => $content
			);
			return $info;
		}
		
		
		$has_disposition = !empty($structure['disposition']);
		$is_text         = $structure['type'] == 'text' && $structure['subtype'] == 'plain';
		$is_html         = $structure['type'] == 'text' && $structure['subtype'] == 'html';
		
		// If the part doesn't have a disposition and is not the default text or html, set the disposition to inline
		if (!$has_disposition && ((!$is_text || !empty($info['text'])) && (!$is_html || !empty($info['html'])))) {
			$is_web_image = $structure['type'] == 'image' && in_array($structure['subtype'], array('gif', 'png', 'jpeg', 'pjpeg'));
			$structure['disposition'] = $is_text || $is_html || $is_web_image ? 'inline' : 'attachment';
			$structure['disposition_fields'] = array();
			$has_disposition = TRUE;
		}
		
		
		// Attachments or inline content
		if ($has_disposition) {
			
			$filename = '';
			foreach ($structure['disposition_fields'] as $field => $value) {
				if (strtolower($field) == 'filename') {
					$filename = $value;
					break;
				}
			}
			foreach ($structure['type_fields'] as $field => $value) {
				if (strtolower($field) == 'name') {
					$filename = $value;
					break;
				}
			}
			
			// This automatically handles primary content that has a content-disposition header on it
			if ($structure['disposition'] == 'inline' && $filename === '') {
				if ($is_text && !isset($info['text'])) {
					$info['text'] = $content;
					return $info;
				}
				if ($is_html && !isset($info['html'])) {
					$info['html'] = $content;
					return $info;
				}
			}
			
			if (!isset($info[$structure['disposition']])) {
				$info[$structure['disposition']] = array();
			}
			
			$info[$structure['disposition']][] = array(
				'filename' => $filename,
				'mimetype' => $structure['type'] . '/' . $structure['subtype'],
				'data'     => $content
			);
			return $info;
		}
		
		if ($is_text) {
			$info['text'] = $content;
			return $info;
		}
		
		if ($is_html) {
			$info['html'] = $content;
			return $info;
		}
	}
	
	
	/**
	 * Tries to decrypt an S/MIME message using a private key
	 * 
	 * @param array  &$info       The array of information about a message
	 * @param array  $structure   The structure of this part
	 * @param array  $smime_pair  An associative array containing an S/MIME certificate, private key and password
	 * @return boolean  If the message was decrypted
	 */
	static private function handleSMIMEDecryption(&$info, $structure, $smime_pair)
	{
		$plaintext_file  = tempnam('', '__fMailbox_');
		$ciphertext_file = tempnam('', '__fMailbox_');
		
		$headers   = array();
		$headers[] = "Content-Type: " . $structure['type'] . '/' . $structure['subtype'];
		$headers[] = "Content-Transfer-Encoding: " . $structure['encoding'];
		$header    = "Content-Disposition: " . $structure['disposition'];
		foreach ($structure['disposition_fields'] as $field => $value) {
			$header .= '; ' . $field . '="' . $value . '"';
		}
		$headers[] = $header;
		
		file_put_contents($ciphertext_file, join("\r\n", $headers) . "\r\n\r\n" . $structure['data']);
		
		$private_key = openssl_pkey_get_private(
			$smime_pair['private_key']->read(),
			$smime_pair['password']
		);
		$certificate = $smime_pair['certificate']->read();
		
		$result = openssl_pkcs7_decrypt($ciphertext_file, $plaintext_file, $certificate, $private_key);
		unlink($ciphertext_file);
		
		if (!$result) {
			unlink($plaintext_file);
			return FALSE;
		}
		
		$contents = file_get_contents($plaintext_file);
		$info['raw_message'] = $contents;
		$info = self::handlePart($info, self::parseStructure($contents));
		$info['decrypted'] = TRUE;
		
		unlink($plaintext_file);
		return TRUE;
	}
	
	
	
	/**
	 * Takes a message with an S/MIME signature and verifies it if possible
	 * 
	 * @param array &$info       The array of information about a message
	 * @param array $structure
	 * @param array $smime_pair  An associative array containing an S/MIME certificate file
	 * @return boolean  If the message was verified
	 */
	static private function handleSMIMEVerification(&$info, $structure, $smime_pair)
	{
		$certificates_file = tempnam('', '__fMailbox_');
		$ciphertext_file   = tempnam('', '__fMailbox_');
		
		file_put_contents($ciphertext_file, $info['raw_message']);
		
		$result = openssl_pkcs7_verify(
			$ciphertext_file,
			PKCS7_NOINTERN | PKCS7_NOVERIFY,
			$certificates_file,
			array(),
			$smime_pair['certificate']->getPath()
		);
		unlink($ciphertext_file);
		unlink($certificates_file);
		
		if (!$result || $result === -1) {
			return FALSE;
		}
		
		$info['verified'] = TRUE;
		
		return TRUE;
	}


	/**
	 * This works around a bug in MAMP 1.9.4+ and PHP 5.3 where iconv()
	 * does not seem to properly assign the return value to a variable, but
	 * does work when returning the value.
	 *
	 * @param string $in_charset   The incoming character encoding
	 * @param string $out_charset  The outgoing character encoding
	 * @param string $string       The string to convert
	 * @return string  The converted string
	 */
	static private function iconv($in_charset, $out_charset, $string)
	{
		return iconv($in_charset, $out_charset, $string);
	}
	
	
	/**
	 * Joins parsed emails into a comma-delimited string
	 * 
	 * @param array $emails  An array of emails split into personal, mailbox and host parts
	 * @return string  An comma-delimited list of emails
	 */
	static private function joinEmails($emails)
	{
		$output = '';
		foreach ($emails as $email) {
			if ($output) { $output .= ', '; }
			
			if (!isset($email[0])) {
				$email[0] = !empty($email['personal']) ? $email['personal'] : '';
				$email[2] = $email['mailbox'];
				$email[3] = !empty($email['host']) ? $email['host'] : '';
			}

			$address = $email[2];
			if (!empty($email[3])) {
				$address .= '@' . $email[3];
			}
			$output .= fEmail::combineNameEmail($email[0], $address);
		}
		return $output;
	}
	
	
	/**
	 * Parses a string representation of an email into the persona, mailbox and host parts
	 * 
	 * @param  string $string  The email string to parse
	 * @return array  An associative array with the key `mailbox`, and possibly `host` and `personal`
	 */
	static private function parseEmail($string)
	{
		$email_regex = '((?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+|"[^"\\\\\n\r]+")(?:\.[ \t]*(?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+|"[^"\\\\\n\r]+"[ \t]*))*)@((?:[a-z0-9\\-]+\.)+[a-z]{2,}|\[(?:(?:[01]?\d?\d|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d?\d|2[0-4]\d|25[0-5])\])';
		$name_regex  = '((?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+[ \t]*|"[^"\\\\\n\r]+"[ \t]*)(?:\.?[ \t]*(?:[^\x00-\x20\(\)<>@,;:\\\\"\.\[\]]+[ \t]*|"[^"\\\\\n\r]+"[ \t]*))*)';
		
		if (preg_match('~^[ \t]*' . $name_regex . '[ \t]*<[ \t]*' . $email_regex . '[ \t]*>[ \t]*$~ixD', $string, $match)) {
			$match[1] = trim($match[1]);
			if ($match[1][0] == '"' && substr($match[1], -1) == '"') {
				$match[1] = substr($match[1], 1, -1);
			}
			return array(
				'personal' => self::decodeHeader($match[1]),
				'mailbox' => self::decodeHeader($match[2]),
				'host' => self::decodeHeader($match[3])
			);
		
		} elseif (preg_match('~^[ \t]*(?:<[ \t]*)?' . $email_regex . '(?:[ \t]*>)?[ \t]*$~ixD', $string, $match)) {
			return array(
				'mailbox' => self::decodeHeader($match[1]),
				'host' => self::decodeHeader($match[2])
			);
		
		// This handles the outdated practice of including the personal
		// part of the email in a comment after the email address
		} elseif (preg_match('~^[ \t]*(?:<[ \t]*)?' . $email_regex . '(?:[ \t]*>)?[ \t]*\(([^)]+)\)[ \t]*$~ixD', $string, $match)) {
			$match[3] = trim($match[1]);
			if ($match[3][0] == '"' && substr($match[3], -1) == '"') {
				$match[3] = substr($match[3], 1, -1);
			}
			
			return array(
				'personal' => self::decodeHeader($match[3]),
				'mailbox' => self::decodeHeader($match[1]),
				'host' => self::decodeHeader($match[2])
			);
		}
		
		if (strpos($string, '@') !== FALSE) {
			list ($mailbox, $host) = explode('@', $string, 2);
			return array(
				'mailbox' => self::decodeHeader($mailbox),
				'host' => self::decodeHeader($host)
			);
		}
		
		return array(
			'mailbox' => self::decodeHeader($string),
			'host' => ''
		);
	}
	
	
	/**
	 * Parses full email headers into an associative array
	 * 
	 * @param  string $headers  The header to parse
	 * @param  string $filter   Remove any headers that match this
	 * @return array  The parsed headers
	 */
	static private function parseHeaders($headers, $filter=NULL)
	{
		$header_lines = preg_split("#\r\n(?!\s)#", trim($headers));
		
		$single_email_fields    = array('from', 'sender', 'reply-to');
		$multi_email_fields     = array('to', 'cc');
		$additional_info_fields = array('content-type', 'content-disposition');
		
		$headers = array();
		foreach ($header_lines as $header_line) {
			$header_line = preg_replace("#\r\n\s+#", '', $header_line);
			
			list ($header, $value) = preg_split('#:\s*#', $header_line, 2);
			$header = strtolower($header);
			
			if (strpos($header, $filter) !== FALSE) {
				continue;
			}
			
			$is_single_email          = in_array($header, $single_email_fields);
			$is_multi_email           = in_array($header, $multi_email_fields);
			$is_additional_info_field = in_array($header, $additional_info_fields);
			
			if ($is_additional_info_field) {
				$pieces = preg_split('#;\s*#', $value, 2);
				$value = $pieces[0];
				
				$headers[$header] = array('value' => self::decodeHeader($value));
				
				$fields = array();
				if (!empty($pieces[1])) {
					preg_match_all('#(\w+)=("([^"]+)"|([^\s;]+))(?=;|$)#', $pieces[1], $matches, PREG_SET_ORDER);
					foreach ($matches as $match) {
						$fields[$match[1]] = self::decodeHeader(!empty($match[4]) ? $match[4] : $match[3]);
					}
				}
				$headers[$header]['fields'] = $fields;
			
			} elseif ($is_single_email) {
				$headers[$header] = self::parseEmail($value);
			
			} elseif ($is_multi_email) {
				$strings = array();
				
				preg_match_all('#"[^"]+?"#', $value, $matches, PREG_SET_ORDER);
				foreach ($matches as $i => $match) {
					$strings[] = $match[0];
					$value = preg_replace('#' . preg_quote($match[0], '#') . '#', ':string' . sizeof($strings), $value, 1);
				}
				preg_match_all('#\([^)]+?\)#', $value, $matches, PREG_SET_ORDER);
				foreach ($matches as $i => $match) {
					$strings[] = $match[0];
					$value = preg_replace('#' . preg_quote($match[0], '#') . '#', ':string' . sizeof($strings), $value, 1);
				}
				
				$emails = explode(',', $value);
				array_map('trim', $emails);
				foreach ($strings as $i => $string) {
					$emails = preg_replace(
						'#:string' . ($i+1) . '\b#',
						strtr($string, array('\\' => '\\\\', '$' => '\\$')),
						$emails,
						1
					);
				}
				
				$headers[$header] = array();
				foreach ($emails as $email) {
					$headers[$header][] = self::parseEmail($email);
				}
			
			} elseif ($header == 'references') {
				$headers[$header] = array_map(array('fMailbox', 'decodeHeader'), preg_split('#(?<=>)\s+(?=<)#', $value));
				
			} elseif ($header == 'received') {
				if (!isset($headers[$header])) {
					$headers[$header] = array();
				}
				$headers[$header][] = preg_replace('#\s+#', ' ', self::decodeHeader($value));
				
			} else {
				$headers[$header] = self::decodeHeader($value);
			}
		}
		
		return $headers;
	}
	
	
	/**
	 * Parses a MIME message into an associative array of information
	 * 
	 * The output includes the following keys:
	 * 
	 *  - `'received'`: The date the message was received by the server
	 *  - `'headers'`: An associative array of mail headers, the keys are the header names, in lowercase
	 * 
	 * And one or more of the following:
	 * 
	 *  - `'text'`: The plaintext body
	 *  - `'html'`: The HTML body
	 *  - `'attachment'`: An array of attachments, each containing:
	 *   - `'filename'`: The name of the file
	 *   - `'mimetype'`: The mimetype of the file
	 *   - `'data'`: The raw contents of the file
	 *  - `'inline'`: An array of inline files, each containing:
	 *   - `'filename'`: The name of the file
	 *   - `'mimetype'`: The mimetype of the file
	 *   - `'data'`: The raw contents of the file
	 *  - `'related'`: An associative array of related files, such as embedded images, with the key `'cid:{content-id}'` and an array value containing:
	 *   - `'mimetype'`: The mimetype of the file
	 *   - `'data'`: The raw contents of the file
	 *  - `'verified'`: If the message contents were verified via an S/MIME certificate - if not verified the smime.p7s will be listed as an attachment
	 *  - `'decrypted'`: If the message contents were decrypted via an S/MIME private key - if not decrypted the smime.p7m will be listed as an attachment
	 * 
	 * All values in `headers`, `text` and `body` will have been decoded to
	 * UTF-8. Files in the `attachment`, `inline` and `related` array will all
	 * retain their original encodings.
	 * 
	 * @param string  $message           The full source of the email message
	 * @param boolean $convert_newlines  If `\r\n` should be converted to `\n` in the `text` and `html` parts the message
	 * @return array  The parsed email message - see method description for details
	 */
	static public function parseMessage($message, $convert_newlines=FALSE)
	{
		$info = array();
		list ($headers, $body)   = explode("\r\n\r\n", $message, 2);
		$parsed_headers          = self::parseHeaders($headers);
		$info['received']        = self::cleanDate(preg_replace('#^.*;\s*([^;]+)$#', '\1', $parsed_headers['received'][0]));
		$info['headers']         = array();
		foreach ($parsed_headers as $header => $value) {
			if (substr($header, 0, 8) == 'content-') {
				continue;
			}
			$info['headers'][$header] = $value;
		}
		$info['raw_headers'] = $headers;
		$info['raw_message'] = $message;
		
		$info = self::handlePart($info, self::parseStructure($body, $parsed_headers));
		unset($info['raw_message']);
		unset($info['raw_headers']);
		
		if ($convert_newlines) {
			if (isset($info['text'])) {
				$info['text'] = str_replace("\r\n", "\n", $info['text']);
			}
			if (isset($info['html'])) {
				$info['html'] = str_replace("\r\n", "\n", $info['html']);
			}
		}
		
		if (isset($info['text'])) {
			$info['text'] = preg_replace('#\r?\n$#D', '', $info['text']);
		}
		if (isset($info['html'])) {
			$info['html'] = preg_replace('#\r?\n$#D', '', $info['html']);
		}
		
		return $info;
	}
	
	
	/**
	 * Takes a response from an IMAP command and parses it into a
	 * multi-dimensional array
	 * 
	 * @param string  $text       The IMAP command response
	 * @param boolean $top_level  If we are parsing the top level
	 * @return array  The parsed representation of the response text
	 */
	static private function parseResponse($text, $top_level=FALSE)
	{
		$regex = '[\\\\\w.\[\]]+|"([^"\\\\]+|\\\\"|\\\\\\\\)*"|\((?:(?1)[ \t]*)*\)';
		
		if (preg_match('#\{(\d+)\}#', $text, $match)) {
			$regex = '\{' . $match[1] . '\}\r\n.{' . ($match[1]) . '}|' . $regex;
		}
		
		preg_match_all('#(' . $regex . ')#s', $text, $matches, PREG_SET_ORDER);
		$output = array();
		foreach ($matches as $match) {
			if (substr($match[0], 0, 1) == '"') {
				$output[] = str_replace('\\"', '"', substr($match[0], 1, -1));
			} elseif (substr($match[0], 0, 1) == '(') {
				$output[] = self::parseResponse(substr($match[0], 1, -1));
			} elseif (substr($match[0], 0, 1) == '{') {
				$output[] = preg_replace('#^[^\r]+\r\n#', '', $match[0]);
			} else {
				$output[] = $match[0];
			}
		}
		
		if ($top_level) {
			$new_output = array();
			$total_size = count($output);
			for ($i = 0; $i < $total_size; $i = $i + 2) {
				$new_output[strtolower($output[$i])] = $output[$i+1];
			}
			$output = $new_output;
		}
		
		return $output;
	}
	
	
	/**
	 * Takes the raw contents of a MIME message and creates an array that
	 * describes the structure of the message
	 * 
	 * @param string $data     The contents to get the structure of
	 * @param string $headers  The parsed headers for the message - if not present they will be extracted from the `$data`
	 * @return array  The multi-dimensional, associative array containing the message structure
	 */
	static private function parseStructure($data, $headers=NULL)
	{
		if (!$headers) {
			list ($headers, $data) = explode("\r\n\r\n", $data, 2);
			$headers = self::parseHeaders($headers);
		}
		
		if (!isset($headers['content-type'])) {
			$headers['content-type'] = array(
				'value'  => 'text/plain',
				'fields' => array()
			);
		}
		
		list ($type, $subtype) = explode('/', strtolower($headers['content-type']['value']), 2);
		
		if ($type == 'multipart') {
			$structure    = array(
				'type'    => $type,
				'subtype' => $subtype,
				'parts'   => array()
			);
			$boundary     = $headers['content-type']['fields']['boundary'];
			$start_pos    = strpos($data, '--' . $boundary) + strlen($boundary) + 4;
			$end_pos      = strrpos($data, '--' . $boundary . '--') - 2;
			$sub_contents = explode("\r\n--" . $boundary . "\r\n", substr(
				$data,
				$start_pos,
				$end_pos - $start_pos
			));
			foreach ($sub_contents as $sub_content) {
				$structure['parts'][] = self::parseStructure($sub_content);
			}
			
		} else {
			$structure = array(
				'type'               => $type,
				'type_fields'        => !empty($headers['content-type']['fields']) ? $headers['content-type']['fields'] : array(),
				'subtype'            => $subtype,
				'content_id'         => isset($headers['content-id']) ? $headers['content-id'] : NULL,
				'encoding'           => isset($headers['content-transfer-encoding']) ? strtolower($headers['content-transfer-encoding']) : '8bit',
				'disposition'        => isset($headers['content-disposition']) ? strtolower($headers['content-disposition']['value']) : NULL,
				'disposition_fields' => isset($headers['content-disposition']) ? $headers['content-disposition']['fields'] : array(),
				'data'               => $data
			);
		}
		
		return $structure;
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
		self::$smime_pairs = array();
	}
	
	
	/**
	 * Takes an associative array and unfolds the keys and values so that the
	 * result in an integer-indexed array of `0 => key1, 1 => value1, 2 => key2,
	 * 3 => value2, ...`.
	 * 
	 * @param array $array  The array to unfold
	 * @return array  The unfolded array
	 */
	static private function unfoldAssociativeArray($array)
	{
		$new_array = array();
		foreach ($array as $key => $value) {
			$new_array[] = $key;
			$new_array[] = $value;
		}
		return $new_array;
	}
	
	
	/**
	 * A counter to use for generating command keys
	 * 
	 * @var integer
	 */
	private $command_num = 1;
	
	/**
	 * The connection resource
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
	 * The server hostname or IP address
	 * 
	 * @var string
	 */
	private $host;
	
	/**
	 * The password for the account
	 * 
	 * @var string
	 */
	private $password;
	
	/**
	 * The port for the server
	 * 
	 * @var integer
	 */
	private $port;
	
	/**
	 * If the connection to the server should be secure
	 * 
	 * @var boolean
	 */
	private $secure;
	
	/**
	 * The timeout for the connection
	 * 
	 * @var integer
	 */
	private $timeout = 5;
	
	/**
	 * The type of mailbox, `'imap'` or `'pop3'`
	 * 
	 * @var string
	 */
	private $type;
	
	/**
	 * The username for the account
	 * 
	 * @var string
	 */
	private $username;
	
	
	/**
	 * Configures the connection to the server
	 * 
	 * Please note that the GMail POP3 server does not act like other POP3
	 * servers and the GMail IMAP server should be used instead. GMail POP3 only
	 * allows retrieving a message once - during future connections the email
	 * in question will no longer be available.
	 * 
	 * @param  string  $type      The type of mailbox, `'pop3'` or `'imap'`
	 * @param  string  $host      The server hostname or IP address
	 * @param  string  $username  The user to log in as
	 * @param  string  $password  The user's password
	 * @param  integer $port      The port to connect via - only required if non-standard
	 * @param  boolean $secure    If SSL should be used for the connection - this requires the `openssl` extension
	 * @param  integer $timeout   The timeout to use when connecting
	 * @return fMailbox
	 */
	public function __construct($type, $host, $username, $password, $port=NULL, $secure=FALSE, $timeout=NULL)
	{
		if ($timeout === NULL) {
			$timeout = ini_get('default_socket_timeout');
		}
		
		$valid_types = array('imap', 'pop3');
		if (!in_array($type, $valid_types)) {
			throw new fProgrammerException(
				'The mailbox type specified, %1$s, in invalid. Must be one of: %2$s.',
				$type,
				join(', ', $valid_types)
			);
		}
		
		if ($port === NULL) {
			if ($type == 'imap') {
				$port = !$secure ? 143 : 993;
			} else {
				$port = !$secure ? 110 : 995;
			}
		}
		
		if ($secure && !extension_loaded('openssl')) {
			throw new fEnvironmentException(
				'A secure connection was requested, but the %s extension is not installed',
				'openssl'
			);
		}
		
		$this->type     = $type;
		$this->host     = $host;
		$this->username = $username;
		$this->password = $password;
		$this->port     = $port;
		$this->secure   = $secure;
		$this->timeout  = $timeout;
	}
	
	
	/**
	 * Disconnects from the server
	 * 
	 * @return void
	 */
	public function __destruct()
	{
		$this->close();
	}
	
	
	/**
	 * Closes the connection to the server
	 * 
	 * @return void
	 */
	public function close()
	{
		if (!$this->connection) {
			return;
		}
		
		if ($this->type == 'imap') {
			$this->write('LOGOUT');
		} else {
			$this->write('QUIT', 1);
		}
		
		$this->connection = NULL;
	}
	
	
	/**
	 * Connects to the server
	 * 
	 * @return void
	 */
	private function connect()
	{
		if ($this->connection) {
			return;
		}
		
		fCore::startErrorCapture(E_WARNING);
		
		$this->connection = fsockopen(
			$this->secure ? 'tls://' . $this->host : $this->host,
			$this->port,
			$error_number,
			$error_string,
			$this->timeout
		);
		
		foreach (fCore::stopErrorCapture('#ssl#i') as $error) {
			throw new fConnectivityException('There was an error connecting to the server. A secure connection was requested, but was not available. Try a non-secure connection instead.');
		}
		
		if (!$this->connection) {
			throw new fConnectivityException('There was an error connecting to the server');
		}
		
		stream_set_timeout($this->connection, $this->timeout);
		
		
		if ($this->type == 'imap') {
			if (!$this->secure && extension_loaded('openssl')) {
				$response = $this->write('CAPABILITY');
				if (preg_match('#\bstarttls\b#i', $response[0])) {
					$this->write('STARTTLS');
					do {
						if (isset($res)) {
							sleep(0.1);	
						}
						$res = stream_socket_enable_crypto($this->connection, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT);
					} while ($res === 0);
				}
			}
			
			$response = $this->write('LOGIN ' . $this->username . ' ' . $this->password);
			if (!$response || !preg_match('#^[^ ]+\s+OK#', $response[count($response)-1])) {
				throw new fValidationException(
					'The username and password provided were not accepted for the %1$s server %2$s on port %3$s',
					strtoupper($this->type),
					$this->host,
					$this->port
				);
			}
			$this->write('SELECT "INBOX"');
			
		} elseif ($this->type == 'pop3') {
			$response = $this->read(1);
			if (isset($response[0])) {
				if ($response[0][0] == '-') {
					throw new fConnectivityException(
						'There was an error connecting to the POP3 server %1$s on port %2$s',
						$this->host,
						$this->port
					);
				}
				preg_match('#<[^@]+@[^>]+>#', $response[0], $match);
			}
			
			if (!$this->secure && extension_loaded('openssl')) {
				$response = $this->write('STLS', 1);
				if ($response[0][0] == '+') {
					do {
						if (isset($res)) {
							sleep(0.1);	
						}
						$res = stream_socket_enable_crypto($this->connection, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT);
					} while ($res === 0);
					if ($res === FALSE) {
						throw new fConnectivityException('Error establishing secure connection');
					}
				}
			}
			
			$authenticated = FALSE;
			if (isset($match[0])) {
				$response = $this->write('APOP ' . $this->username . ' ' . md5($match[0] . $this->password), 1);
				if (isset($response[0]) && $response[0][0] == '+') {
					$authenticated = TRUE;
				}
			}
			
			if (!$authenticated) {
				$response = $this->write('USER ' . $this->username, 1);
				if ($response[0][0] == '+') {
					$response = $this->write('PASS ' . $this->password, 1);
					if (isset($response[0][0]) && $response[0][0] == '+') {
						$authenticated = TRUE;
					}
				}
			}
			
			if (!$authenticated) {
				throw new fValidationException(
					'The username and password provided were not accepted for the %1$s server %2$s on port %3$s',
					strtoupper($this->type),
					$this->host,
					$this->port
				);
			}
		}
	}
	
	
	/**
	 * Deletes one or more messages from the server
	 * 
	 * Passing more than one UID at a time is more efficient for IMAP mailboxes,
	 * whereas POP3 mailboxes will see no difference in performance.
	 * 
	 * @param  integer|array $uid  The UID(s) of the message(s) to delete
	 * @return void
	 */
	public function deleteMessages($uid)
	{
		$this->connect();
		
		settype($uid, 'array');
		
		if ($this->type == 'imap') {
			$this->write('UID STORE ' . join(',', $uid) . ' +FLAGS (\\Deleted)');
			$this->write('EXPUNGE');
			
		} elseif ($this->type == 'pop3') {
			foreach ($uid as $id) {
				$this->write('DELE ' . $id, 1);
			}
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
	 * Retrieves a single message from the server
	 * 
	 * The output includes the following keys:
	 * 
	 *  - `'uid'`: The UID of the message
	 *  - `'received'`: The date the message was received by the server
	 *  - `'headers'`: An associative array of mail headers, the keys are the header names, in lowercase
	 * 
	 * And one or more of the following:
	 * 
	 *  - `'text'`: The plaintext body
	 *  - `'html'`: The HTML body
	 *  - `'attachment'`: An array of attachments, each containing:
	 *   - `'filename'`: The name of the file
	 *   - `'mimetype'`: The mimetype of the file
	 *   - `'data'`: The raw contents of the file
	 *  - `'inline'`: An array of inline files, each containing:
	 *   - `'filename'`: The name of the file
	 *   - `'mimetype'`: The mimetype of the file
	 *   - `'data'`: The raw contents of the file
	 *  - `'related'`: An associative array of related files, such as embedded images, with the key `'cid:{content-id}'` and an array value containing:
	 *   - `'mimetype'`: The mimetype of the file
	 *   - `'data'`: The raw contents of the file
	 *  - `'verified'`: If the message contents were verified via an S/MIME certificate - if not verified the smime.p7s will be listed as an attachment
	 *  - `'decrypted'`: If the message contents were decrypted via an S/MIME private key - if not decrypted the smime.p7m will be listed as an attachment
	 * 
	 * All values in `headers`, `text` and `body` will have been decoded to
	 * UTF-8. Files in the `attachment`, `inline` and `related` array will all
	 * retain their original encodings.
	 * 
	 * @param  integer $uid               The UID of the message to retrieve
	 * @param  boolean $convert_newlines  If `\r\n` should be converted to `\n` in the `text` and `html` parts the message
	 * @return array  The parsed email message - see method description for details
	 */
	public function fetchMessage($uid, $convert_newlines=FALSE)
	{
		$this->connect();
		
		if ($this->type == 'imap') {
			$response = $this->write('UID FETCH ' . $uid . ' (BODY[])');
			preg_match('#\{(\d+)\}$#', $response[0], $match);
			
			$message = '';
			foreach ($response as $i => $line) {
				if (!$i) { continue; }
				if (strlen($message) + strlen($line) + 2 > $match[1]) {
					$message .= substr($line . "\r\n", 0, $match[1] - strlen($message));
				} else {
					$message .= $line . "\r\n";
				}
			}
			
			$info = self::parseMessage($message, $convert_newlines);
			$info['uid'] = $uid;
			
		} elseif ($this->type == 'pop3') {
			$response = $this->write('RETR ' . $uid);
			array_shift($response);
			$response = join("\r\n", $response);
			
			$info = self::parseMessage($response, $convert_newlines);
			$info['uid'] = $uid;
		}
		
		return $info;
	}
	
	
	/**
	 * Gets a list of messages from the server
	 * 
	 * The structure of the returned array is:
	 * 
	 * {{{
	 * array(
	 *     (integer) {uid} => array(
	 *         'uid'         => (integer) {a unique identifier for this message on this server},
	 *         'received'    => (string) {date message was received},
	 *         'size'        => (integer) {size of message in bytes},
	 *         'date'        => (string) {date message was sent},
	 *         'from'        => (string) {the from header value},
	 *         'subject'     => (string) {the message subject},
	 *         'message_id'  => (string) {optional - the message-id header value, should be globally unique},
	 *         'to'          => (string) {optional - the to header value},
	 *         'in_reply_to' => (string) {optional - the in-reply-to header value}
	 *     ), ...
	 * )
	 * }}}
	 * 
	 * All values will have been decoded to UTF-8.
	 * 
	 * @param  integer $limit  The number of messages to retrieve
	 * @param  integer $page   The page of messages to retrieve
	 * @return array  A list of messages on the server - see method description for details
	 */
	public function listMessages($limit=NULL, $page=NULL)
	{
		$this->connect();
		
		if ($this->type == 'imap') {
			if (!$limit) {
				$start = 1;
				$end   = '*';
			} else {
				if (!$page) {
					$page = 1;
				}
				$start = ($limit * ($page-1)) + 1;
				$end   = $start + $limit - 1;
			}
			
			$total_messages = 0;
			$response = $this->write('STATUS "INBOX" (MESSAGES)');
			foreach ($response as $line) {
				if (preg_match('#^\s*\*\s+STATUS\s+"?INBOX"?\s+\((.*)\)$#', $line, $match)) {
					$details = self::parseResponse($match[1], TRUE);
					$total_messages = $details['messages'];
				}
			}
			
			if ($start > $total_messages) {
				return array();
			}
			
			if ($end > $total_messages) {
				$end = $total_messages;
			}
			
			$output = array();
			$response = $this->write('FETCH ' . $start . ':' . $end . ' (UID INTERNALDATE RFC822.SIZE ENVELOPE)');
			foreach ($response as $line) {
				if (preg_match('#^\s*\*\s+(\d+)\s+FETCH\s+\((.*)\)$#', $line, $match)) {
					$details = self::parseResponse($match[2], TRUE);
					$info    = array();
					
					$info['uid']      = $details['uid'];
					$info['received'] = self::cleanDate($details['internaldate']);
					$info['size']     = $details['rfc822.size'];
					
					$envelope = $details['envelope'];
					$info['date']    = $envelope[0] != 'NIL' ? $envelope[0] : '';
					$info['from']    = self::joinEmails($envelope[2]);
					if (preg_match('#=\?[^\?]+\?[QB]\?[^\?]+\?=#', $envelope[1])) {
						do {
							$last_subject = $envelope[1];
							$envelope[1] = preg_replace('#(=\?([^\?]+)\?[QB]\?[^\?]+\?=) (\s*=\?\2)#', '\1\3', $envelope[1]);
						} while ($envelope[1] != $last_subject);
						$info['subject'] = self::decodeHeader($envelope[1]);
					} else {
						$info['subject'] = $envelope[1] == 'NIL' ? '' : self::decodeHeader($envelope[1]);
					}
					if ($envelope[9] != 'NIL') {
						$info['message_id'] = $envelope[9];
					}
					if ($envelope[5] != 'NIL') {
						$info['to'] = self::joinEmails($envelope[5]);
					}
					if ($envelope[8] != 'NIL') {
						$info['in_reply_to'] = $envelope[8];
					}
					
					$output[$info['uid']] = $info;
				}
			}
		
		} elseif ($this->type == 'pop3') {
			if (!$limit) {
				$start = 1;
				$end   = NULL;
			} else {
				if (!$page) {
					$page = 1;
				}
				$start = ($limit * ($page-1)) + 1;
				$end   = $start + $limit - 1;
			}
			
			$total_messages = 0;
			$response = $this->write('STAT', 1);
			preg_match('#^\+OK\s+(\d+)\s+#', $response[0], $match);
			$total_messages = $match[1];
			
			if ($start > $total_messages) {
				return array();
			}
			
			if ($end === NULL || $end > $total_messages) {
				$end = $total_messages;
			}
			
			$sizes = array();
			$response = $this->write('LIST');
			array_shift($response);
			foreach ($response as $line) {
				preg_match('#^(\d+)\s+(\d+)$#', $line, $match);
				$sizes[$match[1]] = $match[2];
			}
			
			$output = array();
			for ($i = $start; $i <= $end; $i++) {
				$response = $this->write('TOP ' . $i . ' 1');
				array_shift($response);
				$value = array_pop($response);
				// Some servers add an extra blank line after the 1 requested line
				if (trim($value) == '') {
					array_pop($response);
				}
				
				$response = trim(join("\r\n", $response));
				$headers = self::parseHeaders($response);
				$output[$i] = array(
					'uid'      => $i,
					'received' => self::cleanDate(preg_replace('#^.*;\s*([^;]+)$#', '\1', $headers['received'][0])),
					'size'     => $sizes[$i],
					'date'     => $headers['date'],
					'from'     => self::joinEmails(array($headers['from'])),
					'subject'  => isset($headers['subject']) ? $headers['subject'] : ''
				);
				if (isset($headers['message-id'])) {
					$output[$i]['message_id'] = $headers['message-id'];
				}
				if (isset($headers['to'])) {
					$output[$i]['to'] = self::joinEmails($headers['to']);
				}
				if (isset($headers['in-reply-to'])) {
					$output[$i]['in_reply_to'] = $headers['in-reply-to'];
				}
			}
		}
		
		return $output;
	}
	
	
	/**
	 * Reads responses from the server
	 * 
	 * @param  integer|string $expect  The expected number of lines of response or a regex of the last line
	 * @return array  The lines of response from the server
	 */
	private function read($expect=NULL)
	{
		$read     = array($this->connection);
		$write    = NULL;
		$except   = NULL;
		$response = array();
		
		// PHP 5.2.0 to 5.2.5 has a bug on amd64 linux where stream_select()
		// fails, so we have to fake it - http://bugs.php.net/bug.php?id=42682
		static $broken_select = NULL;
		if ($broken_select === NULL) {
			$broken_select = strpos(php_uname('m'), '64') !== FALSE && fCore::checkVersion('5.2.0') && !fCore::checkVersion('5.2.6');
		}
		
		// Fixes an issue with stream_select throwing a warning on PHP 5.3 on Windows
		if (fCore::checkOS('windows') && fCore::checkVersion('5.3.0')) {
			$select = @stream_select($read, $write, $except, $this->timeout);
		
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
			} while ($broken_select_buffer === NULL && microtime(TRUE) - $start_time < $this->timeout);
			$select = $broken_select_buffer !== NULL;
			
		} else {
			$select = stream_select($read, $write, $except, $this->timeout);
		}
		
		if ($select) {
			while (!feof($this->connection)) {
				$line = fgets($this->connection);
				if ($line === FALSE) {
					break;
				}
				$line = substr($line, 0, -2);
				
				// When we fake select, we have to handle what we've retrieved
				if ($broken_select && $broken_select_buffer !== NULL) {
					$line = $broken_select_buffer . $line;
					$broken_select_buffer = NULL;
				}
				
				$response[] = $line;
				
				// Automatically stop at the termination octet or a bad response
				if ($this->type == 'pop3' && ($line == '.' || (count($response) == 1 && $response[0][0] == '-'))) {
					break;
				}
				
				if ($expect !== NULL) {
					$matched_number = is_int($expect) && sizeof($response) == $expect;
					$matched_regex  = is_string($expect) && preg_match($expect, $line);
					if ($matched_number || $matched_regex) {
						break;
					}
				}
			}
		}
		if (fCore::getDebug($this->debug)) {
			fCore::debug("Received:\n" . join("\r\n", $response), $this->debug);
		}
		
		if ($this->type == 'pop3') {
			// Remove the termination octet
			if ($response && $response[sizeof($response)-1] == '.') {
				$response = array_slice($response, 0, -1);
			}
			// Remove byte-stuffing
			$lines = count($response);
			for ($i = 0; $i < $lines; $i++) {
				if (strlen($response[$i]) && $response[$i][0] == '.') {
					$response[$i] = substr($response[$i], 1);
				}
			}
		}
		
		return $response;
	}
	
	
	/**
	 * Sends commands to the IMAP or POP3 server
	 * 
	 * @param  string  $command   The command to send
	 * @param  integer $expected  The number of lines or regex expected for a POP3 command
	 * @return array  The response from the server
	 */
	private function write($command, $expected=NULL)
	{
		if (!$this->connection) {
			throw new fProgrammerException('Unable to send data since the connection has already been closed');
		}
		
		if ($this->type == 'imap') {
			$identifier = 'a' . str_pad($this->command_num++, 4, '0', STR_PAD_LEFT);
			$command    = $identifier . ' ' . $command;	
		}
		
		if (substr($command, -2) != "\r\n") {
			$command .= "\r\n";
		}
				
		if (fCore::getDebug($this->debug)) {
			fCore::debug("Sending:\n" . trim($command), $this->debug);
		}
		
		$res = fwrite($this->connection, $command);

		if ($res === FALSE || $res === 0) {
			throw new fConnectivityException(
				'Unable to write data to %1$s server %2$s on port %3$s',
				strtoupper($this->type),
				$this->host,
				$this->port
			);	
		}
		
		if ($this->type == 'imap') {
			return $this->read('#^' . $identifier . '#');
		} elseif ($this->type == 'pop3') {
			return $this->read($expected);
		}
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