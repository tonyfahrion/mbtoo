<?php
# Mantis - a php based bugtracking system
# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
# Copyright (C) 2002 - 2008  Mantis Team   - mantisbt-dev@lists.sourceforge.net
# Mantis is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# Mantis is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Mantis.  If not, see <http://www.gnu.org/licenses/>.
#
# --------------------------------------------------------
# $Id$
# --------------------------------------------------------

/**
 * Database
 *
 * This is the general interface for all database calls.
 * Modifications required for database support, outside of adodb support should occur here.
 *
 * @package CoreAPI
 * @subpackage DatabaseAPI
 * @uses config_api.php
 * @uses gpc_api.php
 */
$t_core_dir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

require_once( $t_core_dir . 'gpc_api.php' );

# Do not explicitly include $t_core_dir to allow using system ADODB by including
# it in include path and removing the one distributed with Mantis (see #7907).
require_once( 'adodb/adodb.inc.php' );

/**
 * An array in which all executed queries are stored.  This is used for profiling
 * @global array $g_queries_array
 	 */
$g_queries_array = array();

/**
 * Stores whether a database connection was succesfully opened.
 * @global bool $g_db_connected
 	 */
$g_db_connected = false;

/**
 * Store whether to log queries ( used for show_queries_count/query list)
 * @global bool $g_db_log_queries
 	 */
$g_db_log_queries = config_get_global( 'show_queries_count' );

/**
 * set adodb fetch mode
 * @global bool $ADODB_FETCH_MODE
 	 */
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

/**
 * Tracks the query parameter count for use with db_aparam().
 * @global int $g_db_param_count
 */
$g_db_param_count = 0;

/**
 * Open a connection to the database.
 * @param string $p_dsn Database connection string ( specified instead of other params)
 * @param string $p_hostname Database server hostname
 * @param string $p_username database server username
 * @param string $p_password database server password
 * @param string $p_database_name database name
 * @param string $p_db_schema Schema name (only used if database type is DB2)
 * @param bool $p_pconnect Use a Persistent connection to database
 * @return bool indicating if the connection was successful
 */
function db_connect( $p_dsn, $p_hostname = null, $p_username = null, $p_password = null, $p_database_name = null, $p_db_schema = null, $p_pconnect = false ) {
	global $g_db_connected, $g_db;

	if( $p_dsn === false ) {
		$t_db_type = config_get_global( 'db_type' );
		$g_db = ADONewConnection( $t_db_type );

		if( $p_pconnect ) {
			$t_result = $g_db->PConnect( $p_hostname, $p_username, $p_password, $p_database_name );
		}
		else {
			$t_result = $g_db->Connect( $p_hostname, $p_username, $p_password, $p_database_name );
		}
	}
	else {
		$g_db = ADONewConnection( $p_dsn );
		$t_result = $g_db->IsConnected();
	}

	if( $t_result ) {

		# For MySQL, the charset for the connection needs to be specified.
		if( db_is_mysql() ) {

			# @@@ Is there a way to translate any charset name to MySQL format? e.g. remote the dashes?
			# @@@ Is this needed for other databases?
			if( strtolower( lang_get( 'charset' ) ) === 'utf-8' ) {
				db_query_bound( 'SET NAMES UTF8' );
			}
		}
		elseif( db_is_db2() && $p_db_schema !== null && !is_blank( $p_db_schema ) ) {
			$t_result2 = db_query_bound( 'set schema ' . $p_db_schema );
			if( $t_result2 === false ) {
				db_error();
				trigger_error( ERROR_DB_CONNECT_FAILED, ERROR );
				return false;
			}
		}
	}
	else {
		db_error();
		trigger_error( ERROR_DB_CONNECT_FAILED, ERROR );
		return false;
	}

	$g_db_connected = true;

	return true;
}

/**
 * Returns whether a connection to the database exists
 * @global stores database connection state
 * @return bool indicating if the a database connection has been made
 */
function db_is_connected() {
	global $g_db_connected;

	return $g_db_connected;
}

/**
 * Checks if the database driver is MySQL
 * @return bool true if mysql
 */
function db_is_mysql() {
	$t_db_type = config_get_global( 'db_type' );

	switch( $t_db_type ) {
		case 'mysql':
		case 'mysqli':
			return true;
	}

	return false;
}

