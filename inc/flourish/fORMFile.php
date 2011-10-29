<?php
/**
 * Provides file manipulation functionality for fActiveRecord classes
 * 
 * @copyright  Copyright (c) 2008-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fORMFile
 * 
 * @version    1.0.0b30
 * @changes    1.0.0b30  Updated code for the new fUpload API [wb, 2011-08-24]
 * @changes    1.0.0b29  Fixed a bug when uploading a new file to a column with an existing file that was not found on the filesystem [wb, 2011-05-10]
 * @changes    1.0.0b28  Backwards Compatibility Break - ::configureImageUploadColumn() no longer accepts the optional `$image_type` as the fourth parameter, instead ::addFImageMethodCall() must be called with `saveChanges` as the `$method` and the image type as the first parameter [wb, 2010-11-30]
 * @changes    1.0.0b27  Fixed column inheritance to properly handle non-images and inheriting into image upload columns [wb, 2010-09-18]
 * @changes    1.0.0b26  Enhanced ::configureColumnInheritance() to ensure both columns specified have been set up as file upload columns [wb, 2010-08-18]
 * @changes    1.0.0b25  Updated code to work with the new fORM API [wb, 2010-08-06]
 * @changes    1.0.0b24  Changed validation messages array to use column name keys [wb, 2010-05-26]
 * @changes    1.0.0b23  Fixed a bug with ::upload() that could cause a method called on a non-object error in relation to the upload directory not being defined [wb, 2010-05-10]
 * @changes    1.0.0b22  Updated the TEMP_DIRECTORY constant to not include the trailing slash, code now uses DIRECTORY_SEPARATOR to fix issues on Windows [wb, 2010-04-28]
 * @changes    1.0.0b21  Fixed ::set() to perform column inheritance, just like ::upload() does [wb, 2010-03-15]
 * @changes    1.0.0b20  Fixed the `set` and `process` methods to return the record instance, changed `upload` methods to return the fFile object, updated ::reflect() with new return values [wb, 2010-03-15]
 * @changes    1.0.0b19  Fixed a few missed instances of old fFile method names [wb, 2009-12-16]
 * @changes    1.0.0b18  Updated code for the new fFile API [wb, 2009-12-16]
 * @changes    1.0.0b17  Updated code for the new fORMDatabase and fORMSchema APIs [wb, 2009-10-28]
 * @changes    1.0.0b16  fImage method calls for file upload columns will no longer cause notices due to a missing image type [wb, 2009-09-09]
 * @changes    1.0.0b15  ::addFImageMethodCall() no longer requires column be an image upload column, inheritance to an image column now only happens for fImage objects [wb, 2009-07-29] 
 * @changes    1.0.0b14  Updated to use new fORM::registerInspectCallback() method [wb, 2009-07-13]
 * @changes    1.0.0b13  Updated code for new fORM API [wb, 2009-06-15]
 * @changes    1.0.0b12  Changed replacement values in preg_replace() calls to be properly escaped [wb, 2009-06-11]
 * @changes    1.0.0b11  Updated code to use new fValidationException::formatField() method [wb, 2009-06-04]  
 * @changes    1.0.0b10  Fixed a bug where an inherited file upload column would not be properly re-set with an `existing-` input [wb, 2009-05-26]
 * @changes    1.0.0b9   ::upload() and ::set() now set the `$values` entry to `NULL` for filenames that are empty [wb, 2009-03-02]
 * @changes    1.0.0b8   Changed ::set() to accept objects and reject directories [wb, 2009-01-21]
 * @changes    1.0.0b7   Changed the class to use the new fFilesystem::createObject() method [wb, 2009-01-21]
 * @changes    1.0.0b6   Old files are now checked against the current file to prevent removal of an in-use file [wb, 2008-12-23]
 * @changes    1.0.0b5   Fixed ::replicate() to ensure the temp directory exists and ::set() to use the temp directory [wb, 2008-12-23]
 * @changes    1.0.0b4   ::objectify() no longer throws an exception when a file can't be found [wb, 2008-12-18]
 * @changes    1.0.0b3   Added ::replicate() so that replicated files get pu in the temp directory [wb, 2008-12-12]
 * @changes    1.0.0b2   Fixed a bug with objectifying file columns [wb, 2008-11-24]
 * @changes    1.0.0b    The initial implementation [wb, 2008-05-28]
 */
