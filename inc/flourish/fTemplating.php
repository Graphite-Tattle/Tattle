<?php
/**
 * Allows for quick and flexible HTML templating
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond, others
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @author     Matt Nowack [mn] <mdnowack@gmail.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fTemplating
 * 
 * @version    1.0.0b23
 * @changes    1.0.0b23  Added a default `$name` for ::retrieve() to mirror ::attach() [wb, 2011-08-31]
 * @changes    1.0.0b22  Backwards Compatibility Break - removed the static method ::create(), added the static method ::attach() to fill its place [wb, 2011-08-31]
 * @changes    1.0.0b21  Fixed a bug in ::enableMinification() where the minification cache directory was sometimes not properly converted to a web path [wb, 2011-08-31]
 * @changes    1.0.0b20  Fixed a bug in CSS minification that would reduce multiple zeros that are part of a hex color code, fixed minification of `+ ++` and similar constructs in JS [wb, 2011-08-31]
 * @changes    1.0.0b19  Corrected a bug in ::enablePHPShortTags() that would prevent proper translation inside of HTML tag attributes [wb, 2011-01-09]
 * @changes    1.0.0b18  Fixed a bug with CSS minification and black hex codes [wb, 2010-10-10]
 * @changes    1.0.0b17  Backwards Compatibility Break - ::delete() now returns the values of the element or elements that were deleted instead of returning the fTemplating instance [wb, 2010-09-19]
 * @changes    1.0.0b16  Fixed another bug with minifying JS regex literals [wb, 2010-09-13]
 * @changes    1.0.0b15  Fixed a bug with minifying JS regex literals that occur after a reserved word [wb, 2010-09-12]
 * @changes    1.0.0b14  Added documentation about `[sub-key]` syntax [wb, 2010-09-12]
 * @changes    1.0.0b13  Backwards Compatibility Break - ::add(), ::delete(), ::get() and ::set() now interpret `[` and `]` as array shorthand and thus they can not be used in element names, renamed ::remove() to ::filter() - added `$beginning` parameter to ::add() and added ::remove() method [wb, 2010-09-12]
 * @changes    1.0.0b12  Added ::enableMinification(), ::enablePHPShortTags(), the ability to be able to place child fTemplating objects via a new magic element `__main__` and the `$main_element` parameter for ::__construct() [wb, 2010-08-31]
 * @changes    1.0.0b11  Fixed a bug with the elements not being initialized to a blank array [wb, 2010-08-12]
 * @changes    1.0.0b10  Updated ::place() to ignore URL query strings when detecting an element type [wb, 2010-07-26]
 * @changes    1.0.0b9   Added the methods ::delete() and ::remove() [wb+mn, 2010-07-15]
 * @changes    1.0.0b8   Fixed a bug with placing absolute file paths on Windows [wb, 2010-07-09]
 * @changes    1.0.0b7   Removed `e` flag from preg_replace() calls [wb, 2010-06-08]
 * @changes    1.0.0b6   Changed ::set() and ::add() to return the object for method chaining, changed ::set() and ::get() to accept arrays of elements [wb, 2010-06-02]
 * @changes    1.0.0b5   Added ::encode() [wb, 2010-05-20]
 * @changes    1.0.0b4   Added ::create() and ::retrieve() for named fTemplating instances [wb, 2010-05-11]
 * @changes    1.0.0b3   Fixed an issue with placing relative file path [wb, 2010-04-23]
 * @changes    1.0.0b2   Added the ::inject() method [wb, 2009-01-09]
 * @changes    1.0.0b    The initial implementation [wb, 2007-06-14]
 */
class fTemplating
{
	const attach   = 'fTemplating::attach';
	const reset    = 'fTemplating::reset';
	const retrieve = 'fTemplating::retrieve';
	
	
	/**
	 * Named fTemplating instances
	 * 
	 * @var array
	 */
	static $instances = array();
	
	
	/**
	 * Attaches a named template that can be accessed from any scope via ::retrieve()
	 * 
	 * @param  fTemplating $templating  The fTemplating object to attach
	 * @param  string      $name        The name for this templating instance
	 * @return void
	 */
	static public function attach($templating, $name='default')
	{
		self::$instances[$name] = $templating;
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
		self::$instances = array();
	}
	
	
	/**
	 * Retrieves a named template
	 * 
	 * @param  string $name  The name of the template to retrieve
	 * @return fTemplating  The specified fTemplating instance
	 */
	static public function retrieve($name='default')
	{
		if (!isset(self::$instances[$name])) {
			throw new fProgrammerException(
				'The named template specified, %s, has not been attached yet',
				$name
			);
		}
		return self::$instances[$name];
	}
	
	
	/**
	 * The buffered object id, used for differentiating different instances when doing replacements
	 * 
	 * @var integer
	 */
	private $buffered_id;
	
	/**
	 * A data store for templating
	 * 
	 * @var array
	 */
	private $elements;
	
	/**
	 * The directory to store minified code in
	 * 
	 * @var string
	 */
	private $minification_directory;
	
	/**
	 * The path prefix to prepend to CSS and JS paths to find them on the filesystem
	 * 
	 * @var string
	 */
	private $minification_prefix;
	
	/**
	 * The minification mode: development or production
	 * 
	 * @var string
	 */
	private $minification_mode;
	
	/**
	 * The directory to look for files
	 * 
	 * @var string
	 */
	protected $root;
	
	/**
	 * The directory to store PHP files with short tags fixed
	 * 
	 * @var string
	 */
	private $short_tag_directory;
	