/**
 * Checks if the database driver is PostgreSQL
 * @return bool true if postgres
 */
function db_is_pgsql() {
	$t_db_type = config_get_global( 'db_type' );

	switch( $t_db_type ) {
		case 'postgres':
		case 'postgres64':
		case 'postgres7':
		case 'pgsql':
			return true;
	}

	return false;
}

/**
 * Checks if the database driver is DB2
 * @return bool true if db2
 */
function db_is_db2() {
	$t_db_type = config_get_global( 'db_type' );

	switch( $t_db_type ) {
		case 'db2':
			return true;
	}

	return false;
}

/**
 * execute query, requires connection to be opened
 * An error will be triggered if there is a problem executing the query.
 * @global array of previous executed queries for profiling
 * @global adodb database connection object
 * @global boolean indicating whether queries array is populated
 * @param string $p_query Query string to execute
 * @param int $p_limit Number of results to return
 * @param int $p_offset offset query results for paging
 * @return ADORecordSet|bool adodb result set or false if the query failed.
 * @deprecated db_query_bound should be used in preference to this function. This function will likely be removed in 1.2.0 final
 */
function db_query( $p_query, $p_limit = -1, $p_offset = -1 ) {
	global $g_queries_array, $g_db, $g_db_log_queries;

	if( ON == $g_db_log_queries ) {
		$t_start = microtime_float();

		$t_backtrace = debug_backtrace();
		$t_caller = basename( $t_backtrace[0]['file'] );
		$t_caller .= ":" . $t_backtrace[0]['line'];

		# Is this called from another function?
		if( isset( $t_backtrace[1] ) ) {
			$t_caller .= ' ' . $t_backtrace[1]['function'] . '()';
		}
		else {

			# or from a script directly?
			$t_caller .= ' ' . $_SERVER['PHP_SELF'];
		}
	}

	if(( $p_limit != -1 ) || ( $p_offset != -1 ) ) {
		$t_result = $g_db->SelectLimit( $p_query, $p_limit, $p_offset );
	}
	else {
		$t_result = $g_db->Execute( $p_query );
	}

	if( ON == $g_db_log_queries ) {
		$t_elapsed = number_format( microtime_float() - $t_start, 4 );

		array_push( $g_queries_array, array( $p_query, $t_elapsed, $t_caller ) );
	}

	if( !$t_result ) {
		db_error( $p_query );
		trigger_error( ERROR_DB_QUERY_FAILED, ERROR );
		return false;
	}
	else {
		return $t_result;
	}
}

/**
 * execute query, requires connection to be opened
 * An error will be triggered if there is a problem executing the query.
 * @global array of previous executed queries for profiling
 * @global adodb database connection object
 * @global boolean indicating whether queries array is populated
 * @param string $p_query Parameterlised Query string to execute
 * @param array $arr_parms Array of parameters matching $p_query
 * @param int $p_limit Number of results to return
 * @param int $p_offset offset query results for paging
 * @return ADORecordSet|bool adodb result set or false if the query failed.
 */
