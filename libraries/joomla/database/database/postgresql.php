<?php
/**
 * @package		Joomla.Platform
 * @subpackage	Database
 * 
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

JLoader::register('JDatabaseQueryPostgreSQL', dirname(__FILE__).'/postgresqlquery.php');

/**
 * PostgreSQL database driver
 *
 * @package		Joomla.Framework
 * @subpackage	Database
 * @since		11.3
 */
class JDatabasePostgreSQL extends JDatabase
{
	/**
	 * The database driver name
	 *
	 * @var string
	 */
	public $name = 'postgresql';

	/**
	 *  The null/zero date string
	 *
	 * @var string
	 */
	protected $nullDate = '1970-01-01 00:00:00';

	/**
	 * Quote for named objects
	 *
	 * @var string
	 */
	protected $nameQuote = '"';

	/**
	 * Operator used for concatenation
	 *
	 * @var string
	 */
	protected $concat_operator = '||';

	/**
	 * Database object constructor
	 *
	 * @param	array	List of options used to configure the connection
	 * @since	11.3
	 * @see		JDatabase
	 */
	function __construct( $options )
	{	
		$host		= (isset($options['host']))	? $options['host']		: 'localhost';
		$user		= (isset($options['user']))	? $options['user']		: '';
		$password	= (isset($options['password']))	? $options['password']	: '';
		$database	= (isset($options['database'])) ? $options['database']	: '';

		// perform a number of fatality checks, then return gracefully
		if (!function_exists( 'pg_connect' )) {
			// Legacy error handling switch based on the JError::$legacy switch.
			// @deprecated  11.3
			if (JError::$legacy) {
				$this->errorNum = 1;
				$this->errorMsg = JText::_('JLIB_DATABASE_ERROR_ADAPTER_POSTGRESQL');  // -> 'The PostgreSQL adapter "pg" is not available.';
				return;
			}
			else
				throw new JDatabaseException(JText::_('JLIB_DATABASE_ERROR_ADAPTER_POSTGRESQL'));  // -> 'The PostgreSQL adapter "pg" is not available.';
		}

		// connect to the server
		if (!($this->connection = @pg_connect( "host={$host} dbname={$database} user={$user} password={$password}" ))) {
			// Legacy error handling switch based on the JError::$legacy switch.
			// @deprecated  11.3
			if (JError::$legacy) {
				$this->errorNum = 2;
				$this->errorMsg = JText::_('JLIB_DATABASE_ERROR_CONNECT_POSTGRESQL');  // -> 'The PostgreSQL adapter "pg" is not available.';
				return;
			}
			else
				throw new JDatabaseException(JText::_('JLIB_DATABASE_ERROR_CONNECT_POSTGRESQL'));  // -> 'The PostgreSQL adapter "pg" is not available.';
		}

		// finalize initialization
		parent::__construct($options);
	}

	/**
	 * Database object destructor
	 *
	 * @return void
	 * @since 11.3
	 */
	public function __destruct()
	{
		if (is_resource($this->connection)) {
			pg_close($this->connection);
		}
	}
	
	/**
	 * Method to escape a string for usage in an SQL statement.
	 *
	 * @param   string  $text   The string to be escaped.
	 * @param   boolean    $extra  Optional parameter to provide extra escaping.
	 *
	 * @return  string  The escaped string.
	 *
	 * @since   11.3
	 */
	public function escape($text, $extra = false)
	{
		$result = pg_escape_string( $this->getConnection() , $text );

		if ($extra) {
			$result = addcslashes($result, '%_');
		}

		return $result;
	}
		
	/**
	 * Test to see if the PostgreSQL connector is available
	 *
	 * @return boolean  True on success, false otherwise.
	 */
	public static function test()
	{
		return (function_exists( 'pg_connect' ));
	}

