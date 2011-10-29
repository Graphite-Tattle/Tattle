<?php
/**
 * Provides a common API for different databases - will automatically use any installed extension
 * 
 * This class is implemented to use the UTF-8 character encoding. Please see
 * http://flourishlib.com/docs/UTF-8 for more information.
 * 
 * The following databases are supported:
 * 
 *  - [http://ibm.com/db2 DB2]
 *  - [http://microsoft.com/sql/ MSSQL]
 *  - [http://mysql.com MySQL]
 *  - [http://oracle.com Oracle]
 *  - [http://postgresql.org PostgreSQL]
 *  - [http://sqlite.org SQLite]
 * 
 * The class will automatically use the first of the following extensions it finds:
 * 
 *  - DB2
 *   - [http://php.net/ibm_db2 ibm_db2]
 *   - [http://php.net/pdo_ibm pdo_ibm]
 *  - MSSQL
 *   - [http://msdn.microsoft.com/en-us/library/cc296221.aspx sqlsrv]
 *   - [http://php.net/pdo_dblib pdo_dblib]
 *   - [http://php.net/mssql mssql] (or [http://php.net/sybase sybase])
 *  - MySQL
 *   - [http://php.net/mysql mysql]
 *   - [http://php.net/mysqli mysqli]
 *   - [http://php.net/pdo_mysql pdo_mysql]
 *  - Oracle
 *   - [http://php.net/oci8 oci8]
 *   - [http://php.net/pdo_oci pdo_oci]
 *  - PostgreSQL
 *   - [http://php.net/pgsql pgsql]
 *   - [http://php.net/pdo_pgsql pdo_pgsql]
 *  - SQLite
 *   - [http://php.net/pdo_sqlite pdo_sqlite] (for v3.x)
 *   - [http://php.net/sqlite sqlite] (for v2.x)
 * 
 * The `odbc` and `pdo_odbc` extensions are not supported due to character
 * encoding and stability issues on Windows, and functionality on non-Windows
 * operating systems.
 * 
 * @copyright  Copyright (c) 2007-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fDatabase
 * 
 * @version    1.0.0b40
 * @changes    1.0.0b40  Fixed a bug with notices being triggered when failing to connect to a SQLite database [wb, 2011-06-20]
 * @changes    1.0.0b39  Fixed a bug with detecting some MySQL database version numbers [wb, 2011-05-24]
 * @changes    1.0.0b38  Backwards Compatibility Break - callbacks registered to the `extracted` hook via ::registerHookCallback() no longer receive the `$strings` parameter, instead all strings are added into the `$values` parameter - added ::getVersion(), fixed a bug with SQLite messaging, fixed a bug with ::__destruct(), improved handling of transactional queries, added ::close(), enhanced class to throw four different exceptions for different connection errors, silenced PHP warnings upon connection error [wb, 2011-05-09]
 * @changes    1.0.0b37  Fixed usage of the mysqli extension to only call mysqli_set_charset() if it exists [wb, 2011-03-04]
 * @changes    1.0.0b36  Updated ::escape() and methods that use ::escape() to handle float values that don't contain a digit before or after the . [wb, 2011-02-01]
 * @changes    1.0.0b35  Updated the class to replace `LIMIT` and `OFFSET` value placeholders in the SQL with their values before translating since most databases that translate `LIMIT` statements need to move or add values together [wb, 2011-01-11]
 * @changes    1.0.0b34  Fixed a bug with creating translated prepared statements [wb, 2011-01-09]
 * @changes    1.0.0b33  Added code to explicitly set the connection encoding for the mysql and mysqli extensions since some PHP installs don't see to fully respect `SET NAMES` [wb, 2010-12-06]
 * @changes    1.0.0b32  Fixed handling auto-incrementing values for Oracle when the trigger was on `INSERT OR UPDATE` instead of just `INSERT` [wb, 2010-12-04]
 * @changes    1.0.0b31  Fixed handling auto-incrementing values for MySQL when the `INTO` keyword is left out of an `INSERT` statement [wb, 2010-11-04]
 * @changes    1.0.0b30  Fixed the pgsql, mssql and mysql extensions to force a new connection instead of reusing an existing one [wb, 2010-08-17]
 * @changes    1.0.0b29  Backwards Compatibility Break - removed ::enableSlowQueryWarnings(), added ability to replicate via ::registerHookCallback() [wb, 2010-08-10]
 * @changes    1.0.0b28  Backwards Compatibility Break - removed ODBC support. Added support for the `pdo_ibm` extension. [wb, 2010-07-31]
 * @changes    1.0.0b27  Fixed a bug with running multiple copies of a SQL statement with string values through a single ::translatedQuery() call [wb, 2010-07-14]
 * @changes    1.0.0b26  Updated the class to use new fCore functionality [wb, 2010-07-05]
 * @changes    1.0.0b25  Added IBM DB2 support [wb, 2010-04-13]
 * @changes    1.0.0b24  Fixed an auto-incrementing transaction bug with Oracle and debugging issues with all databases [wb, 2010-03-17]
 * @changes    1.0.0b23  Resolved another bug with capturing auto-incrementing values for PostgreSQL and Oracle [wb, 2010-03-15]
 * @changes    1.0.0b22  Changed ::clearCache() to also clear the cache on the fSQLTranslation [wb, 2010-03-09]
 * @changes    1.0.0b21  Added ::execute() for result-less SQL queries, ::prepare() and ::translatedPrepare() to create fStatement objects for prepared statements, support for prepared statements in ::query() and ::unbufferedQuery(), fixed default caching key for ::enableCaching() [wb, 2010-03-02]
 * @changes    1.0.0b20  Added a parameter to ::enableCaching() to provide a key token that will allow cached values to be shared between multiple databases with the same schema [wb, 2009-10-28]
 * @changes    1.0.0b19  Added support for escaping identifiers (column and table names) to ::escape(), added support for database schemas, rewrote internal SQL string spliting [wb, 2009-10-22]
 * @changes    1.0.0b18  Updated the class for the new fResult and fUnbufferedResult APIs, fixed ::unescape() to not touch NULLs [wb, 2009-08-12]
 * @changes    1.0.0b17  Added the ability to pass an array of all values as a single parameter to ::escape() instead of one value per parameter [wb, 2009-08-11]
 * @changes    1.0.0b16  Fixed PostgreSQL and Oracle from trying to get auto-incrementing values on inserts when explicit values were given [wb, 2009-08-06]
 * @changes    1.0.0b15  Fixed a bug where auto-incremented values would not be detected when table names were quoted [wb, 2009-07-15]
 * @changes    1.0.0b14  Changed ::determineExtension() and ::determineCharacterSet() to be protected instead of private [wb, 2009-07-08]
 * @changes    1.0.0b13  Updated ::escape() to accept arrays of values for insertion into full SQL strings [wb, 2009-07-06]
 * @changes    1.0.0b12  Updates to ::unescape() to improve performance [wb, 2009-06-15]
 * @changes    1.0.0b11  Changed replacement values in preg_replace() calls to be properly escaped [wb, 2009-06-11]
 * @changes    1.0.0b10  Changed date/time/timestamp escaping from `strtotime()` to fDate/fTime/fTimestamp for better localization support [wb, 2009-06-01]
 * @changes    1.0.0b9   Fixed a bug with ::escape() where floats that start with a . were encoded as `NULL` [wb, 2009-05-09]
 * @changes    1.0.0b8   Added Oracle support, change PostgreSQL code to no longer cause lastval() warnings, added support for arrays of values to ::escape() [wb, 2009-05-03]
 * @changes    1.0.0b7   Updated for new fCore API [wb, 2009-02-16]
 * @changes    1.0.0b6   Fixed a bug with executing transaction queries when using the mysqli extension [wb, 2009-02-12]
 * @changes    1.0.0b5   Changed @ error suppression operator to `error_reporting()` calls [wb, 2009-01-26]
 * @changes    1.0.0b4   Added a few error suppression operators back in so that developers don't get errors and exceptions [wb, 2009-01-14]
 * @changes    1.0.0b3   Removed some unnecessary error suppresion operators [wb, 2008-12-11]
 * @changes    1.0.0b2   Fixed a bug with PostgreSQL when using the PDO extension and executing an INSERT statement [wb, 2008-12-11]
 * @changes    1.0.0b    The initial implementation [wb, 2007-09-25]
 */
class fDatabase
{
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
	 * An fCache object to cache the schema info to
	 * 
	 * @var fCache
	 */
	private $cache;
	
	/**
	 * The cache prefix to use for cache entries
	 * 
	 * @var string
	 */
	private $cache_prefix;
	
	/**
	 * Database connection resource or PDO object
	 * 
	 * @var mixed
	 */
	private $connection;
	
	/**
	 * The database name
	 * 
	 * @var string
	 */
	private $database;
	
	/**
	 * If debugging is enabled
	 * 
	 * @var boolean
	 */
	private $debug;
	
	/**
	 * A temporary error holder for the mssql extension
	 * 
	 * @var string
	 */
	private $error;
	
	/**
	 * The extension to use for the database specified
	 * 
	 * Options include:
	 * 
	 *  - `'ibm_db2'`
	 *  - `'mssql'`
	 *  - `'mysql'`
	 *  - `'mysqli'`
	 *  - `'oci8'`
	 *  - `'pgsql'`
	 *  - `'sqlite'`
	 *  - `'sqlsrv'`
	 *  - `'pdo'`
	 * 
	 * @var string
	 */
	protected $extension;
	
	/**
	 * Hooks callbacks to be used for accessing and modifying queries
	 * 
	 * This array will have the structure:
	 * 
	 * {{{
	 * array(
	 *     'unmodified' => array({callbacks}),
	 *     'extracted'  => array({callbacks}),
	 *     'run'        => array({callbacks})
	 * )
	 * }}}
	 * 
	 * @var array
	 */
	private $hook_callbacks;
	
	/**
	 * The host the database server is located on
	 * 
	 * @var string
	 */
	private $host;
	
	/**
	 * If a transaction is in progress
	 * 
	 * @var boolean
	 */
	private $inside_transaction;
	
	/**
	 * The password for the user specified
	 * 
	 * @var string
	 */
	private $password;
	
	/**
	 * The port number for the host
	 * 
	 * @var string
	 */
	private $port;
	
	/**
	 * The total number of seconds spent executing queries
	 * 
	 * @var float
	 */
	private $query_time;
	
	/**
	 * A cache of database-specific code
	 * 
	 * @var array 
	 */
	protected $schema_info;
	
	/**
	 * The last executed fStatement object
	 * 
	 * @var fStatement
	 */
	private $statement;

	/**
	 * The timeout for the database connection
	 *
	 * @var integer
	 */
	private $timeout;
	
	/**
	 * The fSQLTranslation object for this database
	 * 
	 * @var object
	 */
	private $translation;
	
	/**
	 * The database type: `'db2'`, `'mssql'`, `'mysql'`, `'oracle'`, `'postgresql'`, or `'sqlite'`
	 * 
	 * @var string
	 */
	private $type;
	
	/**
	 * The unbuffered query instance
	 * 
	 * @var fUnbufferedResult
	 */
	private $unbuffered_result;
	
	/**
	 * The user to connect to the database as
	 * 
	 * @var string
	 */
	private $username;
	
	
	/**
	 * Configures the connection to a database - connection is not made until the first query is executed
	 *
	 * Passing `NULL` to any parameter other than `$type` and `$database` will
	 * cause the default value to be used.
	 * 
	 * @param  string  $type      The type of the database: `'db2'`, `'mssql'`, `'mysql'`, `'oracle'`, `'postgresql'`, `'sqlite'`
	 * @param  string  $database  Name of the database. If SQLite the path to the database file.
	 * @param  string  $username  Database username - not used for SQLite
	 * @param  string  $password  The password for the username specified - not used for SQLite
	 * @param  string  $host      Database server host or IP, defaults to localhost - not used for SQLite. MySQL socket connection can be made by entering `'sock:'` followed by the socket path. PostgreSQL socket connection can be made by passing just `'sock:'`. 
	 * @param  integer $port      The port to connect to, defaults to the standard port for the database type specified - not used for SQLite
	 * @param  integer $timeout   The number of seconds to timeout after if a connection can not be made - not used for SQLite
	 * @return fDatabase
	 */
	public function __construct($type, $database, $username=NULL, $password=NULL, $host=NULL, $port=NULL, $timeout=NULL)
	{
		$valid_types = array('db2', 'mssql', 'mysql', 'oracle', 'postgresql', 'sqlite');
		if (!in_array($type, $valid_types)) {
			throw new fProgrammerException(
				'The database type specified, %1$s, is invalid. Must be one of: %2$s.',
				$type,
				join(', ', $valid_types)
			);
		}
		
		if (empty($database)) {
			throw new fProgrammerException('No database was specified');
		}
		
		if ($host === NULL) {
			$host = 'localhost';
		}
		
		$this->type     = $type;
		$this->database = $database;
		$this->username = $username;
		$this->password = $password;
		$this->host     = $host;
		$this->port     = $port;
		$this->timeout  = $timeout;
		
		$this->hook_callbacks = array(
			'unmodified' => array(),
			'extracted'  => array(),
			'run'        => array()
		);
		
		$this->schema_info = array();
		
		$this->determineExtension();
	}
	
	
	/**
	 * Closes the open database connection
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	public function __destruct()
	{
		if (!$this->connection) { return; }
		
		fCore::debug('Total query time: ' . $this->query_time . ' seconds', $this->debug);
		if ($this->extension == 'ibm_db2') {
			db2_close($this->connection);
		} elseif ($this->extension == 'mssql') {
			mssql_close($this->connection);
		} elseif ($this->extension == 'mysql') {
			mysql_close($this->connection);
		} elseif ($this->extension == 'mysqli') {
			// Before 5.2.0 the destructor order would cause mysqli to
			// close itself which would make this call trigger a warning
			if (fCore::checkVersion('5.2.0')) {
				mysqli_close($this->connection);
			}
		} elseif ($this->extension == 'oci8') {
			oci_close($this->connection);
		} elseif ($this->extension == 'pgsql') {
			pg_close($this->connection);
		} elseif ($this->extension == 'sqlite') {
			sqlite_close($this->connection);
		} elseif ($this->extension == 'sqlsrv') {
			sqlsrv_close($this->connection);
		} elseif ($this->extension == 'pdo') {
			// PDO objects close their own connections when destroyed
		}

		$this->connection = FALSE;
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
	 * Checks to see if an SQL error occured
	 * 
	 * @param  fResult|fUnbufferedResult|boolean $result      The result object for the query
	 * @param  mixed                             $extra_info  The sqlite extension will pass a string error message, the oci8 extension will pass the statement resource
	 * @param  string                            $sql         The SQL that was executed
	 * @return void
	 */
	private function checkForError($result, $extra_info=NULL, $sql=NULL)
	{
		if ($result === FALSE || $result->getResult() === FALSE) {
			
			if ($this->extension == 'ibm_db2') {
				if (is_resource($extra_info)) {
					$message = db2_stmt_errormsg($extra_info);
				} else {
					$message = db2_stmt_errormsg();
				}
			} elseif ($this->extension == 'mssql') {
				$message = $this->error;
				$this->error = '';

			} elseif ($this->extension == 'mysql') {
				$message = mysql_error($this->connection);
			} elseif ($this->extension == 'mysqli') {
				if (is_object($extra_info)) {
					$message = $extra_info->error;	
				} else {
					$message = mysqli_error($this->connection);
				}
			} elseif ($this->extension == 'oci8') {
				$error_info = oci_error($extra_info ? $extra_info : $this->connection);
				$message = $error_info['message'];
			} elseif ($this->extension == 'pgsql') {
				$message = pg_last_error($this->connection);
			} elseif ($this->extension == 'sqlite') {
				if ($extra_info === NULL) {
					$message = sqlite_error_string(sqlite_last_error($this->connection));
				} else {
					$message = $extra_info;
				}
			} elseif ($this->extension == 'sqlsrv') {
				$error_info = sqlsrv_errors(SQLSRV_ERR_ALL);
				$message = $error_info[0]['message'];
			} elseif ($this->extension == 'pdo') {
				if ($extra_info instanceof PDOStatement) {
					$error_info = $extra_info->errorInfo();
				} else {
					$error_info = $this->connection->errorInfo();
				}
				
				if (empty($error_info[2])) {
					$error_info[2] = 'Unknown error - this usually indicates a bug in the PDO driver';	
				}
				$message = $error_info[2];
			}
			
			$db_type_map = array(
				'db2'        => 'DB2',
				'mssql'      => 'MSSQL',
				'mysql'      => 'MySQL',
				'oracle'     => 'Oracle',
				'postgresql' => 'PostgreSQL',
				'sqlite'     => 'SQLite'
			);
			
			throw new fSQLException(
				'%1$s error (%2$s) in %3$s',
				$db_type_map[$this->type],
				$message,
				is_object($result) ? $result->getSQL() : $sql
			);
		}
	}
	
	
	/**
	 * Clears all of the schema info out of the object and, if set, the fCache object
	 * 
	 * @return void
	 */
	public function clearCache()
	{
		$this->schema_info = array();
		if ($this->cache) {
			$this->cache->delete($this->makeCachePrefix() . 'schema_info');
		}
		if ($this->type == 'mssql') {
			$this->determineCharacterSet();		
		}
		if ($this->translation) {
			$this->translation->clearCache();	
		}
	}