function db_query_bound( $p_query, $arr_parms = null, $p_limit = -1, $p_offset = -1 ) {
	global $g_queries_array, $g_db, $g_db_log_queries, $g_db_param_count;

	if( ON == $g_db_log_queries ) {
		$t_db_type = config_get_global( 'db_type' );

		$t_start = microtime_float();

		$t_backtrace = debug_backtrace();
		$t_caller = basename( $t_backtrace[0]['file'] );
		$t_caller .= ":" . $t_backtrace[0]['line'];

		# Is this called from another function?
		if( isset( $t_backtrace[1] ) ) {
			$t_caller .= ' ' . $t_backtrace[1]['function'] . '()';
		}
		else {

			# or from a script directly?
			$t_caller .= ' - ';
		}
	}

	if( $arr_parms != null && db_is_pgsql() ) {
		$params = count( $arr_parms );
		for( $i = 0;$i < $params;$i++ ) {
			if( $arr_parms[$i] === false ) {
				$arr_parms[$i] = 0;
			}
		}
	}

	if(( $p_limit != -1 ) || ( $p_offset != -1 ) ) {
		$t_result = $g_db->SelectLimit( $p_query, $p_limit, $p_offset, $arr_parms );
	}
	else {
		$t_result = $g_db->Execute( $p_query, $arr_parms );
	}

	if( ON == $g_db_log_queries ) {
		$t_elapsed = number_format( microtime_float() - $t_start, 4 );

		$lastoffset = 0;
		$i = 1;
		if( !( is_null( $arr_parms ) || empty( $arr_parms ) ) ) {
			while( preg_match( '/(\?)/', $p_query, $matches, PREG_OFFSET_CAPTURE, $lastoffset ) ) {
				if( $i <= count( $arr_parms ) ) {
					if( is_null( $arr_parms[$i - 1] ) ) {
						$replace = 'NULL';
					}
					elseif( is_string( $arr_parms[$i - 1] ) ) {
						$replace = "'" . $arr_parms[$i - 1] . "'";
					}
					elseif( is_integer( $arr_parms[$i - 1] ) || is_float( $arr_parms[$i - 1] ) ) {
						$replace = (float) $arr_parms[$i - 1];
					}
					elseif( is_bool( $arr_parms[$i - 1] ) ) {
						switch( $t_db_type ) {
							case 'pgsql':
								$replace = "'" . $arr_parms[$i - 1] . "'";
							break;
						default:
							$replace = $arr_parms[$i - 1];
							break;
						}
					}
					else {
						echo( "Invalid argument type passed to query_bound(): $i" );
						exit( 1 );
					}
					$p_query = substr( $p_query, 0, $matches[1][1] ) . $replace . substr( $p_query, $matches[1][1] + strlen( $matches[1][0] ) );
					$lastoffset = $matches[1][1] + strlen( $replace );
				}
				else {
					$lastoffset = $matches[1][1] + 1;
				}
				$i++;
			}
		}

		array_push( $g_queries_array, array( $p_query, $t_elapsed, $t_caller ) );
	}

	# We can't reset the counter because we have queries being built
	# and executed while building bigger queries in filter_api. -jreese
	# $g_db_param_count = 0;

	if( !$t_result ) {
		db_error( $p_query );
		trigger_error( ERROR_DB_QUERY_FAILED, ERROR );
		return false;
	}
	else {
		return $t_result;
	}
}

/**
 * Generate a string to insert a parameter into a database query string
 * @return string 'wildcard' matching a paramater in correct ordered format for the current database.
 */
function db_param() {
	global $g_db;
	global $g_db_param_count;

	return $g_db->Param( $g_db_param_count++ );
}

/**
 * Retrieve number of rows returned for a specific database query
 * @param ADORecordSet $p_result Database Query Record Set to retrieve record count for.
 * @return int Record Count
 */
function db_num_rows( $p_result ) {
	global $g_db;

	return $p_result->RecordCount();
}

/**
 * Retrieve number of rows affected by a specific database query
 * @param ADORecordSet $p_result Database Query Record Set to retrieve affected rows for.
 * @return int Affected Rows
 */
function db_affected_rows() {
	global $g_db;

	return $g_db->Affected_Rows();
}

/**
 * Retrieve the next row returned from a specific database query
 * @param bool|ADORecordSet $p_result Database Query Record Set to retrieve next result for.
 * @return array Database result
 */
function db_fetch_array( &$p_result ) {
	global $g_db, $g_db_type;

	if( $p_result->EOF ) {
		return false;
	}

	# mysql obeys FETCH_MODE_BOTH, hence ->fields works, other drivers do not support this
	if( $g_db_type == 'mysql' || $g_db_type == 'odbc_mssql' ) {
		$t_array = $p_result->fields;
		$p_result->MoveNext();
		return $t_array;
	}
	else {
		$t_row = $p_result->GetRowAssoc( false );

		for( $i = 0;$i < $p_result->FieldCount();$i++ ) {
			$t_field = $p_result->FetchField( $i );
			switch( $t_field->type ) {
				case 'bool':
					switch( $t_row[$t_field->name] ) {
						case 'f':
							$t_row[$t_field->name] = false;
							break;
						case 't':
							$t_row[$t_field->name] = true;
							break;
					}
					break;
				default :
					break;
			}
		}
		$p_result->MoveNext();
		return $t_row;
	}
}