	/**
	 * Determines if the connection to the server is active.
	 *
	 * @return	boolean
	 * @since	11.3
	 */
	public function connected()
	{
		if(is_resource($this->connection)) {
			return pg_ping($this->connection);
		}
		return false;
	}
	
	
	/**
	 * Drops a table from the database.
	 *
	 * @param   string  $tableName  The name of the database table to drop.
	 * @param   boolean    $ifExists   Optionally specify that the table must exist before it is dropped.
	 *
	 * @return  boolean	true
	 * @since   11.3
	 */
	function dropTable($tableName, $ifExists = true)
	{
		//$query = $this->getQuery(true);

		$query = 'DROP TABLE ' ;
		if ( $ifExists )
			$query .= ' IF EXISTS ';
		
		$query .= $this->quoteName($tableName) ; 
			/*($ifExists ? ' IF EXISTS ' : '') .
			$query->quoteName($tableName) ;
			*/
		$this->setQuery( $query
			/*'DROP TABLE '.
			($ifExists ? 'IF EXISTS ' : '').
			$query->quoteName($tableName)*/
		);

		$this->query();

		return true;
	}
	
	/**
	 * Get the number of affected rows for the previous executed SQL statement.
	 *
	 * @return int The number of affected rows in the previous operation
	 * 
	 * @since 11.3
	 */
	public function getAffectedRows()
	{
		return pg_affected_rows( $this->cursor );
	}
	
	/**
	 * Method to get the database collation in use by sampling a text field of a table in the database.
	 *
	 * @return  mixed  The collation in use by the database or boolean false if not supported.
	 *
	 * @since   11.3
	 */
	public function getCollation()
	{
		$this->setQuery( 'SHOW LC_COLLATE' );
		$array = $this->loadAssocList();
		return $array[0]['lc_collate'];
	}
	
	/**
	 * Gets an exporter class object.
	 *
	 * @return  JDatabaseExporterMySQL  An exporter object.
	 * 
	 * @todo	Not yet implemented
	 *
	 * @since   11.3
	 */
	/*public function getExporter()
	{
		// Make sure we have an exporter class for this driver.
		if (!class_exists('JDatabaseExporterMySQL')) {
			throw new JDatabaseException(JText::_('JLIB_DATABASE_ERROR_MISSING_EXPORTER'));
		}

		$o = new JDatabaseExporterMySQL;
		$o->setDbo($this);

		return $o;
	}*/

	/**
	 * Gets an importer class object.
	 *
	 * @return  JDatabaseImporterMySQL  An importer object.
	 * 
	 * @todo	Not yet implemented
	 *
	 * @since   11.3
	 */
	/*public function getImporter()
	{
		// Make sure we have an importer class for this driver.
		if (!class_exists('JDatabaseImporterMySQL')) {
			throw new JDatabaseException(JText::_('JLIB_DATABASE_ERROR_MISSING_IMPORTER'));
		}

		$o = new JDatabaseImporterMySQL;
		$o->setDbo($this);

		return $o;
	}*/
	
	/**
	 * Get the number of returned rows for the previous executed SQL statement.
	 *
	 * @param   resource  $cursor  An optional database cursor resource to extract the row count from.
	 *
	 * @return  integer   The number of returned rows.
	 *
	 * @since   11.3
	 */
	public function getNumRows( $cur = null )
	{
		return pg_num_rows( $cur ? $cur : $this->cursor );
	}
	
	/**
	 * Get the current or query, or new JDatabaseQuery object.
	 *
	 * @param   boolean   $new  False to return the last query set, True to return a new JDatabaseQuery object.
	 *
	 * @return  mixed  The current value of the internal SQL variable or a new JDatabaseQuery object.
	 *
	 * @since   11.3
	 * @throws  JDatabaseException
	 */
	function getQuery($new = false)
	{
		if ($new) {
			// Make sure we have a query class for this driver.
			if (!class_exists('JDatabaseQueryPostgreSQL')) {
				throw new JDatabaseException(JText::_('JLIB_DATABASE_ERROR_MISSING_QUERY'));
			}
			return new JDatabaseQueryPostgreSQL($this);
		}
		else {
			return $this->sql;
		}
	}
	
	/**
	 * Shows the table CREATE statement that creates the given tables.
	 * 
	 * This is unsuported by PostgreSQL.
	 *
	 * @param   mixed  $tables  A table name or a list of table names.
	 *
	 * @since   11.3
	 * @throws  JDatabaseException
	 */	
	public function getTableCreate($tables)
	{
		return '';
	}
	
