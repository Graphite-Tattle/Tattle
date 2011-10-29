<?php
/**
 * Representation of a prepared statement for use with the fDatabase class
 * 
 * @copyright  Copyright (c) 2010-2011 Will Bond
 * @author     Will Bond [wb] <will@flourishlib.com>
 * @license    http://flourishlib.com/license
 * 
 * @package    Flourish
 * @link       http://flourishlib.com/fStatement
 * 
 * @version    1.0.0b7
 * @changes    1.0.0b7  Fixed handling of arrays of values for ::execute(), ::executeQuery() and ::executeUnbufferedQuery(), fixed escaping of values that become `NULL` [wb, 2011-05-09]
 * @changes    1.0.0b6  Added ::getUntranslatedSQL() [wb, 2011-01-09]
 * @changes    1.0.0b5  Fixed an edge case where the mysqli extension would leak memory when fetching a `TEXT` or `BLOB` column [wb, 2010-08-28]
 * @changes    1.0.0b4  Updated class to use fCore::startErrorCapture() instead of `error_reporting()` [wb, 2010-08-09]
 * @changes    1.0.0b3  Backwards Compatibility Break - removed ODBC support. Fixed UTF-8 support for the `pdo_dblib` extension. [wb, 2010-07-31]
 * @changes    1.0.0b2  Added IBM DB2 support [wb, 2010-04-13]
 * @changes    1.0.0b   The initial implementation [wb, 2010-03-02]
 */
class fStatement
{	
	/**
	 * This holds references for params in sqlsrv prepared statements
	 * 
	 * @var string
	 */
	private $bound_params = array();
	
	/**
	 * The database the statement was created from
	 * 
	 * @var fDatabase
	 */
	private $database = NULL;
	
	/**
	 * The identifier for this statement - only used by the pgsql extension
	 * 
	 * @var string
	 */
	private $identifier = NULL;
	
	/**
	 * The data type placeholders
	 * 
	 * @var array
	 */
	private $placeholders = array();
	
	/**
	 * The statement object
	 * 
	 * @var mixed
	 */
	private $statement = NULL;
	
	/**
	 * The SQL the statement was created from
	 * 
	 * @var string
	 */
	private $sql = NULL;
	
	/**
	 * The original SQL, if it was translated
	 * 
	 * @var string
	 */
	private $untranslated_sql = NULL;
	