	/**
	 * The short tag mode: development or production
	 * 
	 * @var string
	 */
	private $short_tag_mode;
	
	
	/**
	 * Initializes this templating engine
	 * 
	 * @param  string $root          The filesystem path to use when accessing relative files, defaults to `$_SERVER['DOCUMENT_ROOT']`
	 * @param  string $main_element  The value for the `__main__` element - this is used when calling ::place() without an element, or when placing fTemplating objects as children
	 * @return fTemplating
	 */
	public function __construct($root=NULL, $main_element=NULL)
	{
		if ($root === NULL) {
			$root = $_SERVER['DOCUMENT_ROOT'];
		}
		
		if (!file_exists($root)) {
			throw new fProgrammerException(
				'The root specified, %s, does not exist on the filesystem',
				$root
			);
		}
		
		if (!is_readable($root)) {
			throw new fEnvironmentException(
				'The root specified, %s, is not readable',
				$root
			);
		}
		
		if (substr($root, -1) != '/' && substr($root, -1) != '\\') {
			$root .= DIRECTORY_SEPARATOR;
		}
		
		$this->buffered_id    = NULL;
		$this->elements       = array();
		$this->root           = $root;
		
		if ($main_element !== NULL) {
			$this->set('__main__', $main_element);
		}
	}
	
	
	/**
	 * Finishing placing elements if buffering was used
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	public function __destruct()
	{
		// The __destruct method can't throw unhandled exceptions intelligently, so we will always catch here just in case
		try {
			$this->placeBuffered();
		} catch (Exception $e) {
			fCore::handleException($e);
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
	 * Adds a value to an array element
	 * 
	 * @param  string  $element    The element to add to - array elements can be modified via `[sub-key]` syntax, and thus `[` and `]` can not be used in element names
	 * @param  mixed   $value      The value to add
	 * @param  boolean $beginning  If the value should be added to the beginning of the element
	 * @return fTemplating  The template object, to allow for method chaining
	 */
	public function add($element, $value, $beginning=FALSE)
	{
		$tip =& $this->elements;
		
		if ($bracket_pos = strpos($element, '[')) {
			$original_element  = $element;
			$array_dereference = substr($element, $bracket_pos);
			$element           = substr($element, 0, $bracket_pos);
			
			preg_match_all('#(?<=\[)[^\[\]]+(?=\])#', $array_dereference, $array_keys, PREG_SET_ORDER);
			$array_keys = array_map('current', $array_keys);
			array_unshift($array_keys, $element);
			
			foreach (array_slice($array_keys, 0, -1) as $array_key) {
				if (!isset($tip[$array_key])) {
					$tip[$array_key] = array();
				} elseif (!is_array($tip[$array_key])) {
					throw new fProgrammerException(
						'%1$s was called for an element, %2$s, which is not an array',
						'add()',
						$original_element
					);
				}
				$tip =& $tip[$array_key];
			}
			$element = end($array_keys);
		}
		
		
		if (!isset($tip[$element])) {
			$tip[$element] = array();
		} elseif (!is_array($tip[$element])) {
			throw new fProgrammerException(
				'%1$s was called for an element, %2$s, which is not an array',
				'add()',
				$element
			);
		}
		
		if ($beginning) {
			array_unshift($tip[$element], $value);
		} else {
			$tip[$element][] = $value;
		}
		
		return $this;
	}
	
	
	/**
	 * Enables buffered output, allowing ::set() and ::add() to happen after a ::place() but act as if they were done before
	 * 
	 * Please note that using buffered output will affect the order in which
	 * code is executed since the elements are not actually ::place()'ed until
	 * the destructor is called.
	 * 
	 * If the non-template code depends on template code being executed
	 * sequentially before it, you may not want to use output buffering.
	 * 
	 * @return void
	 */
	public function buffer()
	{
		static $id_sequence = 1;
		
		if ($this->buffered_id) {
			throw new fProgrammerException('Buffering has already been started');
		}
		
		if (!fBuffer::isStarted()) {
			fBuffer::start();
		}
		
		$this->buffered_id = $id_sequence;
		
		$id_sequence++;
	}
	
	
	/**
	 * Deletes an element from the template
	 * 
	 * @param  string $element        The element to delete - array elements can be modified via `[sub-key]` syntax, and thus `[` and `]` can not be used in element names
	 * @param  mixed  $default_value  The value to return if the `$element` is not set
	 * @param  array  |$elements      The elements to delete - an array of element names or an associative array of keys being element names and the values being the default values
	 * @return mixed  The value of the `$element` that was deleted - an associative array of deleted elements will be returned if an array of `$elements` was specified
	 */
	public function delete($element, $default_value=NULL)
	{
		if (is_array($element)) {
			$elements = $element;
			
			if (is_numeric(key($elements))) {
				$new_elements = array();
				foreach ($elements as $element) {
					$new_elements[$element] = NULL;
				}
				$elements = $new_elements;
			}
			
			$output = array();
			foreach ($elements as $key => $default_value) {
				$output[$key] = $this->delete($key, $default_value);
			}
			return $output;
		}
		
		$tip   =& $this->elements;
		$value =  $default_value;
		
		if ($bracket_pos = strpos($element, '[')) {
			$original_element  = $element;
			$array_dereference = substr($element, $bracket_pos);
			$element           = substr($element, 0, $bracket_pos);
			
			preg_match_all('#(?<=\[)[^\[\]]+(?=\])#', $array_dereference, $array_keys, PREG_SET_ORDER);
			$array_keys = array_map('current', $array_keys);
			array_unshift($array_keys, $element);
			
			foreach (array_slice($array_keys, 0, -1) as $array_key) {
				if (!isset($tip[$array_key])) {
					return $value;
				} elseif (!is_array($tip[$array_key])) {
					throw new fProgrammerException(
						'%1$s was called for an element, %2$s, which is not an array',
						'delete()',
						$original_element
					);
				}
				$tip =& $tip[$array_key];
			}
			$element = end($array_keys);
		}
		
		if (isset($tip[$element])) {
			$value = $tip[$element];
			unset($tip[$element]);
		}
		
		return $value;
	}
	
	
	/**
	 * Erases all output since the invocation of the template - only works if buffering is on
	 * 
	 * @return void
	 */
	public function destroy()
	{
		if (!$this->buffered_id) {
			throw new fProgrammerException(
				'A template can only be destroyed if buffering has been enabled'
			);
		}
		
		$this->buffered_id = NULL;
		
		fBuffer::erase();
		fBuffer::stop();
		
		$this->__destruct();
	}
	
	
	/**
	 * Enables minified output for CSS and JS elements
	 * 
	 * For CSS and JS, compilation means that the file will be minified and
	 * cached. The filename will change whenever the content change, allowing
	 * for far-futures expire headers.
	 * 
	 * Please note that this option requires that all CSS and JS paths be
	 * relative to the $_SERVER['DOCUMENT_ROOT'] and start with a `/`. Also
	 * this class will not clean up old cached files out of the cache
	 * directory.
	 * 
	 * This functionality will be inherited by all child fTemplating objects
	 * that do not have their own explicit minification settings.
	 * 
	 * @param  string             $mode             The compilation mode - `'development'` means that file modification times will be checked on each load, `'production'` means that the cache files will only be regenerated when missing
	 * @param  fDirectory|string  $cache_directory  The directory to cache the compiled files into - this needs to be inside the document root or a path added to fFilesystem::addWebPathTranslation()
	 * @param  fDirectory|string  $path_prefix      The directory to prepend to all CSS and JS paths to load the files from the filesystem - this defaults to `$_SERVER['DOCUMENT_ROOT']`
	 * @return void
	 */
	public function enableMinification($mode, $cache_directory, $path_prefix=NULL)
	{
		$valid_modes = array('development', 'production');
		if (!in_array($mode, $valid_modes)) {
			throw new fProgrammerException(
				'The mode specified, %1$s, is invalid. Must be one of: %2$s.',
				$mode,
				join(', ', $valid_modes)
			);
		}
		
		$cache_directory = $cache_directory instanceof fDirectory ? $cache_directory->getPath() : realpath($cache_directory);
		if (!is_writable($cache_directory)) {
			throw new fEnvironmentException(
				'The cache directory specified, %s, is not writable',
				$cache_directory
			);
		}
		
		$path_prefix = $path_prefix instanceof fDirectory ? $path_prefix->getPath() : $path_prefix;
		if ($path_prefix === NULL) {
			$path_prefix = $_SERVER['DOCUMENT_ROOT'];
		}
		
		$this->minification_mode      = $mode;
		$this->minification_directory = fDirectory::makeCanonical($cache_directory);
		$this->minification_prefix    = $path_prefix;
	}
	
	
	/**
	 * Converts PHP short tags to long tags when short tags are turned off
	 * 
	 * Please note that this only affects PHP files that are **directly**
	 * evaluated with ::place() or ::inject(). It will not affect PHP files that
	 * have been evaluated via `include` or `require` statements inside of the
	 * directly evaluated PHP files.
	 * 
	 * This functionality will be inherited by all child fTemplating objects
	 * that do not have their own explicit short tag settings.
	 * 
	 * @param  string             $mode             The compilation mode - `'development'` means that file modification times will be checked on each load, `'production'` means that the cache files will only be regenerated when missing
	 * @param  fDirectory|string  $cache_directory  The directory to cache the compiled files into - this directory should not be accessible from the web
	 * @return void
	 */
	public function enablePHPShortTags($mode, $cache_directory)
	{
		// This does not need to be enabled if short tags are on
		if (ini_get('short_open_tag') && strtolower(ini_get('short_open_tag')) != 'off') {
			return;
		}
		
		$valid_modes = array('development', 'production');
		if (!in_array($mode, $valid_modes)) {
			throw new fProgrammerException(
				'The mode specified, %1$s, is invalid. Must be one of: %2$s.',
				$mode,
				join(', ', $valid_modes)
			);
		}
		
		$cache_directory = $cache_directory instanceof fDirectory ? $cache_directory->getPath() : $cache_directory;
		if (!is_writable($cache_directory)) {
			throw new fEnvironmentException(
				'The cache directory specified, %s, is not writable',
				$cache_directory
			);
		}
		
		$this->short_tag_mode      = $mode;
		$this->short_tag_directory = fDirectory::makeCanonical($cache_directory);
	}
	
	
	/**
	 * Gets the value of an element and runs it through fHTML::encode()
	 * 
	 * @param  string $element        The element to get - array elements can be accessed via `[sub-key]` syntax, and thus `[` and `]` can not be used in element names
	 * @param  mixed  $default_value  The value to return if the element has not been set
	 * @return mixed  The value of the element specified run through fHTML::encode(), or the default value if it has not been set
	 */
	public function encode($element, $default_value=NULL)
	{
		return fHTML::encode($this->get($element, $default_value));
	}
	
	
	/**
	 * Removes a value from an array element
	 *
	 * @param string $element  The element to remove from - array elements can be modified via `[sub-key]` syntax, and thus `[` and `]` can not be used in element names
	 * @param mixed  $value    The value to remove - compared in a non-strict manner, such that removing `0` will remove a blank string and false also
	 * @return fTemplating  The template object, to allow for method chaining
	 */
	public function filter($element, $value)
	{
		$tip =& $this->elements;
		
		if ($bracket_pos = strpos($element, '[')) {
			$original_element  = $element;
			$array_dereference = substr($element, $bracket_pos);
			$element           = substr($element, 0, $bracket_pos);
			
			preg_match_all('#(?<=\[)[^\[\]]+(?=\])#', $array_dereference, $array_keys, PREG_SET_ORDER);
			$array_keys = array_map('current', $array_keys);
			array_unshift($array_keys, $element);
			
			foreach (array_slice($array_keys, 0, -1) as $array_key) {
				if (!isset($tip[$array_key])) {
					return $this;
				} elseif (!is_array($tip[$array_key])) {
					throw new fProgrammerException(
						'%1$s was called for an element, %2$s, which is not an array',
						'filter()',
						$original_element
					);
				}
				$tip =& $tip[$array_key];
			}
			$element = end($array_keys);
		}
		
		if (!isset($tip[$element])) {
			return $this;
		} elseif (!is_array($tip[$element])) {
			throw new fProgrammerException(
				'%1$s was called for an element, %2$s, which is not an array',
				'filter()',
				$element
			);
		}
		
		$keys = array_keys($tip[$element], $value);
		if ($keys) {
			foreach ($keys as $key) {
				unset($tip[$element][$key]);
			}
			$tip[$element] = array_values($tip[$element]);
		}
		
		return $this;
	}
	
	
	/**
	 * Takes an array of PHP files and caches a version with all short tags converted to regular tags
	 * 
	 * @param array $values  The file paths to the PHP files
	 * @return array  An array of file paths to the corresponding converted PHP files
	 */
	private function fixShortTags($values)
	{
		$fixed_paths = array();
		foreach ($values as $value) {
			// Check to see if the element is a path relative to the template root
			if (!preg_match('#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//|\./|\.\\\\)#i', $value)) {
				$value = $this->root . $value;
			}
			
			$real_value = realpath($value);
			$cache_path = $this->short_tag_directory . sha1($real_value) . '.php';
			
			$fixed_paths[] = $cache_path;
			if (file_exists($cache_path) && ($this->short_tag_mode == 'production' || filemtime($cache_path) >= filemtime($real_value))) {
				continue;
			}
			
			$code = file_get_contents($real_value);
			$output = '';
			
			$in_php = FALSE;
			
			do {
				if (!$in_php) {
					$token_regex = '<\?';
				} else {
					$token_regex .= '/\*|//|\\#|\'|"|<<<[a-z_]\w*|<<<\'[a-z_]\w*\'|\?>';
				}
				if (!preg_match('#' . $token_regex . '#i', $code, $match)) {
					$part  = $code;
					$code  = '';
					$token = NULL;
				
				} else {
					$token = $match[0];
					$pos   = strpos($code, $token);
					if ($pos === FALSE) {
						break;
					}
					$part  = substr($code, 0, $pos);
					$code  = substr($code, $pos);
				}
				
				$regex = NULL;
				if ($token == "<?") {
					$output .= $part;
					$in_php = TRUE;
					continue;
				} elseif ($token == "?>") {
					$regex = NULL;
					$in_php = FALSE;
				} elseif ($token == "//") {
					$regex = '#^//.*(\n|$)#D';
				} elseif ($token == "#") {
					$regex = '@^#.*(\n|$)@D';
				} elseif ($token == "/*") {
					$regex = '#^.{2}.*?(\*/|$)#sD';
				} elseif ($token == "'") {
					$regex = '#^\'((\\\\.)+|[^\\\\\']+)*(\'|$)#sD';
				} elseif ($token == '"') {
					$regex = '#^"((\\\\.)+|[^\\\\"]+)*("|$)#sD';
				} elseif ($token) {
					$regex = '#\A<<<\'?([a-zA-Z_]\w*)\'?.*?^\1;\n#sm';
				}
				
				$part = str_replace('<?=', '<?php echo', $part);
				$part = preg_replace('#<\?(?!php)#i', '<?php', $part);
				
				// This makes sure that __FILE__ and __DIR__ stay as the
				// original value since the cached file will be in a different
				// place with a different filename
				$part = preg_replace('#(?<=[^a-zA-Z0-9]|^)__FILE__(?=[^a-zA-Z0-9]|$)#iD', "'" . $real_value . "'", $part);
				if (fCore::checkVersion('5.3')) {
					$part = preg_replace('#(?<=[^a-zA-Z0-9]|^)__DIR__(?=[^a-zA-Z0-9]|$)#iD', "'" . dirname($real_value) . "'", $part);
				}
				
				$output .= $part;
				
				if ($regex) {
					preg_match($regex, $code, $match);
					$output .= $match[0];
					$code = substr($code, strlen($match[0]));
				}
				
			} while (strlen($code));
			
			file_put_contents($cache_path, $output);
		}
		
		return $fixed_paths;
	}
	
	
	/**
	 * Gets the value of an element
	 * 
	 * @param  string $element        The element to get - array elements can be accessed via `[sub-key]` syntax, and thus `[` and `]` can not be used in element names
	 * @param  mixed  $default_value  The value to return if the element has not been set
	 * @param  array  |$elements      An array of elements to get, or an associative array where a string key is the element to get and the value is the default value
	 * @return mixed  The value of the element(s) specified, or the default value(s) if it has not been set
	 */
	public function get($element, $default_value=NULL)
	{
		if (is_array($element)) {
			$elements = $element;
			
			// Turn an array of elements into an array of elements with NULL default values
			if (array_values($elements) === $elements) {
				$elements = array_combine($elements, array_fill(0, count($elements), NULL));
			}
			
			$output = array();
			foreach ($elements as $element => $default_value) {
				$output[$element] = $this->get($element, $default_value);
			}
			return $output;
		}
		
		$array_dereference = NULL;
		if ($bracket_pos = strpos($element, '[')) {
			$array_dereference = substr($element, $bracket_pos);
			$element           = substr($element, 0, $bracket_pos);
		}
		
		if (!isset($this->elements[$element])) {
			return $default_value;
		}
		$value = $this->elements[$element];
		
		if ($array_dereference) {
			preg_match_all('#(?<=\[)[^\[\]]+(?=\])#', $array_dereference, $array_keys, PREG_SET_ORDER);
			$array_keys = array_map('current', $array_keys);
			foreach ($array_keys as $array_key) {
				if (!is_array($value) || !isset($value[$array_key])) {
					$value = $default_value;
					break;
				}
				$value = $value[$array_key];
			}
		}
		
		return $value;
	}
	
	
	/**
	 * Combines an array of CSS or JS files and places them as a single file
	 * 
	 * @param string $type     The type of compilation, 'css' or 'js'
	 * @param string $element  The element name
	 * @param array  $values   An array of file paths
	 * @return void
	 */
	protected function handleMinified($type, $element, $values)
	{
		$paths = array();
		$media = NULL;
		foreach ($values as $value) {
			if (is_array($value)) {
				$paths[] = $this->minification_prefix . $value['path'];
				if ($type == 'css') {
					$media = !empty($value['media']) ? $value['media'] : NULL;
				}
			} else {
				$paths[] = $this->minification_prefix . $value;
			}
		}
		
		$hash       = sha1(join('|', $paths));
		$cache_file = $this->minification_directory . $hash . '.' . $type;
		
		$regenerate    = FALSE;
		$checked_paths = FALSE;
		if (!file_exists($cache_file)) {
			$regenerate = TRUE;
		} elseif ($this->minification_mode == 'development') {
			$cache_mtime   = filemtime($cache_file);
			$checked_paths = TRUE;
			
			foreach ($paths as $path) {
				if (!file_exists($path)) {
					throw new fEnvironmentException(
						'The file specified, %s, does not exist under the $path_prefix specified',
						preg_replace('#^' . preg_quote($this->minification_prefix, '#') . '#', '', $path)
					);
				}
				if (filemtime($path) > $cache_mtime) {
					$regenerate = TRUE;
					break;
				}
			}
			
		}
		
		if ($regenerate) {
			$minified = '';
			
			foreach ($paths as $path) {
				$path_cache_file = $this->minification_directory . sha1($path) . '.' . $type;
				
				if ($checked_paths && !file_exists($path)) {
					throw new fEnvironmentException(
						'The file specified, %s, does not exist under the $path_prefix specified',
						preg_replace('#^' . preg_quote($this->minification_prefix, '#') . '#', '', $path)
					);
				}
				
				// Checks if this path has been cached
				if (file_exists($path_cache_file) && filemtime($path_cache_file) >= filemtime($path)) {
					$minified_path = file_get_contents($path_cache_file);
				} else {
					$minified_path = trim($this->minify(file_get_contents($path), $type));
					file_put_contents($path_cache_file, $minified_path);
				}
				
				$minified .= "\n" . $minified_path;
			}
			
			file_put_contents($cache_file, substr($minified, 1));
		}
		
		$version        = filemtime($cache_file);
		$compiled_value = fFilesystem::translateToWebPath($cache_file) . '?v=' . $version;
		if ($type == 'css' && $media) {
			$compiled_value = array(
				'path'  => $compiled_value,
				'media' => $media
			);
		}
		
		$method = 'place' . strtoupper($type);
		$this->$method($compiled_value);
	}
	
	
	/**
	 * Includes the file specified - this is identical to ::place() except a filename is specified instead of an element
	 * 
	 * Please see the ::place() method for more details about functionality.
	 * 
	 * @param  string $file_path  The file to place
	 * @param  string $file_type  Will force the file to be placed as this type of file instead of auto-detecting the file type. Valid types include: `'css'`, `'js'`, `'php'` and `'rss'`.
	 * @return void
	 */
	public function inject($file_path, $file_type=NULL)
	{
		$prefix = '__injected_';
		$num    = 1;
		while (isset($this->elements[$prefix . $num])) {
			$num++;
		}
		$element = $prefix . $num;
		
		$this->set($element, $file_path);
		$this->place($element, $file_type);
	}
	
	
	/**
	 * Minifies JS or CSS
	 * 
	 * For JS, this function is based on the JSMin algorithm (not the code) from
	 * http://www.crockford.com/javascript/jsmin.html with the addition of
	 * preserving /*! comment blocks for things like licenses. Some other
	 * versions of JSMin change the contents of special comment blocks, but
	 * this version does not.
	 * 
	 * @param string $code  The code to minify
	 * @param string $type  The type of code, 'css' or 'js'
	 * @return string  The minified code
	 */
	protected function minify($code, $type)
	{
		$output = '';
		$buffer = '';
		$stack  = array();
			
		$token_regex  = '#/\*|\'|"';
		if ($type == 'js') {
			$token_regex .= '|//';
			$token_regex .= '|/';
		} elseif ($type == 'css') {
			$token_regex .= '|url\(';
		}
		$token_regex .= '#i';
		
		do {
			if (!preg_match($token_regex, $code, $match)) {
				$part  = $code;
				$code  = '';
				$token = NULL;
			
			} else {
				$token = $match[0];
				$pos   = strpos($code, $token);
				if ($pos === FALSE) {
					break;
				}
				$part  = substr($code, 0, $pos);
				$code  = substr($code, $pos);
			}
			
			$regex = NULL;
			if ($token == '/') {
				if (!preg_match('#([(,=:[!&|?{};\n]|\breturn)\s*$#D', $part)) {
					$part .= $token;
					$code = substr($code, 1);
				} else {
					$regex = '#^/((\\\\.)+|[^\\\\/]+)*(/|$)#sD';
				}
			} elseif ($token == "url(") {
				$regex = '#^url\(((\\\\.)+|[^\\\\\\)]+)*(\)|$)#sD';
			} elseif ($token == "//") {
				$regex = '#^//.*(\n|$)#D';
			} elseif ($token == "/*") {
				$regex = '#^.{2}.*?(\*/|$)#sD';
			} elseif ($token == "'") {
				$regex = '#^\'((\\\\.)+|[^\\\\\']+)*(\'|$)#sD';
			} elseif ($token == '"') {
				$regex = '#^"((\\\\.)+|[^\\\\"]+)*("|$)#sD';
			}
			
			$this->minifyCode($part, $buffer, $stack, $type);
			$output .= $buffer;
			$buffer  = $part;
			
			if ($regex) {
				preg_match($regex, $code, $match);
				$code = substr($code, strlen($match[0]));
				$this->minifyLiteral($match[0], $buffer, $type);
				$output .= $buffer;
				$buffer  = $match[0];
			} elseif (!$token) {
				$output .= $buffer;
			}
			
		} while (strlen($code));
		
		return $output;
	}
	
	
	/**
	 * Takes a block of CSS or JS and reduces the number of characters
	 * 
	 * @param string &$part    The part of code to minify
	 * @param string &$buffer  A buffer containing the last code or literal encountered
	 * @param array  $stack    A stack used to keep track of the nesting level of CSS
	 * @param mixed  $type     The type of code, `'css'` or `'js'`
	 * @return void
	 */
	protected function minifyCode(&$part, &$buffer, &$stack, $type='js')
	{
		// This pulls in the end of the last match for useful context
		$end_buffer = substr($buffer, -1);
		$lookbehind = in_array($end_buffer, array(' ', "\n")) ? substr($buffer, -2) : $end_buffer;
		$buffer     = substr($buffer, 0, 0-strlen($lookbehind));
		$part       = $lookbehind . $part;
		
		if ($type == 'js') {
			
			// All whitespace and control characters are collapsed
			$part = preg_replace('#[\x00-\x09\x0B\x0C\x0E-\x20]+#', ' ', $part);
			$part = preg_replace('#[\n\r]+#', "\n", $part);
			
			// Whitespace is removed where not needed
			$part = preg_replace('#(?<![a-z0-9\x80-\xFF\\\\$_+\-])[ ]+#i', '', $part);
			$part = preg_replace('#[ ]+(?![a-z0-9\x80-\xFF\\\\$_+\-])#i', '', $part);
			
			$part = preg_replace('#(?<![a-z0-9\x80-\xFF\\\\$_}\\])"\'+-])\n+#i', '', $part);
			$part = preg_replace('#\n+(?![a-z0-9\x80-\xFF\\\\$_{[(+-])#i', '', $part);

			// Remove spaces around + and - unless they are followed by a plus or minus
			$part = preg_replace('#(?<=[+-])[ ]+(?![+\-])#i', '', $part);
			$part = preg_replace('#(?<![+-])[ ]+(?=[+\-])#i', '', $part);
					
		} elseif ($type == 'css') {
			
			// All whitespace is collapsed
			$part = preg_replace('#\s+#', ' ', $part);
			
			// Whitespace is removed where not needed
			$part = preg_replace('#\s*([;{},>+])\s*#', '\1', $part);
				
			// This keeps track of the current scope since some minification
			// rules are different if inside or outside of a rule block 
			$new_part = '';
			do {
				if (!preg_match('#@media|\{|\}#', $part, $match)) {
					$chunk = $part;
					$part  = '';
					$token = NULL;
				} else {
					$token = $match[0];
					$pos = strpos($part, $token);
					if ($pos === FALSE) {
						break;
					}
					$chunk = substr($part, 0, $pos+strlen($token));
					$part  = substr($part, $pos+strlen($token));
				}
				
				if (end($stack) == 'rule_block') {
					
					// Colons don't need space inside of a block
					$chunk = preg_replace('#\s*:\s*#', ':', $chunk);
					
					// Useless semi-colons are removed
					$chunk = str_replace(';}', '}', $chunk);
					
					// All zero units are reduces to just 0
					$chunk = preg_replace('#((?<!\d|\.|\#|\w)0+(\.0+)?|(?<!\d)\.0+)(?=\D|$)((%|in|cm|mm|em|ex|pt|pc|px)(\b|$))?#iD', '0', $chunk);
					
					// All .0 decimals are removed
					$chunk = preg_replace('#(\d+)\.0+(?=\D)#iD', '\1', $chunk);
					
					// All leading zeros are removed
					$chunk = preg_replace('#(?<!\d)0+(\.\d+)(?=\D)#iD', '\1', $chunk);
					
					// All measurements that contain the same value 4 times are reduced to a single
					$chunk = preg_replace('#(?<!\d|\.)([\d\.]+(?:%|in|cm|mm|em|ex|pt|pc|px))(\s*\1){3}#i', '\1', $chunk);
					
					// Hex color codes are reduced if possible
					$chunk = preg_replace('@#([a-f0-9])\1([a-f0-9])\2([a-f0-9])\3(?!\d)@iD', '#\1\2\3', $chunk);
					
					$chunk = str_ireplace('! important', '!important', $chunk);
					
				
				} else {
					
					// This handles an IE6 edge-case
					$chunk = preg_replace('#(:first-l(etter|ine))\{#', '\1 {', $chunk);
					
				}
				
				$new_part .= $chunk;
				
				if ($token == '@media') {
					$stack[] = 'media_rule';
				} elseif ($token == '{' && end($stack) == 'media_rule') {
					$stack = array('media_block');
				} elseif ($token == '{') {
					$stack[] = 'rule_block';
				} elseif ($token) {
					array_pop($stack);
				}
				
			} while (strlen($part));
			
			$part = $new_part;
		}
	}
	
	
	/**
	 * Takes a literal and either discards or keeps it
	 * 
	 * @param mixed  &$part    The literal to process
	 * @param mixed  &$buffer  The last literal or code processed
	 * @param string $type     The language the literal is in, `'css'` or `'js'`
	 * @return void
	 */
	protected function minifyLiteral(&$part, &$buffer, $type)
	{
		// Comments are skipped unless they are special
		if (substr($part, 0, 2) == '/*' && substr($part, 0, 3) != '/*!') {
			$part = $buffer . ' ';
			$buffer = '';
		}
		if ($type == 'js' && substr($part, 0, 2) == '//') {
			$part = $buffer . "\n";
			$buffer = '';
		}
	}
	
	
	/**
	 * Includes the element specified - element must be set through ::set() first
	 * 
	 * If the element is a file path ending in `.css`, `.js`, `.rss` or `.xml`
	 * an appropriate HTML tag will be printed (files ending in `.xml` will be
	 * treated as an RSS feed). If the element is a file path ending in `.inc`,
	 * `.php` or `.php5` it will be included.
	 * 
	 * Paths that start with `./` will be loaded relative to the current script.
	 * Paths that start with a file or directory name will be loaded relative
	 * to the `$root` passed in the constructor. Paths that start with `/` will
	 * be loaded from the root of the filesystem.
	 * 
	 * You can pass the `media` attribute of a CSS file or the `title` attribute
	 * of an RSS feed by adding an associative array with the following formats:
	 * 
	 * {{{
	 * array(
	 *     'path'  => (string) {css file path},
	 *     'media' => (string) {media type}
	 * );
	 * array(
	 *     'path'  => (string) {rss file path},
	 *     'title' => (string) {feed title}
	 * );
	 * }}}
	 * 
	 * @param  string $element    The element to place
	 * @param  string $file_type  Will force the element to be placed as this type of file instead of auto-detecting the file type. Valid types include: `'css'`, `'js'`, `'php'` and `'rss'`.
	 * @return void
	 */
	public function place($element='__main__', $file_type=NULL)
	{
		// Put in a buffered placeholder
		if ($this->buffered_id) {
			echo '%%fTemplating::' . $this->buffered_id . '::' . $element . '::' . $file_type . '%%';
			return;
		}
		
		if (!isset($this->elements[$element])) {
			return;
		}
		
		$this->placeElement($element, $file_type);
	}
	
	
	/**
	 * Prints a CSS `link` HTML tag to the output
	 * 
	 * @param  mixed $info  The path or array containing the `'path'` to the CSS file. Array can also contain a key `'media'`.
	 * @return void
	 */
	protected function placeCSS($info)
	{
		if (!is_array($info)) {
			$info = array('path'  => $info);
		}
		
		if (!isset($info['media'])) {
			$info['media'] = 'all';
		}
		
		echo '<link rel="stylesheet" type="text/css" href="' . $info['path'] . '" media="' . $info['media'] . '" />' . "\n";
	}
	
	
	/**
	 * Performs the action of actually placing an element
	 * 
	 * @param  string $element    The element that is being placed
	 * @param  string $file_type  The file type to treat all values as
	 * @return void
	 */
	protected function placeElement($element, $file_type)
	{
		$values = $this->elements[$element];
		if (!is_object($values)) {
			settype($values, 'array');
		} else {
			$values = array($values);	
		}
		$values = array_values($values);
		
		$value_groups = array();
		
		$last_type     = NULL;
		$last_location = NULL;
		$last_media    = NULL;
		foreach ($values as $i => $value) {	
			$type = $this->verifyValue($element, $value, $file_type);
			
			$media    = is_array($value) && isset($value['media']) ? $value['media'] : NULL;
			$path     = is_array($value) ? $value['path'] : $value;
			$location = is_string($path) && preg_match('#^https?://#', $path) ? 'http' : 'local';
			
			if ($type != $last_type || $location != $last_location || $media != $last_media) {
				$value_groups[] = array(
					'type'     => $type,
					'location' => $location,
					'values'   => array()
				);
			}
			$value_groups[count($value_groups)-1]['values'][] = $value;
			
			$last_type     = $type;
			$last_location = $location;
			$last_media    = $media;
		}
		
		foreach ($value_groups as $value_group) {
			if ($value_group['location'] == 'local') {
				if ($this->minification_directory && in_array($value_group['type'], array('js', 'css'))) {
					$this->handleMinified($value_group['type'], $element, $value_group['values']);
					continue;
				}
				if ($this->short_tag_directory && $value_group['type'] == 'php') {
					$value_group['values'] = $this->fixShortTags($value_group['values']);
				}
			}
			
			foreach ($value_group['values'] as $value) {
				switch ($value_group['type']) {
					case 'css':
						$this->placeCSS($value);
						break;
					
					case 'fTemplating':
						// This causes children to inherit settings if they aren't already set
						if ($value->minification_directory === NULL) {
							$value->minification_directory = $this->minification_directory;
							$value->minification_mode      = $this->minification_mode;
							$value->minification_prefix    = $this->minification_prefix;
						}
						if ($value->short_tag_directory === NULL) {
							$value->short_tag_directory = $this->short_tag_directory;
							$value->short_tag_mode      = $this->short_tag_mode;
						}
						$value->place();
						break;
					
					case 'js':
						$this->placeJS($value);
						break;
						
					case 'php':
						$this->placePHP($element, $value);
						break;
						
					case 'rss':
						$this->placeRSS($value);
						break;
						
					default:
						throw new fProgrammerException(
							'The file type specified, %1$s, is invalid. Must be one of: %2$s.',
							$type,
							'css, js, php, rss'
						);
				}
			}
		}
	}
	
	
	/**
	 * Prints a java`script` HTML tag to the output
	 * 
	 * @param  mixed $info  The path or array containing the `'path'` to the javascript file
	 * @return void
	 */
	protected function placeJS($info)
	{
		if (!is_array($info)) {
			$info = array('path'  => $info);
		}
		
		echo '<script type="text/javascript" src="' . $info['path'] . '"></script>' . "\n";
	}
	
	
	/**
	 * Includes a PHP file
	 * 
	 * @param  string $element  The element being placed
	 * @param  string $path     The path to the PHP file
	 * @return void
	 */
	protected function placePHP($element, $path)
	{
		// Check to see if the element is a path relative to the template root
		if (!preg_match('#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//|\./|\.\\\\)#i', $path)) {
			$path = $this->root . $path;
		}
		
		if (!file_exists($path)) {
			throw new fProgrammerException(
				'The path specified for %1$s, %2$s, does not exist on the filesystem',
				$element,
				$path
			);
		}
		
		if (!is_readable($path)) {
			throw new fEnvironmentException(
				'The path specified for %1$s, %2$s, is not readable',
				$element,
				$path
			);
		}
				
		include($path);
	}
	
	
	/**
	 * Prints an RSS `link` HTML tag to the output
	 * 
	 * @param  mixed $info  The path or array containing the `'path'` to the RSS xml file. May also contain a `'title'` key for the title of the RSS feed.
	 * @return void
	 */
	protected function placeRSS($info)
	{
		if (!is_array($info)) {
			$info = array(
				'path'  => $info,
				'title' => fGrammar::humanize(
					preg_replace('#.*?([^/]+).(rss|xml)$#iD', '\1', $info)
				)
			);
		}
		
		if (!isset($info['title'])) {
			throw new fProgrammerException(
				'The RSS value %s is missing the title key',
				$info
			);
		}
		
		echo '<link rel="alternate" type="application/rss+xml" href="' . $info['path'] . '" title="' . $info['title'] . '" />' . "\n";
	}
	
	
	/**
	 * Performs buffered replacements using a breadth-first technique
	 * 
	 * @return void
	 */
	private function placeBuffered()
	{
		if (!$this->buffered_id) {
			return;
		}
		
		$contents = fBuffer::get();
		fBuffer::erase();
		
		// We are gonna use a regex replacement that is eval()'ed as PHP code
		$regex       = '/%%fTemplating::' . $this->buffered_id . '::(.*?)::(.*?)%%/';
		
		// Remove the buffered id, thus making any nested place() calls be executed immediately
		$this->buffered_id = NULL;
		
		echo preg_replace_callback($regex, array($this, 'placeBufferedCallback'), $contents);
	}
	
	
	/**
	 * Performs a captured place of an element to use with buffer placing
	 * 
	 * @param array $match  A regex match from ::placeBuffered()
	 * @return string  The output of placing the element
	 */
	private function placeBufferedCallback($match)
	{
		fBuffer::startCapture();
		$this->placeElement($match[1], $match[2]);
		return fBuffer::stopCapture();
	}
	
	
	/**
	 * Gets the value of an element and runs it through fHTML::prepare()
	 * 
	 * @param  string $element        The element to get - array elements can be access via `[sub-key]` syntax, and thus `[` and `]` can not be used in element names
	 * @param  mixed  $default_value  The value to return if the element has not been set
	 * @return mixed  The value of the element specified run through fHTML::prepare(), or the default value if it has not been set
	 */
	public function prepare($element, $default_value=NULL)
	{
		return fHTML::prepare($this->get($element, $default_value));
	}
	
	
	/**
	 * Removes and returns the value from the end of an array element
	 * 
	 * @param  string  $element    The element to remove from to - array elements can be modified via `[sub-key]` syntax, and thus `[` and `]` can not be used in element names
	 * @param  boolean $beginning  If the value should be removed from the beginning of the element
	 * @return mixed  The value that was removed
	 */
	public function remove($element, $beginning=FALSE)
	{
		$tip =& $this->elements;
		
		if ($bracket_pos = strpos($element, '[')) {
			$original_element  = $element;
			$array_dereference = substr($element, $bracket_pos);
			$element           = substr($element, 0, $bracket_pos);
			
			preg_match_all('#(?<=\[)[^\[\]]+(?=\])#', $array_dereference, $array_keys, PREG_SET_ORDER);
			$array_keys = array_map('current', $array_keys);
			array_unshift($array_keys, $element);
			
			foreach (array_slice($array_keys, 0, -1) as $array_key) {
				if (!isset($tip[$array_key])) {
					return NULL;
				} elseif (!is_array($tip[$array_key])) {
					throw new fProgrammerException(
						'%1$s was called for an element, %2$s, which is not an array',
						'remove()',
						$original_element
					);
				}
				$tip =& $tip[$array_key];
			}
			$element = end($array_keys);
		}
		
		
		if (!isset($tip[$element])) {
			return NULL;
		} elseif (!is_array($tip[$element])) {
			throw new fProgrammerException(
				'%1$s was called for an element, %2$s, which is not an array',
				'remove()',
				$element
			);
		}
		
		if ($beginning) {
			return array_shift($tip[$element]);
		}
			
		return array_pop($tip[$element]);
	}
	
	
	/**
	 * Sets the value for an element
	 * 
	 * @param  string $element    The element to set - the magic element `__main__` is used for placing the current fTemplating object as a child of another fTemplating object - array elements can be modified via `[sub-key]` syntax, and thus `[` and `]` can not be used in element names
	 * @param  mixed  $value      The value for the element
	 * @param  array  :$elements  An associative array with the key being the `$element` to set and the value being the `$value` for that element
	 * @return fTemplating  The template object, to allow for method chaining
	 */
	public function set($element, $value=NULL)
	{
		if ($value === NULL && is_array($element)) {
			foreach ($element as $key => $value) {
				$this->set($key, $value);
			}
			return $this;
		}
		
		$tip =& $this->elements;
		
		if ($bracket_pos = strpos($element, '[')) {
			$array_dereference = substr($element, $bracket_pos);
			$element               = substr($element, 0, $bracket_pos);
			
			preg_match_all('#(?<=\[)[^\[\]]+(?=\])#', $array_dereference, $array_keys, PREG_SET_ORDER);
			$array_keys = array_map('current', $array_keys);
			array_unshift($array_keys, $element);
			
			foreach (array_slice($array_keys, 0, -1) as $array_key) {
				if (!isset($tip[$array_key]) || !is_array($tip[$array_key])) {
					$tip[$array_key] = array();
				}
				$tip =& $tip[$array_key];
			}
			$element = end($array_keys);
		}
		
		$tip[$element] = $value;
		
		return $this;
	}
	
	
	/**
	 * Ensures the value is valid
	 * 
	 * @param  string $element    The element that is being placed
	 * @param  mixed  $value      A value to be placed
	 * @param  string $file_type  The file type that this element will be displayed as - skips checking file extension
	 * @return string  The file type of the value being placed
	 */
	protected function verifyValue($element, $value, $file_type=NULL)
	{
		if (!$value && !is_numeric($value)) {
			throw new fProgrammerException(
				'The element specified, %s, has a value that is empty',
				$value
			);
		}
		
		if (is_array($value) && !isset($value['path'])) {
			throw new fProgrammerException(
				'The element specified, %1$s, has a value, %2$s, that is missing the path key',
				$element,
				$value
			);
		}
		
		if ($file_type) {
			return $file_type;
		}
		
		if ($value instanceof self) {
			return 'fTemplating';
		}
		
		$path = (is_array($value)) ? $value['path'] : $value;
		$path = preg_replace('#\?.*$#D', '', $path);
		$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		
		// Allow some common variations on file extensions
		$extension_map = array(
			'inc'  => 'php',
			'php5' => 'php',
			'xml'  => 'rss'
		);
		
		if (isset($extension_map[$extension])) {
			$extension = $extension_map[$extension];
		}
		
		if (!in_array($extension, array('css', 'js', 'php', 'rss'))) {
			throw new fProgrammerException(
				'The element specified, %1$s, has a value whose path, %2$s, does not end with a recognized file extension: %3$s.',
				$element,
				$path,
				'.css, .inc, .js, .php, .php5, .rss, .xml'
			);
		}
		
		return $extension;
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