/**
 * Retrieve a result returned from a specific database query
 * @param bool|ADORecordSet $p_result Database Query Record Set to retrieve next result for.
 * @param int $p_index1 Row to retrieve (optional)
 * @param int $p_index2 Column to retrieve (optional)
 * @return mixed Database result
 */
function db_result( $p_result, $p_index1 = 0, $p_index2 = 0 ) {
	global $g_db;

	if( $p_result && ( db_num_rows( $p_result ) > 0 ) ) {
		$p_result->Move( $p_index1 );
		$t_result = $p_result->GetArray();

		if( isset( $t_result[0][$p_index2] ) ) {
			return $t_result[0][$p_index2];
		}

		// The numeric index doesn't exist. FETCH_MODE_ASSOC may have been used.
		// Get 2nd dimension and make it numerically indexed
		$t_result = array_values( $t_result[0] );
		return $t_result[$p_index2];
	}

	return false;
}

/**
 * return the last inserted id for a specific database table
 * @param string $p_table a valid database table name
 * @return int last successful insert id
 */
function db_insert_id( $p_table = null ) {
	global $g_db;

	if( isset( $p_table ) && db_is_pgsql() ) {
		$query = "SELECT currval('" . $p_table . "_id_seq')";
		$result = db_query_bound( $query );
		return db_result( $result );
	}
	return $g_db->Insert_ID();
}

/**
 * Check if the specified table exists.
 * @param string $p_table_name a valid database table name
 * @return bool indicating whether the table exists
 */
function db_table_exists( $p_table_name ) {
	global $g_db, $g_db_schema;

	if( is_blank( $p_table_name ) ) {
		return false;

		// no tables found
	}

	if( db_is_db2() ) {

		// must pass schema
		$t_tables = $g_db->MetaTables( 'TABLE', false, '', $g_db_schema );
	}
	else {
		$t_tables = $g_db->MetaTables( 'TABLE' );
	}

	# Can't use in_array() since it is case sensitive
	$t_table_name = strtolower( $p_table_name );
	foreach( $t_tables as $t_current_table ) {
		if( strtolower( $t_current_table ) == $t_table_name ) {
			return true;
		}
	}

	return false;
}

/**
 * Check if the specified table index exists.
 * @param string $p_table_name a valid database table name
 * @param string $p_index_name a valid database index name
 * @return bool indicating whether the index exists
 */
function db_index_exists( $p_table_name, $p_index_name ) {
	global $g_db, $g_db_schema;

	if( is_blank( $p_index_name ) || is_blank( $p_table_name ) ) {
		return false;

		// no index found
	}

	$t_indexes = $g_db->MetaIndexes( $p_table_name );

	# Can't use in_array() since it is case sensitive
	$t_index_name = strtolower( $p_index_name );
	foreach( $t_indexes as $t_current_index_name => $t_current_index_obj ) {
		if( strtolower( $t_current_index_name ) == $t_index_name ) {
			return true;
		}
	}
	return false;
}

/**
 * Check if the specified field exists in a given table
 * @param string $p_field_name a database field name
 * @param string $p_table_name a valid database table name
 * @return bool indicating whether the field exists
 */
function db_field_exists( $p_field_name, $p_table_name ) {
	global $g_db;
	return in_array( $p_field_name, $g_db->MetaColumnNames( $p_table_name ) );
}

/**
 * Retrieve list of fields for a given table
 * @param string $p_table_name a valid database table name
 * @return array array of fields on table
 */
function db_field_names( $p_table_name ) {
	global $g_db;
	return $g_db->MetaColumnNames( $p_table_name );
}

/**
 * Returns the last error number. The error number is reset after every call to Execute(). If 0 is returned, no error occurred.
 * @return int last error number
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_error_num() {
	global $g_db;

	return $g_db->ErrorNo();
}

/**
 * Returns the last status or error message. Returns the last status or error message. The error message is reset when Execute() is called.
 * This can return a string even if no error occurs. In general you do not need to call this function unless an ADOdb function returns false on an error.
 * @return string last error string
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_error_msg() {
	global $g_db;

	return $g_db->ErrorMsg();
}

/**
 * send both the error number and error message and query (optional) as paramaters for a triggered error
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_error( $p_query = null ) {
	if( null !== $p_query ) {
		error_parameters( db_error_num(), db_error_msg(), $p_query );
	}
	else {
		error_parameters( db_error_num(), db_error_msg() );
	}
}

/**
 * close the connection.
 * Not really necessary most of the time since a connection is automatically closed when a page finishes loading.
 */