class fORMFile
{
	// The following constants allow for nice looking callbacks to static methods
	const addFImageMethodCall        = 'fORMFile::addFImageMethodCall';
	const addFUploadMethodCall       = 'fORMFile::addFUploadMethodCall';
	const begin                      = 'fORMFile::begin';
	const commit                     = 'fORMFile::commit';
	const configureColumnInheritance = 'fORMFile::configureColumnInheritance';
	const configureFileUploadColumn  = 'fORMFile::configureFileUploadColumn';
	const configureImageUploadColumn = 'fORMFile::configureImageUploadColumn';
	const delete                     = 'fORMFile::delete';
	const deleteOld                  = 'fORMFile::deleteOld';
	const encode                     = 'fORMFile::encode';
	const inspect                    = 'fORMFile::inspect';
	const moveFromTemp               = 'fORMFile::moveFromTemp';
	const objectify                  = 'fORMFile::objectify';
	const populate                   = 'fORMFile::populate';
	const prepare                    = 'fORMFile::prepare';
	const process                    = 'fORMFile::process';
	const processImage               = 'fORMFile::processImage';
	const reflect                    = 'fORMFile::reflect';
	const replicate                  = 'fORMFile::replicate';
	const reset                      = 'fORMFile::reset';
	const rollback                   = 'fORMFile::rollback';
	const set                        = 'fORMFile::set';
	const upload                     = 'fORMFile::upload';
	const validate                   = 'fORMFile::validate';
	
	
	/**
	 * The temporary directory to use for various tasks
	 * 
	 * @internal
	 * 
	 * @var string
	 */
	const TEMP_DIRECTORY = '__flourish_temp';
	
	
	/**
	 * Defines how columns can inherit uploaded files
	 * 
	 * @var array
	 */
	static private $column_inheritence = array();
	
	/**
	 * Methods to be called on fUpload before the file is uploaded
	 * 
	 * @var array
	 */
	static private $fupload_method_calls = array();
	
	/**
	 * Columns that can be filled by file uploads
	 * 
	 * @var array
	 */
	static private $file_upload_columns = array();
	
	/**
	 * Methods to be called on the fImage instance
	 * 
	 * @var array
	 */
	static private $fimage_method_calls = array();
	
	/**
	 * Columns that can be filled by image uploads
	 * 
	 * @var array
	 */
	static private $image_upload_columns = array();
	