	/**
	 * Get the details list of keys for a table.
	 *
	 * @param   string  $table  The name of the table.
	 *
	 * @return  array  An array of the column specification for the table.
	 *
	 * @since   11.3
	 * @throws  JDatabaseException
	 */
	public function getTableKeys($table)
	{
		// To check if table exists and prevent SQL injection
		$tableList = $this->getTableList();
		
		if ( in_array($table, $tableList) )
		{
			// Get the details columns information.
			$query = $this->getQuery();
			$query->select('pgClass2nd.relname, pgIndex.*')
				  ->from ( 'pg_class AS pgClassFirst , pg_index AS pgIndex, pg_class AS pgClass2nd' )
				  ->where( 'pgClassFirst.oid=pgIndex.indrelid' )
				  ->where( 'pgClass2nd.relfilenode=pgIndex.indexrelid' )
				  ->where( 'pgClassFirst.relname=' . $this->quote($table) );
			$this->setQuery($query);
			$keys = $this->loadObjectList();
			
			return $keys;	
		}
		return false;
	}
	
	/**
	 * Method to get an array of all tables in the database.
	 *
	 * @return  array  An array of all the tables in the database.
	 *
	 * @since   11.3
	 * @throws  JDatabaseException
	 */
	public function getTableList()
	{
		$query = $this->getQuery(true);
		$query->select('table_name')
			  ->from( 'information_schema.tables' )
			  ->where( 'table_type=' .  $this->quote('BASE TABLE') )
			  ->where( 'table_schema NOT IN (' . $this->quote('pg_catalog') . ', '
			  								   . $this->quote('information_schema') .' )' );
		$this->setQuery($query);
		$tables = $this->loadColumn();
		
		return $tables;
	}
	
	/**
	 * Get the version of the database connector.
	 *
	 * @return  string  The database connector version.
	 *
	 * @since   11.3
	 */
	public function getVersion()
	{
		$version = pg_version( $this->connection );
		return $version['server'];
	}
	
	/**
	 * Determines if the database engine supports UTF-8 character encoding.
	 *
	 * @return  boolean  True if supported.
	 *
	 * @since   11.3
	 * @deprecated 12.1
	 */
	public function hasUTF()
	{
		JLog::add('JDatabasePostgreSQL::hasUTF() is deprecated.', JLog::WARNING, 'deprecated');
		return true;
	}
	
	/**
	 * Method to get the auto-incremented value from the last INSERT statement.
	 * To be called after the INSERT statement, it's MANDATORY to have a sequence on 
	 * every primary key table.
	 *
	 *	To get the auto incremented value it's possible to call this function after
	 *	INSERT INTO query, or use INSERT INTO with RETURNING clause.
	 *
	 *	@example with insertid() call:
	 *		$query = $this->getQuery(true);		
	 *		$query->insert('jos_dbtest')
	 *				->columns('title,start_date,description')
	 *				->values("'testTitle2nd','1971-01-01','testDescription2nd'");
	 *		$this->setQuery($query);
	 *		$this->query();
	 *		$id = $this->insertid();
	 *
	 *	@example with RETURNING clause:
	 *		$query = $this->getQuery(true);		
	 *		$query->insert('jos_dbtest')
	 *				->columns('title,start_date,description')
	 *				->values("'testTitle2nd','1971-01-01','testDescription2nd'")
	 *				->returning('id');
	 *		$this->setQuery($query);
	 *		$id = $this->loadResult();
	 *
	 * @return  integer  The value of the auto-increment field from the last inserted row. 
	 * @since   11.3
	 */
	public function insertid()
	{
		$insertQuery = $this->getQuery();
		$table = $insertQuery->getInsertTable();
		
		/* find sequence column name */
		$colNameQuery = $this->getQuery(true);
		$colNameQuery->select('column_default')
					 ->from('information_schema.columns')
					 ->where("table_name=" . $this->quote($table[0]), 'AND')
					 ->where("column_default LIKE '%nextval%'");
		
 		$this->setQuery( $colNameQuery );
		$colName = $this->loadRow();
		$changedColName = str_replace('nextval', 'currval', $colName);
		
		$insertidQuery = $this->getQuery(true);
		$insertidQuery->select( $changedColName );
		$this->setQuery( $insertidQuery );
		$insertVal = $this->loadRow();
		
		return $insertVal; 
	}
	