function db_close() {
	global $g_db;

	$t_result = $g_db->Close();
}

/**
 * prepare a string before DB insertion
 * @param string $p_string unprepared string
 * @return string prepared database query string
 * @deprecated db_query_bound should be used in preference to this function. This function may be removed in 1.2.0 final
 */
function db_prepare_string( $p_string ) {
	global $g_db;
	$t_db_type = config_get_global( 'db_type' );

	switch( $t_db_type ) {
		case 'mssql':
		case 'odbc_mssql':
		case 'ado_mssql':
			if( ini_get( 'magic_quotes_sybase' ) ) {
				return addslashes( $p_string );
			}
			else {
				ini_set( 'magic_quotes_sybase', true );
				$t_string = addslashes( $p_string );
				ini_set( 'magic_quotes_sybase', false );
				return $t_string;
			}

			# just making a point with the superfluous break;s  I know it does not execute after a return  ;-)
			break;
		case 'db2':
			$t_escaped = $g_db->qstr( $p_string, false );
			return substr( $t_escaped, 1, strlen( $t_escaped ) - 2 );
			break;
		case 'mssql':
			break;
		case 'odbc_mssql':
			break;
		case 'mysql':
			return mysql_real_escape_string( $p_string );

			# For some reason mysqli_escape_string( $p_string ) always returns an empty
			# string.  This is happening with PHP v5.0.2.
		case 'mysqli':
			$t_escaped = $g_db->qstr( $p_string, false );
			return substr( $t_escaped, 1, strlen( $t_escaped ) - 2 );
		case 'postgres':
		case 'postgres64':
		case 'postgres7':
		case 'pgsql':
			return pg_escape_string( $p_string );
		default:
			error_parameters( 'db_type', $t_db_type );
			trigger_error( ERROR_CONFIG_OPT_INVALID, ERROR );
	}
}

/**
 * prepare a binary string before DB insertion
 * @param string $p_string unprepared binary data
 * @return string prepared database query string
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_prepare_binary_string( $p_string ) {
	global $g_db;
	$t_db_type = config_get_global( 'db_type' );

	switch( $t_db_type ) {
		case 'mssql':
		case 'odbc_mssql':
		case 'ado_mssql':
			$content = unpack( "H*hex", $p_string );
			return '0x' . $content['hex'];
			break;
		case 'postgres':
		case 'postgres64':
		case 'postgres7':
		case 'pgsql':
			return '\'' . pg_escape_bytea( $p_string ) . '\'';
			break;
		default:
			return '\'' . db_prepare_string( $p_string ) . '\'';
			break;
	}
}

/**
 * prepare a date for binding in the format database accepts.
 * @param string|int $p_date can be a Unix integer timestamp or an ISO format Y-m-d. If null or false or '' is passed in, it will be converted to an SQL null.
 * @return string Formatted Date for DB insertion e.g. 1970-01-01 ready for database insertion
 */
function db_bind_date( $p_date ) {
	global $g_db;
	return $g_db->BindDate( $p_date );
}

/**
 * prepare a date/time for binding in the format database accepts.
 * @param string|int $p_date can be a Unix integer timestamp or an ISO format Y-m-d h:m:s. If null or false or '' is passed in, it will be converted to an SQL null.
 * @param bool $p_gmt whether to use GMT or current timezone (default false)
 * @return string Formatted Date for DB insertion e.g. 1970-01-01 00:00:00 ready for database insertion
 */
function db_bind_timestamp( $p_date, $p_gmt = false ) {
	global $g_db;
	if( $p_gmt ) {
		return $g_db->BindTimestamp( $g_db->UserTimeStamp( $p_date, 'Y-m-d H:i:s', true ) );
	}
	else {
		return $g_db->BindTimeStamp( $p_date );
	}
}