	/**
	 * Keeps track of the nesting level of the filesystem transaction so we know when to start, commit, rollback, etc
	 * 
	 * @var integer
	 */
	static private $transaction_level = 0;
	
	
	/**
	 * Adds an fImage method call to the image manipulation for a column if an image file is uploaded
	 * 
	 * Any call to fImage::saveChanges() will be called last. If no explicit
	 * method call to fImage::saveChanges() is made, it will be called
	 * implicitly with default parameters.
	 * 
	 * @param  mixed  $class       The class name or instance of the class
	 * @param  string $column      The column to call the method for
	 * @param  string $method      The fImage method to call
	 * @param  array  $parameters  The parameters to pass to the method
	 * @return void
	 */
	static public function addFImageMethodCall($class, $column, $method, $parameters=array())
	{
		$class = fORM::getClass($class);
		
		if (empty(self::$file_upload_columns[$class][$column])) {
			throw new fProgrammerException(
				'The column specified, %s, has not been configured as a file or image upload column',
				$column
			);
		}
		
		if (empty(self::$fimage_method_calls[$class])) {
			self::$fimage_method_calls[$class] = array();
		}
		if (empty(self::$fimage_method_calls[$class][$column])) {
			self::$fimage_method_calls[$class][$column] = array();
		}
		
		self::$fimage_method_calls[$class][$column][] = array(
			'method'     => $method,
			'parameters' => $parameters
		);
	}
	
	
	/**
	 * Adds an fUpload method call to the fUpload initialization for a column
	 * 
	 * @param  mixed  $class       The class name or instance of the class
	 * @param  string $column      The column to call the method for
	 * @param  string $method      The fUpload method to call
	 * @param  array  $parameters  The parameters to pass to the method
	 * @return void
	 */
	static public function addFUploadMethodCall($class, $column, $method, $parameters=array())
	{
		if ($method == 'enableOverwrite') {
			throw new fProgrammerException(
				'The method specified, %1$s, is not compatible with how %2$s stores and associates files with records',
				$method,
				'fORMFile'
			); 	
		}
		
		$class = fORM::getClass($class);
		
		if (empty(self::$file_upload_columns[$class][$column])) {
			throw new fProgrammerException(
				'The column specified, %s, has not been configured as a file or image upload column',
				$column
			);
		}
		
		if (empty(self::$fupload_method_calls[$class])) {
			self::$fupload_method_calls[$class] = array();
		}
		if (empty(self::$fupload_method_calls[$class][$column])) {
			self::$fupload_method_calls[$class][$column] = array();
		}
		
		self::$fupload_method_calls[$class][$column][] = array(
			'method'     => $method,
			'parameters' => $parameters
		);
	}
	
	
	/**
	 * Begins a transaction, or increases the level
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	static public function begin()
	{
		// If the transaction was started by something else, don't even track it
		if (self::$transaction_level == 0 && fFilesystem::isInsideTransaction()) {
			return;
		}
		
		self::$transaction_level++;
		
		if (!fFilesystem::isInsideTransaction()) {
			fFilesystem::begin();
		}
	}
	
	
	/**
	 * Commits a transaction, or decreases the level
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	static public function commit()
	{
		// If the transaction was started by something else, don't even track it
		if (self::$transaction_level == 0) {
			return;
		}
		
		self::$transaction_level--;
		
		if (!self::$transaction_level) {
			fFilesystem::commit();
		}
	}
	
	
	/**
	 * Composes text using fText if loaded
	 * 
	 * @param  string  $message    The message to compose
	 * @param  mixed   $component  A string or number to insert into the message
	 * @param  mixed   ...
	 * @return string  The composed and possible translated message
	 */
	static private function compose($message)
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
	 * Sets a column to be a file upload column
	 * 
	 * Configuring a column to be a file upload column means that whenever
	 * fActiveRecord::populate() is called for an fActiveRecord object, any
	 * appropriately named file uploads (via `$_FILES`) will be moved into
	 * the directory for this column.
	 * 
	 * Setting the column to a file path will cause the specified file to
	 * be copied into the directory for this column.
	 * 
	 * @param  mixed             $class      The class name or instance of the class
	 * @param  string            $column     The column to set as a file upload column
	 * @param  fDirectory|string $directory  The directory to upload/move to
	 * @return void
	 */
	static public function configureFileUploadColumn($class, $column, $directory)
	{
		$class     = fORM::getClass($class);
		$table     = fORM::tablize($class);
		$schema    = fORMSchema::retrieve($class);
		$data_type = $schema->getColumnInfo($table, $column, 'type');
		
		$valid_data_types = array('varchar', 'char', 'text');
		if (!in_array($data_type, $valid_data_types)) {
			throw new fProgrammerException(
				'The column specified, %1$s, is a %2$s column. Must be one of %3$s to be set as a file upload column.',
				$column,
				$data_type,
				join(', ', $valid_data_types)
			);
		}
		
		if (!is_object($directory)) {
			$directory = new fDirectory($directory);
		}
		
		if (!$directory->isWritable()) {
			throw new fEnvironmentException(
				'The file upload directory, %s, is not writable',
				$directory->getPath()
			);
		}
		
		$camelized_column = fGrammar::camelize($column, TRUE);
		
		fORM::registerActiveRecordMethod(
			$class,
			'upload' . $camelized_column,
			self::upload
		);
		
		fORM::registerActiveRecordMethod(
			$class,
			'set' . $camelized_column,
			self::set
		);
		
		fORM::registerActiveRecordMethod(
			$class,
			'encode' . $camelized_column,
			self::encode
		);
		
		fORM::registerActiveRecordMethod(
			$class,
			'prepare' . $camelized_column,
			self::prepare
		);
		
		fORM::registerReflectCallback($class, self::reflect);
		fORM::registerInspectCallback($class, $column, self::inspect);
		fORM::registerReplicateCallback($class, $column, self::replicate);
		fORM::registerObjectifyCallback($class, $column, self::objectify);
		
		$only_once_hooks = array(
			'post-begin::delete()'    => self::begin,
			'pre-commit::delete()'    => self::delete,
			'post-commit::delete()'   => self::commit,
			'post-rollback::delete()' => self::rollback,
			'post::populate()'        => self::populate,
			'post-begin::store()'     => self::begin,
			'post-validate::store()'  => self::moveFromTemp,
			'pre-commit::store()'     => self::deleteOld,
			'post-commit::store()'    => self::commit,
			'post-rollback::store()'  => self::rollback,
			'post::validate()'        => self::validate
		);
		
		foreach ($only_once_hooks as $hook => $callback) {
			if (!fORM::checkHookCallback($class, $hook, $callback)) {
				fORM::registerHookCallback($class, $hook, $callback);
			}
		}
		
		if (empty(self::$file_upload_columns[$class])) {
			self::$file_upload_columns[$class] = array();
		}
		
		self::$file_upload_columns[$class][$column] = $directory;
	}
	
	
	/**
	 * Takes one file or image upload columns and sets it to inherit any uploaded/set files from another column
	 * 
	 * @param  mixed  $class                The class name or instance of the class
	 * @param  string $column               The column that will inherit the uploaded file
	 * @param  string $inherit_from_column  The column to inherit the uploaded file from
	 * @return void
	 */
	static public function configureColumnInheritance($class, $column, $inherit_from_column)
	{
		$class = fORM::getClass($class);
		
		if (empty(self::$file_upload_columns[$class][$column])) {
			throw new fProgrammerException(
				'The column specified, %s, has not been configured as a file upload column',
				$column
			);
		}
		
		if (empty(self::$file_upload_columns[$class][$inherit_from_column])) {
			throw new fProgrammerException(
				'The column specified, %s, has not been configured as a file upload column',
				$column
			);
		}
		
		if (empty(self::$column_inheritence[$class])) {
			self::$column_inheritence[$class] = array();
		}
		
		if (empty(self::$column_inheritence[$class][$inherit_from_column])) {
			self::$column_inheritence[$class][$inherit_from_column] = array();
		}
		
		self::$column_inheritence[$class][$inherit_from_column][] = $column;
	}
	
	
	/**
	 * Sets a column to be an image upload column
	 * 
	 * This method works exactly the same as ::configureFileUploadColumn()
	 * except that only image files are accepted.
	 * 
	 * To alter an image, including the file type, use ::addFImageMethodCall().
	 * 
	 * @param  mixed             $class       The class name or instance of the class
	 * @param  string            $column      The column to set as a file upload column
	 * @param  fDirectory|string $directory   The directory to upload to
	 * @return void
	 */
	static public function configureImageUploadColumn($class, $column, $directory)
	{
		self::configureFileUploadColumn($class, $column, $directory);
		
		$class = fORM::getClass($class);
		
		$camelized_column = fGrammar::camelize($column, TRUE);
		
		fORM::registerActiveRecordMethod(
			$class,
			'process' . $camelized_column,
			self::process
		);
		
		if (empty(self::$image_upload_columns[$class])) {
			self::$image_upload_columns[$class] = array();
		}
		
		self::$image_upload_columns[$class][$column] = TRUE;
		
		self::addFUploadMethodCall(
			$class,
			$column,
			'setMimeTypes',
			array(
				array(
					'image/gif',
					'image/jpeg',
					'image/pjpeg',
					'image/png'
				),
				self::compose('The file uploaded is not an image')
			)
		);
	}
	
	
	/**
	 * Deletes the files for this record
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @return void
	 */
	static public function delete($object, &$values, &$old_values, &$related_records, &$cache)
	{
		$class = get_class($object);
		
		foreach (self::$file_upload_columns[$class] as $column => $directory) {
			
			// Remove the current file for the column
			if ($values[$column] instanceof fFile) {
				$values[$column]->delete();
			}
			
			// Remove the old files for the column
			foreach (fActiveRecord::retrieveOld($old_values, $column, array(), TRUE) as $file) {
				if ($file instanceof fFile) {
					$file->delete();
				}
			}
		}
	}
	
	
	/**
	 * Deletes old files for this record that have been replaced by new ones
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @return void
	 */
	static public function deleteOld($object, &$values, &$old_values, &$related_records, &$cache)
	{
		$class = get_class($object);
		
		// Remove the old files for the column
		foreach (self::$file_upload_columns[$class] as $column => $directory) {
			$current_file = $values[$column];
			foreach (fActiveRecord::retrieveOld($old_values, $column, array(), TRUE) as $file) {
				if ($file instanceof fFile && (!$current_file instanceof fFile || $current_file->getPath() != $file->getPath())) {
					$file->delete();
				}
			}
		}
	}
	
	
	/**
	 * Encodes a file for output into an HTML `input` tag
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @param  string        $method_name       The method that was called
	 * @param  array         $parameters        The parameters passed to the method
	 * @return void
	 */
	static public function encode($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		list ($action, $subject) = fORM::parseMethod($method_name);
		
		$column   = fGrammar::underscorize($subject);
		$filename = ($values[$column] instanceof fFile) ? $values[$column]->getName() : NULL;
		if ($filename && strpos($values[$column]->getPath(), self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $filename) !== FALSE) {
			$filename = self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $filename;
		}
		
		return fHTML::encode($filename);
	}
	
	
	/**
	 * Adds metadata about features added by this class
	 * 
	 * @internal
	 * 
	 * @param  string $class      The class being inspected
	 * @param  string $column     The column being inspected
	 * @param  array  &$metadata  The array of metadata about a column
	 * @return void
	 */
	static public function inspect($class, $column, &$metadata)
	{
		if (!empty(self::$image_upload_columns[$class][$column])) {
			$metadata['feature'] = 'image';
			
		} elseif (!empty(self::$file_upload_columns[$class][$column])) {
			$metadata['feature'] = 'file';
		}
		
		$metadata['directory'] = self::$file_upload_columns[$class][$column]->getPath();
	}
	
	
	/**
	 * Moves uploaded files from the temporary directory to the permanent directory
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @return void
	 */
	static public function moveFromTemp($object, &$values, &$old_values, &$related_records, &$cache)
	{
		foreach ($values as $column => $value) {
			if (!$value instanceof fFile) {
				continue;
			}
			
			// If the file is in a temp dir, move it out
			if (strpos($value->getParent()->getPath(), self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR) !== FALSE) {
				$new_filename = str_replace(self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR, '', $value->getPath());
				$new_filename = fFilesystem::makeUniqueName($new_filename);
				$value->rename($new_filename, FALSE);
			}
		}
	}
	
	
	/**
	 * Turns a filename into an fFile or fImage object
	 * 
	 * @internal
	 * 
	 * @param  string $class   The class this value is for
	 * @param  string $column  The column the value is in
	 * @param  mixed  $value   The value
	 * @return mixed  The fFile, fImage or raw value
	 */
	static public function objectify($class, $column, $value)
	{
		if ((!is_string($value) && !is_numeric($value) && !is_object($value)) || !strlen(trim($value))) {
			return $value;
		}
		
		$path = self::$file_upload_columns[$class][$column]->getPath() . $value;
		
		try {
			
			return fFilesystem::createObject($path);
			 
		// If there was some error creating the file, just return the raw value
		} catch (fExpectedException $e) {
			return $value;
		}
	}
	
	
	/**
	 * Performs the upload action for file uploads during fActiveRecord::populate()
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @return void
	 */
	static public function populate($object, &$values, &$old_values, &$related_records, &$cache)
	{
		$class = get_class($object);
		
		foreach (self::$file_upload_columns[$class] as $column => $directory) {
			if (fUpload::check($column, FALSE) || fRequest::check('existing-' . $column) || fRequest::check('delete-' . $column)) {
				$method = 'upload' . fGrammar::camelize($column, TRUE);
				$object->$method();
			}
		}
	}
	
	
	/**
	 * Prepares a file for output into HTML by returning filename or the web server path to the file
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @param  string        $method_name       The method that was called
	 * @param  array         $parameters        The parameters passed to the method
	 * @return void
	 */
	static public function prepare($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		list ($action, $subject) = fORM::parseMethod($method_name);
		$column = fGrammar::underscorize($subject);
		
		if (sizeof($parameters) > 1) {
			throw new fProgrammerException(
				'The column specified, %s, does not accept more than one parameter',
				$column
			);
		}
		
		$translate_to_web_path = (empty($parameters[0])) ? FALSE : TRUE;
		$value                 = $values[$column];
		
		if ($value instanceof fFile) {
			$path = ($translate_to_web_path) ? $value->getPath(TRUE) : $value->getName();
		} else {
			$path = NULL;
		}
		
		return fHTML::prepare($path);
	}
	
	
	/**
	 * Takes a directory and creates a temporary directory inside of it - if the temporary folder exists, all files older than 6 hours will be deleted
	 * 
	 * @param  string $folder  The folder to create a temporary directory inside of
	 * @return fDirectory  The temporary directory for the folder specified
	 */
	static private function prepareTempDir($folder)
	{
		// Let's clean out the upload temp dir
		try {
			$temp_dir = new fDirectory($folder->getPath() . self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR);
		} catch (fValidationException $e) {
			$temp_dir = fDirectory::create($folder->getPath() . self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR);
		}
		
		$temp_files = $temp_dir->scan();
		foreach ($temp_files as $temp_file) {
			if (filemtime($temp_file->getPath()) < strtotime('-6 hours')) {
				unlink($temp_file->getPath());
			}
		}
		
		return $temp_dir;	
	}
	
	
	/**
	 * Handles re-processing an existing image file 
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @param  string        $method_name       The method that was called
	 * @param  array         $parameters        The parameters passed to the method
	 * @return fActiveRecord  The record object, to allow for method chaining
	 */
	static public function process($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		list ($action, $subject) = fORM::parseMethod($method_name);
		
		$column = fGrammar::underscorize($subject);
		$class  = get_class($object);
		
		self::processImage($class, $column, $values[$column]);
		
		return $object;
	}
	
	
	/**
	 * Performs image manipulation on an uploaded/set image
	 * 
	 * @internal
	 * 
	 * @param  string $class   The name of the class we are manipulating the image for
	 * @param  string $column  The column the image is assigned to
	 * @param  fFile  $image   The image object to manipulate
	 * @return void
	 */
	static public function processImage($class, $column, $image)
	{
		// If we don't have an image or we haven't set it up to manipulate images, just exit
		if (!$image instanceof fImage || empty(self::$fimage_method_calls[$class][$column])) {
			return;
		}
		
		$save_changes_called = FALSE;
		
		// Manipulate the image
		if (!empty(self::$fimage_method_calls[$class][$column])) {
			foreach (self::$fimage_method_calls[$class][$column] as $method_call) {
				if ($method_call['method'] == 'saveChanges') {
					$save_changes_called = TRUE;
				}
				$callback   = array($image, $method_call['method']);
				$parameters = $method_call['parameters'];
				if (!is_callable($callback)) {
					throw new fProgrammerException(
						'The fImage method specified, %s, is not a valid method',
						$method_call['method'] . '()'
					);
				}
				call_user_func_array($callback, $parameters);
			}
		}
		
		if (!$save_changes_called) {
			call_user_func($image->saveChanges);
		}
	}
	
	
	/**
	 * Adjusts the fActiveRecord::reflect() signatures of columns that have been configured in this class
	 * 
	 * @internal
	 * 
	 * @param  string  $class                 The class to reflect
	 * @param  array   &$signatures           The associative array of `{method name} => {signature}`
	 * @param  boolean $include_doc_comments  If doc comments should be included with the signature
	 * @return void
	 */
	static public function reflect($class, &$signatures, $include_doc_comments)
	{
		$image_columns = (isset(self::$image_upload_columns[$class])) ? array_keys(self::$image_upload_columns[$class]) : array();
		$file_columns  = (isset(self::$file_upload_columns[$class]))  ? array_keys(self::$file_upload_columns[$class])  : array();
		
		foreach($file_columns as $column) {
			$camelized_column = fGrammar::camelize($column, TRUE);
			
			$noun = 'file';
			if (in_array($column, $image_columns)) {
				$noun = 'image';
			}
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Encodes the filename of " . $column . " for output into an HTML form\n";
				$signature .= " * \n";
				$signature .= " * Only the filename will be returned, any directory will be stripped.\n";
				$signature .= " * \n";
				$signature .= " * @return string  The HTML form-ready value\n";
				$signature .= " */\n";
			}
			$encode_method = 'encode' . $camelized_column;
			$signature .= 'public function ' . $encode_method . '()';
			
			$signatures[$encode_method] = $signature;
			
			if (in_array($column, $image_columns)) {
				$signature = '';
				if ($include_doc_comments) {
					$signature .= "/**\n";
					$signature .= " * Takes the existing image and runs it through the prescribed fImage method calls\n";
					$signature .= " * \n";
					$signature .= " * @return fActiveRecord  The record object, to allow for method chaining\n";
					$signature .= " */\n";
				}
				$process_method = 'process' . $camelized_column;
				$signature .= 'public function ' . $process_method . '()';
				
				$signatures[$process_method] = $signature;
			}
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Prepares the filename of " . $column . " for output into HTML\n";
				$signature .= " * \n";
				$signature .= " * By default only the filename will be returned and any directory will be stripped.\n";
				$signature .= " * The \$include_web_path parameter changes this behaviour.\n";
				$signature .= " * \n";
				$signature .= " * @param  boolean \$include_web_path  If the full web path to the " . $noun . " should be included\n";
				$signature .= " * @return string  The HTML-ready value\n";
				$signature .= " */\n";
			}
			$prepare_method = 'prepare' . $camelized_column;
			$signature .= 'public function ' . $prepare_method . '($include_web_path=FALSE)';
			