	/**
	 * Execute the query
	 *
	 * @return mixed A database resource if successful, FALSE if not.
	 */
	public function query()
	{
		if (!is_resource($this->connection)) 
		{
			// Legacy error handling switch based on the JError::$legacy switch.
			// @deprecated  11.3
			if (JError::$legacy) 
			{
				if ($this->debug) 
				{
					JError::raiseError(500, 'JDatabasePostgreSQL::query: '.$this->errorNum.' - '.$this->errorMsg);
				}
				return false;
			}
			else 
			{
				JLog::add(JText::sprintf('JLIB_DATABASE_QUERY_FAILED', $this->errorNum, $this->errorMsg), JLog::ERROR, 'database');
				throw new JDatabaseException;
			}
		}

		// Take a local copy so that we don't modify the original query and cause issues later
		$sql = $this->replacePrefix((string) $this->sql);
		if ($this->limit > 0 || $this->offset > 0) 
		{
			$sql .= ' LIMIT '.$this->limit.' OFFSET '.$this->offset;
		}
		
		// If debugging is enabled then let's log the query.
		if ($this->debug) 
		{
			// Increment the query counter and add the query to the object queue.
			$this->count++;
			$this->log[] = $sql;

			JLog::add($sql, JLog::DEBUG, 'databasequery');
		}
		// Reset the error values.
		$this->errorNum = 0;
		$this->errorMsg = '';
		
		// Execute the query.
		$this->cursor = pg_query( $this->connection, $sql );

		if (!$this->cursor) 
		{
			$this->errorNum = (int) pg_result_error_field( $this->cursor, PGSQL_DIAG_SQLSTATE ) . ' ';
			$this->errorMsg = (string) pg_result_error_field( $this->cursor, PGSQL_DIAG_MESSAGE_PRIMARY )." SQL=$sql <br />";
			
			// Legacy error handling switch based on the JError::$legacy switch.
			// @deprecated  11.3
			if (JError::$legacy) 
			{
				if ($this->debug) 
				{
					JError::raiseError(500, 'JDatabasePostgreSQL::query: '.$this->errorNum.' - '.$this->errorMsg );
				}
				return false;
			}
			else 
			{
				JLog::add(JText::sprintf('JLIB_DATABASE_QUERY_FAILED', $this->errorNum, $this->errorMsg), JLog::ERROR, 'databasequery');
				throw new JDatabaseException;
			}
		}
		return $this->cursor;
	}
	
	
	/**
	 * Selects the database, but redundant for PostgreSQL
	 *
	 * @return boolean Always true
	 */
	public function select($database=null) 
	{
		return true;
	}
	

	/**
	 * Custom settings for UTF support
	 */
	public function setUTF()
	{
		pg_set_client_encoding( $this->connection, 'UTF8' );
	}

	/**
	 * Returns an array containing database's table list.
	 *
	 * @return	array	The database's table list.
	 */
	public function showTables()
	{
		$query = "SELECT table_name FROM information_schema.tables " .
				 "WHERE table_type = 'BASE TABLE' AND ". 
				 "table_schema NOT IN ('pg_catalog', 'information_schema')";
		
		$this->setQuery( $query );
		$tableList = $this->loadColumn();
		return $tableList;
	}
	
	/**
	 * Method to fetch a row from the result set cursor as an array.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 *
	 * @since   11.3
	 */
	protected function fetchArray($cursor = null)
	{
		return pg_fetch_row($cursor ? $cursor : $this->cursor);
	}

	/**
	 * Method to fetch a row from the result set cursor as an associative array.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 *
	 * @since   11.3
	 */
	protected function fetchAssoc($cursor = null)
	{
		return pg_fetch_assoc($cursor ? $cursor : $this->cursor);
	}

