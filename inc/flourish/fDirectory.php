<?php
/**
 * Represents a directory on the filesystem, also provides static directory-related methods
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fDirectory
 * 
 * @version    1.0.0b14
 * @changes    1.0.0b14  Fixed a bug in ::delete() where a non-existent method was being called on fFilesystem, added a permission check to ::delete() [wb, 2011-08-23]
 * @changes    1.0.0b13  Added the ::clear() method [wb, 2011-01-10]
 * @changes    1.0.0b12  Fixed ::scanRecursive() to not add duplicate entries for certain nested directory structures [wb, 2010-08-10]
 * @changes    1.0.0b11  Fixed ::scan() to properly add trailing /s for directories [wb, 2010-03-16]
 * @changes    1.0.0b10  BackwardsCompatibilityBreak - Fixed ::scan() and ::scanRecursive() to strip the current directory's path before matching, added support for glob style matching [wb, 2010-03-05]
 * @changes    1.0.0b9   Changed the way directories deleted in a filesystem transaction are handled, including improvements to the exception that is thrown [wb+wb-imarc, 2010-03-05]
 * @changes    1.0.0b8   Backwards Compatibility Break - renamed ::getFilesize() to ::getSize(), added ::move() [wb, 2009-12-16]
 * @changes    1.0.0b7   Fixed ::__construct() to throw an fValidationException when the directory does not exist [wb, 2009-08-21]
 * @changes    1.0.0b6   Fixed a bug where deleting a directory would prevent any future operations in the same script execution on a file or directory with the same path [wb, 2009-08-20]
 * @changes    1.0.0b5   Added the ability to skip checks in ::__construct() for better performance in conjunction with fFilesystem::createObject() [wb, 2009-08-06]
 * @changes    1.0.0b4   Refactored ::scan() to use the new fFilesystem::createObject() method [wb, 2009-01-21]
 * @changes    1.0.0b3   Added the $regex_filter parameter to ::scan() and ::scanRecursive(), fixed bug in ::scanRecursive() [wb, 2009-01-05]
 * @changes    1.0.0b2   Removed some unnecessary error suppresion operators [wb, 2008-12-11]
 * @changes    1.0.0b    The initial implementation [wb, 2007-12-21]
 */
class fDirectory
{
	// The following constants allow for nice looking callbacks to static methods
	const create        = 'fDirectory::create';
	const makeCanonical = 'fDirectory::makeCanonical';
	
	
	/**
	 * Creates a directory on the filesystem and returns an object representing it
	 * 
	 * The directory creation is done recursively, so if any of the parent
	 * directories do not exist, they will be created.
	 * 
	 * This operation will be reverted by a filesystem transaction being rolled back.
	 * 
	 * @throws fValidationException  When no directory was specified, or the directory already exists
	 * 
	 * @param  string  $directory  The path to the new directory
	 * @param  numeric $mode       The mode (permissions) to use when creating the directory. This should be an octal number (requires a leading zero). This has no effect on the Windows platform.
	 * @return fDirectory
	 */
	static public function create($directory, $mode=0777)
	{
		if (empty($directory)) {
			throw new fValidationException('No directory name was specified');
		}
		
		if (file_exists($directory)) {
			throw new fValidationException(
				'The directory specified, %s, already exists',
				$directory
			);
		}
		
		$parent_directory = fFilesystem::getPathInfo($directory, 'dirname');
		if (!file_exists($parent_directory)) {
			fDirectory::create($parent_directory, $mode);
		}
		
		if (!is_writable($parent_directory)) {
			throw new fEnvironmentException(
				'The directory specified, %s, is inside of a directory that is not writable',
				$directory
			);
		}
		
		mkdir($directory, $mode);
		
		$directory = new fDirectory($directory);
		
		fFilesystem::recordCreate($directory);
		
		return $directory;
	}
	
	
	/**
	 * Makes sure a directory has a `/` or `\` at the end
	 * 
	 * @param  string $directory  The directory to check
	 * @return string  The directory name in canonical form
	 */
	static public function makeCanonical($directory)
	{
		if (substr($directory, -1) != '/' && substr($directory, -1) != '\\') {
			$directory .= DIRECTORY_SEPARATOR;
		}
		return $directory;
	}
	
	
	/**
	 * A backtrace from when the file was deleted 
	 * 
	 * @var array
	 */
	protected $deleted = NULL;
	
