<?php
/**
 * A simple interface to cache data using different backends
 * 
 * @copyright  Copyright (c) 2009-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fCache
 * 
 * @version    1.0.0b5
 * @changes    1.0.0b5  Added missing documentation for using Redis as a backend [wb, 2011-08-25]
 * @changes    1.0.0b4  Added the `database`, `directory` and `redis` types, added support for the memcached extention and support for custom serialization callbacks [wb, 2011-06-21]
 * @changes    1.0.0b3  Added `0` to the memcache delete method call since otherwise the method triggers notices on some installs [wb, 2011-05-10]
 * @changes    1.0.0b2  Fixed API calls to the memcache extension to pass the TTL as the correct parameter [wb, 2011-02-01]
 * @changes    1.0.0b   The initial implementation [wb, 2009-04-28]
 */
class fCache
{
	/**
	 * The cache configuration, used for database, directory and file caches
	 * 
	 * The array structure for database caches is:
	 * {{{
	 * array(
	 *     'table'        => (string) {the database table to use},
	 *     'key_column'   => (string) {the varchar column to store the key in, should be able to handle at least 250 characters},
	 *     'value_column' => (string) {the text/varchar column to store the value in},
	 *     'ttl_column'   => (string) {the integer column to store the expiration time in}
	 * )
	 * }}}
	 *
	 * The array structure for directory caches:
	 * {{{
	 * array(
	 *     'path' => (string) {the directory path with trailing slash}
	 * )
	 * }}}
	 *
	 * The array structure for file caches:
	 * {{{
	 * array(
	 *     'path'  => (string) {the file path},
	 *     'state' => (string) {clean or dirty, used to appropriately save}
	 * )
	 * }}}
	 * 
	 * @var array
	 */
	protected $config;
	
	/**
	 * The data store to use
	 *
	 * Either the:
	 *  - array structure for file cache
	 *  - Memcache or Memcached object for memcache
	 *  - fDatabase object for database
	 *  - Redis object for redis
	 *
	 * Not used for apc, directory or xcache
	 * 
	 * @var mixed
	 */
	protected $data_store;

	/**
	 * The type of cache
	 * 
	 * The valid values are:
	 *  - `'apc'`
	 *  - `'database'`
	 *  - `'directory'`
	 *  - `'file'`
	 *  - `'memcache'`
	 *  - `'redis'`
	 *  - `'xcache'`
	 * 
	 * @var string
	 */
	protected $type;
	
