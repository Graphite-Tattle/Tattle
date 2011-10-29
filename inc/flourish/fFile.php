<?php
/**
 * Represents a file on the filesystem, also provides static file-related methods
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fFile
 * 
 * @version    1.0.0b39
 * @changes    1.0.0b39  Backwards Compatibility Break - ::output() now automatically ends any open output buffering and discards the contents [wb, 2011-08-24]
 * @changes    1.0.0b38  Added the Countable interface to the class [wb, 2011-06-03]
 * @changes    1.0.0b37  Fixed mime type detection of BMP images [wb, 2011-03-07]
 * @changes    1.0.0b36  Added the `$remove_extension` parameter to ::getName() [wb, 2011-01-10]
 * @changes    1.0.0b35  Added calls to clearstatcache() in ::append() and ::write() to prevent incorrect data from being returned by ::getMTime() and ::getSize() [wb, 2010-11-27]
 * @changes    1.0.0b34  Added ::getExtension() [wb, 2010-05-10]
 * @changes    1.0.0b33  Fixed another situation where ::rename() with the same name would cause the file to be deleted [wb, 2010-04-13]
 * @changes    1.0.0b32  Fixed ::rename() to not fail when the new and old filename are the same [wb, 2010-03-16]
 * @changes    1.0.0b31  Added ::append() [wb, 2010-03-15]
 * @changes    1.0.0b30  Changed the way files deleted in a filesystem transaction are handled, including improvements to the exception that is thrown [wb+wb-imarc, 2010-03-05]
 * @changes    1.0.0b29  Fixed a couple of undefined variable errors in ::determineMimeTypeByContents() [wb, 2010-03-03]
 * @changes    1.0.0b28  Added support for some JPEG files created by Photoshop [wb, 2009-12-16]
 * @changes    1.0.0b27  Backwards Compatibility Break - renamed ::getFilename() to ::getName(), ::getFilesize() to ::getSize(), ::getDirectory() to ::getParent(), added ::move() [wb, 2009-12-16]
 * @changes    1.0.0b26  ::getDirectory(), ::getFilename() and ::getPath() now all work even if the file has been deleted [wb, 2009-10-22]
 * @changes    1.0.0b25  Fixed ::__construct() to throw an fValidationException when the file does not exist [wb, 2009-08-21]
 * @changes    1.0.0b24  Fixed a bug where deleting a file would prevent any future operations in the same script execution on a file or directory with the same path [wb, 2009-08-20]
 * @changes    1.0.0b23  Added the ability to skip checks in ::__construct() for better performance in conjunction with fFilesystem::createObject() [wb, 2009-08-06]
 * @changes    1.0.0b22  Fixed ::__toString() to never throw an exception [wb, 2009-08-06]
 * @changes    1.0.0b21  Fixed a bug in ::determineMimeType() [wb, 2009-07-21]
 * @changes    1.0.0b20  Fixed the exception message thrown by ::output() when output buffering is turned on [wb, 2009-06-26]
 * @changes    1.0.0b19  ::rename() will now rename the file in its current directory if the new filename has no directory separator [wb, 2009-05-04]
 * @changes    1.0.0b18  Changed ::__sleep() to not reset the iterator since it can cause side-effects [wb, 2009-05-04]
 * @changes    1.0.0b17  Added ::__sleep() and ::__wakeup() for proper serialization with the filesystem map [wb, 2009-05-03]
 * @changes    1.0.0b16  ::output() now accepts `TRUE` in the second parameter to use the current filename as the attachment filename [wb, 2009-03-23]
 * @changes    1.0.0b15  Added support for mime type detection of MP3s based on the MPEG-2 (as opposed to MPEG-1) standard [wb, 2009-03-23]
 * @changes    1.0.0b14  Fixed a bug with detecting the mime type of some MP3s [wb, 2009-03-22]
 * @changes    1.0.0b13  Fixed a bug with overwriting files via ::rename() on Windows [wb, 2009-03-11]
 * @changes    1.0.0b12  Backwards compatibility break - Changed the second parameter of ::output() from `$ignore_output_buffer` to `$filename` [wb, 2009-03-05]
 * @changes    1.0.0b11  Changed ::__clone() and ::duplicate() to copy file permissions to the new file [wb, 2009-01-05] 
 * @changes    1.0.0b10  Fixed ::duplicate() so an exception is not thrown when no parameters are passed [wb, 2009-01-05]
 * @changes    1.0.0b9   Removed the dependency on fBuffer [wb, 2009-01-05]
 * @changes    1.0.0b8   Added the Iterator interface, ::output() and ::getMTime() [wb, 2008-12-17]
 * @changes    1.0.0b7   Removed some unnecessary error suppresion operators [wb, 2008-12-11]
 * @changes    1.0.0b6   Added the ::__clone() method that duplicates the file on the filesystem when cloned [wb, 2008-12-11]
 * @changes    1.0.0b5   Fixed detection of mime type for JPEG files with Exif information [wb, 2008-12-04]
 * @changes    1.0.0b4   Changed the constructor to ensure the path is to a file and not directory [wb, 2008-11-24]
 * @changes    1.0.0b3   Fixed mime type detection of Microsoft Office files [wb, 2008-11-23]
 * @changes    1.0.0b2   Made ::rename() and ::write() return the object for method chaining [wb, 2008-11-22] 
 * @changes    1.0.0b    The initial implementation [wb, 2007-06-14]
 */
