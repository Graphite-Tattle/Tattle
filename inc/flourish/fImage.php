<?php
/**
 * Represents an image on the filesystem, also provides image manipulation functionality
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Will Bond, iMarc LLC [wb-imarc] <will@imarc.net>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fImage
 * 
 * @version    1.0.0b33
 * @changes    1.0.0b33  Fixed a method signature [wb, 2011-08-24]
 * @changes    1.0.0b32  Added a call to clearstatcache() to ::saveChanges() to solve a bug when fFile::output() is called in the same script execution [wb, 2011-05-23]
 * @changes    1.0.0b31  Fixed a bug in using ImageMagick to convert files with a colon in the filename [wb, 2011-03-20]
 * @changes    1.0.0b30  Added a check for systems using the GD extension and no memory limit, plus a check for ImageMagick's convert command failing [wb, 2011-03-20]
 * @changes    1.0.0b29  Added checks for AIX [wb, 2011-01-19]
 * @changes    1.0.0b28  Added the ::rotate() method, added code to try and prevent fatal errors due to hitting the memory limit when using GD [wb, 2010-11-29]
 * @changes    1.0.0b27  Backwards Compatibility Break - changed the parameter order in ::crop() from `$crop_from_x`, `$crop_from_y`, `$new_width`, `$new_height` to `$new_width`, `$new_height`, `$crop_from_x`, `$crop_from_y` - added `$horizontal_position` and `$vertical_position` parameters to ::cropToRatio() [wb-imarc, 2010-11-09]
 * @changes    1.0.0b26  Fixed a bug where processing via ImageMagick was not properly setting the default RGB colorspace [wb, 2010-10-19]
 * @changes    1.0.0b25  Fixed the class to not generate multiple files when saving a JPG from an animated GIF or a TIF with a thumbnail [wb, 2010-09-12]
 * @changes    1.0.0b24  Updated class to use fCore::startErrorCapture() instead of `error_reporting()` [wb, 2010-08-09]
 * @changes    1.0.0b23  Fixed the class to detect when exec() is disabled and the function has a space before or after in the list [wb, 2010-07-21]
 * @changes    1.0.0b22  Fixed ::isImageCompatible() to handle certain JPGs created with Photoshop [wb, 2010-04-03]
 * @changes    1.0.0b21  Fixed ::resize() to allow dimensions to be numeric strings instead of just integers [wb, 2010-04-09]
 * @changes    1.0.0b20  Added ::append() [wb, 2010-03-15]
 * @changes    1.0.0b19  Updated for the new fFile API [wb-imarc, 2010-03-05]
 * @changes    1.0.0b18  Fixed a bug in ::saveChanges() that would incorrectly cause new filenames to be created, added the $overwrite parameter to ::saveChanges(), added the $allow_upsizing parameter to ::resize() [wb, 2010-03-03]
 * @changes    1.0.0b17  Fixed a couple of bug with using ImageMagick on Windows and BSD machines [wb, 2010-03-02]
 * @changes    1.0.0b16  Fixed some bugs with GD not properly handling transparent backgrounds and desaturation of .gif files [wb, 2009-10-27]
 * @changes    1.0.0b15  Added ::getDimensions() [wb, 2009-08-07]
 * @changes    1.0.0b14  Performance updates for checking image type and compatiblity [wb, 2009-07-31]
 * @changes    1.0.0b13  Updated class to work even if the file extension is wrong or not present, ::saveChanges() detects files that aren't writable [wb, 2009-07-29]
 * @changes    1.0.0b12  Fixed a bug where calling ::saveChanges() after unserializing would throw an exception related to the image processor [wb, 2009-05-27]
 * @changes    1.0.0b11  Added a ::crop() method [wb, 2009-05-27]
 * @changes    1.0.0b10  Fixed a bug with GD not saving changes to files ending in .jpeg [wb, 2009-03-18]
 * @changes    1.0.0b9   Changed ::processWithGD() to explicitly free the image resource [wb, 2009-03-18]
 * @changes    1.0.0b8   Updated for new fCore API [wb, 2009-02-16]
 * @changes    1.0.0b7   Changed @ error suppression operator to `error_reporting()` calls [wb, 2009-01-26]
 * @changes    1.0.0b6   Fixed ::cropToRatio() and ::resize() to always return the object even if nothing is to be done [wb, 2009-01-05]
 * @changes    1.0.0b5   Added check to see if exec() is disabled, which causes ImageMagick to not work [wb, 2009-01-03]
 * @changes    1.0.0b4   Fixed ::saveChanges() to not delete the image if no changes have been made [wb, 2008-12-18]
 * @changes    1.0.0b3   Fixed a bug with $jpeg_quality in ::saveChanges() from 1.0.0b2 [wb, 2008-12-16]
 * @changes    1.0.0b2   Changed some int casts to round() to fix ::resize() dimension issues [wb, 2008-12-11]
 * @changes    1.0.0b    The initial implementation [wb, 2007-12-19]
 */
class fImage extends fFile
{
	// The following constants allow for nice looking callbacks to static methods
	const create                  = 'fImage::create';
	const getCompatibleMimetypes  = 'fImage::getCompatibleMimetypes';
	const isImageCompatible       = 'fImage::isImageCompatible';
	const reset                   = 'fImage::reset';
	const setImageMagickDirectory = 'fImage::setImageMagickDirectory';
	const setImageMagickTempDir   = 'fImage::setImageMagickTempDir';
	
	
	/**
	 * If we are using the ImageMagick processor, this stores the path to the binaries
	 * 
	 * @var string
	 */
	static private $imagemagick_dir = NULL;
	
	/**
	 * A custom tmp path to use for ImageMagick
	 * 
	 * @var string
	 */
	static private $imagemagick_temp_dir = NULL;
	