	/**
	 * If the statement has been used yet
	 * 
	 * @var boolean
	 */
	private $used = FALSE;
	
	
	/**
	 * Sets up a prepared statement
	 * 
	 * @internal
	 * 
	 * @param  fDatabase $database            The database object this result set was created from
	 * @param  string    $query               The SQL statement to prepare
	 * @param  array     $placeholders        The data type placeholders
	 * @param  string    $untranslated_query  The original untranslated SQL, if applicable
	 * @return fStatement
	 */
	public function __construct($database, $query, $placeholders, $untranslated_sql)
	{
		if (!$database instanceof fDatabase) {
			throw new fProgrammerException(
				'The database object provided does not appear to be a descendant of fDatabase'
			);
		}
		
		$this->database         = $database;
		$this->placeholders     = $placeholders;
		$this->sql              = vsprintf($query, $placeholders);
		$this->untranslated_sql = $untranslated_sql;
		
		$extension = $this->database->getExtension();
		if ($extension == 'pdo' && $this->database->getType() == 'mssql') {
			$extension = 'pdo_dblib';
		}
		
		switch ($extension) {
			// These database extensions don't have prepared statements
			case 'mssql':
			case 'mysql':
			case 'pdo_dblib':
			case 'sqlite':
				$query = vsprintf($query, $placeholders);
				break;
				
			case 'oci8':	
				$named_placeholders = array();
				for ($i = 1; $i <= sizeof($placeholders); $i++) {
					$named_placeholders[] = ':p' . $i;	
				}
				$query = vsprintf($query, $named_placeholders);
				break;
				
			case 'ibm_db2':
			case 'mysqli':
			case 'pdo':
			case 'sqlsrv':
				$question_marks = array();
				if (sizeof($placeholders)) {
					$question_marks = array_fill(0, sizeof($placeholders), '?');	
				}
				$query = vsprintf($query, $question_marks);
				break;
				
			case 'pgsql':
				$dollar_placeholders = array();
				for ($i = 1; $i <= sizeof($placeholders); $i++) {
					$dollar_placeholders[] = '$' . $i;	
				}
				$query = vsprintf($query, $dollar_placeholders);
				break;
		}
		
		$connection = $this->database->getConnection();
		
		fCore::startErrorCapture(E_WARNING);
		
		switch ($extension) {
			// These database extensions don't have prepared statements
			case 'mssql':
			case 'mysql':
			case 'pdo_dblib':
			case 'sqlite':
				$statement = $query;
				break;
				
			case 'ibm_db2':
				$statement = db2_prepare($connection, $query, array('cursor' => DB2_FORWARD_ONLY));
				break;
			
			case 'mysqli':
				$statement = mysqli_prepare($connection, $query);
				break;
				
			case 'oci8':	
				$statement = oci_parse($connection, $query);
				break;
				
			case 'pdo':
				$statement = $connection->prepare($query);
				break;
				
			case 'pgsql':
				static $statement_number = 0;
				$statement_number++;
				$this->identifier = 'fstmt' . $statement_number;
				$statement = pg_prepare($connection, $this->identifier, $query);
				break;
				
			case 'sqlsrv':
				$params = array();
				for ($i = 0; $i < sizeof($placeholders); $i++) {
					if ($placeholders[$i] == '%s') {
						$this->bound_params[$i] = array(
							NULL,
							SQLSRV_PARAM_IN,
							SQLSRV_PHPTYPE_STRING('UTF-8')
						);	
					} else {
						$this->bound_params[$i] = array(NULL);
					}
					$params[$i] =& $this->bound_params[$i];
				}
				$statement = sqlsrv_prepare($connection, $query, $params);
				break;
		}
		
		fCore::stopErrorCapture();
		
		if (!$statement) {
			switch ($extension) {
				case 'ibm_db2':
					$message = db2_stmt_errormsg($statement);
					break;
				
				case 'mysqli':
					$message = mysqli_error($connection);
					break;
					
				case 'oci8':
					$error_info = oci_error($statement);
					$message = $error_info['message'];
					break;
					
				case 'pgsql':
					$message = pg_last_error($connection);
					break;
				
				case 'sqlsrv':
					$error_info = sqlsrv_errors(SQLSRV_ERR_ALL);
					$message = $error_info[0]['message'];
					break;
					
				case 'pdo':
					$error_info = $connection->errorInfo();
					$message = $error_info[2];
					break;
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
				$db_type_map[$this->database->getType()],
				$message,
				$this->sql
			);	
		}
		
		$this->statement = $statement;
	}
	
	
	/**
	 * Frees up the result object to save memory
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	public function __destruct()
	{
		if (!$this->statement) {
			return;	
		}
		
		$extension = $this->database->getExtension();
		if ($extension == 'pdo' && $this->database->getType() == 'mssql') {
			$extension = 'pdo_dblib';
		}
		
		switch ($extension) {
			case 'ibm_db2':
				db2_free_stmt($this->statement);
				break;
				
			case 'pdo':
				$this->statement->closeCursor();
				break;
				
			case 'oci8':
				oci_free_statement($this->statement);
				break;
				
			case 'sqlsrv':
				sqlsrv_free_stmt($this->statement);
				break;
		}
		
		unset($this->statement);
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
	 * Executes the statement without returning a result
	 * 
	 * @internal
	 * 
	 * @param  array   $params     The parameters for the statement
	 * @param  mixed   &$extra     A variable to place extra information needed by some database extensions
	 * @param  boolean $different  If this statement is different than the last statement run on the fDatabase instance
	 * @return mixed  The (usually boolean) result of the extension function/method call
	 */
	public function execute($params, &$extra, $different)
	{
		if (is_array($params) && count($params) == 1 && is_array($params[0]) && count($this->placeholders) > 1) {
			$params = $params[0];
		}

		if ($different && $this->used) {
			$this->regenerateStatement();
		}
		$this->used = TRUE;
		
		$extension = $this->database->getExtension();
		if ($extension == 'pdo' && $this->database->getType() == 'mssql') {
			$extension = 'pdo_dblib';
		}
		
		$connection = $this->database->getConnection();
		$statement  = $this->statement;
		
		$params = $this->prepareParams($params);
				
		switch ($extension) {
			case 'ibm_db2':
				$extra  = $statement;
				$result = db2_execute($statement, $params);
				break;
				
			case 'mssql':
				$result = mssql_query($this->database->escape($statement, $params), $connection);
				break;
				
			case 'mysql':
				$result = mysql_unbuffered_query($this->database->escape($statement, $params), $connection);
				break;
				
			case 'mysqli':
				$result = mysqli_stmt_execute($statement);
				break;
				
			case 'oci8':
				$result = oci_execute($statement, $this->database->isInsideTransaction() ? OCI_DEFAULT : OCI_COMMIT_ON_SUCCESS);
				$extra  = $this->statement;
				break;
				
			case 'pgsql':
				$result = pg_execute($connection, $this->identifier, $params);
				break;
				
			case 'sqlite':
				$result = sqlite_exec($connection, $this->database->escape($statement, $params), $extra);
				break;
				
			case 'sqlsrv':
				$result = sqlsrv_execute($this->statement);
				break;
				
			case 'pdo':
				$extra  = $statement;
				$result = $statement->execute();
				break;
			
			case 'pdo_dblib':
				$sql = $this->database->escape($statement, $params);
				if (!fCore::checkOS('windows')) {
					$result = $connection->query($sql);
					if ($result instanceof PDOStatement) {
						$extra = $result;
						$result->closeCursor();
						$result = TRUE;
					} else {
						$result = FALSE;
					}
				} else {
					$result = $connection->exec($sql);
				}
				break;
		}
		
		return $result;
	}
	
	
	/**
	 * Executes the statement in buffered mode
	 * 
	 * @internal
	 * 
	 * @param  fResult $result     The object to place the result into
	 * @param  array   $params     The parameters for the statement
	 * @param  mixed   &$extra     A variable to place extra information needed by some database extensions
	 * @param  boolean $different  If this statement is different than the last statement run on the fDatabase instance
	 * @return void
	 */
	public function executeQuery($result, $params, &$extra, $different)
	{
		if (is_array($params) && count($params) == 1 && is_array($params[0]) && count($this->placeholders) > 1) {
			$params = $params[0];
		}

		if ($different && $this->used) {
			$this->regenerateStatement();
		}
		$this->used = TRUE;
		
		$extension = $this->database->getExtension();
		if ($extension == 'pdo' && $this->database->getType() == 'mssql') {
			$extension = 'pdo_dblib';
		}
		
		$connection = $this->database->getConnection();
		$statement  = $this->statement;
		
		$params = $this->prepareParams($params);
				
		switch ($extension) {
			case 'ibm_db2':
				$extra = $statement;
				if (db2_execute($statement, $params)) {
					$rows = array();
					while ($row = db2_fetch_assoc($statement)) {
						$rows[] = $row;
					}
					$result->setResult($rows);
					unset($rows);
				} else {
					$result->setResult(FALSE);
				}
				break;
				
			case 'mssql':
				$result->setResult(mssql_query($this->database->escape($statement, $params), $connection));
				break;
			
			case 'mysql':
				$result->setResult(mysql_query($this->database->escape($statement, $params), $connection));
				break;
			
			case 'mysqli':
				$extra = $this->statement;
				if ($statement->execute()) {
					$statement->store_result();
					$rows = array();
					$meta = $statement->result_metadata();
					if ($meta) {
						$row_references = array();
						while ($field = $meta->fetch_field()) {
							$row_references[] = &$row[$field->name];
						}

						call_user_func_array(array($statement, 'bind_result'), $row_references);
						
						while ($statement->fetch()) {
							$copied_row = array();
							foreach($row as $key => $val)
							{
								$copied_row[$key] = $val;
							}
							$rows[] = $copied_row;
						}
						unset($row_references);
						$meta->free_result();
					}
					$result->setResult($rows);
					$statement->free_result();
				} else {
					$result->setResult(FALSE);	
				}
				break;
			
			case 'oci8':
				$extra = $this->statement;
				if (oci_execute($extra, $this->database->isInsideTransaction() ? OCI_DEFAULT : OCI_COMMIT_ON_SUCCESS)) {
					// oci8 complains if you try to fetch results multiple times from a prepared statement
					// if the statement does not returns any rows, so we ignore easy-to-detect non-selects
					if (!preg_match('#\s*(INSERT|UPDATE|DELETE)\s+#i', $this->sql)) {
						oci_fetch_all($extra, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
					} else {
						$rows = array();
					}
					$result->setResult($rows);
					unset($rows);	
				} else {
					$result->setResult(FALSE);
				}
				break;
			
			case 'pgsql':
				$result->setResult(pg_execute($connection, $this->identifier, $params));
				break;
			
			case 'sqlite':
				$result->setResult(sqlite_query($connection, $this->database->escape($statement, $params), SQLITE_ASSOC, $extra));
				break;
			
			case 'sqlsrv':
				$extra = $statement;
				if (sqlsrv_execute($statement)) {
					$rows = array();
					while ($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
						$rows[] = $row;
					}
					$result->setResult($rows);
					unset($rows);
				} else {
					$result->setResult(FALSE);
				}
				break;
			
			case 'pdo':
				$extra = $this->statement;
				if (preg_match('#^\s*CREATE(\s+OR\s+REPLACE)?\s+TRIGGER#i', $result->getSQL())) {
					$extra->execute();
					$returned_rows = array();
				} else {
					if (!$extra->execute()) {
						$returned_rows = FALSE;
					} else {
						// This fixes a segfault issue with blobs and fetchAll() for pdo_ibm
						if ($this->database->getType() == 'db2') {
							$returned_rows = array();
							$scanned_for_blobs = FALSE;
							$blob_columns = array();
							while (($row = $extra->fetch(PDO::FETCH_ASSOC)) !== FALSE) {
								if (!$scanned_for_blobs) {
									foreach ($row as $key => $value) {
										if (is_resource($value)) {
											$blob_columns[] = $key;
										}
									}
								}
								foreach ($blob_columns as $blob_column) {
									$row[$blob_column] = stream_get_contents($row[$blob_column]);
								}
								$returned_rows[] = $row;
							}
						} else {
							$returned_rows = $extra->fetchAll(PDO::FETCH_ASSOC);
						}
						
						// The pdo_pgsql driver likes to return empty rows equal to the number of affected rows for insert and deletes
						if ($this->database->getType() == 'postgresql' && $returned_rows && $returned_rows[0] == array()) {
							$returned_rows = array(); 		
						}	
					}
				}
				
				$result->setResult($returned_rows);
				break;
			
			case 'pdo_dblib':
				$extra = $connection->query($this->database->escape($statement, $params));
				$returned_rows = (is_object($extra)) ? $extra->fetchAll(PDO::FETCH_ASSOC) : $extra;
				$result->setResult($returned_rows);
				break;
		}
		
		return $result;		
	}
	
	
	/**
	 * Executes the statement in unbuffered mode (if possible)
	 * 
	 * @internal
	 * 
	 * @param  fUnbufferedResult $result     The object to place the result into
	 * @param  array             $params     The parameters for the statement
	 * @param  mixed             &$extra     A variable to place extra information needed by some database extensions
	 * @param  boolean           $different  If this statement is different than the last statement run on the fDatabase instance
	 * @return void
	 */
	public function executeUnbufferedQuery($result, $params, &$extra, $different)
	{
		if (is_array($params) && count($params) == 1 && is_array($params[0]) && count($this->placeholders) > 1) {
			$params = $params[0];
		}

		if ($different && $this->used) {
			$this->regenerateStatement();
		}
		$this->used = TRUE;
		
		$extension = $this->database->getExtension();
		if ($extension == 'pdo' && $this->database->getType() == 'mssql') {
			$extension = 'pdo_dblib';
		}
		
		$connection = $this->database->getConnection();
		$statement  = $this->statement;
		
		$params = $this->prepareParams($params);
		
		// For the extensions that require the statement be passed to the result
		// object, we store it in a stdClass object so the result object knows
		// not to free it when done
		$statement_holder = new stdClass;
		$statement_holder->statement = NULL;
				
		switch ($extension) {
			case 'ibm_db2':
				$extra = $statement;
				if (db2_execute($statement, $params)) {
					$statement_holder->statement = $statement;
				} else {
					$result->setResult(FALSE);
				}
				break;
			
			case 'mssql':
				$result->setResult(mssql_query($result->getSQL(), $this->connection, 20));
				break;
			
			case 'mysql':
				$result->setResult(mysql_unbuffered_query($result->getSQL(), $this->connection));
				break;
			
			case 'mysqli':
				$extra = $this->statement;
				if ($statement->execute()) {
					$statement_holder->statement = $statement;
				} else {
					$result->setResult(FALSE);	
				}
				break;
			
			case 'oci8':
				$result->setResult(oci_execute($statement, $this->database->isInsideTransaction() ? OCI_DEFAULT : OCI_COMMIT_ON_SUCCESS));
				break;
			
			case 'pgsql':
				$result->setResult(pg_execute($connection, $this->identifier, $params));
				break;
			
			case 'sqlite':
				$result->setResult(sqlite_unbuffered_query($connection, $this->database->escape($statement, $params), SQLITE_ASSOC, $extra));
				break;
			
			case 'sqlsrv':
				$extra = sqlsrv_execute($statement);
				if ($extra) {
					$statement_holder->statement = $statement;
				} else {
					$result->setResult($extra);
				}
				break;
			
			case 'pdo':
				$extra = $statement->execute();
				if ($extra) {
					$result->setResult($statement);
				} else {
					$result->setResult($extra);	
				}
				break;
			
			case 'pdo_dblib':
				$sql = $this->database->escape($statement, $params);
				$result->setResult($res = $connection->query($sql));
				break;
		}
		
		if ($statement_holder->statement) {
			$result->setResult($statement_holder);	
		}
		
		return $result;		
	}
	
	
	/**
	 * Returns the SQL for the prepared statement
	 * 
	 * @return string  The SQL statement
	 */
	public function getSQL()
	{
		return $this->sql;	
	}
	
	
	/**
	 * Returns the untranslated SQL for the prepared statement
	 * 
	 * @return string  The untranslated SQL statement
	 */
	public function getUntranslatedSQL()
	{
		return $this->untranslated_sql;	
	}
	
	
	/**
	 * Takes an array of parameters and prepares them for use in a prepared statement
	 * 
	 * @param  array $params  The parameters
	 * @return array  The prepared parameters
	 */
	private function prepareParams($params)
	{
		$type      = $this->database->getType();
		$extension = $this->database->getExtension();
		if ($extension == 'pdo' && $this->database->getType() == 'mssql') {
			$extension = 'pdo_dblib';
		}
		
		$statement  = $this->statement;
		$new_params = array();
		
		// The mysqli extension requires all params be set in one call
		$params_reference = array();
		$params_type      = '';
		
		foreach ($params as $i => $param) {
			// Prepared statements don't support multi-value params
			if (is_array($param)) {
				throw new fProgrammerException(
					'The value passed for placeholder #%s is an array, however multiple values are not supported with prepared statements',
					$i+1
				);	
			}
			
			// A few of the database extensions don't have prepared statement support
			// so instead we don't bother preparing the params, we just do a normal escape
			if (in_array($extension, array('mssql', 'mysql', 'pdo_dblib', 'sqlite'))) {
				$new_params[] = $params[$i];
				continue;		
			}
			
			$placeholder = $this->placeholders[$i];
			
			// We want to normally escape all values except strings
			// since that actually changed the string
			if (!in_array($placeholder, array('%l', '%s'))) {
				
				$params[$i] = $this->database->escape($placeholder, $params[$i]);

				if ($params[$i] === 'NULL') {
					$params[$i] = NULL;

				} else {
					// Dates, times, timestamps and some booleans need to be unquoted
					$date_time_types = in_array($placeholder, array('%d', '%t', '%p'));
					$bool_types      = ($type == 'mssql' || $type == 'sqlite' || $type == 'db2') && $placeholder == '%b';
					if ($date_time_types || $bool_types) {
						$params[$i] = substr($params[$i], 1, -1);	
					
					// Some booleans need to be converted to integers
					} elseif ($placeholder == '%b' && ($type == 'postgresql' || $type == 'mysql')) {
						$params[$i] = $params[$i] == 'TRUE' ? 1 : 0;
					}
				}				
					
			// For strings and blobs we need to manually cast objects
			// This is done in fDatabase::escape() for all other types
			} else {
				if (is_object($params[$i]) && is_callable(array($params[$i], '__toString'))) {
					$params[$i] = $params[$i]->__toString();
				} elseif (is_object($params[$i])) {
					$params[$i] = (string) $params[$i];	
				}
			}
			
			// For the database extensions that require is, here we bind params
			// to the actual statements using the appropriate data types
			switch ($extension) {
				
				case 'mysqli':
					switch ($placeholder) {
						case '%l':
							// Blobs that are larger than the packet size have to have NULL
							// bound and then the data sent via the long data function
							$n = NULL;
							$params_type         .= 'b';
							$params_reference[$i] = &$params[$i];
							$statement->send_long_data($i, $params[$i]);
							break;
						case '%b':
							$params_type         .= 'i';
							$params_reference[$i] = &$params[$i];
							break;
						case '%f':
							$params_type         .= 'd';
							$params_reference[$i] = &$params[$i];
							break;
						case '%d':
						case '%i': // Ints are sent as strings to get past 32 bit int limit, allowing unsigned ints
						case '%s':
						case '%t':
						case '%p':
							$params_type         .= 's';
							$params_reference[$i] = &$params[$i];
							break;
					}
					break;
			
				case 'oci8':
					switch ($placeholder) {
						case '%l':
							oci_bind_by_name($statement, ':p' . ($i+1), $params[$i], -1, SQLT_BLOB);
							break;
						case '%b':
						case '%i':
							oci_bind_by_name($statement, ':p' . ($i+1), $params[$i], -1, SQLT_INT);
							break;
						case '%d':
						case '%f': // There is no constant for floats, so we treat them as strings
						case '%s':
						case '%t':
						case '%p':
							oci_bind_by_name($statement, ':p' . ($i+1), $params[$i], -1, SQLT_CHR);
							break;
					}
					break;
				
				case 'pdo':
					switch ($placeholder) {
						case '%l':
							$statement->bindParam($i+1, $params[$i], $params[$i] === NULL ? PDO::PARAM_NULL : PDO::PARAM_LOB);
							break;
						case '%b':
							$statement->bindParam($i+1, $params[$i], $params[$i] === NULL ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
							break;
						case '%i':
							$statement->bindParam($i+1, $params[$i], $params[$i] === NULL && $type != 'db2' ? PDO::PARAM_NULL : PDO::PARAM_INT);
							break;
						case '%d':
						case '%f': // For some reason float are supposed to be bound as strings
						case '%s':
						case '%t':
						case '%p':
							$statement->bindParam($i+1, $params[$i], $params[$i] === NULL ? PDO::PARAM_NULL : PDO::PARAM_STR);
							break;
					}
					break;
				
				case 'sqlsrv':
					$this->bound_params[$i][0] = $params[$i];
					break;
			}
			
			$new_params[] = $params[$i];
		}
		
		if ($extension == 'mysqli') {
			array_unshift($params_reference, $params_type);
			call_user_func_array(array($statement, 'bind_param'), $params_reference);	
		}
		
		return $new_params;
	}
	
	
	/**
	 * The MySQL PDO driver has issues if you try to reuse a prepared statement
	 * without any placeholders.
	 * 
	 * @return void
	 */
	private function regenerateStatement()
	{
		$is_pdo   = $this->database->getExtension() == 'pdo';
		$is_mysql = $this->database->getType() == 'mysql';
		if ($this->placeholders || !$is_pdo || !$is_mysql) {
			return;
		}
		
		$this->statement = $this->database->getConnection()->prepare($this->sql);
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