			$signatures[$prepare_method] = $signature;
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Takes a file uploaded through an HTML form for " . $column . " and moves it into the specified directory\n";
				$signature .= " * \n";
				$signature .= " * Any columns that were designated as inheriting from this column will get a copy\n";
				$signature .= " * of the uploaded file.\n";
				$signature .= " * \n";
				if ($noun == 'image') {
					$signature .= " * Any fImage calls that were added to the column will be processed on the uploaded image.\n";
					$signature .= " * \n";
				}
				$signature .= " * @return fFile  The uploaded file\n";
				$signature .= " */\n";
			}
			$upload_method = 'upload' . $camelized_column;
			$signature .= 'public function ' . $upload_method . '()';
			
			$signatures[$upload_method] = $signature;
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Takes a file that exists on the filesystem and copies it into the specified directory for " . $column . "\n";
				$signature .= " * \n";
				if ($noun == 'image') {
					$signature .= " * Any fImage calls that were added to the column will be processed on the copied image.\n";
					$signature .= " * \n";
				}
				$signature .= " * @return fActiveRecord  The record object, to allow for method chaining\n";
				$signature .= " */\n";
			}
			$set_method = 'set' . $camelized_column;
			$signature .= 'public function ' . $set_method . '()';
			
			$signatures[$set_method] = $signature;
			
			$signature = '';
			if ($include_doc_comments) {
				$signature .= "/**\n";
				$signature .= " * Returns metadata about " . $column . "\n";
				$signature .= " * \n";
				$signature .= " * @param  string \$element  The element to return. Must be one of: 'type', 'not_null', 'default', 'valid_values', 'max_length', 'feature', 'directory'.\n";
				$signature .= " * @return mixed  The metadata array or a single element\n";
				$signature .= " */\n";
			}
			$inspect_method = 'inspect' . $camelized_column;
			$signature .= 'public function ' . $inspect_method . '($element=NULL)';
			
			$signatures[$inspect_method] = $signature;
		}
	}
	
	
	/**
	 * Creates a copy of an uploaded file in the temp directory for the newly cloned record
	 * 
	 * @internal
	 * 
	 * @param  string $class   The class this value is for
	 * @param  string $column  The column the value is in
	 * @param  mixed  $value   The value
	 * @return mixed  The cloned fFile object
	 */
	static public function replicate($class, $column, $value)
	{
		if (!$value instanceof fFile) {
			return $value;	
		}
		
		// If the file we are replicating is in the temp dir, the copy can live there too
		if (strpos($value->getParent()->getPath(), self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR) !== FALSE) {
			$value = clone $value;	
		
		// Otherwise, the copy of the file must be placed in the temp dir so it is properly cleaned up
		} else {
			$upload_dir = self::$file_upload_columns[$class][$column];
			
			try {
				$temp_dir = new fDirectory($upload_dir->getPath() . self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR);
			} catch (fValidationException $e) {
				$temp_dir = fDirectory::create($upload_dir->getPath() . self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR);
			}
			
			$value = $value->duplicate($temp_dir);	
		}
		
		return $value;
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
		self::$column_inheritence   = array();
		self::$fupload_method_calls = array();
		self::$file_upload_columns  = array();
		self::$fimage_method_calls  = array();
		self::$image_upload_columns = array();
		self::$transaction_level    = 0;
	}
	
	
	/**
	 * Rolls back a transaction, or decreases the level
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	static public function rollback()
	{
		// If the transaction was started by something else, don't even track it
		if (self::$transaction_level == 0) {
			return;
		}
		
		self::$transaction_level--;
		
		if (!self::$transaction_level) {
			fFilesystem::rollback();
		}
	}
	
	
	/**
	 * Copies a file from the filesystem to the file upload directory and sets it as the file for the specified column
	 * 
	 * This method will perform the fImage calls defined for the column.
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @param  string        $method_name       The method that was called
	 * @param  array         $parameters        The parameters passed to the method
	 * @return fActiveRecord  The record object, to allow for method chaining
	 */
	static public function set($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		$class = get_class($object);
		
		list ($action, $subject) = fORM::parseMethod($method_name);
		
		$column   = fGrammar::underscorize($subject);
		$doc_root = realpath($_SERVER['DOCUMENT_ROOT']);
		
		if (!array_key_exists(0, $parameters)) {
			throw new fProgrammerException(
				'The method %s requires exactly one parameter',
				$method_name . '()'
			);
		}
		
		$file_path = $parameters[0];
		
		// Handle objects being passed in
		if ($file_path instanceof fFile) {
			$file_path = $file_path->getPath();	
		} elseif (is_object($file_path) && is_callable(array($file_path, '__toString'))) {
			$file_path = $file_path->__toString();
		} elseif (is_object($file_path)) {
			$file_path = (string) $file_path;
		}
		
		if ($file_path !== NULL && $file_path !== '' && $file_path !== FALSE) {
			if (!$file_path || (!file_exists($file_path) && !file_exists($doc_root . $file_path))) {
				throw new fEnvironmentException(
					'The file specified, %s, does not exist. This may indicate a missing enctype="multipart/form-data" attribute in form tag.',
					$file_path
				);
			}
			
			if (!file_exists($file_path) && file_exists($doc_root . $file_path)) {
				$file_path = $doc_root . $file_path;
			}
			
			if (is_dir($file_path)) {
				throw new fProgrammerException(
					'The file specified, %s, is not a file but a directory',
					$file_path
				);
			}
			
			$upload_dir = self::$file_upload_columns[$class][$column];
			
			try {
				$temp_dir = new fDirectory($upload_dir->getPath() . self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR);
			} catch (fValidationException $e) {
				$temp_dir = fDirectory::create($upload_dir->getPath() . self::TEMP_DIRECTORY . DIRECTORY_SEPARATOR);
			}
			
			$file     = fFilesystem::createObject($file_path);
			$new_file = $file->duplicate($temp_dir);
			
		} else {
			$new_file = NULL;
		}
		
		fActiveRecord::assign($values, $old_values, $column, $new_file);
		
		// Perform column inheritance
		if (!empty(self::$column_inheritence[$class][$column])) {
			foreach (self::$column_inheritence[$class][$column] as $other_column) {
				self::set($object, $values, $old_values, $related_records, $cache, 'set' . fGrammar::camelize($other_column, TRUE), array($file));
			}
		}
		
		if ($new_file) {
			self::processImage($class, $column, $new_file);
		}
		
		return $object;
	}
	
	
	/**
	 * Sets up an fUpload object for a specific column
	 * 
	 * @param  string $class   The class to set up for
	 * @param  string $column  The column to set up for
	 * @return fUpload  The configured fUpload object
	 */
	static private function setUpFUpload($class, $column)
	{
		$upload = new fUpload();
		
		// Set up the fUpload class
		if (!empty(self::$fupload_method_calls[$class][$column])) {
			foreach (self::$fupload_method_calls[$class][$column] as $method_call) {
				if (!is_callable($upload->{$method_call['method']})) {
					throw new fProgrammerException(
						'The fUpload method specified, %s, is not a valid method',
						$method_call['method'] . '()'
					);
				}
				call_user_func_array($upload->{$method_call['method']}, $method_call['parameters']);
			}
		}
		
		return $upload;
	}
	
	
	/**
	 * Uploads a file
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object            The fActiveRecord instance
	 * @param  array         &$values           The current values
	 * @param  array         &$old_values       The old values
	 * @param  array         &$related_records  Any records related to this record
	 * @param  array         &$cache            The cache array for the record
	 * @param  string        $method_name       The method that was called
	 * @param  array         $parameters        The parameters passed to the method
	 * @return fFile  The uploaded file
	 */
	static public function upload($object, &$values, &$old_values, &$related_records, &$cache, $method_name, $parameters)
	{
		$class = get_class($object);
		
		list ($action, $subject) = fORM::parseMethod($method_name);
		$column = fGrammar::underscorize($subject);
		
		$existing_temp_file = FALSE;
		
		
		// Try to upload the file putting it in the temp dir incase there is a validation problem with the record
		try {
			$upload_dir = self::$file_upload_columns[$class][$column];
			$temp_dir   = self::prepareTempDir($upload_dir);
			
			if (!fUpload::check($column)) {
				throw new fExpectedException('Please upload a file');	
			}
			
			$uploader = self::setUpFUpload($class, $column);
			$file     = $uploader->move($temp_dir, $column);
			
		// If there was an eror, check to see if we have an existing file
		} catch (fExpectedException $e) {
			
			// If there is an existing file and none was uploaded, substitute the existing file
			$existing_file = fRequest::get('existing-' . $column);
			$delete_file   = fRequest::get('delete-' . $column, 'boolean');
			$no_upload     = $e->getMessage() == self::compose('Please upload a file');
			
			if ($existing_file && $delete_file && $no_upload) {
				$file = NULL;
				
			} elseif ($existing_file) {
				
				$file_path = $upload_dir->getPath() . $existing_file;
				$file      = fFilesystem::createObject($file_path);
				
				$current_file = $values[$column];
				
				// If the existing file is the same as the current file, we can just exit now
				if ($current_file && $current_file instanceof fFile && $file->getPath() == $current_file->getPath()) {
					return;	
				}
				
				$existing_temp_file = TRUE;
				
			} else {
				$file = NULL;
			}
		}
		
		// Assign the file
		fActiveRecord::assign($values, $old_values, $column, $file);
		
		// Perform the file upload inheritance
		if (!empty(self::$column_inheritence[$class][$column])) {
			foreach (self::$column_inheritence[$class][$column] as $other_column) {
				
				if ($file) {
					
					// Image columns will only inherit if it is an fImage object
					if (!$file instanceof fImage && isset(self::$image_upload_columns[$class]) && array_key_exists($other_column, self::$image_upload_columns[$class])) {
						continue;
					}
					
					$other_upload_dir = self::$file_upload_columns[$class][$other_column];
					$other_temp_dir   = self::prepareTempDir($other_upload_dir);
					
					if ($existing_temp_file) {
						$other_file = fFilesystem::createObject($other_temp_dir->getPath() . $file->getName());
					} else {
						$other_file = $file->duplicate($other_temp_dir, FALSE);
					}
					
				} else {
					$other_file = $file;
				}
				
				fActiveRecord::assign($values, $old_values, $other_column, $other_file);
				
				if (!$existing_temp_file && $other_file) {
					self::processImage($class, $other_column, $other_file);
				}
			}
		}
		
		// Process the file
		if (!$existing_temp_file && $file) {
			self::processImage($class, $column, $file);
		}
		
		return $file;
	}
	
	
	/**
	 * Validates uploaded files to ensure they match all of the criteria defined
	 * 
	 * @internal
	 * 
	 * @param  fActiveRecord $object                The fActiveRecord instance
	 * @param  array         &$values               The current values
	 * @param  array         &$old_values           The old values
	 * @param  array         &$related_records      Any records related to this record
	 * @param  array         &$cache                The cache array for the record
	 * @param  array         &$validation_messages  The existing validation messages
	 * @return void
	 */
	static public function validate($object, &$values, &$old_values, &$related_records, &$cache, &$validation_messages)
	{
		$class = get_class($object);
		
		foreach (self::$file_upload_columns[$class] as $column => $directory) {
			$column_name = fORM::getColumnName($class, $column);
			
			if (isset($validation_messages[$column])) {
				$search_message  = self::compose('%sPlease enter a value', fValidationException::formatField($column_name));
				$replace_message = self::compose('%sPlease upload a file', fValidationException::formatField($column_name));
				$validation_messages[$column] = str_replace($search_message, $replace_message, $validation_messages[$column]);
			}
			
			// Grab the error that occured
			try {
				if (fUpload::check($column)) {
					$uploader = self::setUpFUpload($class, $column);
					$uploader->validate($column);
				}
			} catch (fValidationException $e) {
				if ($e->getMessage() != self::compose('Please upload a file')) {
					$validation_messages[$column] = fValidationException::formatField($column_name) . $e->getMessage();
				}
			}
		}
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return fORMFile
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2008-2011 Will Bond <will@flourishlib.com>
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