	/**
	 * The processor to use for the image manipulation
	 * 
	 * @var string
	 */
	static private $processor = NULL;
	
	
	/**
	 * Checks to make sure we can get to and execute the ImageMagick convert binary
	 * 
	 * @param  string $path  The path to ImageMagick on the filesystem
	 * @return void
	 */
	static private function checkImageMagickBinary($path)
	{
		// Make sure we can execute the convert binary
		if (self::isSafeModeExecDirRestricted($path)) {
			throw new fEnvironmentException(
				'Safe mode is turned on and the ImageMagick convert binary is not in the directory defined by the safe_mode_exec_dir ini setting or safe_mode_exec_dir is not set - safe_mode_exec_dir is currently %s.',
				ini_get('safe_mode_exec_dir')
			);
		}
		
		if (self::isOpenBaseDirRestricted($path)) {
			exec($path . 'convert -version', $executable);
		} else {
			$executable = is_executable($path . (fCore::checkOS('windows') ? 'convert.exe' : 'convert'));
		}
		
		if (!$executable) {
			throw new fEnvironmentException(
				'The ImageMagick convert binary located in the directory %s does not exist or is not executable',
				$path
			);
		}
	}
	
	
	/**
	 * Creates an image on the filesystem and returns an object representing it
	 * 
	 * This operation will be reverted by a filesystem transaction being rolled
	 * back.
	 * 
	 * @throws fValidationException  When no image was specified or when the image already exists
	 * 
	 * @param  string $file_path  The path to the new image
	 * @param  string $contents   The contents to write to the image
	 * @return fImage
	 */
	static public function create($file_path, $contents)
	{
		if (empty($file_path)) {
			throw new fValidationException('No filename was specified');
		}
		
		if (file_exists($file_path)) {
			throw new fValidationException(
				'The image specified, %s, already exists',
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
		
		$image = new fImage($file_path);
		
		fFilesystem::recordCreate($image);
		
		return $image;
	}
	
	
	/**
	 * Determines what processor to use for image manipulation
	 * 
	 * @return void
	 */
	static private function determineProcessor()
	{
		// Determine what processor to use
		if (self::$processor === NULL) {
			
			// Look for imagemagick first since it can handle more than GD
			try {
				
				// If exec is disabled we can't use imagemagick
				if (in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
					throw new Exception();
				}
				
				if (fCore::checkOS('windows')) {
					
						$win_search = 'dir /B "C:\Program Files\ImageMagick*" 2> NUL';
						exec($win_search, $win_output);
						$win_output = trim(join("\n", $win_output));
						 
						if (!$win_output || stripos($win_output, 'File not found') !== FALSE) {
							throw new Exception();
						}
						 
						$path = 'C:\\Program Files\\' . $win_output . '\\';
						
				} elseif (fCore::checkOS('linux', 'bsd', 'solaris', 'osx', 'aix')) {
					
					$found = FALSE;
					
					if (fCore::checkOS('solaris')) {
						$locations = array(
							'/opt/local/bin/',
							'/opt/bin/',
							'/opt/csw/bin/'
						);
						
					} else {
						$locations = array(
							'/usr/local/bin/',
							'/usr/bin/'
						);
					}
					
					foreach($locations as $location) {
						if (self::isSafeModeExecDirRestricted($location)) {
							continue;
						}
						if (self::isOpenBaseDirRestricted($location)) {
							exec($location . 'convert -version', $output);
							if ($output) {
								$found = TRUE;
								$path  = $location;
								break;
							}
						} elseif (is_executable($location . 'convert')) {
							$found = TRUE;
							$path  = $location;
							break;
						}
					}
					
					// We have no fallback in solaris
					if (!$found && fCore::checkOS('solaris')) {
						throw new Exception();
					}
					
					
					if (!$found && fCore::checkOS('linux', 'freebsd', 'aix')) {
						$nix_search = 'whereis -b convert';
						exec($nix_search, $nix_output);
						$nix_output = trim(str_replace('convert:', '', join("\n", $nix_output)));
						
						if (!$nix_output) {
							throw new Exception();
						}
					
						$path = preg_replace('#^(.*)convert$#i', '\1', $nix_output);
					}
					
					if (!$found && fCore::checkOS('osx', 'netbsd', 'openbsd')) {
						$osx_search = 'whereis convert';
						exec($osx_search, $osx_output);
						$osx_output = trim(join("\n", $osx_output));
						
						if (!$osx_output) {
							throw new Exception();
						}
					
						if (preg_match('#^(.*)convert#i', $osx_output, $matches)) {
							$path = $matches[1];
						}
					}
					
				} else {
					$path = NULL;
				}
				
				self::checkImageMagickBinary($path);
				
				self::$imagemagick_dir = $path;
				self::$processor = 'imagemagick';
				
			} catch (Exception $e) {
				
				// Look for GD last since it does not support tiff files
				if (function_exists('gd_info')) {
					
					self::$processor = 'gd';
				
				} else {
					self::$processor = 'none';
				}
			}
		}
	}
	
	
	/**
	 * Returns an array of acceptable mime types for the processor that was detected
	 * 
	 * @internal
	 * 
	 * @return array  The mime types that the detected image processor can manipulate
	 */
	static public function getCompatibleMimetypes()
	{
		self::determineProcessor();
		
		$mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png');
		
		if (self::$processor == 'imagemagick') {
			$mimetypes[] = 'image/tiff';
		}
		
		return $mimetypes;
	}
	
	
	/**
	 * Gets the dimensions and type of an image stored on the filesystem
	 * 
	 * The `'type'` key will have one of the following values:
	 * 
	 *  - `{null}` (File type is not supported)
	 *  - `'jpg'`
	 *  - `'gif'`
	 *  - `'png'`
	 *  - `'tif'`
	 * 
	 * @throws fValidationException  When the file specified is not an image
	 * 
	 * @param  string $image_path  The path to the image to get stats for
	 * @param  string $element     The element to retrieve: `'type'`, `'width'`, `'height'`
	 * @return mixed  An associative array: `'type' => {mixed}, 'width' => {integer}, 'height' => {integer}`, or the element specified
	 */
	static protected function getInfo($image_path, $element=NULL)
	{
		$extension = strtolower(fFilesystem::getPathInfo($image_path, 'extension'));
		if (!in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'tif', 'tiff'))) {
			$type = self::getImageType($image_path);
			if ($type === NULL) {
				throw new fValidationException(
					'The file specified, %s, does not appear to be an image',
					$image_path
				);
			}	
		}
		
		fCore::startErrorCapture(E_WARNING);
		$image_info = getimagesize($image_path);
		fCore::stopErrorCapture();
		
		if ($image_info == FALSE) {
			throw new fValidationException(
				'The file specified, %s, is not an image',
				$image_path
			);
		}
		
		$valid_elements = array('type', 'width', 'height');
		if ($element !== NULL && !in_array($element, $valid_elements)) {
			throw new fProgrammerException(
				'The element specified, %1$s, is invalid. Must be one of: %2$s.',
				$element,
				join(', ', $valid_elements)
			);
		}
		
		$types = array(IMAGETYPE_GIF     => 'gif',
					   IMAGETYPE_JPEG    => 'jpg',
					   IMAGETYPE_PNG     => 'png',
					   IMAGETYPE_TIFF_II => 'tif',
					   IMAGETYPE_TIFF_MM => 'tif');
		
		$output           = array();
		$output['width']  = $image_info[0];
		$output['height'] = $image_info[1];
		if (isset($types[$image_info[2]])) {
			$output['type'] = $types[$image_info[2]];
		} else {
			$output['type'] = NULL;
		}
		
		if ($element !== NULL) {
			return $output[$element];
		}
		
		return $output;
	}
	
	
	/**
	 * Gets the image type from a file by looking at the file contents
	 * 
	 * @param  string $image  The image path to get the type for
	 * @return string|NULL  The type of the image - `'jpg'`, `'gif'`, `'png'` or `'tif'` - NULL if not one of those  
	 */
	static private function getImageType($image)
	{
		$handle   = fopen($image, 'r');
		$contents = fread($handle, 12);
		fclose($handle);
		
		$_0_8  = substr($contents, 0, 8);
		$_0_4  = substr($contents, 0, 4);
		$_6_4  = substr($contents, 6, 4);
		$_20_4 = substr($contents, 20, 4);
		
		if ($_0_4 == "MM\x00\x2A" || $_0_4 == "II\x2A\x00") {
			return 'tif';
		}
		
		if ($_0_8 == "\x89PNG\x0D\x0A\x1A\x0A") {
			return 'png';
		}
		
		if ($_0_4 == 'GIF8') {
			return 'gif';
		}
		
		if ($_6_4 == 'JFIF' || $_6_4 == 'Exif' || ($_0_4 == "\xFF\xD8\xFF\xED" && $_20_4 == "8BIM")) {
			return 'jpg';
		}
		
		return NULL;
	}
	
	
	/**
	 * Checks to make sure the class can handle the image file specified
	 * 
	 * @internal
	 * 
	 * @throws fValidationException  When the image specified does not exist
	 * 
	 * @param  string $image  The image to check for incompatibility
	 * @return boolean  If the image is compatible with the detected image processor
	 */
	static public function isImageCompatible($image)
	{
		self::determineProcessor();
		
		if (!file_exists($image)) {
			throw new fValidationException(
				'The image specified, %s, does not exist',
				$image
			);
		}
		
		$type = self::getImageType($image);
	
		if ($type === NULL || ($type == 'tif' && self::$processor == 'gd')) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	/**
	 * Checks if the path specified is restricted by open basedir
	 * 
	 * @param  string $path  The path to check
	 * @return boolean  If the path is restricted by the `open_basedir` ini setting
	 */
	static private function isOpenBaseDirRestricted($path)
	{
		if (ini_get('open_basedir')) {
			$open_basedirs = explode((fCore::checkOS('windows')) ? ';' : ':', ini_get('open_basedir'));
			$found = FALSE;
			
			foreach ($open_basedirs as $open_basedir) {
				if (strpos($path, $open_basedir) === 0) {
					$found = TRUE;
				}
			}
			
			if (!$found) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	
	/**
	 * Checks if the path specified is restricted by the safe mode exec dir restriction
	 * 
	 * @param  string $path  The path to check
	 * @return boolean  If the path is restricted by the `safe_mode_exec_dir` ini setting
	 */
	static private function isSafeModeExecDirRestricted($path)
	{
		if (!in_array(strtolower(ini_get('safe_mode')), array('0', '', 'off'))) {
			$exec_dir = ini_get('safe_mode_exec_dir');
			if (!$exec_dir || stripos($path, $exec_dir) === FALSE) {
				return TRUE;
			}
		}
		return FALSE;
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
		self::$imagemagick_dir      = NULL;
		self::$imagemagick_temp_dir = NULL;
		self::$processor            = NULL;	
	}
	
	
	/**
	 * Sets the directory the ImageMagick binary is installed in and tells the class to use ImageMagick even if GD is installed
	 * 
	 * @param  string $directory  The directory ImageMagick is installed in
	 * @return void
	 */
	static public function setImageMagickDirectory($directory)
	{
		$directory = fDirectory::makeCanonical($directory);
		
		self::checkImageMagickBinary($directory);
		
		self::$imagemagick_dir = $directory;
		self::$processor = 'imagemagick';
	}
	
	
	/**
	 * Sets a custom directory to use for the ImageMagick temporary files
	 * 
	 * @param  string $temp_dir  The directory to use for the ImageMagick temp dir
	 * @return void
	 */
	static public function setImageMagickTempDir($temp_dir)
	{
		$temp_dir = new fDirectory($temp_dir);
		if (!$temp_dir->isWritable()) {
			throw new fEnvironmentException(
				'The ImageMagick temp directory specified, %s, does not appear to be writable',
				$temp_dir->getPath()
			);
		}
		self::$imagemagick_temp_dir = $temp_dir->getPath();
	}
	
	
	/**
	 * The modifications to perform on the image when it is saved
	 * 
	 * @var array
	 */
	private $pending_modifications = array();
	
	
	/**
	 * Creates an object to represent an image on the filesystem
	 * 
	 * @throws fValidationException  When no image was specified, when the image does not exist or when the path specified is not an image
	 * 
	 * @param  string  $file_path    The path to the image
	 * @param  boolean $skip_checks  If file checks should be skipped, which improves performance, but may cause undefined behavior - only skip these if they are duplicated elsewhere
	 * @return fImage
	 */
	public function __construct($file_path, $skip_checks=FALSE)
	{
		self::determineProcessor();
		
		parent::__construct($file_path, $skip_checks);
		
		if (!self::isImageCompatible($file_path)) {
			$valid_image_types = array('GIF', 'JPG', 'PNG');
			if (self::$processor == 'imagemagick') {
				$valid_image_types[] = 'TIF';
			}
			throw new fValidationException(
				'The image specified, %1$s, is not a valid %2$s file',
				$file_path,
				fGrammar::joinArray($valid_image_types, 'or')
			);
		}
	}
	
	
	/**
	 * Prevents a programmer from trying to append an image
	 * 
	 * @param  mixed $data  The data to append to the image
	 * @return void
	 */
	public function append($data)
	{
		throw new fProgrammerException('It is not possible to append an image');
	}
	
	
	/**
	 * Crops the image by the exact pixel dimensions specified
	 * 
	 * The crop does not occur until ::saveChanges() is called.
	 * 
	 * @param  numeric        $new_width    The width in pixels to crop the image to
	 * @param  numeric        $new_height   The height in pixels to crop the image to
	 * @param  numeric|string $crop_from_x  The number of pixels from the left of the image to start the crop from, or a horizontal position of `'left'`, `'center'` or `'right'`
	 * @param  numeric|string $crop_from_y  The number of pixels from the top of the image to start the crop from, or a vertical position of `'top'`, `'center'` or `'bottom'`
	 * @return fImage  The image object, to allow for method chaining
	 */
	public function crop($new_width, $new_height, $crop_from_x, $crop_from_y)
	{
		$this->tossIfDeleted();
		
		// Get the original dimensions for our parameter checking
		$dim = $this->getCurrentDimensions();
		$orig_width  = $dim['width'];
		$orig_height = $dim['height'];
		
		if (is_string($crop_from_x) && !is_numeric($crop_from_x)) {
			switch (strtolower($crop_from_x)) {
				case 'left':
					$crop_from_x = 0;
					break;
				case 'center':
					$crop_from_x = floor(max($orig_width-$new_width, 0)/2);
					break;
				case 'right':
					$crop_from_x = max($orig_width-$new_width, 0);
					break;
				default:
					throw new fProgrammerException(
						'The crop-from x specified, %1$s, is not a valid horizontal position. Must be one of: %2$s.',
						$crop_from_x,
						array('left', 'center', 'right')
					);
			}
		}
		
		if (is_string($crop_from_y) && !is_numeric($crop_from_y)) {
			switch (strtolower($crop_from_y)) {
				case 'top':
					$crop_from_y = 0;
					break;
				case 'center':
					$crop_from_y = floor(max($orig_height-$new_height, 0)/2);
					break;
				case 'bottom':
					$crop_from_y = max($orig_height-$new_height, 0);
					break;
				default:
					throw new fProgrammerException(
						'The crop-from y specified, %1$s, is not a valid vertical position. Must be one of: %2$s.',
						$crop_from_y,
						array('top', 'center', 'bottom')
					);
			}
		}
		
		// Make sure the user input is valid
		if (!is_numeric($crop_from_x) || $crop_from_x < 0 || $crop_from_x > $orig_width - 1) {
			throw new fProgrammerException(
				'The crop-from x specified, %s, is not a number, is less than zero, or would result in a zero-width image',
				$crop_from_x
			);
		}
		if (!is_numeric($crop_from_y) || $crop_from_y < 0 || $crop_from_y > $orig_height - 1) {
			throw new fProgrammerException(
				'The crop-from y specified, %s, is not a number, is less than zero, or would result in a zero-height image',
				$crop_from_y
			);
		}
		
		if (!is_numeric($new_width) || $new_width <= 0 || $crop_from_x + $new_width > $orig_width) {
			throw new fProgrammerException(
				'The new width specified, %1$s, is not a number, is less than or equal to zero, or is larger than can be cropped with the specified crop-from x of %2$s',
				$new_width,
				$crop_from_x
			);
		}
		if (!is_numeric($new_height) || $new_height <= 0 || $crop_from_y + $new_height > $orig_height) {
			throw new fProgrammerException(
				'The new height specified, %1$s, is not a number, is less than or equal to zero, or is larger than can be cropped with the specified crop-from y of %2$s',
				$new_height,
				$crop_from_y
			);
		}
		
		// If nothing changed, don't even record the modification
		if ($orig_width == $new_width && $orig_height == $new_height) {
			return $this;
		}
		
		// Record what we are supposed to do
		$this->pending_modifications[] = array(
			'operation'  => 'crop',
			'start_x'    => $crop_from_x,
			'start_y'    => $crop_from_y,
			'width'      => $new_width,
			'height'     => $new_height,
			'old_width'  => $orig_width,
			'old_height' => $orig_height
		);
		
		return $this;
	}
	
		
	/**
	 * Crops the biggest area possible from the center of the image that matches the ratio provided
	 * 
	 * The crop does not occur until ::saveChanges() is called.
	 * 
	 * @param  numeric $ratio_width          The width ratio to crop the image to
	 * @param  numeric $ratio_height         The height ratio to crop the image to
	 * @param  string  $horizontal_position  A horizontal position of `'left'`, `'center'` or `'right'`
	 * @param  string  $vertical_position    A vertical position of `'top'`, `'center'` or `'bottom'`
	 * @return fImage  The image object, to allow for method chaining
	 */
	public function cropToRatio($ratio_width, $ratio_height, $horizontal_position='center', $vertical_position='center')
	{
		$this->tossIfDeleted();
		
		// Make sure the user input is valid
		if ((!is_numeric($ratio_width) && $ratio_width !== NULL) || $ratio_width < 0) {
			throw new fProgrammerException(
				'The ratio width specified, %s, is not a number or is less than or equal to zero',
				$ratio_width
			);
		}
		if ((!is_numeric($ratio_height) && $ratio_height !== NULL) || $ratio_height < 0) {
			throw new fProgrammerException(
				'The ratio height specified, %s, is not a number or is less than or equal to zero',
				$ratio_height
			);
		}
		
		
		// Make sure 
		$valid_horizontal_positions = array('left', 'center', 'right');
		if (!in_array(strtolower($horizontal_position), $valid_horizontal_positions)) {
			throw new fProgrammerException(
				'The horizontal position specified, %1$s, is not valid. Must be one of: %2$s.',
				$horizontal_position,
				$valid_horizontal_positions
			);
		}
		
		$valid_vertical_positions = array('top', 'center', 'bottom');
		if (!in_array(strtolower($vertical_position), $valid_vertical_positions)) {
			throw new fProgrammerException(
				'The vertical position specified, %1$s, is not valid. Must be one of: %2$s.',
				$vertical_position,
				$valid_vertical_positions
			);
		}
		
		// Get the new dimensions
		$dim = $this->getCurrentDimensions();
		$orig_width  = $dim['width'];
		$orig_height = $dim['height'];
		
		$orig_ratio = $orig_width / $orig_height;
		$new_ratio  = $ratio_width / $ratio_height;
			
		if ($orig_ratio > $new_ratio) {
			$new_height = $orig_height;
			$new_width  = round($new_ratio * $new_height);
		} else {
			$new_width  = $orig_width;
			$new_height = round($new_width / $new_ratio);
		}
			
		return $this->crop($new_width, $new_height, $horizontal_position, $vertical_position);
	}
	
	
	/**
	 * Converts the image to grayscale
	 * 
	 * Desaturation does not occur until ::saveChanges() is called.
	 * 
	 * @return fImage  The image object, to allow for method chaining
	 */
	public function desaturate()
	{
		$this->tossIfDeleted();
		
		$dim = $this->getCurrentDimensions();
		
		// Record what we are supposed to do
		$this->pending_modifications[] = array(
			'operation'  => 'desaturate',
			'width'      => $dim['width'],
			'height'     => $dim['height'],
			'old_width'  => $dim['width'],
			'old_height' => $dim['height']
		);
		
		return $this;
	}
	
	
	/**
	 * Gets the dimensions of the image as of the last modification
	 * 
	 * @return array  An associative array: `'width' => {integer}, 'height' => {integer}`
	 */
	private function getCurrentDimensions()
	{
		if (empty($this->pending_modifications)) {
			$output = self::getInfo($this->file);
			unset($output['type']);
		
		} else {
			$last_modification = $this->pending_modifications[sizeof($this->pending_modifications)-1];
			$output['width']  = $last_modification['width'];
			$output['height'] = $last_modification['height'];
		}
		
		return $output;
	}
	
	
	/**
	 * Returns the width and height of the image as a two element array
	 * 
	 * @return array  In the format `0 => (integer) {width}, 1 => (integer) {height}`
	 */
	public function getDimensions()
	{
		$info = self::getInfo($this->file);
		return array($info['width'], $info['height']);
	}
	
	
	/**
	 * Returns the height of the image
	 * 
	 * @return integer  The height of the image in pixels
	 */
	public function getHeight()
	{
		return self::getInfo($this->file, 'height');
	}
	
	
	/**
	 * Returns the type of the image
	 * 
	 * @return string  The type of the image: `'jpg'`, `'gif'`, `'png'`, `'tif'`
	 */
	public function getType()
	{
		return self::getImageType($this->file);
	}
	
	
	/**
	 * Returns the width of the image
	 * 
	 * @return integer  The width of the image in pixels
	 */
	public function getWidth()
	{
		return self::getInfo($this->file, 'width');
	}
	
	
	/**
	 * Checks if the current image is an animated gif
	 * 
	 * @return boolean  If the image is an animated gif
	 */
	private function isAnimatedGif()
	{
		$type = self::getImageType($this->file);
		if ($type == 'gif') {
			if (preg_match('#\x00\x21\xF9\x04.{4}\x00\x2C#s', file_get_contents($this->file))) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	
	/**
	 * Processes the current image using GD
	 * 
	 * @param  string  $output_file   The file to save the image to
	 * @param  integer $jpeg_quality  The JPEG quality to use
	 * @return void
	 */
	private function processWithGD($output_file, $jpeg_quality)
	{
		$type       = self::getImageType($this->file);
		$save_alpha = FALSE;
		
		$path_info = fFilesystem::getPathInfo($output_file);
		$new_type  = $path_info['extension'];
		$new_type  = ($type == 'jpeg') ? 'jpg' : $type;
		
		if (!in_array($new_type, array('gif', 'jpg', 'png'))) {
			$new_type = $type;	
		}
		
		if (ini_get('memory_limit') != '-1') {
			// We will estimate memory usage at 3MB if we can't actually check it
			$beginning_memory_usage = 3145728;
			if (function_exists('memory_get_usage')) {
				$beginning_memory_usage = memory_get_usage();
			}
			$memory_limit_bytes = fFilesystem::convertToBytes(ini_get('memory_limit'));
			
			// Estimate the memory usage and throw an exception if we will run out
			$load_byte_usage = $this->pending_modifications[0]['old_width'] * $this->pending_modifications[0]['old_height'] * 4;
			if ($load_byte_usage + $beginning_memory_usage > $memory_limit_bytes) {
				throw new fEnvironmentException(
					'The predicted memory usage to complete the image modifications using the GD extension, %1$s, will most likely exceed the memory limit of %2$s',
					$load_byte_usage + $beginning_memory_usage,
					$memory_limit_bytes
				);
			}
		}
		
		switch ($type) {
			case 'gif':
				$gd_res = imagecreatefromgif($this->file);
				$save_alpha = TRUE;
				break;
			case 'jpg':
				$gd_res = imagecreatefromjpeg($this->file);
				break;
			case 'png':
				$gd_res = imagecreatefrompng($this->file);
				$save_alpha = TRUE;
				break;
		}
		
		
		foreach ($this->pending_modifications as $num => $mod) {
			
			if (ini_get('memory_limit') != '-1') {
				$old_byte_usage = $this->pending_modifications[0]['old_width'] * $this->pending_modifications[0]['old_height'] * 4;
				$new_byte_usage = $this->pending_modifications[0]['width'] * $this->pending_modifications[0]['height'] * 4;
				if ($old_byte_usage + $new_byte_usage + $beginning_memory_usage > $memory_limit_bytes) {
					throw new fEnvironmentException(
						'The predicted memory usage to complete the image modifications using the GD extension, %1$s, will most likely exceed the memory limit of %2$s',
						$old_byte_usage + $new_byte_usage + $beginning_memory_usage,
						$memory_limit_bytes
					);
				}
			}
			
			$new_gd_res = imagecreatetruecolor($mod['width'], $mod['height']);
			if ($save_alpha) {
				imagealphablending($new_gd_res, FALSE);
				imagesavealpha($new_gd_res, TRUE);
				if ($new_type == 'gif') {
					$transparent = imagecolorallocatealpha($new_gd_res, 255, 255, 255, 127);
					imagefilledrectangle($new_gd_res, 0, 0, $mod['width'], $mod['height'], $transparent);
					imagecolortransparent($new_gd_res, $transparent);
				}
			}
			
			// Perform the resize operation
			if ($mod['operation'] == 'resize') {
				
				imagecopyresampled($new_gd_res,       $gd_res,
								   0,                 0,
								   0,                 0,
								   $mod['width'],     $mod['height'],
								   $mod['old_width'], $mod['old_height']);
				
			// Perform the crop operation
			} elseif ($mod['operation'] == 'crop') {
			
				imagecopyresampled($new_gd_res,       $gd_res,
								   0,                 0,
								   $mod['start_x'],   $mod['start_y'],
								   $mod['width'],     $mod['height'],
								   $mod['width'],     $mod['height']);
				
			// Perform the desaturate operation
			} elseif ($mod['operation'] == 'desaturate') {
			
				// Create a palette of grays
				$grays = array();
				for ($i=0; $i < 256; $i++) {
					$grays[$i] = imagecolorallocate($new_gd_res, $i, $i, $i);
				}
				$transparent = imagecolorallocatealpha($new_gd_res, 255, 255, 255, 127);
				
				// Loop through every pixel and convert the rgb values to grays
				for ($x=0; $x < $mod['width']; $x++) {
					for ($y=0; $y < $mod['height']; $y++) {
						
						$color = imagecolorat($gd_res, $x, $y);
						if ($type != 'gif') {
							$red   = ($color >> 16) & 0xFF;
							$green = ($color >> 8) & 0xFF;
							$blue  = $color & 0xFF;
							if ($save_alpha) {
								$alpha = ($color >> 24) & 0x7F;
							}
						} else {
							$color_info = imagecolorsforindex($gd_res, $color);
							$red   = $color_info['red'];
							$green = $color_info['green'];
							$blue  = $color_info['blue'];
							$alpha = $color_info['alpha'];
						}
						
						if (!$save_alpha || $alpha != 127) {
							
							// Get the appropriate gray (http://en.wikipedia.org/wiki/YIQ)
							$yiq = round(($red * 0.299) + ($green * 0.587) + ($blue * 0.114));
							
							if (!$save_alpha || $alpha == 0) {
								$new_color = $grays[$yiq];	
							} else {
								$new_color = imagecolorallocatealpha($new_gd_res, $yiq, $yiq, $yiq, $alpha);	
							}
							
						} else {
							$new_color = $transparent;
						}
						
						imagesetpixel($new_gd_res, $x, $y, $new_color);
					}
				}
			
			// Perform the rotate operation
			} elseif ($mod['operation'] == 'rotate') {
				// The imagerotate() function is only available if the PHP-bundled
				// version of GD is used, which is not always the case (e.g. debian/ubuntu)
				if (function_exists('imagerotate')) {
					// For some reason imagerotate() seem to rotate counter-clockwise
					if ($mod['degrees'] == 90) {
						$mod['degrees'] = 270;
					} elseif ($mod['degrees'] == 270) {
						$mod['degrees'] = 90;
					}
					
					// If the source image is not true color, we need to convert
					// to a true color image first, otherwise imagerotate() fails
					// and returns false, causing no image to be saved
					if (imagecolorstotal($gd_res)) {
						imagecopy($new_gd_res, $gd_res, 0, 0, 0, 0, $mod['width'], $mod['height']);
						imagedestroy($gd_res);
						$gd_res = $new_gd_res;
						unset($new_gd_res);
					}
					
					$new_gd_res = imagerotate($gd_res, $mod['degrees'], -1);
					
					// If you don't set the alpha mode for PNG, images that
					// contain transparency and are rotated will be distored
					// in odd ways
					if ($new_type == 'png') {
						imagealphablending($new_gd_res, false);
						imagesavealpha($new_gd_res, true);
					}
					
				} else {
					switch ($mod['degrees']) {
						case 90: 
				            for ($x=0; $x < $mod['width']; $x++) { 
				                for ($y=0; $y < $mod['height']; $y++) { 
				                    imagecopy($new_gd_res, $gd_res, $mod['height'] - $y - 1, $x, $x, $y, 1, 1);
								}
							}
				            break; 

				        case 180:
				        	// Rather than copying one pixel at a time, like with 90
				        	// and 270 degrees, for 180 degrees we can copy one rpw
				        	// at a time for better performance
				            for ($x=0; $x < $mod['width']; $x++) {
				                imagecopy($new_gd_res, $gd_res, $mod['width'] - $x - 1, 0, $x, 0, 1, $mod['height']);
							}
				            $row = imagecreatetruecolor($mod['width'], 1);
				            for ($y=0; $y < $mod['height']/2; $y++) {
				                imagecopy($row, $new_gd_res, 0, 0, 0, $mod['height'] - $y - 1, $mod['width'], 1);
				                imagecopy($new_gd_res, $new_gd_res, 0, $mod['height'] - $y - 1, 0, $y, $mod['width'], 1);
				                imagecopy($new_gd_res, $row, 0, $y, 0, 0, $mod['width'], 1);
				            }
				            imagedestroy($row);
				            break; 

				        case 270: 
				            for ($x=0; $x < $mod['width']; $x++) { 
				                for ($y=0; $y < $mod['height']; $y++) {
				                    imagecopy($new_gd_res, $gd_res, $y, $mod['width'] - $x - 1, $x, $y, 1, 1);
								}
							} 
				            break;
					}
				}
			}
			
			imagedestroy($gd_res);
				
			$gd_res = $new_gd_res;	
		}
		
		// Save the file
		switch ($new_type) {
			case 'gif':
				imagetruecolortopalette($gd_res, TRUE, 256);
				imagegif($gd_res, $output_file);
				break;
			case 'jpg':
				imagejpeg($gd_res, $output_file, $jpeg_quality);
				break;
			case 'png':
				imagepng($gd_res, $output_file);
				break;
		}
		
		imagedestroy($gd_res);
	}
	
	
	/**
	 * Processes the current image using ImageMagick
	 * 
	 * @param  string  $output_file   The file to save the image to
	 * @param  integer $jpeg_quality  The JPEG quality to use
	 * @return void
	 */
	private function processWithImageMagick($output_file, $jpeg_quality)
	{
		$type = self::getImageType($this->file);
		if (fCore::checkOS('windows')) {
			$command_line  = str_replace(' ', '" "', self::$imagemagick_dir . 'convert.exe');
		} else {
			$command_line  = escapeshellarg(self::$imagemagick_dir . 'convert');
		}
		
		if (self::$imagemagick_temp_dir) {
			$command_line .= ' -set registry:temporary-path ' . escapeshellarg(self::$imagemagick_temp_dir) . ' ';
		}
		
		// Determining in what format the file is going to be saved
		$path_info = fFilesystem::getPathInfo($output_file);
		$new_type = $path_info['extension'];
		$new_type = ($new_type == 'jpeg') ? 'jpg' : $new_type;
		
		if (!in_array($new_type, array('gif', 'jpg', 'png'))) {
			$new_type = $type;	
		}
		
		$file = $this->file;
		if ($type != 'gif' || $new_type != 'gif') {
			$file .= '[0]';
		}
		
		$command_line .= ' ' . escapeshellarg(str_replace('tif', 'tiff', $type) . ':' . $file) . ' ';
		
		// Animated gifs need to be coalesced
		if ($this->isAnimatedGif()) {
			$command_line .= ' -coalesce ';
		}
		
		// TIFF files should be set to a depth of 8
		if ($type == 'tif') {
			$command_line .= ' -depth 8 ';
		}
		
		foreach ($this->pending_modifications as $mod) {
			
			// Perform the resize operation
			if ($mod['operation'] == 'resize') {
				$command_line .= ' -resize "' . $mod['width'] . 'x' . $mod['height'];
				if ($mod['old_width'] < $mod['width'] || $mod['old_height'] < $mod['height']) {
					$command_line .= '<';
				}
				$command_line .= '" ';
				
			// Perform the crop operation
			} elseif ($mod['operation'] == 'crop') {
				$command_line .= ' -crop ' . $mod['width'] . 'x' . $mod['height'];
				$command_line .= '+' . $mod['start_x'] . '+' . $mod['start_y'];
				$command_line .= ' -repage ' . $mod['width'] . 'x' . $mod['height'] . '+0+0 ';
				
			// Perform the desaturate operation
			} elseif ($mod['operation'] == 'desaturate') {
				$command_line .= ' -colorspace GRAY ';
			
			// Perform the rotate operation
			} elseif ($mod['operation'] == 'rotate') {
				$command_line .= ' -rotate ' . $mod['degrees'] . ' ';
			}
		}
		
		// Default to the RGB colorspace
		if (strpos($command_line, ' -colorspace ') === FALSE) {
			$command_line .= ' -colorspace RGB ';
		}
		
		if ($new_type == 'jpg') {
			$command_line .= ' -compress JPEG -quality ' . $jpeg_quality . ' ';
		}
		
		$command_line .= ' ' . escapeshellarg($new_type . ':' . $output_file) . ' 2>&1';
		
		exec($command_line, $output, $return_value);

		if ($return_value !== 0) {
			throw new fEnvironmentException(
				"An error occurred running the command, %1\$s, to modify the image. The error output was:\n%2\$s",
				$command_line,
				join("\n", $output)
			);
		}
	}
	
	
	/**
	 * Sets the image to be resized proportionally to a specific size canvas
	 * 
	 * Will only size down an image. This method uses resampling to ensure the
	 * resized image is smooth in appearance. Resizing does not occur until
	 * ::saveChanges() is called.
	 * 
	 * @param  integer $canvas_width    The width of the canvas to fit the image on, `0` for no constraint
	 * @param  integer $canvas_height   The height of the canvas to fit the image on, `0` for no constraint
	 * @param  boolean $allow_upsizing  If the image is smaller than the desired canvas, the image will be increased in size
	 * @return fImage  The image object, to allow for method chaining
	 */
	public function resize($canvas_width, $canvas_height, $allow_upsizing=FALSE)
	{
		$this->tossIfDeleted();
		
		// Make sure the user input is valid
		if ((!is_numeric($canvas_width) && $canvas_width !== NULL) || $canvas_width < 0) {
			throw new fProgrammerException(
				'The canvas width specified, %s, is not an integer or is less than zero',
				$canvas_width
			);
		}
		if ((!is_numeric($canvas_height) && $canvas_height !== NULL) || $canvas_height < 0) {
			throw new fProgrammerException(
				'The canvas height specified, %s is not an integer or is less than zero',
				$canvas_height
			);
		}
		if ($canvas_width == 0 && $canvas_height == 0) {
			throw new fProgrammerException(
				'The canvas width and canvas height are both zero, so no resizing will occur'
			);
		}
		
		// Calculate what the new dimensions will be
		$dim = $this->getCurrentDimensions();
		$orig_width  = $dim['width'];
		$orig_height = $dim['height'];
		
		if ($canvas_width == 0) {
			$new_height = $canvas_height;
			$new_width  = round(($new_height/$orig_height) * $orig_width);
		
		} elseif ($canvas_height == 0) {
			$new_width  = $canvas_width;
			$new_height = round(($new_width/$orig_width) * $orig_height);
		
		} else {
			$orig_ratio   = $orig_width/$orig_height;
			$canvas_ratio = $canvas_width/$canvas_height;
			
			if ($canvas_ratio > $orig_ratio) {
				$new_height = $canvas_height;
				$new_width  = round($orig_ratio * $new_height);
			} else {
				$new_width  = $canvas_width;
				$new_height = round($new_width / $orig_ratio);
			}
		}
		
		// If the size did not change, don't even record the modification
		$same_size   = $orig_width == $new_width || $orig_height == $new_height;
		$wont_change = ($orig_width < $new_width || $orig_height < $new_height) && !$allow_upsizing;
		if ($same_size || $wont_change) {
			return $this;
		}
		
		// Record what we are supposed to do
		$this->pending_modifications[] = array(
			'operation'  => 'resize',
			'width'      => $new_width,
			'height'     => $new_height,
			'old_width'  => $orig_width,
			'old_height' => $orig_height
		);
		
		return $this;
	}
	
	
	/**
	 * Sets the image to be rotated
	 * 
	 * Rotation does not occur until ::saveChanges() is called.
	 * 
	 * @param  integer $degrees   The number of degrees to rotate - 90, 180, or 270
	 */
	public function rotate($degrees)
	{
		$this->tossIfDeleted();
		
		// Make sure the user input is valid
		$valid_degrees = array(90, 180, 270);
		if (!in_array($degrees, $valid_degrees)) {
			throw new fProgrammerException(
				'The number of degrees specified, %1$s, is not valid. Must be one of: %2$s.',
				$degrees,
				$valid_degrees
			);
		}
		
		// Calculate what the new dimensions will be
		$dim = $this->getCurrentDimensions();
		$orig_width  = $dim['width'];
		$orig_height = $dim['height'];
		
		if ($degrees == 180) {
			$new_width  = $dim['width'];
			$new_height = $dim['height'];
		} else {
			$new_width  = $dim['height'];
			$new_height = $dim['width'];
		}
		
		// Record what we are supposed to do
		$this->pending_modifications[] = array(
			'operation'  => 'rotate',
			'degrees'    => $degrees,
			'width'      => $new_width,
			'height'     => $new_height,
			'old_width'  => $orig_width,
			'old_height' => $orig_height
		);
		
		return $this;
	}
	
	
	/**
	 * Saves any changes to the image
	 * 
	 * If the file type is different than the current one, removes the current
	 * file once the new one is created.
	 * 
	 * This operation will be reverted by a filesystem transaction being rolled
	 * back. If a transaction is in progress and the new image type causes a
	 * new file to be created, the old file will not be deleted until the
	 * transaction is committed.
	 * 
	 * @param  string  $new_image_type  The new file format for the image: 'NULL` (no change), `'jpg'`, `'gif'`, `'png'`
	 * @param  integer $jpeg_quality    The quality setting to use for JPEG images - this may be ommitted
	 * @param  boolean $overwrite       If an existing file with the same name and extension should be overwritten
	 * @param  string  |$new_image_type
	 * @param  boolean |$overwrite
	 * @return fImage  The image object, to allow for method chaining
	 */
	public function saveChanges($new_image_type=NULL, $jpeg_quality=90, $overwrite=FALSE)
	{
		// This allows ommitting the $jpeg_quality parameter, which is very useful for non-jpegs
		$args = func_get_args();
		if (count($args) == 2 && is_bool($args[1])) {
			$overwrite    = $args[1];
			$jpeg_quality = 90;
		}
		
		$this->tossIfDeleted();
		self::determineProcessor();
		
		if (self::$processor == 'none') {
			throw new fEnvironmentException(
				"The changes to the image can't be saved because neither the GD extension or ImageMagick appears to be installed on the server"
			);
		}
		
		$type = self::getImageType($this->file);
		if ($type == 'tif' && self::$processor == 'gd') {
			throw new fEnvironmentException(
				'The image specified, %s, is a TIFF file and the GD extension can not handle TIFF files. Please install ImageMagick if you wish to manipulate TIFF files.',
				$this->file
			);
		}
		
		$valid_image_types = array('jpg', 'gif', 'png');
		if ($new_image_type !== NULL && !in_array($new_image_type, $valid_image_types)) {
			throw new fProgrammerException(
				'The new image type specified, %1$s, is invalid. Must be one of: %2$s.',
				$new_image_type,
				join(', ', $valid_image_types)
			);
		}
		
		if (is_numeric($jpeg_quality)) {
			$jpeg_quality = (int) round($jpeg_quality);
		}
		
		if (!is_integer($jpeg_quality) || $jpeg_quality < 1 || $jpeg_quality > 100) {
			throw new fProgrammerException(
				'The JPEG quality specified, %1$s, is either not an integer, less than %2$s or greater than %3$s.',
				$jpeg_quality,
				1,
				100
			);	
		}
		
		if ($new_image_type && fFilesystem::getPathInfo($this->file, 'extension') != $new_image_type) {
			if ($overwrite) {
				$path_info   = fFilesystem::getPathInfo($this->file);
				$output_file = $path_info['dirname'] . $path_info['filename'] . '.' . $new_image_type;
			} else {
				$output_file = fFilesystem::makeUniqueName($this->file, $new_image_type);
			}
			
			if (file_exists($output_file)) {
				if (!is_writable($output_file)) {
					throw new fEnvironmentException(
						'Changes to the image can not be saved because the file, %s, is not writable',
						$output_file
					);
				}
				
			} else {
				$output_dir = dirname($output_file);
				if (!is_writable($output_dir)) {
					throw new fEnvironmentException(
						'Changes to the image can not be saved because the directory to save the new file, %s, is not writable',
						$output_dir
					);
				}
			}
			
		} else {
			$output_file = $this->file;
			if (!is_writable($output_file)) {
				throw new fEnvironmentException(
					'Changes to the image can not be saved because the file, %s, is not writable',
					$output_file
				);	
			}
		}
		
		// If we don't have any changes and no name change, just exit
		if (!$this->pending_modifications && $output_file == $this->file) {
			return $this;
		}
		
		// Wrap changes to the image into the filesystem transaction
		if ($output_file == $this->file && fFilesystem::isInsideTransaction()) {
			fFilesystem::recordWrite($this);
		}
		
		if (self::$processor == 'gd') {
			$this->processWithGD($output_file, $jpeg_quality);
		} elseif (self::$processor == 'imagemagick') {
			$this->processWithImageMagick($output_file, $jpeg_quality);
		}
		
		$old_file = $this->file;
		fFilesystem::updateFilenameMap($this->file, $output_file);
		
		// If we created a new image, delete the old one
		if ($output_file != $old_file) {
			$old_image = new fImage($old_file);
			$old_image->delete();
		}
		
		$this->pending_modifications = array();
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