	/**
	 * Closes the database connection
	 *
	 * @return void
	 */
	public function close()
	{
		$this->__destruct();
	}
	
	
	/**
	 * Connects to the database specified, if no connection exists
	 *
	 * This method is only intended to force a connection, all operations that
	 * require a database connection will automatically call this method.
	 * 
	 * @throws fAuthorizationException  When the username and password are not accepted
	 *
	 * @return void
	 */
	public function connect()
	{
		// Don't try to reconnect if we are already connected
		if ($this->connection) { return; }

		$connection_error     = FALSE;
		$authentication_error = FALSE;
		$database_error       = FALSE;

		$errors = NULL;

		// Establish a connection to the database
		if ($this->extension == 'pdo') {
			$username = $this->username;
			$password = $this->password;
			$options  = array();
			if ($this->timeout !== NULL && $this->type != 'sqlite' && $this->type != 'mssql') {
				$options[PDO::ATTR_TIMEOUT] = $this->timeout;
			}

			if ($this->type == 'db2') {
				if ($this->host === NULL && $this->port === NULL) {
					$dsn = 'ibm:DSN:' . $this->database;
				} else {
					$dsn  = 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=' . $this->database . ';HOSTNAME=' . $this->host . ';';
					$dsn .= 'PORT=' . ($this->port ? $this->port : 60000) . ';';
					$dsn .= 'PROTOCOL=TCPIP;UID=' . $username . ';PWD=' . $password . ';';
					if ($this->timeout !== NULL) {
						$dsn .= 'CONNECTTIMEOUT=' . $this->timeout . ';';
					}
					$username = NULL;
					$password = NULL;
				}
				
			} elseif ($this->type == 'mssql') {
				$separator = (fCore::checkOS('windows')) ? ',' : ':';
				$port      = ($this->port) ? $separator . $this->port : '';
				$driver    = (fCore::checkOs('windows')) ? 'mssql' : 'dblib';
				$dsn       = $driver . ':host=' . $this->host . $port . ';dbname=' . $this->database;
				
				// This driver does not support timeouts so we fake it here
				if ($this->timeout !== NULL) {
					fCore::startErrorCapture();
					$resource = fsockopen($this->host, $this->port ? $this->port : 1433, $errno, $errstr, $this->timeout);
					$errors = fCore::stopErrorCapture();
					if ($resource !== FALSE) {
						fclose($resource);
					}
				}
				
			} elseif ($this->type == 'mysql') {
				if (substr($this->host, 0, 5) == 'sock:') {
					$dsn = 'mysql:unix_socket=' . substr($this->host, 5) . ';dbname=' . $this->database;	
				} else {
					$port = ($this->port) ? ';port=' . $this->port : '';
					$dsn  = 'mysql:host=' . $this->host . ';dbname=' . $this->database . $port;
				}
				
			} elseif ($this->type == 'oracle') {
				$port = ($this->port) ? ':' . $this->port : '';
				$dsn  = 'oci:dbname=' . $this->host . $port . '/' . $this->database . ';charset=AL32UTF8';

				// This driver does not support timeouts so we fake it here
				if ($this->timeout !== NULL) {
					fCore::startErrorCapture();
					$resource = fsockopen($this->host, $this->port ? $this->port : 1521, $errno, $errstr, $this->timeout);
					$errors = fCore::stopErrorCapture();
					if ($resource !== FALSE) {
						fclose($resource);
					}
				}
				
			} elseif ($this->type == 'postgresql') {
				
				$dsn = 'pgsql:dbname=' . $this->database;
				if ($this->host && $this->host != 'sock:') {
					$dsn .= ' host=' . $this->host;	
				}
				if ($this->port) {
					$dsn .= ' port=' . $this->port;	
				}
				
			} elseif ($this->type == 'sqlite') {
				$dsn = 'sqlite:' . $this->database;
			}
			
			try {
				if ($errors) {
					$this->connection = FALSE;
				} else {
					$this->connection = new PDO($dsn, $username, $password, $options);	
					if ($this->type == 'mysql') {
						$this->connection->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, 1);	
					}
				}

			} catch (PDOException $e) {
				$this->connection = FALSE;

				$errors = $e->getMessage();
			}
		}
		
		if ($this->extension == 'sqlite') {
			$this->connection = sqlite_open($this->database);
		}
		
		if ($this->extension == 'ibm_db2') {
			$username = $this->username;
			$password = $this->password;
			if ($this->host === NULL && $this->port === NULL && $this->timeout === NULL) {
				$connection_string = $this->database;
			} else {
				$connection_string  = 'DATABASE=' . $this->database . ';HOSTNAME=' . $this->host . ';';
				$connection_string .= 'PORT=' . ($this->port ? $this->port : 60000) . ';';
				$connection_string .= 'PROTOCOL=TCPIP;UID=' . $this->username . ';PWD=' . $this->password . ';';
				if ($this->timeout !== NULL) {
					$connection_string .= 'CONNECTTIMEOUT=' . $this->timeout . ';';
				}
				$username = NULL;
				$password = NULL;
			}
			$options = array(
				'autocommit'    => DB2_AUTOCOMMIT_ON,
				'DB2_ATTR_CASE' => DB2_CASE_LOWER
			);
			$this->connection = db2_connect($connection_string, $username, $password, $options);
			if ($this->connection === FALSE) {
				$errors = db2_conn_errormsg();
			}
		}
		
		if ($this->extension == 'mssql') {
			if ($this->timeout !== NULL) {
				$old_timeout = ini_get('mssql.connect_timeout');
				ini_set('mssql.connect_timeout', $this->timeout);
			}

			fCore::startErrorCapture();
			
			$separator        = (fCore::checkOS('windows')) ? ',' : ':';
			$this->connection = mssql_connect(($this->port) ? $this->host . $separator . $this->port : $this->host, $this->username, $this->password, TRUE);

			if ($this->connection !== FALSE && mssql_select_db($this->database, $this->connection) === FALSE) {
				$this->connection = FALSE;
			}

			$errors = fCore::stopErrorCapture();

			if ($this->timeout !== NULL) {
				ini_set('mssql.connect_timeout', $old_timeout);
			}
		}
		
		if ($this->extension == 'mysql') {
			if ($this->timeout !== NULL) {
				$old_timeout = ini_get('mysql.connect_timeout');
				ini_set('mysql.connect_timeout', $this->timeout);
			}

			if (substr($this->host, 0, 5) == 'sock:') {
				$host = substr($this->host, 4);
			} elseif ($this->port) {
				$host = $this->host . ':' . $this->port;	
			} else {
				$host = $this->host;	
			}
			
			fCore::startErrorCapture();
			
			$this->connection = mysql_connect($host, $this->username, $this->password, TRUE);

			$errors = fCore::stopErrorCapture();

			if ($this->connection !== FALSE && mysql_select_db($this->database, $this->connection) === FALSE) {
				$errors = 'Unknown database';
				$this->connection = FALSE;
			}

			if ($this->connection && function_exists('mysql_set_charset') && !mysql_set_charset('utf8', $this->connection)) {
                throw new fConnectivityException(
                	'There was an error setting the database connection to use UTF-8'
				);
            }

            if ($this->timeout !== NULL) {
				ini_set('mysql.connect_timeout', $old_timeout);
			}
		}
			
		if ($this->extension == 'mysqli') {
			$this->connection = mysqli_init();
			if ($this->timeout !== NULL) {
				mysqli_options($this->connection, MYSQLI_OPT_CONNECT_TIMEOUT, $this->timeout);
			}

			fCore::startErrorCapture();

			if (substr($this->host, 0, 5) == 'sock:') {
				$result = mysqli_real_connect($this->connection, 'localhost', $this->username, $this->password, $this->database, $this->port, substr($this->host, 5));
			} elseif ($this->port) {
				$result = mysqli_real_connect($this->connection, $this->host, $this->username, $this->password, $this->database, $this->port);
			} else {
				$result = mysqli_real_connect($this->connection, $this->host, $this->username, $this->password, $this->database);
			}
			if (!$result) {
				$this->connection = FALSE;
			}

			$errors = fCore::stopErrorCapture();
			
			if ($this->connection && function_exists('mysqli_set_charset') && !mysqli_set_charset($this->connection, 'utf8')) {
                throw new fConnectivityException(
                	'There was an error setting the database connection to use UTF-8'
                );
            }
		}
		
		if ($this->extension == 'oci8') {

			fCore::startErrorCapture();
			$resource = TRUE;

			// This driver does not support timeouts so we fake it here
			if ($this->timeout !== NULL) {
				$resource = fsockopen($this->host, $this->port ? $this->port : 1521, $errno, $errstr, $this->timeout);
				if ($resource !== FALSE) {
					fclose($resource);
					$resource = TRUE;
				} else {
					$this->connection = FALSE;
				}
			}

			if ($resource) {
				$this->connection = oci_connect($this->username, $this->password, $this->host . ($this->port ? ':' . $this->port : '') . '/' . $this->database, 'AL32UTF8');
			}

			$errors = fCore::stopErrorCapture();
		}
			
		if ($this->extension == 'pgsql') {
			$connection_string = "dbname='" . addslashes($this->database) . "'";
			if ($this->host && $this->host != 'sock:') {
				$connection_string .= " host='" . addslashes($this->host) . "'";	
			}
			if ($this->username) {
				$connection_string .= " user='" . addslashes($this->username) . "'";
			}
			if ($this->password) {
				$connection_string .= " password='" . addslashes($this->password) . "'";
			}
			if ($this->port) {
				$connection_string .= " port='" . $this->port . "'";
			}
			if ($this->timeout !== NULL) {
				$connection_string .= " connect_timeout='" . $this->timeout . "'";
			}

			fCore::startErrorCapture();

			$this->connection = pg_connect($connection_string, PGSQL_CONNECT_FORCE_NEW);

			$errors = fCore::stopErrorCapture();
		}
		
		if ($this->extension == 'sqlsrv') {
			$options = array(
				'Database' => $this->database
			);
			if ($this->username !== NULL) {
				$options['UID'] = $this->username;
			}
			if ($this->password !== NULL) {
				$options['PWD'] = $this->password;
			}
			if ($this->timeout !== NULL) {
				$options['LoginTimeout'] = $this->timeout;
			}

			$this->connection = sqlsrv_connect($this->host . ',' . $this->port, $options);

			if ($this->connection === FALSE) {
				$errors = sqlsrv_errors();
			}

			sqlsrv_configure('WarningsReturnAsErrors', 0);
		}
		
		// Ensure the connection was established
		if ($this->connection === FALSE) {
			$this->handleConnectionErrors($errors);
		}
		
		// Make MySQL act more strict and use UTF-8
		if ($this->type == 'mysql') {
			$this->execute("SET SQL_MODE = 'REAL_AS_FLOAT,PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE'");
			$this->execute("SET NAMES 'utf8'");
			$this->execute("SET CHARACTER SET utf8");
		}
		
		// Make SQLite behave like other DBs for assoc arrays
		if ($this->type == 'sqlite') {
			$this->execute('PRAGMA short_column_names = 1');
		}
		
		// Fix some issues with mssql
		if ($this->type == 'mssql') {
			if (!isset($this->schema_info['character_set'])) {
				$this->determineCharacterSet();
			}
			$this->execute('SET TEXTSIZE 65536');
			$this->execute('SET QUOTED_IDENTIFIER ON');
		}
		
		// Make PostgreSQL use UTF-8
		if ($this->type == 'postgresql') {
			$this->execute("SET NAMES 'UTF8'");
		}
		