	/**
	 * Set the type and master key for the cache
	 * 
	 * A `file` cache uses a single file to store values in an associative
	 * array and is probably not suitable for a large number of keys.
	 * 
	 * Using an `apc` or `xcache` cache will have far better performance
	 * than a file or directory, however please remember that keys are shared
	 * server-wide.
	 *
	 * `$config` is an associative array of configuration options for the various
	 * backends. Some backend require additional configuration, while others
	 * provide provide optional settings.
	 *
	 * The following `$config` options must be set for the `database` backend:
	 *
	 *  - `table`: The database table to use for caching
	 *  - `key_column`: The column to store the cache key in - must support at least 250 character strings
	 *  - `value_column`: The column to store the serialized value in - this should probably be a `TEXT` column to support large values, or `BLOB` if binary serialization is used
	 *  - `value_data_type`: If a `BLOB` column is being used for the `value_column`, this should be set to 'blob', otherwise `string`
	 *  - `ttl_column`: The column to store the expiration timestamp of the cached entry - this should be an integer
	 *
	 * The following `$config` for the following items can be set for all backends:
	 * 
	 *  - `serializer`: A callback to serialize data with, defaults to the PHP function `serialize()`
	 *  - `unserializer`: A callback to unserialize data with, defaults to the PHP function `unserialize()`
	 *
	 * Common serialization callbacks include:
	 * 
	 *  - `json_encode`/`json_decode`
	 *  - `igbinary_serialize`/`igbinary_unserialize`
	 *
	 * Please note that using JSON for serialization will exclude all non-public
	 * properties of objects from being serialized.
	 *
	 * A custom `serialize` and `unserialze` option is `string`, which will cast
	 * all values to a string when storing, instead of serializing them. If a
	 * `__toString()` method is provided for objects, it will be called. 
	 * 
	 * @param  string $type        The type of caching to use: `'apc'`, `'database'`, `'directory'`, `'file'`, `'memcache'`, `'redis'`, `'xcache'`
	 * @param  mixed  $data_store  The path for a `file` or `directory` cache, an `Memcache` or `Memcached` object for a `memcache` cache, an fDatabase object for a `database` cache or a `Redis` object for a `redis` cache - not used for `apc` or `xcache`
	 * @param  array  $config      Configuration options - see method description for details
	 * @return fCache
	 */
	public function __construct($type, $data_store=NULL, $config=array())
	{
		switch ($type) {
			case 'database': 
				foreach (array('table', 'key_column', 'value_column', 'ttl_column') as $key) {
					if (empty($config[$key])) {
						throw new fProgrammerException(
							'The config key %s is not set',
							$key
						);
					}
				}
				$this->config = $config;
				if (!isset($this->config['value_data_type'])) {
					$this->config['value_data_type'] = 'string';
				}
				if (!$data_store instanceof fDatabase) {
					throw new fProgrammerException(
						'The data store provided is not a valid %s object',
						'fDatabase'
					);
				}
				$this->data_store = $data_store;
				break;

			case 'directory': 
				$exists = file_exists($data_store);
				if (!$exists) {
					throw new fEnvironmentException(
						'The directory specified, %s, does not exist',
						$data_store
					);		
				}
				if (!is_dir($data_store)) {
					throw new fEnvironmentException(
						'The path specified, %s, is not a directory',
						$data_store
					);		
				}
				if (!is_writable($data_store)) {
					throw new fEnvironmentException(
						'The directory specified, %s, is not writable',
						$data_store
					);		
				}
				$this->config['path'] = realpath($data_store) . DIRECTORY_SEPARATOR;
				break;

			case 'file': 
				$exists = file_exists($data_store);
				if (!$exists && !is_writable(dirname($data_store))) {
					throw new fEnvironmentException(
						'The file specified, %s, does not exist and the directory it in inside of is not writable',
						$data_store
					);		
				}
				if ($exists && !is_writable($data_store)) {
					throw new fEnvironmentException(
						'The file specified, %s, is not writable',
						$data_store
					);
				}
				$this->config['path'] = $data_store;
				if ($exists) {
					$this->data_store = unserialize(file_get_contents($data_store));
				} else {
					$this->data_store = array();	
				}
				$this->config['state'] = 'clean';
				break;

			case 'memcache':
				if (!$data_store instanceof Memcache && !$data_store instanceof Memcached) {
					throw new fProgrammerException(
						'The data store provided is not a valid %s or %s object',
						'Memcache',
						'Memcached'
					);
				}
				$this->data_store = $data_store;
				break;
			
			case 'redis':
				if (!$data_store instanceof Redis) {
					throw new fProgrammerException(
						'The data store provided is not a valid %s object',
						'Redis'
					);
				}
				$this->data_store = $data_store;
				break;

			case 'apc':
			case 'xcache':
				if (!extension_loaded($type)) {
					throw new fEnvironmentException(
						'The %s extension does not appear to be installed',
						$type
					);	
				}
				break;
				
			default:
				throw new fProgrammerException(
					'The type specified, %s, is not a valid cache type. Must be one of: %s.',
					$type,
					join(', ', array('apc', 'database', 'directory', 'file', 'memcache', 'redis', 'xcache'))
				);	
		}

		$this->config['serializer']   = isset($config['serializer'])   ? $config['serializer']   : 'serialize';
		$this->config['unserializer'] = isset($config['unserializer']) ? $config['unserializer'] : 'unserialize';

		$this->type = $type;				
	}
	
	
	/**
	 * Cleans up after the cache object
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	public function __destruct()
	{
		// Only sometimes clean the cache of expired values
		if (rand(0, 99) == 50) {
			$this->clean();
		}

		$this->save();
	}
	
	
	/**
	 * Tries to set a value to the cache, but stops if a value already exists
	 * 
	 * @param  string  $key    The key to store as, this should not exceed 250 characters
	 * @param  mixed   $value  The value to store, this will be serialized
	 * @param  integer $ttl    The number of seconds to keep the cache valid for, 0 for no limit
	 * @return boolean  If the key/value pair were added successfully
	 */
	public function add($key, $value, $ttl=0)
	{
		$value = $this->serialize($value);

		switch ($this->type) {
			case 'apc':
				return apc_add($key, $value, $ttl);
				
			case 'file':
				if (isset($this->data_store[$key]) && $this->data_store[$key]['expire'] && $this->data_store[$key]['expire'] >= time()) {
					return FALSE;	
				}
				$this->data_store[$key] = array(
					'value'  => $value,
					'expire' => (!$ttl) ? 0 : time() + $ttl
				);
				$this->config['state'] = 'dirty';
				return TRUE;
			
			case 'database':
				$res = $this->data_store->query(
					"SELECT %r FROM %r WHERE %r = %s",
					$this->config['key_column'],
					$this->config['table'],
					$this->config['key_column'],
					$key
				);
				if ($res->countReturnedRows()) {
					return FALSE;
				}
				try {
					$value_placeholder = $this->config['value_data_type'] == 'blob' ? '%l' : '%s';
					$this->data_store->query(
						"INSERT INTO %r (%r, %r, %r) VALUES (%s, " . $value_placeholder . ", %i)",
						$this->config['table'],
						$this->config['key_column'],
						$this->config['value_column'],
						$this->config['ttl_column'],
						$key,
						$value,
						(!$ttl) ? 0 : time() + $ttl
					);
					return TRUE;
				} catch (fSQLException $e) {
					return FALSE;
				}

			case 'directory':
				if (file_exists($this->config['path'] . $key)) {
					return FALSE;
				}
				$expiration_date = (!$ttl) ? 0 : time() + $ttl;
				file_put_contents(
					$this->config['path'] . $key,
					$expiration_date . "\n" . $value
				);
				return TRUE;
			
			case 'memcache':
				if ($ttl > 2592000) {
					$ttl = time() + 2592000;		
				}
				if ($this->data_store instanceof Memcache) {
					return $this->data_store->add($key, $value, 0, $ttl);
				}
				return $this->data_store->add($key, $value, $ttl);
			
			case 'redis':
				if (!$ttl) {
					return $this->data_store->setnx($key, $value);
				}
				if ($this->data_store->exists($key)) {
					return FALSE;
				}
				$this->data_store->setex($key, $ttl, $value);
				return TRUE;
			
			case 'xcache':
				if (xcache_isset($key)) {
					return FALSE;	
				}
				xcache_set($key, $value, $ttl);
				return TRUE;
		}		
	}