class fFile implements Iterator, Countable
{
	// The following constants allow for nice looking callbacks to static methods
	const create = 'fFile::create';
	
	
	/**
	 * Creates a file on the filesystem and returns an object representing it.
	 * 
	 * This operation will be reverted by a filesystem transaction being rolled back.
	 * 
	 * @throws fValidationException  When no file was specified or the file already exists
	 * 
	 * @param  string $file_path  The path to the new file
	 * @param  string $contents   The contents to write to the file, must be a non-NULL value to be written
	 * @return fFile
	 */
	static public function create($file_path, $contents)
	{
		if (empty($file_path)) {
			throw new fValidationException(
				'No filename was specified'
			);
		}
		
		if (file_exists($file_path)) {
			throw new fValidationException(
				'The file specified, %s, already exists',
				$file_path
			);
		}
		
		$directory = fFilesystem::getPathInfo($file_path, 'dirname');
		if (!is_writable($directory)) {
			throw new fEnvironmentException(
				'The file path specified, %s, is inside of a directory that is not writable',
				$file_path
			);
		}
		
		file_put_contents($file_path, $contents);
		
		$file = new fFile($file_path);
		
		if (fFilesystem::isInsideTransaction()) {
			fFilesystem::recordCreate($file);
		}
		
		return $file;
	}
	
	
	/**
	 * Determines the file's mime type by either looking at the file contents or matching the extension
	 * 
	 * Please see the ::getMimeType() description for details about how the
	 * mime type is determined and what mime types are detected.
	 * 
	 * @internal
	 * 
	 * @param  string $file      The file to check the mime type for - must be a valid filesystem path if no `$contents` are provided, otherwise just a filename
	 * @param  string $contents  The first 4096 bytes of the file content - the `$file` parameter only need be a filename if this is provided
	 * @return string  The mime type of the file
	 */
	static public function determineMimeType($file, $contents=NULL)
	{
		// If no contents are provided, we must get them
		if ($contents === NULL) {
			if (!file_exists($file)) {
				throw new fValidationException(
					'The file specified, %s, does not exist',
					$file
				);
			}
			
			// The first 4k should be enough for content checking
			$handle   = fopen($file, 'r');
			$contents = fread($handle, 4096);
			fclose($handle);
		}
		
		$extension = strtolower(fFilesystem::getPathInfo($file, 'extension'));
		
		// If there are no low ASCII chars and no easily distinguishable tokens, we need to detect by file extension
		if (!preg_match('#[\x00-\x08\x0B\x0C\x0E-\x1F]|%PDF-|<\?php|\%\!PS-Adobe-3|<\?xml|\{\\\\rtf|<\?=|<html|<\!doctype|<rss|\#\![/a-z0-9]+(python|ruby|perl|php)\b#i', $contents)) {
			return self::determineMimeTypeByExtension($extension);		
		}
		
		return self::determineMimeTypeByContents($contents, $extension);
	}
	
	
	/**
	 * Looks for specific bytes in a file to determine the mime type of the file
	 * 
	 * @param  string $content    The first 4 bytes of the file content to use for byte checking
	 * @param  string $extension  The extension of the filetype, only used for difficult files such as Microsoft office documents
	 * @return string  The mime type of the file
	 */
	static private function determineMimeTypeByContents($content, $extension)
	{
		$length = strlen($content);
		$_0_8   = substr($content, 0, 8);
		$_0_6   = substr($content, 0, 6);
		$_0_5   = substr($content, 0, 5);
		$_0_4   = substr($content, 0, 4);
		$_0_3   = substr($content, 0, 3);
		$_0_2   = substr($content, 0, 2);
		$_8_4   = substr($content, 8, 4);
		
		// Images
		if ($_0_4 == "MM\x00\x2A" || $_0_4 == "II\x2A\x00") {
			return 'image/tiff';	
		}
		
		if ($_0_8 == "\x89PNG\x0D\x0A\x1A\x0A") {
			return 'image/png';	
		}
		
		if ($_0_4 == 'GIF8') {
			return 'image/gif';	
		}
		
		if ($_0_2 == 'BM' && $length > 14 && in_array($content[14], array("\x0C", "\x28", "\x40", "\x80"))) {
			return 'image/x-ms-bmp';	
		}
		
		$normal_jpeg    = $length > 10 && in_array(substr($content, 6, 4), array('JFIF', 'Exif'));
		$photoshop_jpeg = $length > 24 && $_0_4 == "\xFF\xD8\xFF\xED" && substr($content, 20, 4) == '8BIM';
		if ($normal_jpeg || $photoshop_jpeg) {
			return 'image/jpeg';	
		}
		
		if (preg_match('#^[^\n\r]*\%\!PS-Adobe-3#', $content)) {
			return 'application/postscript';			
		}
		
		if ($_0_4 == "\x00\x00\x01\x00") {
			return 'application/vnd.microsoft.icon';	
		}
		
		
		// Audio/Video
		if ($_0_4 == 'MOVI') {
			if (in_array($_4_4, array('moov', 'mdat'))) {
				return 'video/quicktime';
			}	
		}
		
		if ($length > 8 && substr($content, 4, 4) == 'ftyp') {
			
			$_8_3 = substr($content, 8, 3);
			$_8_2 = substr($content, 8, 2);
			
			if (in_array($_8_4, array('isom', 'iso2', 'mp41', 'mp42'))) {
				return 'video/mp4';
			}	
			
			if ($_8_3 == 'M4A') {
				return 'audio/mp4';
			}
			
			if ($_8_3 == 'M4V') {
				return 'video/mp4';
			}
			
			if ($_8_3 == 'M4P' || $_8_3 == 'M4B' || $_8_2 == 'qt') {
				return 'video/quicktime';	
			}
		}
		
		// MP3
		if (($_0_2 & "\xFF\xF6") == "\xFF\xF2") {
			if (($content[2] & "\xF0") != "\xF0" && ($content[2] & "\x0C") != "\x0C") {
				return 'audio/mpeg';
			}	
		}
		if ($_0_3 == 'ID3') {
			return 'audio/mpeg';	
		}
		
		if ($_0_8 == "\x30\x26\xB2\x75\x8E\x66\xCF\x11") {
			if ($content[24] == "\x07") {
				return 'audio/x-ms-wma';
			}
			if ($content[24] == "\x08") {
				return 'video/x-ms-wmv';
			}
			return 'video/x-ms-asf';	
		}
		
		if ($_0_4 == 'RIFF' && $_8_4 == 'AVI ') {
			return 'video/x-msvideo';	
		}
		
		if ($_0_4 == 'RIFF' && $_8_4 == 'WAVE') {
			return 'audio/x-wav';	
		}
		
		if ($_0_4 == 'OggS') {
			$_28_5 = substr($content, 28, 5);
			if ($_28_5 == "\x01\x76\x6F\x72\x62") {
				return 'audio/vorbis';	
			}
			if ($_28_5 == "\x07\x46\x4C\x41\x43") {
				return 'audio/x-flac';	
			}
			// Theora and OGM	
			if ($_28_5 == "\x80\x74\x68\x65\x6F" || $_28_5 == "\x76\x69\x64\x65") {
				return 'video/ogg';		
			}
		}
		
		if ($_0_3 == 'FWS' || $_0_3 == 'CWS') {
			return 'application/x-shockwave-flash';	
		}
		
		if ($_0_3 == 'FLV') {
			return 'video/x-flv';	
		}
		
		
		// Documents
		if ($_0_5 == '%PDF-') {
			return 'application/pdf'; 	
		}
		
		if ($_0_5 == '{\rtf') {
			return 'text/rtf';	
		}
		
		// Office '97-2003 or Office 2007 formats
		if ($_0_8 == "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" || $_0_8 == "PK\x03\x04\x14\x00\x06\x00") {
			if (in_array($extension, array('xlsx', 'xls', 'csv', 'tab'))) {
				return 'application/vnd.ms-excel';	
			}
			if (in_array($extension, array('pptx', 'ppt'))) {	
				return 'application/vnd.ms-powerpoint';
			}
			// We default to word since we need something if the extension isn't recognized
			return 'application/msword';
		}
		
		if ($_0_8 == "\x09\x04\x06\x00\x00\x00\x10\x00") {
			return 'application/vnd.ms-excel';	
		}
		
		if ($_0_6 == "\xDB\xA5\x2D\x00\x00\x00" || $_0_5 == "\x50\x4F\x5E\x51\x60" || $_0_4 == "\xFE\x37\x0\x23" || $_0_3 == "\x94\xA6\x2E") {
			return 'application/msword';	
		}
		
		
		// Archives
		if ($_0_4 == "PK\x03\x04") {
			return 'application/zip';	
		}
		
		if ($length > 257) {
			if (substr($content, 257, 6) == "ustar\x00") {
				return 'application/x-tar';	
			}
			if (substr($content, 257, 8) == "ustar\x40\x40\x00") {
				return 'application/x-tar';	
			}
		}
		
		if ($_0_4 == 'Rar!') {
			return 'application/x-rar-compressed';	
		}
		
		if ($_0_2 == "\x1F\x9D") {
			return 'application/x-compress';	
		}
		
		if ($_0_2 == "\x1F\x8B") {
			return 'application/x-gzip';	
		}
		
		if ($_0_3 == 'BZh') {
			return 'application/x-bzip2';	
		}
		
		if ($_0_4 == "SIT!" || $_0_4 == "SITD" || substr($content, 0, 7) == 'StuffIt') {
			return 'application/x-stuffit';	
		}	
		
		
		// Text files
		if (strpos($content, '<?xml') !== FALSE) {
			if (stripos($content, '<!DOCTYPE') !== FALSE) {
				return 'application/xhtml+xml';
			}
			if (strpos($content, '<svg') !== FALSE) {
				return 'image/svg+xml';
			}
			if (strpos($content, '<rss') !== FALSE) {
				return 'application/rss+xml';
			}
			return 'application/xml';	
		}   
		
		if (strpos($content, '<?php') !== FALSE || strpos($content, '<?=') !== FALSE) {
			return 'application/x-httpd-php';	
		}
		
		if (preg_match('#^\#\![/a-z0-9]+(python|perl|php|ruby)$#mi', $content, $matches)) {
			switch (strtolower($matches[1])) {
				case 'php':
					return 'application/x-httpd-php';
				case 'python':
					return 'application/x-python';
				case 'perl':
					return 'application/x-perl';
				case 'ruby':
					return 'application/x-ruby';
			}	
		}
		
		
		// Default
		return 'application/octet-stream';
	}
	
	
	/**
	 * Uses the extension of the all-text file to determine the mime type
	 * 
	 * @param  string $extension  The file extension
	 * @return string  The mime type of the file
	 */
	static private function determineMimeTypeByExtension($extension)
	{
		switch ($extension) {
			case 'css':
				return 'text/css';
			
			case 'csv':
				return 'text/csv';
			
			case 'htm':
			case 'html':
			case 'xhtml':
				return 'text/html';
				
			case 'ics':
				return 'text/calendar';
			
			case 'js':
				return 'application/javascript';
			
			case 'php':
			case 'php3':
			case 'php4':
			case 'php5':
			case 'inc':
				return 'application/x-httpd-php';
				
			case 'pl':
			case 'cgi':
				return 'application/x-perl';
			
			case 'py':
				return 'application/x-python';
			
			case 'rb':
			case 'rhtml':
				return 'application/x-ruby';
			
			case 'rss':
				return 'application/rss+xml';
				
			case 'tab':
				return 'text/tab-separated-values';
			
			case 'vcf':
				return 'text/x-vcard';
			
			case 'xml':
				return 'application/xml';
			
			default:
				return 'text/plain';	
		}
	}
	
	
	/**
	 * The current line of the file
	 * 
	 * @var string
	 */
	private $current_line = NULL;
	