		// Oracle has different date and timestamp defaults
		if ($this->type == 'oracle') {
			$this->execute("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'");
			$this->execute("ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
			$this->execute("ALTER SESSION SET NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS TZR'");
			$this->execute("ALTER SESSION SET NLS_TIME_FORMAT = 'HH24:MI:SS'");
			$this->execute("ALTER SESSION SET NLS_TIME_TZ_FORMAT = 'HH24:MI:SS TZR'");
		}
	}
	
	
	/**
	 * Determines the character set of a SQL Server database
	 * 
	 * @return void
	 */
	protected function determineCharacterSet()
	{
		$this->schema_info['character_set'] = 'WINDOWS-1252';
		$this->schema_info['character_set'] = $this->query("SELECT 'WINDOWS-' + CONVERT(VARCHAR, COLLATIONPROPERTY(CONVERT(NVARCHAR, DATABASEPROPERTYEX(DB_NAME(), 'Collation')), 'CodePage')) AS charset")->fetchScalar();
		if ($this->cache) {
			$this->cache->set($this->makeCachePrefix() . 'schema_info', $this->schema_info);	
		}
	}
	
	
	/**
	 * Figures out which extension to use for the database type selected
	 * 
	 * @return void
	 */
	protected function determineExtension()
	{
		switch ($this->type) {
			
			case 'db2':
				
				if (extension_loaded('ibm_db2')) {
					$this->extension = 'ibm_db2';
					
				} elseif (class_exists('PDO', FALSE) && in_array('ibm', PDO::getAvailableDrivers())) {
					$this->extension = 'pdo';
					
				} else {
					$type = 'DB2';
					$exts = 'ibm_db2, pdo_ibm';
				}
				break;
			
			case 'mssql':
			
				if (extension_loaded('sqlsrv')) {
					$this->extension = 'sqlsrv';
					
				} elseif (extension_loaded('mssql')) {
					$this->extension = 'mssql';
					
				} elseif (class_exists('PDO', FALSE) && (in_array('dblib', PDO::getAvailableDrivers()) || in_array('mssql', PDO::getAvailableDrivers()))) {
					$this->extension = 'pdo';
					
				} else {
					$type = 'MSSQL';
					$exts = 'mssql, sqlsrv, pdo_dblib (linux), pdo_mssql (windows)';
				}
				break;
			
			
			case 'mysql':
			
				if (extension_loaded('mysqli')) {
					$this->extension = 'mysqli';
					
				} elseif (class_exists('PDO', FALSE) && in_array('mysql', PDO::getAvailableDrivers())) {
					$this->extension = 'pdo';
					
				} elseif (extension_loaded('mysql')) {
					$this->extension = 'mysql';
					
				} else {
					$type = 'MySQL';
					$exts = 'mysql, pdo_mysql, mysqli';
				}
				break;
				
				
			case 'oracle':
				
				if (extension_loaded('oci8')) {
					$this->extension = 'oci8';
					
				} elseif (class_exists('PDO', FALSE) && in_array('oci', PDO::getAvailableDrivers())) {
					$this->extension = 'pdo';
					
				} else {
					$type = 'Oracle';
					$exts = 'oci8, pdo_oci';
				}
				break;
			
			
			case 'postgresql':
			
				if (extension_loaded('pgsql')) {
					$this->extension = 'pgsql';
					
				} elseif (class_exists('PDO', FALSE) && in_array('pgsql', PDO::getAvailableDrivers())) {
					$this->extension = 'pdo';
					
				} else {
					$type = 'PostgreSQL';
					$exts = 'pgsql, pdo_pgsql';
				}
				break;
				
				
			case 'sqlite':
			
				$sqlite_version = 0;
				
				if (file_exists($this->database)) {
					
					$database_handle  = fopen($this->database, 'r');
					$database_version = fread($database_handle, 64);
					fclose($database_handle);
					
					if (strpos($database_version, 'SQLite format 3') !== FALSE) {
						$sqlite_version = 3;
					} elseif (strpos($database_version, '** This file contains an SQLite 2.1 database **') !== FALSE) {
						$sqlite_version = 2;
					} else {
						throw new fConnectivityException(
							'The database specified does not appear to be a valid %1$s or %2$s database',
							'SQLite v2.1',
							'v3'
						);
					}
				}
				
				if ((!$sqlite_version || $sqlite_version == 3) && class_exists('PDO', FALSE) && in_array('sqlite', PDO::getAvailableDrivers())) {
					$this->extension = 'pdo';
					
				} elseif ($sqlite_version == 3 && (!class_exists('PDO', FALSE) || !in_array('sqlite', PDO::getAvailableDrivers()))) {
					throw new fEnvironmentException(
						'The database specified is an %1$s database and the %2$s extension is not installed',
						'SQLite v3',
						'pdo_sqlite'
					);
				
				} elseif ((!$sqlite_version || $sqlite_version == 2) && extension_loaded('sqlite')) {
					$this->extension = 'sqlite';
					
				} elseif ($sqlite_version == 2 && !extension_loaded('sqlite')) {
					throw new fEnvironmentException(
						'The database specified is an %1$s database and the %2$s extension is not installed',
						'SQLite v2.1',
						'sqlite'
					);
				
				} else {
					$type = 'SQLite';
					$exts = 'pdo_sqlite, sqlite';
				}
				break;
		}
		
		if (!$this->extension) {
			throw new fEnvironmentException(
				'The server does not have any of the following extensions for %2$s support: %2$s',
				$type,
				$exts
			);
		}
	}
	
	
	/**
	 * Sets the schema info to be cached to the fCache object specified
	 * 
	 * @param  fCache $cache      The cache to cache to
	 * @param  string $key_token  Internal use only! (this will be used in the cache key to uniquely identify the cache for this fDatabase object) 
	 * @return void
	 */
	public function enableCaching($cache, $key_token=NULL)
	{
		$this->cache = $cache;
		
		if ($key_token !== NULL) {
			$this->cache_prefix = 'fDatabase::' . $this->type . '::' . $key_token . '::';	
		}
		
		$this->schema_info = $this->cache->get($this->makeCachePrefix() . 'schema_info', array());
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
	 * Escapes a value for insertion into SQL
	 * 
	 * The valid data types are:
	 * 
	 *  - `'blob'`
	 *  - `'boolean'`
	 *  - `'date'`
	 *  - `'float'`
	 *  - `'identifier'`
	 *  - `'integer'`
	 *  - `'string'` (also varchar, char or text)
	 *  - `'varchar'`
	 *  - `'char'`
	 *  - `'text'`
	 *  - `'time'`
	 *  - `'timestamp'`
	 * 
	 * In addition to being able to specify the data type, you can also pass
	 * in an SQL statement with data type placeholders in the following form:
	 *   
	 *  - `%l` for a blob
	 *  - `%b` for a boolean
	 *  - `%d` for a date
	 *  - `%f` for a float
	 *  - `%r` for an indentifier (table or column name)
	 *  - `%i` for an integer
	 *  - `%s` for a string
	 *  - `%t` for a time
	 *  - `%p` for a timestamp
	 * 
	 * Depending on what `$sql_or_type` and `$value` are, the output will be
	 * slightly different. If `$sql_or_type` is a data type or a single
	 * placeholder and `$value` is:
	 * 
	 *  - a scalar value - an escaped SQL string is returned
	 *  - an array - an array of escaped SQL strings is returned
	 * 
	 * If `$sql_or_type` is a SQL string and `$value` is:
	 * 
	 *  - a scalar value - the escaped value is inserted into the SQL string
	 *  - an array - the escaped values are inserted into the SQL string separated by commas
	 * 
	 * If `$sql_or_type` is a SQL string, it is also possible to pass an array
	 * of all values as a single parameter instead of one value per parameter.
	 * An example would look like the following:
	 * 
	 * {{{
	 * #!php
	 * $db->escape(
	 *     "SELECT * FROM users WHERE status = %s AND authorization_level = %s",
	 *     array('Active', 'Admin')
	 * );
	 * }}}
	 * 
	 * @param  string $sql_or_type  This can either be the data type to escape or an SQL string with a data type placeholder - see method description
	 * @param  mixed  $value        The value to escape - both single values and arrays of values are supported, see method description for details
	 * @param  mixed  ...
	 * @return mixed  The escaped value/SQL or an array of the escaped values
	 */
	public function escape($sql_or_type, $value)
	{
		$values = array_slice(func_get_args(), 1);
		
		if (sizeof($values) < 1) {
			throw new fProgrammerException(
				'No value was specified to escape'
			);	
		}
		
		// Convert all objects into strings
		$values = $this->scalarize($values);
		$value  = array_shift($values);
		
		// Handle single value escaping
		$callback = NULL;
		
		switch ($sql_or_type) {
			case 'blob':
			case '%l':
				$callback = $this->escapeBlob;
				break;
			case 'boolean':
			case '%b':
				$callback = $this->escapeBoolean;
				break;
			case 'date':
			case '%d':
				$callback = $this->escapeDate;
				break;
			case 'float':
			case '%f':
				$callback = $this->escapeFloat;
				break;
			case 'identifier':
			case '%r':
				$callback = $this->escapeIdentifier;
				break;
			case 'integer':
			case '%i':
				$callback = $this->escapeInteger;
				break;
			case 'string':
			case 'varchar':
			case 'char':
			case 'text':
			case '%s':
				$callback = $this->escapeString;
				break;
			case 'time':
			case '%t':
				$callback = $this->escapeTime;
				break;
			case 'timestamp':
			case '%p':
				$callback = $this->escapeTimestamp;
				break;
		}
		
		if ($callback) {
			if (is_array($value)) {
				// If the values were passed as a single array, this handles that
				if (count($value) == 1 && is_array(current($value))) {
					$value = current($value);
				}
				return array_map($callback, $value);		
			}
			return call_user_func($callback, $value);
		}	
		
		// Separate the SQL from quoted values
		$parts = $this->splitSQL($sql_or_type, $placeholders);

		// If the values were passed as a single array, this handles that
		if (count($values) == 0 && is_array($value) && count($value) == $placeholders) {
			$values = $value;
			$value  = array_shift($values);	
		}

		array_unshift($values, $value);
		$sql = $this->extractStrings($parts, $values);
		return $this->escapeSQL($sql, $values, FALSE);
	}
	
	
	/**
	 * Escapes a blob for use in SQL, includes surround quotes when appropriate
	 * 
	 * A `NULL` value will be returned as `'NULL'`
	 * 
	 * @param  string $value  The blob to escape
	 * @return string  The escaped blob
	 */
	private function escapeBlob($value)
	{
		if ($value === NULL) {
			return 'NULL';
		}
		
		$this->connect();
		
		if ($this->type == 'db2') {
			return "BLOB(X'" . bin2hex($value) . "')";
			
		} elseif ($this->type == 'mysql') {
			return "x'" . bin2hex($value) . "'";
			
		} elseif ($this->type == 'postgresql') {
			$output = '';
			for ($i=0; $i<strlen($value); $i++) {
				$output .= '\\\\' . str_pad(decoct(ord($value[$i])), 3, '0', STR_PAD_LEFT);
			}
			return "E'" . $output . "'";
			
		} elseif ($this->extension == 'sqlite') {
			return "'" . bin2hex($value) . "'";
			
		} elseif ($this->type == 'sqlite') {
			return "X'" . bin2hex($value) . "'";
			
		} elseif ($this->type == 'mssql') {
			return '0x' . bin2hex($value);
			
		} elseif ($this->type == 'oracle') {
			return "'" . bin2hex($value) . "'";
		}
	}
	
	
	/**
	 * Escapes a boolean for use in SQL, includes surround quotes when appropriate
	 * 
	 * A `NULL` value will be returned as `'NULL'`
	 * 
	 * @param  boolean $value  The boolean to escape
	 * @return string  The database equivalent of the boolean passed
	 */
	private function escapeBoolean($value)
	{
		if ($value === NULL) {
			return 'NULL';
		}
		
		if (in_array($this->type, array('postgresql', 'mysql'))) {
			return ($value) ? 'TRUE' : 'FALSE';
		} elseif (in_array($this->type, array('mssql', 'sqlite', 'db2'))) {
			return ($value) ? "'1'" : "'0'";
		} elseif ($this->type == 'oracle') {
			return ($value) ? '1' : '0';	
		}
	}
	
	
	/**
	 * Escapes a date for use in SQL, includes surrounding quotes
	 * 
	 * A `NULL` or invalid value will be returned as `'NULL'`
	 * 
	 * @param  string $value  The date to escape
	 * @return string  The escaped date
	 */
	private function escapeDate($value)
	{
		if ($value === NULL) {
			return 'NULL';
		}
		
		try {
			$value = new fDate($value);
			return "'" . $value->format('Y-m-d') . "'";
			
		} catch (fValidationException $e) {
			return 'NULL';
		}
	}
	
	
	/**
	 * Escapes a float for use in SQL
	 * 
	 * A `NULL` value will be returned as `'NULL'`
	 * 
	 * @param  float $value  The float to escape
	 * @return string  The escaped float
	 */
	private function escapeFloat($value)
	{
		if ($value === NULL) {
			return 'NULL';
		}
		if (!strlen($value)) {
			return 'NULL';
		}
		if (!preg_match('#^[+\-]?([0-9]+(\.([0-9]+)?)?|(\.[0-9]+))$#D', $value)) {
			return 'NULL';
		}
		
		$value = rtrim($value, '.');
		$value = preg_replace('#(?<![0-9])\.#', '0.', $value);
		
		return (string) $value;
	}
	
	
	/**
	 * Escapes an identifier for use in SQL, necessary for reserved words
	 * 
	 * @param  string $value  The identifier to escape
	 * @return string  The escaped identifier
	 */
	private function escapeIdentifier($value)
	{
		$value = '"' . str_replace(
			array('"', '.'),
			array('',  '"."'),
			$value
		) . '"';
		if (in_array($this->type, array('oracle', 'db2'))) {
			$value = strtoupper($value);	
		}
		return $value;
	}
	
	
	/**
	 * Escapes an integer for use in SQL
	 * 
	 * A `NULL` or invalid value will be returned as `'NULL'`
	 * 
	 * @param  integer $value  The integer to escape
	 * @return string  The escaped integer
	 */
	private function escapeInteger($value)
	{
		if ($value === NULL) {
			return 'NULL';
		}
		if (!strlen($value)) {
			return 'NULL';
		}
		if (!preg_match('#^([+\-]?[0-9]+)(\.[0-9]*)?$#D', $value, $matches)) {
			return 'NULL';
		}
		return str_replace('+', '', $matches[1]);
	}
	
	
	/**
	 * Escapes a string for use in SQL, includes surrounding quotes
	 * 
	 * A `NULL` value will be returned as `'NULL'`.
	 * 
	 * @param  string $value  The string to escape
	 * @return string  The escaped string
	 */
	private function escapeString($value)
	{
		if ($value === NULL) {
			return 'NULL';
		}
		
		$this->connect();
		
		if ($this->type == 'db2') {
			return "'" . str_replace("'", "''", $value) . "'";
		} elseif ($this->extension == 'mysql') {
			return "'" . mysql_real_escape_string($value, $this->connection) . "'";
		} elseif ($this->extension == 'mysqli') {
			return "'" . mysqli_real_escape_string($this->connection, $value) . "'";
		} elseif ($this->extension == 'pgsql') {
			return "'" . pg_escape_string($value) . "'";
		} elseif ($this->extension == 'sqlite') {
			return "'" . sqlite_escape_string($value) . "'";
		} elseif ($this->type == 'oracle') {
			return "'" . str_replace("'", "''", $value) . "'";
			
		} elseif ($this->type == 'mssql') {
			
			// If there are any non-ASCII characters, we need to escape
			if (preg_match('#[^\x00-\x7F]#', $value)) {
				preg_match_all('#.|^\z#us', $value, $characters);
				$output    = "";
				$last_type = NULL;
				foreach ($characters[0] as $character) {
					if (strlen($character) > 1) {
						$b = array_map('ord', str_split($character));
						switch (strlen($character)) {
							case 2:
								$bin = substr(decbin($b[0]), 3) .
										   substr(decbin($b[1]), 2);
								break;
							
							case 3:
								$bin = substr(decbin($b[0]), 4) .
										   substr(decbin($b[1]), 2) .
										   substr(decbin($b[2]), 2);
								break;
							
							// If it is a 4-byte character, MSSQL can't store it
							// so instead store a ?
							default:
								$output .= '?';
								continue;
						}
						if ($last_type == 'nchar') {
							$output .= '+';
						} elseif ($last_type == 'char') {
							$output .= "'+";
						}		
						$output .= "NCHAR(" . bindec($bin) . ")";
						$last_type = 'nchar';
					} else {
						if (!$last_type) {
							$output .= "'";
						} elseif ($last_type == 'nchar') {
							$output .= "+'";	
						}
						$output .= $character;
						// Escape single quotes
						if ($character == "'") {
							$output .= "'";
						}
						$last_type = 'char';
					}
				}
				if ($last_type == 'char') {
					$output .= "'";
				} elseif (!$last_type) {
					$output .= "''";	
				}
			
			// ASCII text is normal
			} else {
				$output = "'" . str_replace("'", "''", $value) . "'";
			}
			
			# a \ before a \r\n has to be escaped with another \
			return preg_replace('#(?<!\\\\)\\\\(?=\r\n)#', '\\\\\\\\', $output);
		
		} elseif ($this->extension == 'pdo') {
			return $this->connection->quote($value);
		}
	}
	
	
	/**
	 * Takes a SQL string and an array of values and replaces the placeholders with the value
	 * 
	 * @param string  $sql               The SQL string containing placeholders
	 * @param array   $values            An array of values to escape into the SQL
	 * @param boolean $unescape_percent  If %% should be translated to % - this should only be done once processing of the string is done
	 * @return string  The SQL with the values escaped into it
	 */
	private function escapeSQL($sql, $values, $unescape_percent)
	{
		$original_sql = $sql;
		$pieces = preg_split('#(?<!%)(%[lbdfristp])\b#', $sql, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		
		$sql   = '';
		$value = array_shift($values);
		
		$missing_values = -1;
		
		foreach ($pieces as $piece) {
			switch ($piece) {
				case '%l':
					$callback = $this->escapeBlob;
					break;
				case '%b':
					$callback = $this->escapeBoolean;
					break;
				case '%d':
					$callback = $this->escapeDate;
					break;
				case '%f':
					$callback = $this->escapeFloat;
					break;
				case '%r':
					$callback = $this->escapeIdentifier;
					break;
				case '%i':
					$callback = $this->escapeInteger;
					break;
				case '%s':
					$callback = $this->escapeString;
					break;
				case '%t':
					$callback = $this->escapeTime;
					break;
				case '%p':
					$callback = $this->escapeTimestamp;
					break;
				default:
					if ($unescape_percent) {
						$piece = str_replace('%%', '%', $piece);
					}
					$sql .= $piece;
					continue 2;	
			}
			
			if (is_array($value)) {
				$sql .= join(', ', array_map($callback, $value));		
			} else {
				$sql .= call_user_func($callback, $value);
			}
					
			if (sizeof($values)) {
				$value = array_shift($values);
			} else {
				$value = NULL;
				$missing_values++;	
			}
		}
		
		if ($missing_values > 0) {
			throw new fProgrammerException(
				'%1$s value(s) are missing for the placeholders in: %2$s',
				$missing_values,
				$original_sql
			);	
		}
		
		if (sizeof($values)) {
			throw new fProgrammerException(
				'%1$s extra value(s) were passed for the placeholders in: %2$s',
				sizeof($values),
				$original_sql
			); 	
		}
		
		return $sql;
	}
	
	
	/**
	 * Escapes a time for use in SQL, includes surrounding quotes
	 * 
	 * A `NULL` or invalid value will be returned as `'NULL'`
	 * 
	 * @param  string $value  The time to escape
	 * @return string  The escaped time
	 */
	private function escapeTime($value)
	{
		if ($value === NULL) {
			return 'NULL';
		}
		
		try {
			$value = new fTime($value);
			
			if ($this->type == 'mssql' || $this->type == 'oracle') {
				return "'" . $value->format('1970-01-01 H:i:s') . "'";	
			}
			
			return "'" . $value->format('H:i:s') . "'";
			
		} catch (fValidationException $e) {
			return 'NULL';
		}
	}
	
	
	/**
	 * Escapes a timestamp for use in SQL, includes surrounding quotes
	 * 
	 * A `NULL` or invalid value will be returned as `'NULL'`
	 * 
	 * @param  string $value  The timestamp to escape
	 * @return string  The escaped timestamp
	 */
	private function escapeTimestamp($value)
	{
		if ($value === NULL) {
			return 'NULL';
		}
		
		try {
			$value = new fTimestamp($value);
			return "'" . $value->format('Y-m-d H:i:s') . "'";
			
		} catch (fValidationException $e) {
			return 'NULL';
		}
	}
	
	
	/**
	 * Executes one or more SQL queries without returning any results
	 * 
	 * @param  string|fStatement $statement  One or more SQL statements in a string or an fStatement prepared statement
	 * @param  mixed             $value      The optional value(s) to place into any placeholders in the SQL - see ::escape() for details
	 * @param  mixed             ...
	 * @return void
	 */
	public function execute($statement)
	{
		$args    = func_get_args();
		$params  = array_slice($args, 1);
		
		if (is_object($statement)) {
			return $this->run($statement, NULL, $params);	
		}
		
		$queries = $this->preprocess($statement, $params, FALSE);
		
		$output = array();
		foreach ($queries as $query) {
			$this->run($query);	
		}
	}


	/**
	 * Pulls quoted strings out into the values array for simpler processing
	 *
	 * @param  array $parts    The parts of the SQL - alternating SQL and quoted strings
	 * @param  array &$values  The value to be escaped into the SQL
	 * @return string  The SQL with all quoted string values extracted into the `$values` array
	 */
	private function extractStrings($parts, &$values)
	{
		$sql = '';

		$value_number = 0;
		foreach ($parts as $part) {
			// We leave blank strings in because Oracle treats them like NULL
			if ($part[0] == "'" && $part != "''") {
				$sql .= '%s';
				$value = str_replace("''", "'", substr($part, 1, -1));
				if ($this->type == 'postgresql') {
					$value = str_replace('\\\\', '\\', $value);
				}
				$values = array_merge(
					array_slice($values, 0, $value_number),
					array($value),
					array_slice($values, $value_number)
				);
				$value_number++;
			} else {
				$value_number += preg_match_all('#(?<!%)%[lbdfristp]\b#', $part, $trash);
				unset($trash);
				$sql .= $part;
			} 		
		}

		return $sql;
	}
	
	
	/**
	 * Returns the database connection resource or object
	 * 
	 * @return mixed  The database connection
	 */
	public function getConnection()
	{
		$this->connect();
		return $this->connection;
	}
	
	
	/**
	 * Gets the name of the database currently connected to
	 * 
	 * @return string  The name of the database currently connected to
	 */
	public function getDatabase()
	{
		return $this->database;
	}
	
	
	/**
	 * Gets the php extension being used
	 * 
	 * @internal
	 * 
	 * @return string  The php extension used for database interaction
	 */
	public function getExtension()
	{
		return $this->extension;
	}
	
	
	/**
	 * Gets the host for this database
	 * 
	 * @return string  The host
	 */
	public function getHost()
	{
		return $this->host;
	}
	
	
	/**
	 * Gets the port for this database
	 * 
	 * @return string  The port
	 */
	public function getPort()
	{
		return $this->port;
	}
	
	
	/**
	 * Gets the fSQLTranslation object used for translated queries
	 * 
	 * @return fSQLTranslation  The SQL translation object
	 */
	public function getSQLTranslation()
	{
		if (!$this->translation) { new fSQLTranslation($this); }
		return $this->translation;	
	}
	
	
	/**
	 * Gets the database type
	 * 
	 * @return string  The database type: `'mssql'`, `'mysql'`, `'postgresql'` or `'sqlite'`
	 */
	public function getType()
	{
		return $this->type;
	}
	
	
	/**
	 * Gets the username for this database
	 * 
	 * @return string  The username
	 */
	public function getUsername()
	{
		return $this->username;
	}


	/**
	 * Gets the version of the database system
	 * 
	 * @return string  The database system version
	 */
	public function getVersion()
	{
		if (isset($this->schema_info['version'])) {
			return $this->schema_info['version'];
		}

		switch ($this->type) {
			case 'db2':
				$sql = "SELECT REPLACE(service_level, 'DB2 v', '') FROM TABLE (sysproc.env_get_inst_info()) AS x";
				break;
			
			case 'mssql':
				$sql = "SELECT CAST(SERVERPROPERTY('ProductVersion') AS VARCHAR(500)) AS ProductVersion";
				break;

			case 'mysql':
				$sql = "SELECT version()";
				break;

			case 'oracle':
				$sql = "SELECT version FROM product_component_version";
				break;
			
			case 'postgresql':
				$sql = "SELECT regexp_replace(version(), E'^PostgreSQL +([0-9]+(\\\\.[0-9]+)*).*$', E'\\\\1')";
				break;

			case 'sqlite':
				$sql = "SELECT sqlite_version()";
				break;
		}

		$this->schema_info['version'] = preg_replace('#-?[a-z].*$#Di', '', $this->query($sql)->fetchScalar());
		return $this->schema_info['version'];
	}
	
	
	/**
	 * Will grab the auto incremented value from the last query (if one exists)
	 * 
	 * @param  fResult $result    The result object for the query
	 * @param  mixed   $resource  Only applicable for `pdo`, `oci8` and `sqlsrv` extentions or `mysqli` prepared statements - this is either the `PDOStatement` object, `mysqli_stmt` object or the `oci8` or `sqlsrv` resource
	 * @return void
	 */
	private function handleAutoIncrementedValue($result, $resource=NULL)
	{
		if (!preg_match('#^\s*INSERT\s+(?:INTO\s+)?(?:`|"|\[)?(["\w.]+)(?:`|"|\])?#i', $result->getSQL(), $table_match)) {
			$result->setAutoIncrementedValue(NULL);
			return;
		}
		$quoted_table = $table_match[1];
		$table        = str_replace('"', '', strtolower($table_match[1]));
		
		$insert_id = NULL;
		
		if ($this->type == 'oracle') {
			if (!isset($this->schema_info['sequences'])) {
				$sql = "SELECT
								LOWER(OWNER) AS \"SCHEMA\",
								LOWER(TABLE_NAME) AS \"TABLE\",
								TRIGGER_BODY
							FROM
								ALL_TRIGGERS
							WHERE
								TRIGGERING_EVENT LIKE 'INSERT%' AND
								STATUS = 'ENABLED' AND
								TRIGGER_NAME NOT LIKE 'BIN\$%' AND
								OWNER NOT IN (
									'SYS',
									'SYSTEM',
									'OUTLN',
									'ANONYMOUS',
									'AURORA\$ORB\$UNAUTHENTICATED',
									'AWR_STAGE',
									'CSMIG',
									'CTXSYS',
									'DBSNMP',
									'DIP',
									'DMSYS',
									'DSSYS',
									'EXFSYS',
									'FLOWS_020100',
									'FLOWS_FILES',
									'LBACSYS',
									'MDSYS',
									'ORACLE_OCM',
									'ORDPLUGINS',
									'ORDSYS',
									'PERFSTAT',
									'TRACESVR',
									'TSMSYS',
									'XDB'
								)";
								
				$this->schema_info['sequences'] = array();
				
				foreach ($this->query($sql) as $row) {
					if (preg_match('#SELECT\s+(["\w.]+).nextval\s+INTO\s+:new\.(\w+)\s+FROM\s+dual#i', $row['trigger_body'], $matches)) {
						$table_name = $row['table'];
						if ($row['schema'] != strtolower($this->username)) {
							$table_name = $row['schema'] . '.' . $table_name;	
						}
						$this->schema_info['sequences'][$table_name] = array('sequence' => $matches[1], 'column' => str_replace('"', '', $matches[2]));
					}
				}
				
				if ($this->cache) {
					$this->cache->set($this->makeCachePrefix() . 'schema_info', $this->schema_info);	
				}
			}
			
			if (!isset($this->schema_info['sequences'][$table]) || preg_match('#INSERT\s+INTO\s+"?' . preg_quote($quoted_table, '#') . '"?\s+\([^\)]*?(\b|")' . preg_quote($this->schema_info['sequences'][$table]['column'], '#') . '(\b|")#i', $result->getSQL())) {
				return;	
			}
			
			$insert_id_sql = "SELECT " . $this->schema_info['sequences'][$table]['sequence'] . ".currval AS INSERT_ID FROM dual";
		}
		
		if ($this->type == 'postgresql') {
			if (!isset($this->schema_info['sequences'])) {
				$sql = "SELECT
								pg_namespace.nspname AS \"schema\",
								pg_class.relname AS \"table\",
								pg_attribute.attname AS column
							FROM
								pg_attribute INNER JOIN
								pg_class ON pg_attribute.attrelid = pg_class.oid INNER JOIN
								pg_namespace ON pg_class.relnamespace = pg_namespace.oid INNER JOIN
								pg_attrdef ON pg_class.oid = pg_attrdef.adrelid AND pg_attribute.attnum = pg_attrdef.adnum
							WHERE
								NOT pg_attribute.attisdropped AND
								pg_attrdef.adsrc LIKE 'nextval(%'";
								
				$this->schema_info['sequences'] = array();
				
				foreach ($this->query($sql) as $row) {
					$table_name = strtolower($row['table']);
					if ($row['schema'] != 'public') {
						$table_name = $row['schema'] . '.' . $table_name;	
					}
					$this->schema_info['sequences'][$table_name] = $row['column'];
				}
				
				if ($this->cache) {
					$this->cache->set($this->makeCachePrefix() . 'schema_info', $this->schema_info);	
				}	
			}
			
			if (!isset($this->schema_info['sequences'][$table]) || preg_match('#INSERT\s+INTO\s+"?' . preg_quote($quoted_table, '#') . '"?\s+\([^\)]*?(\b|")' . preg_quote($this->schema_info['sequences'][$table], '#') . '(\b|")#i', $result->getSQL())) {
				return;
			} 		
		}
		
		if ($this->extension == 'ibm_db2') {
			$insert_id_res  = db2_exec($this->connection, "SELECT IDENTITY_VAL_LOCAL() FROM SYSIBM.SYSDUMMY1");
			$insert_id_row  = db2_fetch_assoc($insert_id_res);
			$insert_id      = current($insert_id_row);
			db2_free_result($insert_id_res);
		
		} elseif ($this->extension == 'mssql') {
			$insert_id_res = mssql_query("SELECT @@IDENTITY AS insert_id", $this->connection);
			$insert_id     = mssql_result($insert_id_res, 0, 'insert_id');
			mssql_free_result($insert_id_res);
		
		} elseif ($this->extension == 'mysql') {
			$insert_id     = mysql_insert_id($this->connection);
		
		} elseif ($this->extension == 'mysqli') {
			if (is_object($resource)) {
				$insert_id = mysqli_stmt_insert_id($resource);
			} else {
				$insert_id = mysqli_insert_id($this->connection);
			}
		
		} elseif ($this->extension == 'oci8') {
			$oci_statement = oci_parse($this->connection, $insert_id_sql);
			oci_execute($oci_statement, $this->inside_transaction ? OCI_DEFAULT : OCI_COMMIT_ON_SUCCESS);
			$insert_id_row = oci_fetch_array($oci_statement, OCI_ASSOC);
			$insert_id = $insert_id_row['INSERT_ID'];
			oci_free_statement($oci_statement);
		
		} elseif ($this->extension == 'pgsql') {
			
			$insert_id_res = pg_query($this->connection, "SELECT lastval()");
			$insert_id_row = pg_fetch_assoc($insert_id_res);
			$insert_id = array_shift($insert_id_row);
			pg_free_result($insert_id_res);
		
		} elseif ($this->extension == 'sqlite') {
			$insert_id = sqlite_last_insert_rowid($this->connection);
		
		} elseif ($this->extension == 'sqlsrv') {
			$insert_id_res = sqlsrv_query($this->connection, "SELECT @@IDENTITY AS insert_id");
			$insert_id_row = sqlsrv_fetch_array($insert_id_res, SQLSRV_FETCH_ASSOC);
			$insert_id     = $insert_id_row['insert_id'];
			sqlsrv_free_stmt($insert_id_res);
		
		} elseif ($this->extension == 'pdo') {
			
			switch ($this->type) {
				case 'db2':
					$insert_id_statement = $this->connection->query("SELECT IDENTITY_VAL_LOCAL() FROM SYSIBM.SYSDUMMY1");
					$insert_id_row = $insert_id_statement->fetch(PDO::FETCH_ASSOC);
					$insert_id = array_shift($insert_id_row);
					$insert_id_statement->closeCursor();
					unset($insert_id_statement);
					break;
				
				case 'mssql':
					try {
						$insert_id_statement = $this->connection->query("SELECT @@IDENTITY AS insert_id");
						if (!$insert_id_statement) {
							throw new Exception();
						}
						
						$insert_id_row = $insert_id_statement->fetch(PDO::FETCH_ASSOC);
						$insert_id = array_shift($insert_id_row);
						
					} catch (Exception $e) {
						// If there was an error we don't have an insert id
					}
					break;
					
				case 'oracle':
					try {
						$insert_id_statement = $this->connection->query($insert_id_sql);
						if (!$insert_id_statement) {
							throw new Exception();
						}
						
						$insert_id_row = $insert_id_statement->fetch(PDO::FETCH_ASSOC);
						$insert_id = array_shift($insert_id_row);
						
					} catch (Exception $e) {
						// If there was an error we don't have an insert id
					}
					break;
				
				case 'postgresql':
					
					$insert_id_statement = $this->connection->query("SELECT lastval()");
					$insert_id_row = $insert_id_statement->fetch(PDO::FETCH_ASSOC);
					$insert_id = array_shift($insert_id_row);
					$insert_id_statement->closeCursor();
					unset($insert_id_statement);
					
					break;
		
				case 'mysql':
					$insert_id = $this->connection->lastInsertId();
					break;
		
				case 'sqlite':
					$insert_id = $this->connection->lastInsertId();
					break;
			}
		}
		
		$result->setAutoIncrementedValue($insert_id);
	}


	/**
	 * Handles connection errors
	 * 
	 * @param  array|string $errors  An array or string of error information
	 * @return void
	 */
	private function handleConnectionErrors($errors)
	{
		if (is_string($errors)) {
			$error = $errors;
		} else {
			$new_errors = array();
			foreach ($errors as $error) {
				$new_errors[] = isset($error['message']) ? $error['message'] : $error['string'];
			}
			$error = join("\n", $new_errors);
		}

		$connection_regexes = array(
			'db2'        => '#selectForConnectTimeout#',
			'mssql'      => '#(Connection refused|Can\'t assign requested address|Server is unavailable or does not exist|unable to connect|target machine actively refused it)#i',
			'mysql'      => '#(Can\'t connect to MySQL server|Lost connection to MySQL server at|Connection refused|Operation timed out|host has failed to respond)#',
			'oracle'     => '#(Connection refused|Can\'t assign requested address|no listener|unable to connect to)#',
			'postgresql' => '#(Connection refused|timeout expired|Network is unreachable|Can\'t assign requested address)#'
		);

		$authentication_regexes = array(
			'db2'        => '#USERNAME AND/OR PASSWORD INVALID#',
			'mssql'      => '#(Login incorrect|Adaptive Server connection failed|Login failed for user(?!.*Cannot open database))#is',
			'mysql'      => '#Access denied for user#',
			'oracle'     => '#invalid username/password#',
			'postgresql' => '#authentication failed#'
		);

		$database_regexes = array(
			'db2'        => '#database alias or database name#',
			'mssql'      => '#Could not locate entry in sysdatabases for database|Cannot open database|General SQL Server error: Check messages from the SQL Server#',
			'mysql'      => '#Unknown database#',
			'oracle'     => '#does not currently know of service requested#',
			'postgresql' => '#database "[^"]+" does not exist#'
		);

		if (isset($authentication_regexes[$this->type]) && preg_match($authentication_regexes[$this->type], $error)) {
			throw new fAuthorizationException(
				'Unable to connect to database - login credentials refused'
			);
		} elseif (isset($database_regexes[$this->type]) && preg_match($database_regexes[$this->type], $error)) {
			throw new fNotFoundException(
				'Unable to connect to database - database specified not found'
			);
		}

		// Provide a better error message if we can detect the hostname does not exist
		if (!preg_match('#^\d+\.\d+\.\d+\.\d+$#', $this->host)) {
			$ip_address = gethostbyname($this->host);
			if ($ip_address == $this->host) {
				throw new fConnectivityException(
					'Unable to connect to database - hostname not found'
				);
			}
		}

		if (isset($connection_regexes[$this->type]) && preg_match($connection_regexes[$this->type], $error)) {
			throw new fConnectivityException(
				'Unable to connect to database - connection refused or timed out'
			);
		}

		throw new fConnectivityException(
			"Unable to connect to database - unknown error:\n%1\$s",
			$error
		);
	}
	
	
	/**
	 * Handles a PHP error to extract error information for the mssql extension
	 * 
	 * @param  array $errors  An array of error information from fCore::stopErrorCapture()
	 * @return void
	 */
	private function handleErrors($errors)
	{
		if ($this->extension != 'mssql') {
			return;	
		}
		
		foreach ($errors as $error) {
			if (substr($error['string'], 0, 14) == 'mssql_query():') {
				if ($this->error) {
					$this->error .= " ";	
				}
				$this->error .= preg_replace('#^mssql_query\(\): ([^:]+: )?#', '', $error['string']);	
			}
		}
	}
	
	
	/**
	 * Makes sure each database and extension handles BEGIN, COMMIT and ROLLBACK 
	 * 
	 * @param  string|fStatement &$statement    The SQL to check for a transaction query
	 * @param  string            $result_class  The type of result object to create
	 * @return mixed  `FALSE` if normal processing should continue, otherwise an object of the type $result_class
	 */
	private function handleTransactionQueries(&$statement, $result_class)
	{
		if (is_object($statement)) {
			$sql = $statement->getSQL();
		} else {
			$sql = $statement;
		}

		// SQL Server supports transactions, but the statements are slightly different.
		// For the interest of convenience, we do simple transaction right here.
		if ($this->type == 'mssql') {
			if (preg_match('#^\s*(BEGIN|START(\s+TRANSACTION)?)\s*$#i', $sql)) {
				$statement = 'BEGIN TRANSACTION';
			} elseif (preg_match('#^\s*SAVEPOINT\s+("?\w+"?)\s*$#i', $sql, $match)) {
				$statement = 'SAVE TRANSACTION ' . $match[1];
			} elseif (preg_match('#^\s*ROLLBACK\s+TO\s+SAVEPOINT\s+("?\w+"?)\s*$#i', $sql, $match)) {
				$statement = 'ROLLBACK TRANSACTION ' . $match[1];
			}
		}
		
		$begin    = FALSE;
		$commit   = FALSE;
		$rollback = FALSE;
		
		// Track transactions since most databases don't support nesting
		if (preg_match('#^\s*(BEGIN|START)(\s+(TRAN|TRANSACTION|WORK))?\s*$#iD', $sql)) {
			if ($this->inside_transaction) {
				throw new fProgrammerException('A transaction is already in progress');
			}
			$this->inside_transaction = TRUE;
			$begin = TRUE;
			
		} elseif (preg_match('#^\s*COMMIT(\s+(TRAN|TRANSACTION|WORK))?\s*$#iD', $sql)) {
			if (!$this->inside_transaction) {
				throw new fProgrammerException('There is no transaction in progress');
			}
			$this->inside_transaction = FALSE;
			$commit = TRUE;
			
		} elseif (preg_match('#^\s*ROLLBACK(\s+(TRAN|TRANSACTION|WORK))?\s*$#iD', $sql)) {
			if (!$this->inside_transaction) {
				throw new fProgrammerException('There is no transaction in progress');
			}
			$this->inside_transaction = FALSE;
			$rollback = TRUE;
		
		// MySQL needs to use this construct for starting transactions when using LOCK tables
		} elseif ($this->type == 'mysql' && preg_match('#^\s*SET\s+autocommit\s*=\s*(0|1)#i', $sql, $match)) {
			$this->inside_transaction = TRUE;
			if ($match[1] == '0') {
				$this->schema_info['mysql_autocommit'] = TRUE;
			} else {
				unset($this->schema_info['mysql_autocommit']);
			}

		// We have to track LOCK TABLES for MySQL because UNLOCK TABLES only implicitly commits if LOCK TABLES was used
		} elseif ($this->type == 'mysql' && preg_match('#^\s*LOCK\s+TABLES#i', $sql)) {
			// This command always implicitly commits
			$this->inside_transaction = FALSE;
			$this->schema_info['mysql_lock_tables'] = TRUE;

		// MySQL has complex handling of UNLOCK TABLES
		} elseif ($this->type == 'mysql' && preg_match('#^\s*UNLOCK\s+TABLES#i', $sql)) {
			// This command only implicitly commits if LOCK TABLES was used
			if (isset($this->schema_info['mysql_lock_tables'])) {
				$this->inside_transaction = FALSE;
			}
			unset($this->schema_info['mysql_lock_tables']);

		// These databases issue implicit commit commands when the following statements are run
		} elseif ($this->type == 'mysql' && preg_match('#^\s*(ALTER|CREATE(?!\s+TEMPORARY)|DROP|RENAME|TRUNCATE|LOAD|UNLOCK|GRANT|REVOKE|SET\s+PASSWORD|CACHE|ANALYSE|CHECK|OPTIMIZE|REPAIR|FLUSH|RESET)\b#i', $sql)) {
			$this->inside_transaction = FALSE;

		} elseif ($this->type == 'oracle' && preg_match('#^\s*(CREATE|ALTER|DROP|TRUNCATE|GRANT|REVOKE|REPLACE|ANALYZE|AUDIT|COMMENT)\b#i', $sql)) {
			$this->inside_transaction = FALSE;
			
		} elseif ($this->type == 'db2' && preg_match('#^\s*CALL\s+SYSPROC\.ADMIN_CMD\(\'REORG\s+TABLE\b#i', $sql)) {
			$this->inside_transaction = FALSE;
			// It appears PDO tracks the transactions, but doesn't know about implicit commits
			if ($this->extension == 'pdo') {
				$this->connection->commit();
			}
		}

		// If MySQL autocommit it set to 0 a new transaction is automatically started
		if (!empty($this->schema_info['mysql_autocommit'])) {
			$this->inside_transaction = TRUE;
		}

		if (!$begin && !$commit && !$rollback) {
			return FALSE;
		}
		
		// The PDO, OCI8 and SQLSRV extensions require special handling through methods and functions
		$is_pdo     = $this->extension == 'pdo';
		$is_oci     = $this->extension == 'oci8';
		$is_sqlsrv  = $this->extension == 'sqlsrv';
		$is_ibm_db2 = $this->extension == 'ibm_db2';
		
		if (!$is_pdo && !$is_oci && !$is_sqlsrv && !$is_ibm_db2) {
			return FALSE;
		}
		
		$this->statement = $statement;
		
		// PDO seems to act weird if you try to start transactions through a normal query call
		if ($is_pdo) {
			try {
				$is_mssql  = $this->type == 'mssql';
				$is_oracle = $this->type == 'oracle';
				if ($begin) {
					// The SQL Server PDO object hasn't implemented transactions
					if ($is_mssql) {
						$this->connection->exec('BEGIN TRANSACTION');
					} elseif ($is_oracle) {
						$this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);
					} else {
						$this->connection->beginTransaction();
					}
				
				} elseif ($commit) {
					if ($is_mssql) {
						$this->connection->exec('COMMIT');
					} elseif ($is_oracle) {
						$this->connection->exec('COMMIT');
						$this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, TRUE);
					} else  {
						$this->connection->commit();
					}
				
				} elseif ($rollback) {
					if ($is_mssql) {
						$this->connection->exec('ROLLBACK');
					} elseif ($is_oracle) {                 
						$this->connection->exec('ROLLBACK');
						$this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, TRUE);
					} else {
						$this->connection->rollBack();
					}
				}
				
			} catch (Exception $e) {
				
				$db_type_map = array(
					'db2'        => 'DB2',
					'mssql'      => 'MSSQL',
					'mysql'      => 'MySQL',
					'oracle'     => 'Oracle',
					'postgresql' => 'PostgreSQL',
					'sqlite'     => 'SQLite'
				);
				
				throw new fSQLException(
					'%1$s error (%2$s) in %3$s',
					$db_type_map[$this->type],
					$e->getMessage(),
					$sql
				);
			}
		
		} elseif ($is_oci) {
			if ($commit) {
				oci_commit($this->connection);
			} elseif ($rollback) {
				oci_rollback($this->connection);
			}
		
		} elseif ($is_sqlsrv) {
			if ($begin) {
				sqlsrv_begin_transaction($this->connection);
			} elseif ($commit) {
				sqlsrv_commit($this->connection);
			} elseif ($rollback) {
				sqlsrv_rollback($this->connection);
			}
			
		} elseif ($is_ibm_db2) {
			if ($begin) {
				db2_autocommit($this->connection, FALSE);
			} elseif ($commit) {
				db2_commit($this->connection);
				db2_autocommit($this->connection, TRUE);
			} elseif ($rollback) {
				db2_rollback($this->connection);
				db2_autocommit($this->connection, TRUE);
			}
		}
		