	/**
	 * Removes all cache entries that have expired
	 *
	 * @return void
	 */
	public function clean()
	{
		switch ($this->type) {
			case 'database':
				$this->data_store->query(
					"DELETE FROM %r WHERE %r != 0 AND %r < %i",
					$this->config['table'],
					$this->config['ttl_column'],
					$this->config['ttl_column'],
					time()
				);
				break;

			case 'directory':
				$clear_before = time();
				$files = array_diff(scandir($this->config['path']), array('.', '..'));
				foreach ($files as $file) {
					if (!file_exists($this->config['path'] . $file)) {
						continue;
					}
					$handle = fopen($this->config['path'] . $file, 'r');
					$expiration_date = trim(fgets($handle));
					fclose($handle);
					if ($expiration_date && $expiration_date < $clear_before) {
						unlink($this->config['path'] . $file);
					}
				}
				break;

			case 'file':
				$clear_before = time();
				foreach ($this->data_store as $key => $value) {
					if ($value['expire'] && $value['expire'] < $clear_before) {
						unset($this->data_store[$key]);	
						$this->config['state'] = 'dirty';
					}	
				}
				break;
		}
	}
	
	
	/**
	 * Clears the WHOLE cache of every key, use with caution!
	 * 
	 * xcache may require a login or password depending on your ini settings.
	 * 
	 * @return boolean  If the cache was successfully cleared
	 */
	public function clear()
	{
		switch ($this->type) {
			case 'apc':
				return apc_clear_cache('user');
			
			case 'database':
				$this->data_store->query(
					"DELETE FROM %r",
					$this->config['table']
				);
				return TRUE;
			
			case 'directory':
				$files = array_diff(scandir($this->config['path']), array('.', '..'));
				$success = TRUE;
				foreach ($files as $file) {
					$success = unlink($this->config['path'] . $file) && $success;
				}
				return $success;
				
			case 'file':
				$this->data_store = array();
				$this->config['state'] = 'dirty';
				return TRUE;
			
			case 'memcache':
				return $this->data_store->flush();
			
			case 'redis':
				return $this->data_store->flushDB();
			
			case 'xcache':
				fCore::startErrorCapture();
				xcache_clear_cache(XC_TYPE_VAR, 0);
				return (bool) fCore::stopErrorCapture();
		}			
	}
	
	
	/**
	 * Deletes a value from the cache
	 * 
	 * @param  string $key  The key to delete
	 * @return boolean  If the delete succeeded
	 */
	public function delete($key)
	{
		switch ($this->type) {
			case 'apc':
				return apc_delete($key);
				
			case 'database':
				return $this->data_store->query(
					"DELETE FROM %r WHERE %r = %s",
					$this->config['table'],
					$this->config['key_column'],
					$key
				)->countAffectedRows();
			
			case 'directory':
				return unlink($this->config['path'] . $key);

			case 'file':
				if (isset($this->data_store[$key])) {
					unset($this->data_store[$key]);
					$this->config['state'] = 'dirty';	
				}
				return TRUE;
			
			case 'memcache':
				return $this->data_store->delete($key, 0);
			
			case 'redis':
				return (bool) $this->data_store->delete($key);
			
			case 'xcache':
				return xcache_unset($key);
		}		
	}
	
	
	/**
	 * Returns a value from the cache
	 * 
	 * @param  string $key      The key to return the value for
	 * @param  mixed  $default  The value to return if the key did not exist
	 * @return mixed  The cached value or the default value if no cached value was found
	 */
	public function get($key, $default=NULL)
	{
		switch ($this->type) {
			case 'apc':
				$value = apc_fetch($key);
				if ($value === FALSE) { return $default; }
				break;

			case 'database':
				$res = $this->data_store->query(
					"SELECT %r FROM %r WHERE %r = %s AND (%r = 0 OR %r >= %i)",
					$this->config['value_column'],
					$this->config['table'],
					$this->config['key_column'],
					$key,
					$this->config['ttl_column'],
					$this->config['ttl_column'],
					time()
				);
				if (!$res->countReturnedRows()) { return $default; }
				$value = $res->fetchScalar();
				break;
			
			case 'directory':
				if (!file_exists($this->config['path'] . $key)) {
					return $default;
				}
				$handle = fopen($this->config['path'] . $key, 'r');
				$expiration_date = fgets($handle);
				if ($expiration_date != 0 && $expiration_date < time()) {
					return $default;
				}
				$value = '';
				while (!feof($handle)) {
					$value .= fread($handle, 524288);
				}
				fclose($handle);
				break;
				
			case 'file':
				if (isset($this->data_store[$key])) {
					$expire = $this->data_store[$key]['expire'];
					if (!$expire || $expire >= time()) {
						$value = $this->data_store[$key]['value'];
					} elseif ($expire) {
						unset($this->data_store[$key]);
						$this->config['state'] = 'dirty';
					}
				} 
				if (!isset($value)) {
					return $default;
				}
				break;
			
			case 'memcache':
				$value = $this->data_store->get($key);
				if ($value === FALSE) { return $default; }
				break;
			
			case 'redis':
				$value = $this->data_store->get($key);
				if ($value === FALSE) { return $default; }
				break;
			
			case 'xcache':
				$value = xcache_get($key);
				if ($value === FALSE) { return $default; }
		}
		
		return $this->unserialize($value);		
	}
	
	
	/**
	 * Only valid for `file` caches, saves the file to disk
	 * 
	 * @return void
	 */
	public function save()
	{
		if ($this->type != 'file' || $this->config['state'] == 'clean') {
			return;
		}
		
		file_put_contents($this->config['path'], serialize($this->data_store));
		$this->config['state'] = 'clean';	
	}