	/**
	 * The current line number of the file
	 * 
	 * @var string
	 */
	private $current_line_number = NULL;
	
	/**
	 * A backtrace from when the file was deleted 
	 * 
	 * @var array
	 */
	protected $deleted = NULL;
	
	/**
	 * The full path to the file
	 * 
	 * @var string
	 */
	protected $file;
	
	/**
	 * The file handle for iteration
	 * 
	 * @var resource
	 */
	private $file_handle = NULL;
	
	
	/**
	 * Duplicates a file in the current directory when the object is cloned
	 * 
	 * @internal
	 * 
	 * @return fFile  The new fFile object
	 */
	public function __clone()
	{
		$this->tossIfDeleted();
		
		$directory = $this->getParent();
		
		if (!$directory->isWritable()) {
			throw new fEnvironmentException(
				'The file count not be cloned because the containing directory, %s, is not writable',
				$directory
			);
		}
		
		$file = fFilesystem::makeUniqueName($directory->getPath() . $this->getName());
		
		copy($this->getPath(), $file);
		chmod($file, fileperms($this->getPath()));
		
		$this->file    =& fFilesystem::hookFilenameMap($file);
		$this->deleted =& fFilesystem::hookDeletedMap($file);
		
		// Allow filesystem transactions
		if (fFilesystem::isInsideTransaction()) {
			fFilesystem::recordDuplicate($this);
		}
	}
	
	
	/**
	 * Creates an object to represent a file on the filesystem
	 * 
	 * If multiple fFile objects are created for a single file, they will
	 * reflect changes in each other including rename and delete actions.
	 * 
	 * @throws fValidationException  When no file was specified, the file does not exist or the path specified is not a file
	 * 
	 * @param  string  $file         The path to the file
	 * @param  boolean $skip_checks  If file checks should be skipped, which improves performance, but may cause undefined behavior - only skip these if they are duplicated elsewhere
	 * @return fFile
	 */
	public function __construct($file, $skip_checks=FALSE)
	{
		if (!$skip_checks) {
			if (empty($file)) {
				throw new fValidationException(
					'No filename was specified'
				);
			}
			
			if (!is_readable($file)) {
				throw new fValidationException(
					'The file specified, %s, does not exist or is not readable',
					$file
				);
			}
			if (is_dir($file)) {
				throw new fValidationException(
					'The file specified, %s, is actually a directory',
					$file
				);
			}
		}
		
		// Store the file as an absolute path
		$file = realpath($file);
		
		$this->file    =& fFilesystem::hookFilenameMap($file);
		$this->deleted =& fFilesystem::hookDeletedMap($file);
		
		// If the file is listed as deleted and were not inside a transaction,
		// but we've gotten to here, then the file exists, so we can wipe the backtrace
		if ($this->deleted !== NULL && !fFilesystem::isInsideTransaction()) {
			fFilesystem::updateDeletedMap($file, NULL);
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
	 * The iterator information doesn't need to be serialized since a resource can't be
	 *
	 * @internal
	 * 
	 * @return array  The instance variables to serialize
	 */
	public function __sleep()
	{
		return array('deleted', 'file');
	}
	
	
	/**
	 * Returns the filename of the file
	 * 
	 * @return string  The filename
	 */
	public function __toString()
	{
		try {
			return $this->getName();
		} catch (Exception $e) {
			return '';	
		}
	}
	
	
	/**
	 * Re-inserts the file back into the filesystem map when unserialized
	 *
	 * @internal
	 * 
	 * @return void
	 */
	public function __wakeup()
	{
		$file    = $this->file;
		$deleted = $this->deleted;
		
		$this->file    =& fFilesystem::hookFilenameMap($file);
		$this->deleted =& fFilesystem::hookDeletedMap($file);
		
		if ($deleted !== NULL) {
			fFilesystem::updateDeletedMap($file, $deleted);
		}
	}
	
	
	/**
	 * Appends the provided data to the file
	 * 
	 * If a filesystem transaction is in progress and is rolled back, this
	 * data will be removed.
	 * 
	 * @param  mixed $data  The data to append to the file
	 * @return fFile  The file object, to allow for method chaining
	 */
	public function append($data)
	{
		$this->tossIfDeleted();
		
		if (!$this->isWritable()) {
			throw new fEnvironmentException(
				'This file, %s, can not be appended because it is not writable',
				$this->file
			);
		}
		
		// Allow filesystem transactions
		if (fFilesystem::isInsideTransaction()) {
			fFilesystem::recordAppend($this, $data);
		}
		
		file_put_contents($this->file, $data, FILE_APPEND);
		clearstatcache();
		
		return $this;
	}


	/**
	 * Returns the number of lines in the file
	 *
	 * @return integer  The number of lines in the file
	 */
	public function count()
	{
		$this->tossIfDeleted();

		$file_handle = fopen($this->file, 'r');

		$lines        = 0;
		$has_contents = FALSE;

		// Read 512KB at a time to make this more efficient without using too much ram
		while (!feof($file_handle)) {
			$text   = fread($file_handle, 524288);
			$text   = str_replace("\r\n", "\n", $text);
			$text   = str_replace("\r", "\n", $text);
			$lines += substr_count($text, "\n");
			if ($text !== "") {
				$has_contents = TRUE;
			}
		}
		fclose($file_handle);

		if ($has_contents) {
			$lines++;
		}

		return $lines;
	}
	
	
	/**
	 * Returns the current line of the file (required by iterator interface)
	 * 
	 * @throws fNoRemainingException   When there are no remaining lines in the file
	 * @internal
	 * 
	 * @return array  The current row
	 */
	public function current()
	{
		$this->tossIfDeleted();
		
		// Primes the result set
		if ($this->file_handle === NULL) {
			$this->next();
			
		} elseif (!$this->valid()) {
			throw new fNoRemainingException('There are no remaining lines');
		}
		
		return $this->current_line;
	}
	
	
	/**
	 * Deletes the current file
	 * 
	 * This operation will NOT be performed until the filesystem transaction
	 * has been committed, if a transaction is in progress. Any non-Flourish
	 * code (PHP or system) will still see this file as existing until that
	 * point.
	 * 
	 * @return void
	 */
	public function delete()
	{
		if ($this->deleted) {
			return;
		}
		
		if (!$this->getParent()->isWritable()) {
			throw new fEnvironmentException(
				'The file, %s, can not be deleted because the directory containing it is not writable',
				$this->file
			);
		}
		
		// Allow filesystem transactions
		if (fFilesystem::isInsideTransaction()) {
			return fFilesystem::recordDelete($this);
		}
		
		unlink($this->file);
		
		fFilesystem::updateDeletedMap($this->file, debug_backtrace());
		fFilesystem::updateFilenameMap($this->file, '*DELETED at ' . time() . ' with token ' . uniqid('', TRUE) . '* ' . $this->file);
	}
	
	
	/**
	 * Creates a new file object with a copy of this file
	 * 
	 * If no directory is specified, the file is created with a new name in
	 * the current directory. If a new directory is specified, you must also
	 * indicate if you wish to overwrite an existing file with the same name
	 * in the new directory or create a unique name.
	 * 
	 * This operation will be reverted by a filesystem transaction being rolled
	 * back.
	 * 
	 * @param  string|fDirectory $new_directory  The directory to duplicate the file into if different than the current directory
	 * @param  boolean           $overwrite      If a new directory is specified, this indicates if a file with the same name should be overwritten.
	 * @return fFile  The new fFile object
	 */
	public function duplicate($new_directory=NULL, $overwrite=NULL)
	{
		$this->tossIfDeleted();
		
		if ($new_directory === NULL) {
			$new_directory = $this->getParent();
		}
		
		if (!is_object($new_directory)) {
			$new_directory = new fDirectory($new_directory);
		}
		
		$new_filename = $new_directory->getPath() . $this->getName();
		
		$check_dir_permissions = FALSE;
		
		if (file_exists($new_filename)) {
			if (!$overwrite) {
				$new_filename = fFilesystem::makeUniqueName($new_filename);
				$check_dir_permissions = TRUE;
				
			} elseif (!is_writable($new_filename)) {
				throw new fEnvironmentException(
					'The new directory specified, %1$s, already contains a file with the name %2$s, but it is not writable',
					$new_directory->getPath(),
					$this->getName()
				);
			}
			
		} else {
			$check_dir_permissions = TRUE;
		}
		
		if ($check_dir_permissions) {
			if (!$new_directory->isWritable()) {
				throw new fEnvironmentException(
					'The new directory specified, %s, is not writable',
					$new_directory
				);
			}
		}
		
		copy($this->getPath(), $new_filename);
		chmod($new_filename, fileperms($this->getPath()));
		
		$class = get_class($this);
		$file  = new $class($new_filename);
		
		// Allow filesystem transactions
		if (fFilesystem::isInsideTransaction()) {
			fFilesystem::recordDuplicate($file);
		}
		
		return $file;
	}
	
	
	/**
	 * Gets the file extension
	 * 
	 * @return string  The extension of the file
	 */
	public function getExtension()
	{
		return fFilesystem::getPathInfo($this->file, 'extension');
	}
	
	
	/**
	 * Gets the file's mime type
	 * 
	 * This method will attempt to look at the file contents and the file
	 * extension to determine the mime type. If the file contains binary
	 * information, the contents will be used for mime type verification,
	 * however if the contents appear to be plain text, the file extension
	 * will be used.
	 * 
	 * The following mime types are supported. All other binary file types
	 * will be returned as `application/octet-stream` and all other text files
	 * will be returned as `text/plain`.
	 * 
	 * **Archive:**
	 * 
	 *  - `application/x-bzip2` BZip2 file
	 *  - `application/x-compress` Compress (*nix) file
	 *  - `application/x-gzip` GZip file
	 *  - `application/x-rar-compressed` Rar file
	 *  - `application/x-stuffit` StuffIt file
	 *  - `application/x-tar` Tar file
	 *  - `application/zip` Zip file
	 * 
	 * **Audio:**
	 * 
	 *  - `audio/x-flac` FLAC audio
	 *  - `audio/mpeg` MP3 audio
	 *  - `audio/mp4` MP4 (AAC) audio
	 *  - `audio/vorbis` Ogg Vorbis audio
	 *  - `audio/x-wav` WAV audio
	 *  - `audio/x-ms-wma` Windows media audio
	 * 
	 * **Document:**
	 * 
	 *  - `application/vnd.ms-excel` Excel (2000, 2003 and 2007) file
	 *  - `application/pdf` PDF file
	 *  - `application/vnd.ms-powerpoint` Powerpoint (2000, 2003, 2007) file
	 *  - `text/rtf` RTF file
	 *  - `application/msword` Word (2000, 2003 and 2007) file
	 * 
	 * **Image:**
	 * 
	 *  - `image/x-ms-bmp` BMP file
	 *  - `application/postscript` EPS file
	 *  - `image/gif` GIF file
	 *  - `application/vnd.microsoft.icon` ICO file
	 *  - `image/jpeg` JPEG file
	 *  - `image/png` PNG file
	 *  - `image/tiff` TIFF file
	 *  - `image/svg+xml` SVG file
	 * 
	 * **Text:**
	 * 
	 *  - `text/css` CSS file
	 *  - `text/csv` CSV file
	 *  - `text/html` (X)HTML file
	 *  - `text/calendar` iCalendar file
	 *  - `application/javascript` Javascript file
	 *  - `application/x-perl` Perl file
	 *  - `application/x-httpd-php` PHP file
	 *  - `application/x-python` Python file
	 *  - `application/rss+xml` RSS feed
	 *  - `application/x-ruby` Ruby file
	 *  - `text/tab-separated-values` TAB file
	 *  - `text/x-vcard` VCard file
	 *  - `application/xhtml+xml` XHTML (Real) file
	 *  - `application/xml` XML file
	 * 
	 * **Video/Animation:**
	 * 
	 *  - `video/x-msvideo` AVI video
	 *  - `application/x-shockwave-flash` Flash movie
	 *  - `video/x-flv` Flash video
	 *  - `video/x-ms-asf` Microsoft ASF video
	 *  - `video/mp4` MP4 video
	 *  - `video/ogg` OGM and Ogg Theora video
	 *  - `video/quicktime` Quicktime video
	 *  - `video/x-ms-wmv` Windows media video
	 * 
	 * @return string  The mime type of the file
	 */
	public function getMimeType()
	{
		$this->tossIfDeleted();
		
		return self::determineMimeType($this->file);	
	}
	
	
	/**
	 * Returns the last modification time of the file
	 * 
	 * @return fTimestamp  The timestamp of when the file was last modified
	 */
	public function getMTime()
	{
		$this->tossIfDeleted();
		
		return new fTimestamp(filemtime($this->file));	
	}
	
	
	/**
	 * Gets the filename (i.e. does not include the directory)
	 * 
	 * @param  boolean $remove_extension  If the extension should be removed from the filename
	 * @return string  The filename of the file
	 */
	public function getName($remove_extension=FALSE)
	{
		// For some reason PHP calls the filename the basename, where filename is the filename minus the extension
		return fFilesystem::getPathInfo($this->file, $remove_extension ? 'filename' : 'basename');
	}
	
	
	/**
	 * Gets the directory the file is located in
	 * 
	 * @return fDirectory  The directory containing the file
	 */
	public function getParent()
	{
		return new fDirectory(fFilesystem::getPathInfo($this->file, 'dirname'));
	}
	
	
	/**
	 * Gets the file's current path (directory and filename)
	 * 
	 * If the web path is requested, uses translations set with
	 * fFilesystem::addWebPathTranslation()
	 * 
	 * @param  boolean $translate_to_web_path  If the path should be the web path
	 * @return string  The path (directory and filename) for the file
	 */
	public function getPath($translate_to_web_path=FALSE)
	{
		if ($translate_to_web_path) {
			return fFilesystem::translateToWebPath($this->file);
		}
		return $this->file;
	}
	
	
	/**
	 * Gets the size of the file
	 * 
	 * The return value may be incorrect for files over 2GB on 32-bit OSes.
	 * 
	 * @param  boolean $format          If the filesize should be formatted for human readability
	 * @param  integer $decimal_places  The number of decimal places to format to (if enabled)
	 * @return integer|string  If formatted a string with filesize in b/kb/mb/gb/tb, otherwise an integer
	 */
	public function getSize($format=FALSE, $decimal_places=1)
	{
		$this->tossIfDeleted();
		
		// This technique can overcome signed integer limit
		$size = sprintf("%u", filesize($this->file));
		
		if (!$format) {
			return $size;
		}
		
		return fFilesystem::formatFilesize($size, $decimal_places);
	}
	
	
	/**
	 * Check to see if the current file is writable
	 * 
	 * @return boolean  If the file is writable
	 */
	public function isWritable()
	{
		$this->tossIfDeleted();
		
		return is_writable($this->file);
	}
	
	
	/**
	 * Returns the current one-based line number (required by iterator interface)
	 * 
	 * @throws fNoRemainingException  When there are no remaining lines in the file
	 * @internal
	 * 
	 * @return integer  The current line number
	 */
	public function key()
	{
		$this->tossIfDeleted();
		
		if ($this->file_handle === NULL) {
			$this->next();
			
		} elseif (!$this->valid()) {
			throw new fNoRemainingException('There are no remaining lines');
		}
		
		return $this->current_line_number;
	}
	
	
	/**
	 * Moves the current file to a different directory
	 * 
	 * Please note that ::rename() will rename a file in its directory or rename
	 * it into a different directory.
	 * 
	 * If the current file's filename already exists in the new directory and
	 * the overwrite flag is set to false, the filename will be changed to a
	 * unique name.
	 * 
	 * This operation will be reverted if a filesystem transaction is in
	 * progress and is later rolled back.
	 * 
	 * @throws fValidationException  When the directory passed is not a directory or is not readable
	 * 
	 * @param  fDirectory|string $new_directory  The directory to move this file into
	 * @param  boolean           $overwrite      If the current filename already exists in the new directory, `TRUE` will cause the file to be overwritten, `FALSE` will cause the new filename to change
	 * @return fFile  The file object, to allow for method chaining
	 */
	public function move($new_directory, $overwrite)
	{
		if (!$new_directory instanceof fDirectory) {
			$new_directory = new fDirectory($new_directory);
		}
		
		return $this->rename($new_directory->getPath() . $this->getName(), $overwrite);
	}
	
	
	/**
	 * Advances to the next line in the file (required by iterator interface)
	 * 
	 * @throws fNoRemainingException  When there are no remaining lines in the file
	 * @internal
	 * 
	 * @return void
	 */
	public function next()
	{
		$this->tossIfDeleted();
		
		if ($this->file_handle === NULL) {
			$this->file_handle         = fopen($this->file, 'r');
			$this->current_line        = '';
			$this->current_line_number = 0;
			
		} elseif (!$this->valid()) {
			throw new fNoRemainingException('There are no remaining lines');
		}
		
		$this->current_line = fgets($this->file_handle);
		$this->current_line_number++;
	}
	
	
	/**
	 * Prints the contents of the file
	 * 
	 * This method is primarily intended for when PHP is used to control access
	 * to files.
	 * 
	 * Be sure to close the session, if open, to prevent performance issues.
	 * Any open output buffers are automatically closed and discarded.
	 * 
	 * @param  boolean $headers   If HTTP headers for the file should be included
	 * @param  mixed   $filename  Present the file as an attachment instead of just outputting type headers - if a string is passed, that will be used for the filename, if `TRUE` is passed, the current filename will be used
	 * @return fFile  The file object, to allow for method chaining
	 */
	public function output($headers, $filename=NULL)
	{
		$this->tossIfDeleted();
		
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		
		if ($headers) {
			if ($filename !== NULL) {
				if ($filename === TRUE) { $filename = $this->getName();	}
				header('Content-Disposition: attachment; filename="' . $filename . '"');		
			}
			header('Cache-Control: ');
			header('Content-Length: ' . $this->getSize());
			header('Content-Type: ' . $this->getMimeType());
			header('Expires: ');
			header('Last-Modified: ' . $this->getMTime()->format('D, d M Y H:i:s'));
			header('Pragma: ');	
		}
			
		readfile($this->file);
		
		return $this;
	}
	
	
	/**
	 * Reads the data from the file
	 * 
	 * Reads all file data into memory, use with caution on large files!
	 * 
	 * This operation will read the data that has been written during the
	 * current transaction if one is in progress.
	 * 
	 * @param  mixed $data  The data to write to the file
	 * @return string  The contents of the file
	 */
	public function read()
	{
		$this->tossIfDeleted();
		
		return file_get_contents($this->file);
	}
	
	
	/**
	 * Renames the current file
	 * 
	 * If the filename already exists and the overwrite flag is set to false,
	 * a new filename will be created.
	 * 
	 * This operation will be reverted if a filesystem transaction is in
	 * progress and is later rolled back.
	 * 
	 * @param  string  $new_filename  The new full path to the file or a new filename in the current directory
	 * @param  boolean $overwrite     If the new filename already exists, `TRUE` will cause the file to be overwritten, `FALSE` will cause the new filename to change
	 * @return fFile  The file object, to allow for method chaining
	 */
	public function rename($new_filename, $overwrite)
	{
		$this->tossIfDeleted();
		
		if (!$this->getParent()->isWritable()) {
			throw new fEnvironmentException(
				'The file, %s, can not be renamed because the directory containing it is not writable',
				$this->file
			);
		}
		
		// If the filename does not contain any folder traversal, rename the file in the current directory
		if (preg_match('#^[^/\\\\]+$#D', $new_filename)) {
			$new_filename = $this->getParent()->getPath() . $new_filename;		
		}
		
		$info = fFilesystem::getPathInfo($new_filename);
		
		if (!file_exists($info['dirname'])) {
			throw new fProgrammerException(
				'The new filename specified, %s, is inside of a directory that does not exist',
				$new_filename
			);
		}
		
		// Make the filename absolute
		$new_filename = fDirectory::makeCanonical(realpath($info['dirname'])) . $info['basename'];
		
		if ($this->file == $new_filename && $overwrite) {
			return $this;
		}
		
		if (file_exists($new_filename) && !$overwrite) {
			$new_filename = fFilesystem::makeUniqueName($new_filename);
		}
		
		if (file_exists($new_filename)) {
			if (!is_writable($new_filename)) {
				throw new fEnvironmentException(
					'The new filename specified, %s, already exists, but is not writable',
					$new_filename
				);
			}
			
			if (fFilesystem::isInsideTransaction()) {
				fFilesystem::recordWrite(new fFile($new_filename));
			}
			// Windows requires that the existing file be deleted before being replaced
			unlink($new_filename);
				
		} else {
			$new_dir = new fDirectory($info['dirname']);
			if (!$new_dir->isWritable()) {
				throw new fEnvironmentException(
					'The new filename specified, %s, is inside of a directory that is not writable',
					$new_filename
				);
			}
		}
		
		rename($this->file, $new_filename);
		
		// Allow filesystem transactions
		if (fFilesystem::isInsideTransaction()) {
			fFilesystem::recordRename($this->file, $new_filename);
		}
		
		fFilesystem::updateFilenameMap($this->file, $new_filename);
		
		return $this;
	}
	
	
	/**
	 * Rewinds the file handle (required by iterator interface)
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	public function rewind()
	{
		$this->tossIfDeleted();
		
		if ($this->file_handle !== NULL) {
			rewind($this->file_handle);	
		}
	}
	
	
	/**
	 * Throws an fProgrammerException if the file has been deleted
	 * 
	 * @return void
	 */
	protected function tossIfDeleted()
	{
		if ($this->deleted) {
			throw new fProgrammerException(
				"The action requested can not be performed because the file has been deleted\n\nBacktrace for fFile::delete() call:\n%s",
				fCore::backtrace(0, $this->deleted)
			);
		}
	}
	
	
	/**
	 * Returns if the file has any lines left (required by iterator interface)
	 * 
	 * @internal
	 * 
	 * @return boolean  If the iterator is still valid
	 */
	public function valid()
	{
		$this->tossIfDeleted();
		
		if ($this->file_handle === NULL) {
			return TRUE;
		}
		
		return $this->current_line !== FALSE;
	}
	
	
	/**
	 * Writes the provided data to the file
	 * 
	 * Requires all previous data to be stored in memory if inside a
	 * transaction, use with caution on large files!
	 * 
	 * If a filesystem transaction is in progress and is rolled back, the
	 * previous data will be restored.
	 * 
	 * @param  mixed $data  The data to write to the file
	 * @return fFile  The file object, to allow for method chaining
	 */
	public function write($data)
	{
		$this->tossIfDeleted();
		
		if (!$this->isWritable()) {
			throw new fEnvironmentException(
				'This file, %s, can not be written to because it is not writable',
				$this->file
			);
		}
		
		// Allow filesystem transactions
		if (fFilesystem::isInsideTransaction()) {
			fFilesystem::recordWrite($this);
		}
		
		file_put_contents($this->file, $data);
		clearstatcache();
		
		return $this;
	}
}



/**
 * Copyright (c) 2007-2011 Will Bond <will@flourishlib.com>, others
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