	/**
	 * Method to fetch a row from the result set cursor as an object.
	 *
	 * @param   mixed   $cursor  The optional result set cursor from which to fetch the row.
	 * @param   string  $class   The class name to use for the returned row object.
	 *
	 * @return  mixed   Either the next row from the result set or false if there are no more rows.
	 *
	 * @since   11.3
	 */
	protected function fetchObject($cursor = null, $class = 'stdClass')
	{
		return pg_fetch_object( is_null($cursor) ? $this->cursor : $cursor, null, $class );
	}

	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 */
	protected function freeResult($cursor = null)
	{
		pg_free_result($cursor ? $cursor : $this->cursor);
	}
	
	/**
	 * Diagnostic method to return explain information for a query.
	 *
	 * @return      string  The explain output.
	 *
	 * @since       11.1
	 * @deprecated  11.2
	 */
	public function explain()
	{
		// Deprecation warning.
		JLog::add('JDatabase::explain() is deprecated.', JLog::WARNING, 'deprecated');
		
		$temp = $this->sql;
		$this->sql = "EXPLAIN $this->sql";

		if (!($cur = $this->query())) {
			return null;
		}
		$first = true;

		$buffer = '<table id="explain-sql">';
		$buffer .= '<thead><tr><td colspan="99">'.$this->getQuery().'</td></tr>';
		while ($row = $this->fetchAssoc($cursor)) {
			if ($first) {
				$buffer .= '<tr>';
				foreach ($row as $k=>$v) {
					$buffer .= '<th>'.$k.'</th>';
				}
				$buffer .= '</tr>';
				$first = false;
			}
			$buffer .= '</thead><tbody><tr>';
			foreach ($row as $k=>$v) {
				$buffer .= '<td>'.$v.'</td>';
			}
			$buffer .= '</tr>';
		}
		$buffer .= '</tbody></table>';
		
		// Restore the original query to it's state before we ran the explain.
		$this->sql = $temp;
		
		// Free up system resources and return.
		$this->freeResult($cursor);

		return $buffer;
	}

	/**
	 * Retrieves field information about a given table.
	 *
	 * @param   string  $table     The name of the database table.
	 * @param   boolean    $typeOnly  True to only return field types.
	 *
	 * @return  array  An array of fields for the database table.
	 *
	 * @since   11.3
	 * @throws  JDatabaseException
	 */
	public function getTableColumns( $table, $typeonly = true )
	{
		$result = array();

		$tableSub = $this->replacePrefix( $table );
		
		$query = $this->getQuery(true);
		$query->select('column_name, data_type, collation_name, is_nullable, column_default')
			  ->from( 'information_schema.columns' )
			  ->where( 'table_name=' . $this->quote($tableSub) ); /*$tblval*/
		$this->setQuery($query);
		
		$fields = $this->loadObjectList();

		if ($typeonly) {
			foreach ($fields as $field) {
				$result[$field->column_name] = preg_replace("/[(0-9)]/",'', $field->data_type );
			}
		} else {
			foreach ($fields as $field) {
				$result[$field->column_name] = $field;
			}
		}

		return $result;
	}
	
	
	
	/* EXTRA FUNCTION postgreSQL */
	
	/**
	 * Get the substring position inside a string
	 *
	 * @param string The string being sought
	 * @param string The string/column being searched
	 * @return int   The position of $substring in $string
	 */
	public function getStringPositionSQL( $substring, $string )
	{
		$query = "SELECT POSITION( $substring IN $string )" ;
		$this->setQuery( $query );
		$position = $this->loadRow();
		
		return $position['position'];
	}

	/**
	 * Generate a random value
	 *
	 * @return float The random generated number
	 */
	public function getRandom()
	{		
		$this->setQuery( 'SELECT RANDOM()' );
		$random = $this->loadRow();
		
		return $random['random'];
	}

	/**
	 * Get the query string to alter the database character set.
	 *
	 * @param	string	The database name
	 *
	 * @return  string	The query that alter the database query string
	 *
	 * @since   11.3
	 */
	public function getAlterDbCharacterSet( $dbName )
	{
		$query = 'ALTER DATABASE '.$dbName.' SET CLIENT_ENCODING TO \'UTF8\'';
		
		return $query;
	}