		if ($result_class) {
			$result = new $result_class($this);
			$result->setSQL($sql);
			$result->setResult(TRUE);
			return $result;
		}
		
		return TRUE;
	}
	
	
	/**
	 * Injects an fSQLTranslation object to handle translation
	 * 
	 * @internal
	 * 
	 * @param  fSQLTranslation $sql_translation  The SQL translation object
	 * @return void
	 */
	public function inject($sql_translation)
	{
		$this->translation = $sql_translation;
	}
	
	
	/**
	 * Will indicate if a transaction is currently in progress
	 * 
	 * @return boolean  If a transaction has been started and not yet rolled back or committed
	 */
	public function isInsideTransaction()
	{
		return $this->inside_transaction;
	}
	
	
	/**
	 * Creates a unique cache prefix to help prevent cache conflicts
	 * 
	 * @return string  The cache prefix to use
	 */
	private function makeCachePrefix()
	{
		if (!$this->cache_prefix) {
			$prefix  = 'fDatabase::' . $this->type . '::';
			if ($this->host) {
				$prefix .= $this->host . '::';
			}
			if ($this->port) {
				$prefix .= $this->port . '::';
			}
			$prefix .= $this->database . '::';
			if ($this->username) {
				$prefix .= $this->username . '::';
			}
			$this->cache_prefix = $prefix;
		}
		
		return $this->cache_prefix;
	}
	
	
	/**
	 * Executes a SQL statement
	 * 
	 * @param  string|fStatement $statement  The statement to perform
	 * @param  array             $params     The parameters for prepared statements
	 * @return void
	 */
	private function perform($statement, $params)
	{
		fCore::startErrorCapture();
		
		$extra = NULL;
		if (is_object($statement)) {
			$result = $statement->execute($params, $extra, $statement != $this->statement);
		} elseif ($this->extension == 'ibm_db2') {
			$result = db2_exec($this->connection, $statement, array('cursor' => DB2_FORWARD_ONLY));
		} elseif ($this->extension == 'mssql') {
			$result = mssql_query($statement, $this->connection);
		} elseif ($this->extension == 'mysql') {
			$result = mysql_unbuffered_query($statement, $this->connection);
		} elseif ($this->extension == 'mysqli') { 
			$result = mysqli_query($this->connection, $statement, MYSQLI_USE_RESULT);
		} elseif ($this->extension == 'oci8') {
			$extra  = oci_parse($this->connection, $statement);
			$result = oci_execute($extra, $this->inside_transaction ? OCI_DEFAULT : OCI_COMMIT_ON_SUCCESS);
		} elseif ($this->extension == 'pgsql') {
			$result = pg_query($this->connection, $statement);
		} elseif ($this->extension == 'sqlite') {
			$result = sqlite_exec($this->connection, $statement, $extra);
		} elseif ($this->extension == 'sqlsrv') {
			$result = sqlsrv_query($this->connection, $statement);
		} elseif ($this->extension == 'pdo') {
			if ($this->type == 'mssql' && !fCore::checkOS('windows')) {
				// pdo_dblib is all messed up for return values from ->exec()
				// and even ->query(), but ->query() is closer to correct and
				// we use some heuristics to overcome the limitations
				$result = $this->connection->query($statement);
				if ($result instanceof PDOStatement) {
					$result->closeCursor();
					$extra = $result;
					$result = TRUE;
					if (preg_match('#^\s*EXEC(UTE)?\s+#i', $statement)) {
						$error_info = $extra->errorInfo();
						if (strpos($error_info[2], '(null) [0] (severity 0)') !== 0) {
							$result = FALSE;
						}
					}
				}
			} else {
				$result = $this->connection->exec($statement);
			}
		}
		$this->statement = $statement;
		
		$this->handleErrors(fCore::stopErrorCapture());

		// The mssql extension will sometimes not return FALSE even if there are errors
		if (strlen($this->error)) {
			$result = FALSE;
		}

		if ($this->extension == 'mssql' && $result) {
			$this->error = '';
		}
		
		if ($result === FALSE) {
			$this->checkForError($result, $extra, is_object($statement) ? $statement->getSQL() : $statement);
			
		} elseif (!is_bool($result) && $result !== NULL) {
			if ($this->extension == 'ibm_db2') {
				db2_free_result($result);
			} elseif ($this->extension == 'mssql') {
				mssql_free_result($result);
			} elseif ($this->extension == 'mysql') {
				mysql_free_result($result);
			} elseif ($this->extension == 'mysqli') { 
				mysqli_free_result($result);
			} elseif ($this->extension == 'oci8') {
				oci_free_statement($oci_statement);
			} elseif ($this->extension == 'pgsql') {
				pg_free_result($result);
			} elseif ($this->extension == 'sqlsrv') {
				sqlsrv_free_stmt($result);
			}
		}
	}
	
	
	/**
	 * Executes an SQL query
	 * 
	 * @param  string|fStatement $statement  The statement to perform
	 * @param  fResult           $result     The result object for the query
	 * @param  array             $params     The parameters for prepared statements
	 * @return void
	 */
	private function performQuery($statement, $result, $params)
	{
		fCore::startErrorCapture();
		
		$extra = NULL;
		if (is_object($statement)) {
			$statement->executeQuery($result, $params, $extra, $statement != $this->statement);
			
		} elseif ($this->extension == 'ibm_db2') {
			$extra = db2_exec($this->connection, $statement, array('cursor' => DB2_FORWARD_ONLY));
			if (is_resource($extra)) {
				$rows = array();
				while ($row = db2_fetch_assoc($extra)) {
					$rows[] = $row;	
				}
				$result->setResult($rows);
				unset($rows);
			} else { 
				$result->setResult($extra);	
			}
			
		} elseif ($this->extension == 'mssql') {
			$result->setResult(mssql_query($result->getSQL(), $this->connection));
			
		} elseif ($this->extension == 'mysql') {
			$result->setResult(mysql_query($result->getSQL(), $this->connection));

		} elseif ($this->extension == 'mysqli') {
			$result->setResult(mysqli_query($this->connection, $result->getSQL()));
			
		} elseif ($this->extension == 'oci8') {
			$extra = oci_parse($this->connection, $result->getSQL());
			if ($extra && oci_execute($extra, $this->inside_transaction ? OCI_DEFAULT : OCI_COMMIT_ON_SUCCESS)) {
				oci_fetch_all($extra, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
				$result->setResult($rows);
				unset($rows);	
			} else {
				$result->setResult(FALSE);
			}
			
		} elseif ($this->extension == 'pgsql') {
			$result->setResult(pg_query($this->connection, $result->getSQL()));
			
		} elseif ($this->extension == 'sqlite') {
			$result->setResult(sqlite_query($this->connection, $result->getSQL(), SQLITE_ASSOC, $extra));
			
		} elseif ($this->extension == 'sqlsrv') {
			$extra = sqlsrv_query($this->connection, $result->getSQL());
			if (is_resource($extra)) {
				$rows = array();
				while ($row = sqlsrv_fetch_array($extra, SQLSRV_FETCH_ASSOC)) {
					$rows[] = $row;
				}
				$result->setResult($rows);
				unset($rows);
			} else {
				$result->setResult($extra);
			}
			
		} elseif ($this->extension == 'pdo') {
			if (preg_match('#^\s*CREATE(\s+OR\s+REPLACE)?\s+TRIGGER#i', $result->getSQL())) {
				$this->connection->exec($result->getSQL());
				$extra = FALSE;
				$returned_rows = array();
			} else {
				$extra = $this->connection->query($result->getSQL());
				if (is_object($extra)) {
					// This fixes a segfault issue with blobs and fetchAll() for pdo_ibm
					if ($this->type == 'db2') {
						$returned_rows = array();
						while (($row = $extra->fetch(PDO::FETCH_ASSOC)) !== FALSE) {
							foreach ($row as $key => $value) {
								if (is_resource($value)) {
									$row[$key] = stream_get_contents($value);
								}
							}
							$returned_rows[] = $row;
						}

					// pdo_dblib doesn't throw an exception on error when executing
					// a prepared statement when compiled against FreeTDS, so we have
					// to manually check the error info to see if something went wrong
					} elseif ($this->type == 'mssql' && !fCore::checkOS('windows') && preg_match('#^\s*EXEC(UTE)?\s+#i', $result->getSQL())) {
						$error_info = $extra->errorInfo();
						if ($error_info && strpos($error_info[2], '(null) [0] (severity 0)') !== 0) {
							$returned_rows = FALSE;
						}

					} else {
						$returned_rows = $extra->fetchAll(PDO::FETCH_ASSOC);
					}
				} else {
					$returned_rows = $extra;
				}
				
				// The pdo_pgsql driver likes to return empty rows equal to the number of affected rows for insert and deletes
				if ($this->type == 'postgresql' && $returned_rows && $returned_rows[0] == array()) {
					$returned_rows = array(); 		
				}
			}
			
			$result->setResult($returned_rows);
		}
		$this->statement = $statement;
		
		$this->handleErrors(fCore::stopErrorCapture());

		// The mssql extension will sometimes not return FALSE even if there are errors
		if (strlen($this->error) && strpos($this->error, 'WARNING!') !== 0) {
			$result->setResult(FALSE);
		}
		
		$this->checkForError($result, $extra);
		
		if ($this->extension == 'mssql') {
			$this->error = '';
		}
		
		if ($this->extension == 'ibm_db2') {
			$this->setAffectedRows($result, $extra);
			if ($extra && !is_object($statement)) {
				db2_free_result($extra);
			}
			
		} elseif ($this->extension == 'pdo') {
			$this->setAffectedRows($result, $extra);
			if ($extra && !is_object($statement)) {
				$extra->closeCursor();
			}
			
		} elseif ($this->extension == 'oci8') {
			$this->setAffectedRows($result, $extra);
			if ($extra && !is_object($statement)) {
				oci_free_statement($extra);
			}
			
		} elseif ($this->extension == 'sqlsrv') {
			$this->setAffectedRows($result, $extra);
			if ($extra && !is_object($statement)) {
				sqlsrv_free_stmt($extra);
			}
			
		} else {
			$this->setAffectedRows($result, $extra);
		}
		
		$this->setReturnedRows($result);
		
		$this->handleAutoIncrementedValue($result, $extra);
	}
	
	
	/**
	 * Executes an unbuffered SQL query
	 * 
	 * @param  string|fStatement $statement  The statement to perform
	 * @param  fUnbufferedResult $result     The result object for the query
	 * @param  array             $params     The parameters for prepared statements
	 * @return void
	 */
	private function performUnbufferedQuery($statement, $result, $params)
	{
		fCore::startErrorCapture();
		
		$extra = NULL;
		if (is_object($statement)) {
			$statement->executeUnbufferedQuery($result, $params, $extra, $statement != $this->statement);
		} elseif ($this->extension == 'ibm_db2') {
			$result->setResult(db2_exec($this->connection, $statement, array('cursor' => DB2_FORWARD_ONLY)));
		} elseif ($this->extension == 'mssql') {
			$result->setResult(mssql_query($result->getSQL(), $this->connection, 20));
		} elseif ($this->extension == 'mysql') {
			$result->setResult(mysql_unbuffered_query($result->getSQL(), $this->connection));
		} elseif ($this->extension == 'mysqli') { 
			$result->setResult(mysqli_query($this->connection, $result->getSQL(), MYSQLI_USE_RESULT));
		} elseif ($this->extension == 'oci8') {
			$extra = oci_parse($this->connection, $result->getSQL());
			if (oci_execute($extra, $this->inside_transaction ? OCI_DEFAULT : OCI_COMMIT_ON_SUCCESS)) {
				$result->setResult($extra);
			} else {
				$result->setResult(FALSE);	
			}
		} elseif ($this->extension == 'pgsql') {
			$result->setResult(pg_query($this->connection, $result->getSQL()));
		} elseif ($this->extension == 'sqlite') {
			$result->setResult(sqlite_unbuffered_query($this->connection, $result->getSQL(), SQLITE_ASSOC, $extra));
		} elseif ($this->extension == 'sqlsrv') {
			$result->setResult(sqlsrv_query($this->connection, $result->getSQL()));
		} elseif ($this->extension == 'pdo') {
			$result->setResult($this->connection->query($result->getSQL()));
		}
		$this->statement = $statement;
		
		$this->handleErrors(fCore::stopErrorCapture());
		
		$this->checkForError($result, $extra);
	}
	
	
	/**
	 * Prepares a single fStatement object to execute prepared statements
	 * 
	 * Identifier placeholders (%r) are not supported with prepared statements.
	 * In addition, multiple values can not be escaped by a placeholder - only
	 * a single value can be provided.
	 * 
	 * @param  string  $sql  The SQL to prepare
	 * @return fStatement  A prepared statement object that can be passed to ::query(), ::unbufferedQuery() or ::execute()
	 */
	public function prepare($sql)
	{
		return $this->prepareStatement($sql);	
	}
	
	
	/**
	 * Prepares a single fStatement object to execute prepared statements
	 * 
	 * Identifier placeholders (%r) are not supported with prepared statements.
	 * In addition, multiple values can not be escaped by a placeholder - only
	 * a single value can be provided.
	 * 
	 * @param  string  $sql        The SQL to prepare
	 * @param  boolean $translate  If the SQL should be translated using fSQLTranslation
	 * @return fStatement  A prepare statement object that can be passed to ::query(), ::unbufferedQuery() or ::execute()
	 */
	private function prepareStatement($sql, $translate=FALSE)
	{
		// Ensure an SQL statement was passed
		if (empty($sql)) {
			throw new fProgrammerException('No SQL statement passed');
		}
		
		// This is just to keep the callback method signature consistent
		$values = array();
		
		if ($this->hook_callbacks['unmodified']) {
			foreach ($this->hook_callbacks['unmodified'] as $callback) {
				$params = array(
					$this,
					&$sql,
					&$values
				);
				call_user_func_array($callback, $params);
			}
		}
		
		// Separate the SQL from quoted values
		$parts  = $this->splitSQL($sql);
		$new_parts = array();
		foreach ($parts as $part) {
			if ($part[0] == "'") {
				$new_parts[] = $part;
			} else {
				// We have to escape the placeholders so that the extraction of
				// string to %s placeholder doesn't mess up the creation of the
				// prepare statement
				$new_parts[] = str_replace('%', '%%', $part);
			}
		}
		$query = $this->extractStrings($new_parts, $values);
		
		if ($this->hook_callbacks['extracted']) {
			foreach ($this->hook_callbacks['extracted'] as $callback) {
				$params = array(
					$this,
					&$query,
					&$values
				);
				call_user_func_array($callback, $params);
			}
		}
		
		$untranslated_sql = NULL;
		if ($translate) {
			$untranslated_sql = $sql;
			$query = $this->getSQLTranslation()->translate(array($query));
			if (count($query) > 1) {
				throw new fProgrammerException(
					"The SQL statement %1$s can not be used as a prepared statement because translation turns it into multiple SQL statements",
					$untranslated_sql
				);
			}
			$query = current($query);
		}

		// Pull all of the real placeholders (%%) out and replace them with
		// %%s for sprintf() in fStatement. We have to use %% because we are
		// going to put the extracted string back into the statement via %s.
		$pieces       = preg_split('#(%%[lbdfistp])\b#', $query, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		$placeholders = array();
		$new_query    = '';
		foreach ($pieces as $piece) {
			if (strlen($piece) == 3 && substr($piece, 0, 2) == '%%') {
				$placeholders[] = substr($piece, 1);
				$new_query     .= '%%s';
			} else {
				$new_query .= $piece;
			}		
		}
		$query = $new_query;
		
		// Unescape literal semicolons in the queries
		$query = preg_replace('#(?<!\\\\)\\\\;#', ';', $query);
		
		$query = $this->escapeSQL($query, $values, TRUE);

		return new fStatement($this, $query, $placeholders, $untranslated_sql);
	}
	
	
	/**
	 * Preprocesses SQL by escaping values, spliting queries, cleaning escaped semicolons, fixing backslashed single quotes and translating
	 * 
	 * @internal
	 *
	 * @param  string  $sql                The SQL to process
	 * @param  array   $values             Literal values to escape into the SQL
	 * @param  boolean $translate          If the SQL should be translated
	 * @param  array   &$rollback_queries  MySQL doesn't allow transactions around `ALTER TABLE` statements, and some of those require multiple statements, so this is an array of "undo" SQL statements 
	 * @return array  The split out SQL queries, queries that have been translated will have a string key of a number, `:` and the original SQL, non-translated SQL will have a numeric key
	 */
	public function preprocess($sql, $values, $translate, &$rollback_queries=NULL)
	{
		$this->connect();
		
		// Ensure an SQL statement was passed
		if (empty($sql)) {
			throw new fProgrammerException('No SQL statement passed');
		}
		
		if ($this->hook_callbacks['unmodified']) {
			foreach ($this->hook_callbacks['unmodified'] as $callback) {
				$params = array(
					$this,
					&$sql,
					&$values
				);
				call_user_func_array($callback, $params);
			}
		}
		
		// Separate the SQL from quoted values
		$parts = $this->splitSQL($sql, $placeholders);
		
		// If the values were passed as a single array, this handles that
		if (count($values) == 1 && is_array($values[0]) && count($values[0]) == $placeholders) {
			$values = array_shift($values);	
		}
				
		$sql          = $this->extractStrings($parts, $values);
		$queries      = preg_split('#(?<!\\\\);#', $sql);
		$queries      = array_map('trim', $queries);
		$output       = array();
		$value_number = 0;
		foreach ($queries as $query) {
			if (!strlen($query)) {
				continue;
			}

			$sqlite_ddl   = $this->type == 'sqlite' && preg_match('#^\s*(ALTER\s+TABLE|CREATE\s+TABLE|COMMENT\s+ON)\s+#i', $query);
			$pieces       = preg_split('#(?<!%)(%[lbdfristp])\b#', $query, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
			$new_sql      = '';
			$query_values = array();
			
			$num = 0;
			foreach ($pieces as $piece) {
				
				// A placeholder
				if (strlen($piece) == 2 && $piece[0] == '%') {
					
					$value = $values[$value_number];
					
					// Here we put numbers for LIMIT and OFFSET into the SQL so they can be translated properly
					if ($piece == '%i' && preg_match('#\b(LIMIT|OFFSET)\s+#Di', $new_sql)) {
						$new_sql .= (int) $value;
						$value_number++;
					
					// Here we put blank strings back into the SQL so they can be translated for Oracle
					} elseif ($piece == '%s' && $value !== NULL && ((string) $value) == '') {
						$new_sql .= "''";
						$value_number++;
					
					// SQLite needs the literal string values for DDL statements
					} elseif ($piece == '%s' && $sqlite_ddl) {
						$new_sql .= $this->escapeString($value);
						$value_number++;
					
					} elseif ($piece == '%r') {
						if (is_array($value)) {
							$new_sql .= join(', ', array_map($this->escapeIdentifier, $value));	
						} else {
							$new_sql .= $this->escapeIdentifier($value);
						}
						$value_number++;
						
					// Other placeholder/value combos just get added
					} else {
						$value_number++;
						$new_sql .= '%' . $num . '$' . $piece[1];
						$num++;
						$query_values[] = $value;
					}
				
				// A piece of SQL
				} else {
					$new_sql .= $piece;	
				}
			}
			
			$query = $new_sql;

			if ($this->hook_callbacks['extracted']) {
				foreach ($this->hook_callbacks['extracted'] as $callback) {
					$params = array(
						$this,
						&$query,
						&$query_values
					);
					call_user_func_array($callback, $params);
				}
			}

			if ($translate) {
				$query_set = $this->getSQLTranslation()->translate(array($query), $rollback_queries);
			} else {
				$query_set = array($query);
			}

			foreach ($query_set as $key => $query) {				
				// Unescape literal semicolons in the queries
				$query = preg_replace('#(?<!\\\\)\\\\;#', ';', $query);
				
				// Escape the values into the SQL
				if ($query_values && preg_match_all('#(?<!%)%(\d+)\$([lbdfristp])\b#', $query, $matches, PREG_SET_ORDER)) {
					// If we translated, we may need to shuffle values around
					if ($translate) {
						$new_values = array();
						foreach ($matches as $match) {
							$new_values[] = $query_values[$match[1]];
						}
						$query_values = $new_values;
					}
					$query = preg_replace('#(?<!%)%\d+\$([lbdfristp])\b#', '%\1', $query);
					$query = $this->escapeSQL($query, $query_values, TRUE);	
				}
				
				if (!is_numeric($key)) {
					$key_parts = explode(':', $key);
					$key = count($output) . ':' . $key_parts[1];
				} else {
					$key = count($output);
				}
				$output[$key] = $query;
			}
		}

		return $output;
	}
	
	
	/**
	 * Executes one or more SQL queries and returns the result(s)
	 * 
	 * @param  string|fStatement $statement  One or more SQL statements in a string or a single fStatement prepared statement
	 * @param  mixed             $value      The optional value(s) to place into any placeholders in the SQL - see ::escape() for details
	 * @param  mixed             ...
	 * @return fResult|array  The fResult object(s) for the query
	 */
	public function query($statement)
	{
		$args    = func_get_args();
		$params  = array_slice($args, 1);
		
		if (is_object($statement)) {
			return $this->run($statement, 'fResult', $params);	
		}
		
		$queries = $this->preprocess($statement, $params, FALSE);
		
		$output = array();
		foreach ($queries as $query) {
			$output[] = $this->run($query, 'fResult');	
		}
		
		return sizeof($output) == 1 ? $output[0] : $output;
	}
	
	
	/**
	 * Registers a callback for one of the various query hooks - multiple callbacks can be registered for each hook
	 * 
	 * The following hooks are available:
	 *  - `'unmodified'`: The original SQL passed to fDatabase, for prepared statements this is called just once before the fStatement object is created
	 *  - `'extracted'`: The SQL after all non-empty strings have been extracted and replaced with ordered sprintf-style placeholders
	 *  - `'run'`: After the SQL has been run
	 * 
	 * Methods for the `'unmodified'` hook should have the following signature:
	 * 
	 *  - **`$database`**:  The fDatabase instance
	 *  - **`&$sql`**:      The original, unedited SQL
	 *  - **`&$values`**:   The values to be escaped into the placeholders in the SQL
	 * 
	 * Methods for the `'extracted'` hook should have the following signature:
	 * 
	 *  - **`$database`**:  The fDatabase instance
	 *  - **`&$sql`**:      The SQL with all strings removed and replaced with `%1$s`-style placeholders
	 *  - **`&$values`**:   The values to be escaped into the placeholders in the SQL
	 * 
	 * The `extracted` hook is the best place to modify the SQL since there is
	 * no risk of breaking string literals. Please note that there may be empty
	 * strings (`''`) present in the SQL since some databases treat those as
	 * `NULL`.
	 * 
	 * Methods for the `'run'` hook should have the following signature:
	 * 
	 *  - **`$database`**:    The fDatabase instance
	 *  - **`$query`**:       The (string) SQL or `array(0 => {fStatement object}, 1 => {values array})` 
	 *  - **`$query_time`**:  The (float) number of seconds the query took
	 *  - **`$result`**       The fResult or fUnbufferedResult object, or `FALSE` if no result
	 * 
	 * @param  string   $hook      The hook to register for
	 * @param  callback $callback  The callback to register - see the method description for details about the method signature
	 * @return void
	 */
	public function registerHookCallback($hook, $callback)
	{
		$valid_hooks = array(
			'unmodified',
			'extracted',
			'run'
		);
		
		if (!in_array($hook, $valid_hooks)) {
			throw new fProgrammerException(
				'The hook specified, %1$s, should be one of: %2$s.',
				$hook,
				join(', ', $valid_hooks)
			);
		}
		
		$this->hook_callbacks[$hook][] = $callback;
	}
	
	
	/**
	 * Runs a single statement and times it, removes any old unbuffered queries before starting
	 * 
	 * @param  string|fStatement $statement    The SQL statement or prepared statement to execute
	 * @param  string            $result_type  The type of result object to return, fResult or fUnbufferedResult
	 * @return fResult|fUnbufferedResult  The result for the query
	 */
	private function run($statement, $result_type=NULL, $params=array())
	{
		if ($this->unbuffered_result) {
			$this->unbuffered_result->__destruct();
			$this->unbuffered_result = NULL;
		}
		
		$start_time = microtime(TRUE);	
		
		$result = $this->handleTransactionQueries($statement, $result_type);

		if (is_object($statement)) {
			$sql = $statement->getSQL();		
		} else {
			$sql = $statement;	
		}

		if (!$result) {
			if ($result_type) {
				$result = new $result_type($this, $this->type == 'mssql' ? $this->schema_info['character_set'] : NULL);
				$result->setSQL($sql);
				
				if ($result_type == 'fResult') {
					$this->performQuery($statement, $result, $params);
				} else {
					$this->performUnbufferedQuery($statement, $result, $params);	
				}
				
				if ($statement instanceof fStatement && $statement->getUntranslatedSQL()) {
					$result->setUntranslatedSQL($statement->getUntranslatedSQL());
				}
				
			} else {
				$this->perform($statement, $params);	
			}
		}
		
		// Write some debugging info
		$query_time = microtime(TRUE) - $start_time;
		$this->query_time += $query_time;
		if (fCore::getDebug($this->debug)) {
			fCore::debug(
				self::compose(
					'Query time was %1$s seconds for:%2$s',
					$query_time,
					"\n" . $sql
				),
				$this->debug
			);
		}
		
		if ($this->hook_callbacks['run']) {
			foreach ($this->hook_callbacks['run'] as $callback) {
				$callback_params = array(
					$this,
					is_object($statement) ? array($statement, $params) : $sql,
					$query_time,
					$result
				);
				call_user_func_array($callback, $callback_params);
			}
		}
		
		if ($result_type) {
			return $result;
		}
	}


	/**
	 * Takes an array of rollback statements to undo part of a set of queries which involve one that failed
	 *
	 * This is only used for MySQL since it is the only database that does not
	 * support transactions about `ALTER TABLE` statements, but that also
	 * requires more than one query to accomplish many `ALTER TABLE` tasks.
	 *
	 * @param  array   $rollback_statements  The SQL statements used to rollback `ALTER TABLE` statements
	 * @param  integer $start_number         The number query that failed - this is used to determine which rollback statements to run
	 * @return void
	 */
	private function runRollbackStatements($rollback_statements, $start_number)
	{
		if ($rollback_statements) {
			$rollback_statements = array_slice($rollback_statements, 0, $start_number);
			$rollback_statements = array_reverse($rollback_statements);
			foreach ($rollback_statements as $rollback_statement) {
				$this->run($rollback_statement);
			}
		}
	}
	
	
	/**
	 * Turns an array possibly containing objects into an array of all strings
	 * 
	 * @param  array $values  The array of values to scalarize
	 * @return array  The scalarized values
	 */
	private function scalarize($values)
	{
		$new_values = array();
		foreach ($values as $value) {
			if (is_object($value) && is_callable(array($value, '__toString'))) {
				$value = $value->__toString();
			} elseif (is_object($value)) {
				$value = (string) $value;	
			} elseif (is_array($value)) {
				$value = $this->scalarize($value);	
			}
			$new_values[] = $value;
		}
		return $new_values;	
	}
	
	
	/**
	 * Sets the number of rows affected by the query
	 * 
	 * @param  fResult $result    The result object for the query
	 * @param  mixed   $resource  Only applicable for `ibm_db2`, `pdo`, `oci8` and `sqlsrv` extentions or `mysqli` prepared statements - this is either the `PDOStatement` object, `mysqli_stmt` object or the `oci8` or `sqlsrv` resource
	 * @return void
	 */
	private function setAffectedRows($result, $resource=NULL)
	{
		if ($this->extension == 'ibm_db2') {
			$insert_update_delete = preg_match('#^\s*(INSERT|UPDATE|DELETE)\b#i', $result->getSQL());
			$result->setAffectedRows(!$insert_update_delete ? 0 : db2_num_rows($resource));
		} elseif ($this->extension == 'mssql') {
			$affected_rows_result = mssql_query('SELECT @@ROWCOUNT AS rows', $this->connection);
			$result->setAffectedRows((int) mssql_result($affected_rows_result, 0, 'rows'));
		} elseif ($this->extension == 'mysql') {
			$result->setAffectedRows(mysql_affected_rows($this->connection));
		} elseif ($this->extension == 'mysqli') {
			if (is_object($resource)) {
				$result->setAffectedRows($resource->affected_rows);
			} else {
				$result->setAffectedRows(mysqli_affected_rows($this->connection));
			}
		} elseif ($this->extension == 'oci8') {
			$result->setAffectedRows(oci_num_rows($resource));
		} elseif ($this->extension == 'pgsql') {
			$result->setAffectedRows(pg_affected_rows($result->getResult()));
		} elseif ($this->extension == 'sqlite') {
			$result->setAffectedRows(sqlite_changes($this->connection));
		} elseif ($this->extension == 'sqlsrv') {
			$result->setAffectedRows(sqlsrv_rows_affected($resource));
		} elseif ($this->extension == 'pdo') {
			// This fixes the fact that rowCount is not reset for non INSERT/UPDATE/DELETE statements
			try {
				if (!$resource || !$resource->fetch()) {
					throw new PDOException();
				}
				$result->setAffectedRows(0);
			} catch (PDOException $e) {
				// The SQLite PDO driver seems to return 1 when no rows are returned from a SELECT statement
				if ($this->type == 'sqlite' && $this->extension == 'pdo' && preg_match('#^\s*SELECT#i', $result->getSQL())) {
					$result->setAffectedRows(0);	
				} elseif (!$resource) {
					$result->setAffectedRows(0);
				} else {
					$result->setAffectedRows($resource->rowCount());
				}
			}
		}
	}
	
	
	/**
	 * Sets the number of rows returned by the query
	 * 
	 * @param  fResult $result  The result object for the query
	 * @return void
	 */
	private function setReturnedRows($result)
	{
		if (is_resource($result->getResult()) || is_object($result->getResult())) {
			if ($this->extension == 'mssql') {
				$result->setReturnedRows(mssql_num_rows($result->getResult()));
			} elseif ($this->extension == 'mysql') {
				$result->setReturnedRows(mysql_num_rows($result->getResult()));
			} elseif ($this->extension == 'mysqli') {
				$result->setReturnedRows(mysqli_num_rows($result->getResult()));
			} elseif ($this->extension == 'pgsql') {
				$result->setReturnedRows(pg_num_rows($result->getResult()));
			} elseif ($this->extension == 'sqlite') {
				$result->setReturnedRows(sqlite_num_rows($result->getResult()));
			}
		} elseif (is_array($result->getResult())) {
			$result->setReturnedRows(sizeof($result->getResult()));
		}
	}
	
	
	/**
	 * Splits SQL into pieces of SQL and quoted strings
	 * 
	 * @param  string  $sql            The SQL to split
	 * @param  integer &$placeholders  The number of placeholders in the SQL
	 * @return array  The pieces
	 */
	private function splitSQL($sql, &$placeholders=NULL)
	{
		// Fix \' in MySQL and PostgreSQL
		if(($this->type == 'mysql' || $this->type == 'postgresql') && strpos($sql, '\\') !== FALSE) {
			$sql = preg_replace("#(?<!\\\\)((\\\\{2})*)\\\\'#", "\\1''", $sql);	
		}

		$parts         = array();
		$temp_sql      = $sql;
		$start_pos     = 0;
		$inside_string = FALSE;
		do {
			$pos = strpos($temp_sql, "'", $start_pos);
			if ($pos !== FALSE) {
				if (!$inside_string) {
					$part          = substr($temp_sql, 0, $pos);
					$placeholders += preg_match_all('#(?<!%)%[lbdfristp]\b#', $part, $trash);
					unset($trash);
					$parts[]       = $part;
					$temp_sql      = substr($temp_sql, $pos);
					$start_pos     = 1;
					$inside_string = TRUE;
					 
				} elseif ($pos == strlen($temp_sql)) {
					$parts[]  = $temp_sql;
					$temp_sql = '';
					$pos = FALSE;	
				
				// Skip single-quote-escaped single quotes
				} elseif (strlen($temp_sql) > $pos+1 && $temp_sql[$pos+1] == "'") {
					$start_pos = $pos+2;
							
				} else {
					$parts[]   = substr($temp_sql, 0, $pos+1);
					$temp_sql  = substr($temp_sql, $pos+1);
					$start_pos = 0;
					$inside_string = FALSE;
				}
			}
		} while ($pos !== FALSE);
		if ($temp_sql) {
			$placeholders += preg_match_all('#(?<!%)%[lbdfristp]\b#', $temp_sql, $trash);
			unset($trash);
			$parts[] = $temp_sql;	
		}
		
		return $parts;	
	}
	
	
	/**
	 * Translates one or more SQL statements using fSQLTranslation and executes them without returning any results
	 * 
	 * @param  string $sql    One or more SQL statements
	 * @param  mixed  $value  The optional value(s) to place into any placeholders in the SQL - see ::escape() for details
	 * @param  mixed  ...
	 * @return void
	 */
	public function translatedExecute($sql)
	{
		$args    = func_get_args();
		$queries = $this->preprocess(
			$sql,
			array_slice($args, 1),
			TRUE,
			$rollback_statements
		);
		
		try {
			$output = array();
			$i = 0;
			foreach ($queries as $i => $query) {
				$this->run($query);
				$i++;
			}
		} catch (fSQLException $e) {
			$this->runRollbackStatements($rollback_statements, $i);
			throw $e;
		}
	}
	
	
	/**
	 * Translates a SQL statement and creates an fStatement object from it
	 * 
	 * Identifier placeholders (%r) are not supported with prepared statements.
	 * In addition, multiple values can not be escaped by a placeholder - only
	 * a single value can be provided.
	 * 
	 * @param  string  $sql  The SQL to prepare
	 * @return fStatement  A prepared statement object that can be passed to ::query(), ::unbufferedQuery() or ::execute()
	 */
	public function translatedPrepare($sql)
	{
		return $this->prepareStatement($sql, TRUE);	
	}
	
	
	/**
	 * Translates one or more SQL statements using fSQLTranslation and executes them
	 * 
	 * @param  string $sql    One or more SQL statements
	 * @param  mixed  $value  The optional value(s) to place into any placeholders in the SQL - see ::escape() for details
	 * @param  mixed  ...
	 * @return fResult|array  The fResult object(s) for the query
	 */
	public function translatedQuery($sql)
	{
		$args    = func_get_args();
		$queries = $this->preprocess(
			$sql,
			array_slice($args, 1),
			TRUE,
			$rollback_statements
		);
		
		try {
			$output = array();
			$i = 0;
			
			foreach ($queries as $key => $query) {
				$result = $this->run($query, 'fResult');
				if (!is_numeric($key)) {
					list($number, $original_query) = explode(':', $key, 2);
					$result->setUntranslatedSQL($original_query);
				}
				$output[] = $result;
				$i++;
			}
		} catch (fSQLException $e) {
			$this->runRollbackStatements($rollback_statements, $i);
			throw $e;
		}
		
		return sizeof($output) == 1 ? $output[0] : $output;
	}
	
	
	/**
	 * Executes a single SQL statement in unbuffered mode. This is optimal for
	 * large results sets since it does not load the whole result set into
	 * memory first. The gotcha is that only one unbuffered result can exist at
	 * one time. If another unbuffered query is executed, the old result will
	 * be deleted.
	 * 
	 * @param  string|fStatement $statement  A single SQL statement
	 * @param  mixed             $value      The optional value(s) to place into any placeholders in the SQL - see ::escape() for details
	 * @param  mixed             ...
	 * @return fUnbufferedResult  The result object for the unbuffered query
	 */
	public function unbufferedQuery($statement)
	{
		$args    = func_get_args();
		$params  = array_slice($args, 1);
		
		if (is_object($statement)) {
			$result = $this->run($statement, 'fUnbufferedResult', $params);
			
		} else {
			$queries = $this->preprocess($statement, $params, FALSE);
			
			if (sizeof($queries) > 1) {
				throw new fProgrammerException(
					'Only a single unbuffered query can be run at a time, however %d were passed',
					sizeof($queries)	
				);
			}
			
			$result = $this->run($queries[0], 'fUnbufferedResult');
		}
		
		$this->unbuffered_result = $result;
		
		return $result;
	}
	
	
	/**
	 * Translates the SQL statement using fSQLTranslation and then executes it
	 * in unbuffered mode. This is optimal for large results sets since it does
	 * not load the whole result set into memory first. The gotcha is that only
	 * one unbuffered result can exist at one time. If another unbuffered query
	 * is executed, the old result will be deleted.
	 * 
	 * @param  string $sql    A single SQL statement
	 * @param  mixed  $value  The optional value(s) to place into any placeholders in the SQL - see ::escape() for details
	 * @param  mixed  ...
	 * @return fUnbufferedResult  The result object for the unbuffered query
	 */
	public function unbufferedTranslatedQuery($sql)
	{
		if (preg_match('#^\s*(ALTER|COMMENT|CREATE|DROP)\s+#i', $sql)) {
			throw new fProgrammerException(
				"The SQL provided, %1$s, appears to be a DDL (data definition language) SQL statement, which can not be run via %2$s because it may result in multiple SQL statements being run. Please use %3$s instead.",
				$sql,
				__CLASS__ . '::unbufferedTranslatedQuery()',
				__CLASS__ . '::translatedExecute()'
			);
		}

		$args    = func_get_args();
		$queries = $this->preprocess(
			$sql,
			array_slice($args, 1),
			TRUE
		);  
		
		if (sizeof($queries) > 1) {
			throw new fProgrammerException(
				'Only a single unbuffered query can be run at a time, however %d were passed',
				sizeof($queries)	
			);
		}
		
		$query_keys = array_keys($queries);
		$key        = $query_keys[0];
		list($number, $original_query) = explode(':', $key, 2);
		
		$result = $this->run($queries[$key], 'fUnbufferedResult');
		$result->setUntranslatedSQL($original_query);
		
		$this->unbuffered_result = $result;
		
		return $result;
	}
	
	
	/**
	 * Unescapes a value coming out of a database based on its data type
	 * 
	 * The valid data types are:
	 * 
	 *  - `'blob'` (or `'%l'`)
	 *  - `'boolean'` (or `'%b'`)
	 *  - `'date'` (or `'%d'`)
	 *  - `'float'` (or `'%f'`)
	 *  - `'integer'` (or `'%i'`)
	 *  - `'string'` (also `'%s'`, `'varchar'`, `'char'` or `'text'`)
	 *  - `'time'` (or `'%t'`)
	 *  - `'timestamp'` (or `'%p'`)
	 * 
	 * @param  string $data_type  The data type being unescaped - see method description for valid values
	 * @param  mixed  $value      The value or array of values to unescape
	 * @return mixed  The unescaped value
	 */
	public function unescape($data_type, $value)
	{
		if ($value === NULL) {
			return $value;	
		}
		
		$callback = NULL;
		
		switch ($data_type) {
			// Testing showed that strings tend to be most common,
			// and moving this to the top of the switch statement
			// improved performance on read-heavy pages
			case 'string':
			case 'varchar':
			case 'char':
			case 'text':
			case '%s':
				return $value;
			
			case 'boolean':
			case '%b':
				$callback = $this->unescapeBoolean;
				break;
				
			case 'date':
			case '%d':
				$callback = $this->unescapeDate;
				break;
				
			case 'float':
			case '%f':
				return $value;
				
			case 'integer':
			case '%i':
				return $value;
			
			case 'time':
			case '%t':
				$callback = $this->unescapeTime;
				break;
				
			case 'timestamp':
			case '%p':
				$callback = $this->unescapeTimestamp;
				break;
			
			case 'blob':
			case '%l':
				$callback = $this->unescapeBlob;
				break;
		}
		
		if ($callback) {
			if (is_array($value)) {
				return array_map($callback, $value);	
			}
			return call_user_func($callback, $value);
		}	
		
		throw new fProgrammerException(
			'Unknown data type, %1$s, specified. Must be one of: %2$s.',
			$data_type,
			'blob, %l, boolean, %b, date, %d, float, %f, integer, %i, string, %s, time, %t, timestamp, %p'
		);	
	}
	
	
	/**
	 * Unescapes a blob coming out of the database
	 * 
	 * @param  string $value  The value to unescape
	 * @return binary  The binary data
	 */
	private function unescapeBlob($value)
	{
		$this->connect();
		
		if ($this->extension == 'pgsql') {
			return pg_unescape_bytea($value);
		} elseif ($this->extension == 'pdo' && is_resource($value)) {
			return stream_get_contents($value);
		} elseif ($this->extension == 'sqlite') {
			return pack('H*', $value);
		} else {
			return $value;
		}
	}
	
	
	/**
	 * Unescapes a boolean coming out of the database
	 * 
	 * @param  string $value  The value to unescape
	 * @return boolean  The boolean
	 */
	private function unescapeBoolean($value)
	{
		return ($value === 'f' || !$value) ? FALSE : TRUE;
	}
	
	
	/**
	 * Unescapes a date coming out of the database
	 * 
	 * @param  string $value  The value to unescape
	 * @return string  The date in YYYY-MM-DD format
	 */
	private function unescapeDate($value)
	{
		if ($this->extension == 'sqlsrv' && $value instanceof DateTime) {
			return $value->format('Y-m-d');
		} elseif ($this->type == 'mssql') {
			$value = preg_replace('#:\d{3}#', '', $value);
		}
		return date('Y-m-d', strtotime($value));
	}
	
	
	/**
	 * Unescapes a time coming out of the database
	 * 
	 * @param  string $value  The value to unescape
	 * @return string  The time in `HH:MM:SS` format
	 */
	private function unescapeTime($value)
	{
		if ($this->extension == 'sqlsrv' && $value instanceof DateTime) {
			return $value->format('H:i:s');
		} elseif ($this->type == 'mssql') {
			$value = preg_replace('#:\d{3}#', '', $value);
		}
		return date('H:i:s', strtotime($value));
	}
	
	
	/**
	 * Unescapes a timestamp coming out of the database
	 * 
	 * @param  string $value  The value to unescape
	 * @return string  The timestamp in `YYYY-MM-DD HH:MM:SS` format
	 */
	private function unescapeTimestamp($value)
	{
		if ($this->extension == 'sqlsrv' && $value instanceof DateTime) {
			return $value->format('Y-m-d H:i:s');
		} elseif ($this->type == 'mssql') {
			$value = preg_replace('#:\d{3}#', '', $value);
		}
		return date('Y-m-d H:i:s', strtotime($value));
	}
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