	/**
	 * The full path to the directory
	 * 
	 * @var string
	 */
	protected $directory;
	
	
	/**
	 * Creates an object to represent a directory on the filesystem
	 * 
	 * If multiple fDirectory objects are created for a single directory,
	 * they will reflect changes in each other including rename and delete
	 * actions.
	 * 
	 * @throws fValidationException  When no directory was specified, when the directory does not exist or when the path specified is not a directory
	 * 
	 * @param  string  $directory    The path to the directory
	 * @param  boolean $skip_checks  If file checks should be skipped, which improves performance, but may cause undefined behavior - only skip these if they are duplicated elsewhere
	 * @return fDirectory
	 */
	public function __construct($directory, $skip_checks=FALSE)
	{
		if (!$skip_checks) {
			if (empty($directory)) {
				throw new fValidationException('No directory was specified');
			}
			
			if (!is_readable($directory)) {
				throw new fValidationException(
					'The directory specified, %s, does not exist or is not readable',
					$directory
				);
			}
			if (!is_dir($directory)) {
				throw new fValidationException(
					'The directory specified, %s, is not a directory',
					$directory
				);
			}
		}
		
		$directory = self::makeCanonical(realpath($directory));
		
		$this->directory =& fFilesystem::hookFilenameMap($directory);
		$this->deleted   =& fFilesystem::hookDeletedMap($directory);
		
		// If the directory is listed as deleted and we are not inside a transaction,
		// but we've gotten to here, then the directory exists, so we can wipe the backtrace
		if ($this->deleted !== NULL && !fFilesystem::isInsideTransaction()) {
			fFilesystem::updateDeletedMap($directory, NULL);
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
	 * Returns the full filesystem path for the directory
	 * 
	 * @return string  The full filesystem path
	 */
	public function __toString()
	{
		return $this->getPath();
	}
	
	
	/**
	 * Removes all files and directories inside of the directory
	 * 
	 * @return void
	 */
	public function clear()
	{
		if ($this->deleted) {
			return;	
		}
		
		foreach ($this->scan() as $file) {
			$file->delete();
		}
	}
	
	
	/**
	 * Will delete a directory and all files and directories inside of it
	 * 
	 * This operation will not be performed until the filesystem transaction
	 * has been committed, if a transaction is in progress. Any non-Flourish
	 * code (PHP or system) will still see this directory and all contents as
	 * existing until that point.
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
				'The directory, %s, can not be deleted because the directory containing it is not writable',
				$this->directory
			);
		}
		
		$files = $this->scan();
		
		foreach ($files as $file) {
			$file->delete();
		}
		
		// Allow filesystem transactions
		if (fFilesystem::isInsideTransaction()) {
			return fFilesystem::recordDelete($this);
		}
		
		rmdir($this->directory);
		
		fFilesystem::updateDeletedMap($this->directory, debug_backtrace());
		fFilesystem::updateFilenameMapForDirectory($this->directory, '*DELETED at ' . time() . ' with token ' . uniqid('', TRUE) . '* ' . $this->directory);
	}
	
	
	/**
	 * Gets the name of the directory
	 * 
	 * @return string  The name of the directory
	 */
	public function getName()
	{
		return fFilesystem::getPathInfo($this->directory, 'basename');
	}
	
	
	/**
	 * Gets the parent directory
	 * 
	 * @return fDirectory  The object representing the parent directory
	 */
	public function getParent()
	{
		$this->tossIfDeleted();
		
		$dirname = fFilesystem::getPathInfo($this->directory, 'dirname');
		
		if ($dirname == $this->directory) {
			throw new fEnvironmentException(
				'The current directory does not have a parent directory'
			);
		}
		
		return new fDirectory($dirname);
	}
	
	
	/**
	 * Gets the directory's current path
	 * 
	 * If the web path is requested, uses translations set with
	 * fFilesystem::addWebPathTranslation()
	 * 
	 * @param  boolean $translate_to_web_path  If the path should be the web path
	 * @return string  The path for the directory
	 */
	public function getPath($translate_to_web_path=FALSE)
	{
		$this->tossIfDeleted();
		
		if ($translate_to_web_path) {
			return fFilesystem::translateToWebPath($this->directory);
		}
		return $this->directory;
	}
	
	
	/**
	 * Gets the disk usage of the directory and all files and folders contained within
	 * 
	 * This method may return incorrect results if files over 2GB exist and the
	 * server uses a 32 bit operating system
	 * 
	 * @param  boolean $format          If the filesize should be formatted for human readability
	 * @param  integer $decimal_places  The number of decimal places to format to (if enabled)
	 * @return integer|string  If formatted, a string with filesize in b/kb/mb/gb/tb, otherwise an integer
	 */
	public function getSize($format=FALSE, $decimal_places=1)
	{
		$this->tossIfDeleted();
		
		$size = 0;
		
		$children = $this->scan();
		foreach ($children as $child) {
			$size += $child->getSize();
		}
		
		if (!$format) {
			return $size;
		}
		
		return fFilesystem::formatFilesize($size, $decimal_places);
	}
	
	
	/**
	 * Check to see if the current directory is writable
	 * 
	 * @return boolean  If the directory is writable
	 */
	public function isWritable()
	{
		$this->tossIfDeleted();
		
		return is_writable($this->directory);
	}
	
	
	/**
	 * Moves the current directory into a different directory
	 * 
	 * Please note that ::rename() will rename a directory in its current
	 * parent directory or rename it into a different parent directory.
	 * 
	 * If the current directory's name already exists in the new parent
	 * directory and the overwrite flag is set to false, the name will be
	 * changed to a unique name.
	 * 
	 * This operation will be reverted if a filesystem transaction is in
	 * progress and is later rolled back.
	 * 
	 * @throws fValidationException  When the new parent directory passed is not a directory, is not readable or is a sub-directory of this directory
	 * 
	 * @param  fDirectory|string $new_parent_directory  The directory to move this directory into
	 * @param  boolean           $overwrite             If the current filename already exists in the new directory, `TRUE` will cause the file to be overwritten, `FALSE` will cause the new filename to change
	 * @return fDirectory  The directory object, to allow for method chaining
	 */
	public function move($new_parent_directory, $overwrite)
	{
		if (!$new_parent_directory instanceof fDirectory) {
			$new_parent_directory = new fDirectory($new_parent_directory);
		}
		
		if (strpos($new_parent_directory->getPath(), $this->getPath()) === 0) {
			throw new fValidationException('It is not possible to move a directory into one of its sub-directories');	
		}
		
		return $this->rename($new_parent_directory->getPath() . $this->getName(), $overwrite);
	}
	
	
	/**
	 * Renames the current directory
	 * 
	 * This operation will NOT be performed until the filesystem transaction
	 * has been committed, if a transaction is in progress. Any non-Flourish
	 * code (PHP or system) will still see this directory (and all contained
	 * files/dirs) as existing with the old paths until that point.
	 * 
	 * @param  string  $new_dirname  The new full path to the directory or a new name in the current parent directory
	 * @param  boolean $overwrite    If the new dirname already exists, TRUE will cause the file to be overwritten, FALSE will cause the new filename to change
	 * @return void
	 */
	public function rename($new_dirname, $overwrite)
	{
		$this->tossIfDeleted();
		
		if (!$this->getParent()->isWritable()) {
			throw new fEnvironmentException(
				'The directory, %s, can not be renamed because the directory containing it is not writable',
				$this->directory
			);
		}
		
		// If the dirname does not contain any folder traversal, rename the dir in the current parent directory
		if (preg_match('#^[^/\\\\]+$#D', $new_dirname)) {
			$new_dirname = $this->getParent()->getPath() . $new_dirname;	
		}
		
		$info = fFilesystem::getPathInfo($new_dirname);
		
		if (!file_exists($info['dirname'])) {
			throw new fProgrammerException(
				'The new directory name specified, %s, is inside of a directory that does not exist',
				$new_dirname
			);
		}
		
		if (file_exists($new_dirname)) {
			if (!is_writable($new_dirname)) {
				throw new fEnvironmentException(
					'The new directory name specified, %s, already exists, but is not writable',
					$new_dirname
				);
			}
			if (!$overwrite) {
				$new_dirname = fFilesystem::makeUniqueName($new_dirname);
			}
		} else {
			$parent_dir = new fDirectory($info['dirname']);
			if (!$parent_dir->isWritable()) {
				throw new fEnvironmentException(
					'The new directory name specified, %s, is inside of a directory that is not writable',
					$new_dirname
				);
			}
		}
		
		rename($this->directory, $new_dirname);
		
		// Make the dirname absolute
		$new_dirname = fDirectory::makeCanonical(realpath($new_dirname));
		
		// Allow filesystem transactions
		if (fFilesystem::isInsideTransaction()) {
			fFilesystem::rename($this->directory, $new_dirname);
		}
		
		fFilesystem::updateFilenameMapForDirectory($this->directory, $new_dirname);
	}
	
	
	/**
	 * Performs a [http://php.net/scandir scandir()] on a directory, removing the `.` and `..` entries
	 * 
	 * If the `$filter` looks like a valid PCRE pattern - matching delimeters
	 * (a delimeter can be any non-alphanumeric, non-backslash, non-whitespace
	 * character) followed by zero or more of the flags `i`, `m`, `s`, `x`,
	 * `e`, `A`, `D`,  `S`, `U`, `X`, `J`, `u` - then
	 * [http://php.net/preg_match `preg_match()`] will be used.
	 * 
	 * Otherwise the `$filter` will do a case-sensitive match with `*` matching
	 * zero or more characters and `?` matching a single character.
	 * 
	 * On all OSes (even Windows), directories will be separated by `/`s when
	 * comparing with the `$filter`.
	 * 
	 * @param  string $filter  A PCRE or glob pattern to filter files/directories by path - directories can be detected by checking for a trailing / (even on Windows)
	 * @return array  The fFile (or fImage) and fDirectory objects for the files/directories in this directory
	 */
	public function scan($filter=NULL)
	{
		$this->tossIfDeleted();
		
		$files   = array_diff(scandir($this->directory), array('.', '..'));
		$objects = array();
		
		if ($filter && !preg_match('#^([^a-zA-Z0-9\\\\\s]).*\1[imsxeADSUXJu]*$#D', $filter)) {
			$filter = '#^' . strtr(
				preg_quote($filter, '#'),
				array(
					'\\*' => '.*',
					'\\?' => '.'
				)
			) . '$#D';
		}
		
		natcasesort($files);
		
		foreach ($files as $file) {
			if ($filter) {
				$test_path = (is_dir($this->directory . $file)) ? $file . '/' : $file;
				if (!preg_match($filter, $test_path)) {
					continue;
				}
			}
			
			$objects[] = fFilesystem::createObject($this->directory . $file);
		}
		
		return $objects;
	}
	
	
	/**
	 * Performs a **recursive** [http://php.net/scandir scandir()] on a directory, removing the `.` and `..` entries
	 * 
	 * @param  string $filter  A PCRE or glob pattern to filter files/directories by path - see ::scan() for details
	 * @return array  The fFile (or fImage) and fDirectory objects for the files/directories (listed recursively) in this directory
	 */
	public function scanRecursive($filter=NULL)
	{
		$this->tossIfDeleted();
		
		$objects = $this->scan();
		
		for ($i=0; $i < sizeof($objects); $i++) {
			if ($objects[$i] instanceof fDirectory) {
				array_splice($objects, $i+1, 0, $objects[$i]->scan());
			}
		}
		
		if ($filter) {
			if (!preg_match('#^([^a-zA-Z0-9\\\\\s*?^$]).*\1[imsxeADSUXJu]*$#D', $filter)) {
				$filter = '#^' . strtr(
					preg_quote($filter, '#'),
					array(
						'\\*' => '.*',
						'\\?' => '.'
					)
				) . '$#D';
			}
			
			$new_objects  = array();
			$strip_length = strlen($this->getPath());
			foreach ($objects as $object) {
				$test_path = substr($object->getPath(), $strip_length);
				$test_path = str_replace(DIRECTORY_SEPARATOR, '/', $test_path);
				if (!preg_match($filter, $test_path)) {
					continue;	
				}	
				$new_objects[] = $object;
			}
			$objects = $new_objects;
		}
		
		return $objects;
	}
	
	
	/**
	 * Throws an exception if the directory has been deleted
	 * 
	 * @return void
	 */
	protected function tossIfDeleted()
	{
		if ($this->deleted) {
			throw new fProgrammerException(
				"The action requested can not be performed because the directory has been deleted\n\nBacktrace for fDirectory::delete() call:\n%s",
				fCore::backtrace(0, $this->deleted)
			);
		}
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