	/**
	 * Get the query string to create new Database in correct PostgreSQL syntax.
	 * 
	 * @param	JObject		$options	JObject coming from "initialise" function to pass user 
	 * 									and database name to database driver.
	 * @param	boolean 	$utf		True if the database supports the UTF-8 character set,
	 * 									not used in PostgreSQL "CREATE DATABASE" query.
	 * 
	 * @return  string	The query that creates database, owned by $options['user']
	 *
	 * @since   11.3
	 */
	public function getCreateDbQuery($options, $utf)
	{
		$query = 'CREATE DATABASE ' . $options->db_name . ' OWNER ' . $options->db_user ;
		
		return $query;
	}

	/**
	 * Rename a database table
	 *
	 * @param	string	The old table name
	 * @param	string	The new table name
	 * @return	boolean	True if all was ok
	 * 
	 * @throws	JDatabaseException
	 */
	public function renameTable($oldTable, $newTable)
	{
		// To check if table exists and prevent SQL injection
		$tableList = $this->getTableList();
		
		// Origin Table does not exist
		if ( !in_array($oldTable, $tableList) )
		{
			throw new JDatabaseException(JText::_('JLIB_DATABASE_ERROR_POSTGRESQL_TABLE_NOT_FOUND'));  // -> Origin Table not found	
		}
		else 
		{
			/* Rename indexes */
			$this->setQuery('SELECT relname 
							 FROM pg_class 
							 WHERE oid IN ( 
							 	SELECT indexrelid 
							 	FROM pg_index, pg_class 
							 	WHERE pg_class.relname=' . $this->quote($oldTable,true) . ' 
							 	AND pg_class.oid=pg_index.indrelid 
							 );' );
			
			$oldIndexes = $this->loadColumn();
			foreach ($oldIndexes as $oldIndex)
			{
				$changedIdxName = str_replace($oldTable, $newTable, $oldIndex);
				$this->setQuery('ALTER INDEX ' . $this->escape($oldIndex) . ' RENAME TO ' . $this->escape($changedIdxName) );
				$this->query();
			}
			
			
			/* Rename sequence */
			$this->setQuery('SELECT relname
							 FROM pg_class
							 WHERE relkind = \'S\'
							 AND relnamespace IN (
							 	SELECT oid
							 	FROM pg_namespace
							 	WHERE nspname NOT LIKE \'pg_%\'
							 	AND nspname != \'information_schema\'
							 )
							 AND relname LIKE \'%' . $oldTable . '%\' ;' );
			
			$oldSequences = $this->loadColumn();
			foreach ($oldSequences as $oldSequence)
			{
				$changedSequenceName = str_replace($oldTable, $newTable, $oldSequence);
				$this->setQuery('ALTER SEQUENCE ' . $this->escape($oldSequence) . ' RENAME TO ' . $this->escape($changedSequenceName) );
				$this->query();
			}
			
			
			/* Rename table */
			$this->setQuery('ALTER TABLE ' . $this->escape($oldTable) . ' RENAME TO ' . $this->escape($newTable) );
			$this->query();
		}

		return true;
	}

	/**
	 * This function replaces a string identifier <var>$prefix</var> with the string held is the
	 * <var>tablePrefix</var> class variable.
	 *
	 * @param   string  $sql     The SQL statement to prepare.
	 * @param   string  $prefix  The common table prefix.
	 *
	 * @return  string  The processed SQL statement.
	 *
	 * @since   11.3
	 */
	public function replacePrefix($sql, $prefix = '#__')
	{
		$sql = trim($sql);
		$replacedQuery = '';

		if ( strpos($sql, '\'') )
		{
			// sequence name quoted with ' ' but need to be replaced
			if ( strpos($sql, 'currval') )
			{
				$sql = explode('currval(', $sql);
				for ( $nIndex = 1; $nIndex < count($sql); $nIndex = $nIndex + 2 ) 
				{
					$sql[$nIndex] = str_replace($prefix, $this->tablePrefix, $sql[$nIndex]);
				}
				$sql = implode('currval(', $sql);
			}
			
			// sequence name quoted with ' ' but need to be replaced
			if ( strpos($sql, 'nextval') )
			{
				$sql = explode('nextval(', $sql);
				for ( $nIndex = 1; $nIndex < count($sql); $nIndex = $nIndex + 2 ) 
				{
					$sql[$nIndex] = str_replace($prefix, $this->tablePrefix, $sql[$nIndex]);
				}
				$sql = implode('nextval(', $sql);
			}
			
			$explodedQuery = explode('\'', $sql);
			
			for ( $nIndex = 0; $nIndex < count($explodedQuery); $nIndex = $nIndex + 2 ) 
			{
				if ( strpos($explodedQuery[$nIndex], $prefix) )
					$explodedQuery[$nIndex] = str_replace($prefix, $this->tablePrefix, $explodedQuery[$nIndex]);
			}
			
			$replacedQuery = implode('\'', $explodedQuery);
		}
		else
		{
			$replacedQuery = str_replace($prefix, $this->tablePrefix, $sql);
		}
		
		return $replacedQuery;
	}

	/**
	 * Method to commit a transaction.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 * @throws  JDatabaseException
	 */
	public function transactionCommit()
	{
		$this->setQuery('COMMIT');
		$this->query();
	}

	/**
	 * Method to roll back a transaction.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 * @throws  JDatabaseException
	 */
	public function transactionRollback($toSavepoint = null)
	{
		$query = 'ROLLBACK';
		if(!is_null($toSavepoint))
		{
			$query .= ' TO SAVEPOINT ' . $this->escape($toSavepoint);
		}
		
		$this->setQuery($query);
		$this->query();
	}

	/**
	 * Method to initialize a transaction.
	 *
	 * @return  void
	 *
	 * @since   11.3
	 * @throws  JDatabaseException
	 */
	public function transactionStart()
	{
		$this->setQuery('START TRANSACTION');
		$this->query();
	}
	
	/**
	 * Method to release a savepoint.
	 *
	 * @param   string	Savepoint's name to release 
	 * @return  void
	 *
	 * @since   11.3
	 */
	public function releaseTransactionSavepoint( $savepointName )
	{
		$this->setQuery('RELEASE SAVEPOINT ' . $this->escape($savepointName));
		$this->query();
	}
	
	/**
	 * Method to create a savepoint.
	 *
	 * @param	string	Savepoint's name to create
	 * @return  void
	 *
	 * @since   11.3
	 */
	public function transactionSavepoint( $savepointName )
	{
		$this->setQuery('SAVEPOINT ' . $this->escape($savepointName) );
		$this->query();
	}
	
	/**
	 * Execute a query batch.
	 *
	 * @param   boolean  $abortOnError     Abort on error.
	 * @param   boolean  $transactionSafe  Transaction safe queries.
	 *
	 * @return  mixed  A database resource if successful, false if not.
	 *
	 * @deprecated  12.1
	 * @since   11.3
	 */
	public function queryBatch($abortOnError = true, $transactionSafe = false)
	{
		// Deprecation warning.
		JLog::add('JDatabase::queryBatch() is deprecated.', JLog::WARNING, 'deprecated');

		$sql = $this->replacePrefix((string) $this->sql);
		$this->errorNum = 0;
		$this->errorMsg = '';

		// If the batch is meant to be transaction safe then we need to wrap it in a transaction.
		if ($transactionSafe)
		{
			$sql = 'START TRANSACTION;' . rtrim($sql, "; \t\r\n\0") . '; COMMIT;';
		}
		$queries = $this->splitSql($sql);
		$error = 0;
		foreach ($queries as $query)
		{
			$query = trim($query);
			if ($query != '')
			{
				$this->cursor = pg_query($query, $this->connection);
				if ($this->debug)
				{
					$this->count++;
					$this->log[] = $query;
				}
				if (!$this->cursor)
				{
					$error = 1;
					$this->errorNum = (int) pg_result_error_field( $this->cursor, PGSQL_DIAG_SQLSTATE ) . ' ';
					$this->errorMsg = (string) pg_result_error_field( $this->cursor, PGSQL_DIAG_MESSAGE_PRIMARY )." SQL=$sql <br />";
					
					if ($abortOnError)
					{
						return $this->cursor;
					}
				}
			}
		}
		return $error ? false : true;
	}
	
}