/**
 * prepare a int for database insertion.
 * @param int $p_int integer
 * @return int integer
 * @deprecated db_query_bound should be used in preference to this function. This function may be removed in 1.2.0 final
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_prepare_int( $p_int ) {
	return (int) $p_int;
}

/**
 * prepare a double for database insertion.
 * @param double $p_double double
 * @return double double
 * @deprecated db_query_bound should be used in preference to this function. This function may be removed in 1.2.0 final
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_prepare_double( $p_double ) {
	return (double) $p_double;
}

/**
 * prepare a boolean for database insertion.
 * @param boolean $p_boolean boolean
 * @return int integer representing boolean
 * @deprecated db_query_bound should be used in preference to this function. This function may be removed in 1.2.0 final
 * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
 */
function db_prepare_bool( $p_bool ) {
	return (int) (bool) $p_bool;
}

/**
 * return current timestamp for DB
 * @todo add param bool $p_gmt whether to use GMT or current timezone (default false)
 * @return string Formatted Date for DB insertion e.g. 1970-01-01 00:00:00 ready for database insertion
 */
function db_now() {
	global $g_db;

	return $g_db->BindTimeStamp( time() );
}

/**
 * generate a date/time in database format of a date
 * @param $p_date Date
 * @param bool $p_gmt whether to use GMT or current timezone (default false)
 * @return string Formatted Date for DB insertion e.g. 1970-01-01 00:00:00 ready for database insertion
 * @todo review date handling
 */
function db_timestamp( $p_date = null, $p_gmt = false ) {
	global $g_db;

	if( null !== $p_date ) {
		$p_timestamp = $g_db->UnixTimeStamp( $p_date, $p_gmt );
	}
	else {
		$p_timestamp = time();
	}
	return $g_db->BindTimeStamp( $p_timestamp );
}

/**
 * generate a integer unixtimestamp of a date
 * @param $p_date Date
 * @param bool $p_gmt whether to use GMT or current timezone (default false)
 * @return int unix timestamp of a date
 * @todo review date handling
 */
function db_unixtimestamp( $p_date = null, $p_gmt = false ) {
	global $g_db;

	if( null !== $p_date ) {
		$p_timestamp = $g_db->UnixTimeStamp( $p_date, $p_gmt );
	}
	else {
		$p_timestamp = time();
	}
	return $p_timestamp;
}

/**
 * convert unix timestamp to db compatible date
 * @param $p_timestamp Date
 * @param bool $p_gmt whether to use GMT or current timezone (default false)
 * @return string Formatted Date
 * @todo review date handling
 */
function db_date( $p_timestamp = null, $p_gmt = false ) {
	global $g_db;

	if( null !== $p_timestamp ) {
		$p_date = $g_db->UserTimeStamp( $p_timestamp, 'Y-m-d H:i:s', $p_gmt );
	}
	else {
		$p_date = $g_db->UserTimeStamp( time(), 'Y-m-d H:i:s', $p_gmt );
	}
	return $p_date;
}

/**
 * return a DB compatible date, representing a unixtime(0) + 1 second. Internally considered a NULL date
 * @return string Formatted Date for DB insertion e.g. 1970-01-01 00:00:00 ready for database insertion
 */
function db_null_date() {
	global $g_db;

	return $g_db->BindTimestamp( $g_db->UserTimeStamp( 1, 'Y-m-d H:i:s', true ) );
}

/**
 * convert minutes to a time format [h]h:mm
 * @param int $p_min integer representing number of minutes
 * @return string representing formatted duration string in hh:mm format.
 */
function db_minutes_to_hhmm( $p_min = 0 ) {
	return sprintf( '%02d:%02d', $p_min / 60, $p_min % 60 );
}

/**
 * A helper function that generates a case-sensitive or case-insensitive like phrase based on the current db type.
 * The field name and value are assumed to be safe to insert in a query (i.e. already cleaned).
 * @param string $p_field_name The name of the field to filter on.
 * @param int $p_param_id An integer pointing to use for a query_bound value that will represent the value that includes the pattern (can include % for wild cards) - not including the quotations.
 * @param bool $p_case_sensitive true: case sensitive, false: case insensitive
 * @return string returns (field LIKE 'value') OR (field ILIKE 'value')
 */
function db_helper_like( $p_field_name, $p_case_sensitive = false ) {
	$t_like_keyword = 'LIKE';

	if( $p_case_sensitive === false ) {
		if( db_is_pgsql() ) {
			$t_like_keyword = 'ILIKE';
		}
	}

	return "($p_field_name $t_like_keyword " . db_param() . ')';
}