	/**
	 * Serializes a value before storing it in the cache
	 *
	 * @param mixed $value  The value to serialize
	 * @return string  The serialized value
	 */
	protected function serialize($value)
	{
		if ($this->config['serializer'] == 'string') {
			if (is_object($value) && method_exists($value, '__toString')) {
				return $value->__toString();
			}
			return (string) $value;
		}

		return call_user_func($this->config['serializer'], $value);
	}
	
	
	/**
	 * Sets a value to the cache, overriding any previous value
	 * 
	 * @param  string  $key    The key to store as, this should not exceed 250 characters
	 * @param  mixed   $value  The value to store, this will be serialized
	 * @param  integer $ttl    The number of seconds to keep the cache valid for, 0 for no limit
	 * @return boolean  If the value was successfully saved
	 */
	public function set($key, $value, $ttl=0)
	{
		$value = $this->serialize($value);

		switch ($this->type) {
			case 'apc':
				return apc_store($key, $value, $ttl);
				
			case 'database':
				$res = $this->data_store->query(
					"SELECT %r FROM %r WHERE %r = %s",
					$this->config['key_column'],
					$this->config['table'],
					$this->config['key_column'],
					$key
				);

				$expiration_date = (!$ttl) ? 0 : time() + $ttl;

				try {
					$value_placeholder = $this->config['value_data_type'] == 'blob' ? '%l' : '%s';
					if (!$res->countReturnedRows()) {
						$this->data_store->query(
							"INSERT INTO %r (%r, %r, %r) VALUES (%s, " . $value_placeholder . ", %i)",
							$this->config['table'],
							$this->config['key_column'],
							$this->config['value_column'],
							$this->config['ttl_column'],
							$key,
							$value,
							$expiration_date
						);
					} else {
						$this->data_store->query(
							"UPDATE %r SET %r = " . $value_placeholder . ", %r = %s WHERE %r = %s",
							$this->config['table'],
							$this->config['value_column'],
							$value,
							$this->config['ttl_column'],
							$expiration_date,
							$this->config['key_column'],
							$key
						);
					}
				} catch (fSQLException $e) {
					return FALSE;	
				}
				return TRUE;
			
			case 'directory':
				$expiration_date = (!$ttl) ? 0 : time() + $ttl;
				return (bool) file_put_contents(
					$this->config['path'] . $key,
					$expiration_date . "\n" . $value
				);

			case 'file':
				$this->data_store[$key] = array(
					'value'  => $value,
					'expire' => (!$ttl) ? 0 : time() + $ttl
				);
				$this->config['state'] = 'dirty';
				return TRUE;
			
			case 'memcache':
				if ($ttl > 2592000) {
					$ttl = time() + 2592000;
				}
				if ($this->data_store instanceof Memcache) {
					$result = $this->data_store->replace($key, $value, 0, $ttl);
					if (!$result) {
						return $this->data_store->set($key, $value, 0, $ttl);
					}
					return $result;
				}
				return $this->data_store->set($key, $value, $ttl);
			
			case 'redis':
				if ($ttl) {
					return $this->data_store->setex($key, $value, $ttl);
				}
				return $this->data_store->set($key, $value);
			
			case 'xcache':
				return xcache_set($key, $value, $ttl);
		}				
	}


	/**
	 * Unserializes a value before returning it
	 *
	 * @param string $value  The serialized value
	 * @return mixed  The PHP value
	 */
	protected function unserialize($value)
	{
		if ($this->config['unserializer'] == 'string') {
			return $value;
		}

		return call_user_func($this->config['unserializer'], $value);
	}
}



/**
 * Copyright (c) 2009-2011 Will Bond <will@flourishlib.com>
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