/**
 * A helper function to compare two dates against a certain number of days
 * @param $p_date1_id_or_column
 * @param $p_date2_id_or_column
 * @param $p_limitstring
 * @return string returns database query component to compare dates
 * @todo Check if there is a way to do that using ADODB rather than implementing it here.
 */
function db_helper_compare_days( $p_date1_id_or_column, $p_date2_id_or_column, $p_limitstring ) {
	$t_db_type = config_get_global( 'db_type' );

	$p_date1 = $p_date1_id_or_column;
	$p_date2 = $p_date2_id_or_column;
	if( is_int( $p_date1_id_or_column ) ) {
		$p_date1 = db_param();
	}
	if( is_int( $p_date2_id_or_column ) ) {
		$p_date2 = db_param();
	}
	switch( $t_db_type ) {
		case 'mssql':
		case 'odbc_mssql':
		case 'ado_mssql':
			return "(DATEDIFF(day, $p_date2, $p_date1) " . $p_limitstring . ")";
		case 'mysql':
		case 'mysqli':
			return "(TO_DAYS($p_date1) - TO_DAYS($p_date2) " . $p_limitstring . ")";
		case 'postgres':
		case 'postgres64':
		case 'postgres7':
		case 'pgsql':
			return "(date_mi($p_date1::date, $p_date2::date) " . $p_limitstring . ")";
		case 'oci8':
			return "(($p_date1 - $p_date2)" . $p_limitstring . ")";
		case 'db2':

			// all DB2 UDB use days function
			return "(days($p_date1) - days($p_date2) " . $p_limitstring . ")";
		default:
			error_parameters( 'db_type', $t_db_type );
			trigger_error( ERROR_CONFIG_OPT_INVALID, ERROR );
	}
}

/**
 * count queries
 * @return int
 */
function db_count_queries() {
	global $g_queries_array;

	return count( $g_queries_array );
}

/**
 * count unique queries
 * @return int
 */
function db_count_unique_queries() {
	global $g_queries_array;

	$t_unique_queries = 0;
	$t_shown_queries = array();
	foreach( $g_queries_array as $t_val_array ) {
		if( !in_array( $t_val_array[0], $t_shown_queries ) ) {
			$t_unique_queries++;
			array_push( $t_shown_queries, $t_val_array[0] );
		}
	}
	return $t_unique_queries;
}

/**
 * get total time for queries
 * @return int
 */
function db_time_queries() {
	global $g_queries_array;
	$t_count = count( $g_queries_array );
	$t_total = 0;
	for( $i = 0;$i < $t_count;$i++ ) {
		$t_total += $g_queries_array[$i][1];
	}
	return $t_total;
}

/**
 * get database table name
 * @return string containing full database table name
 */
function db_get_table( $p_option ) {
	if( isset( $GLOBALS['g_db_table'][$p_option] ) ) {
		$t_value = config_eval( $GLOBALS['g_db_table'][$p_option] );
		if( $t_value !== $GLOBALS['g_db_table'][$p_option] ) {
			$GLOBALS['g_db_table'][$p_option] = $t_value;
		}
		return $t_value;
	}
	else {
		error_parameters( $p_option );
		trigger_error( ERROR_CONFIG_OPT_NOT_FOUND, WARNING );
	}
}

/**
 * get list database tables
 * @return array containing table names
 */
function db_get_table_list() {
	$t_tables = Array();
	foreach( $GLOBALS['g_db_table'] as $t_table ) {
		$t_tables[] = config_eval( $t_table );
	}
	return $t_tables;
}


if( !isset( $g_skip_open_db ) ) {
	if( OFF == $g_use_persistent_connections ) {
		db_connect( config_get_global( 'dsn', false ), $g_hostname, $g_db_username, $g_db_password, $g_database_name, config_get_global( 'db_schema' ) );
	}
	else {
		db_connect( config_get_global( 'dsn', false ), $g_hostname, $g_db_username, $g_db_password, $g_database_name, config_get_global( 'db_schema' ), true );
	}
}
else {
	define( 'PLUGINS_DISABLED', true );
}
