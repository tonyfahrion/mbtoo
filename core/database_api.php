<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002 - 2004  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: database_api.php,v 1.23 2004-04-08 02:42:27 prescience Exp $
	# --------------------------------------------------------

	###########################################################################
	# Database
	###########################################################################

	# This is the general interface for all database calls.
	# Use this as a starting point to port to other databases

	include('adodb/adodb.inc.php');

	$g_db = $db = ADONewConnection($g_db_type);

	# An array in which all executed queries are stored.  This is used for profiling
	$g_queries_array = array();

	# Stores whether a database connection was succesfully opened.
	$g_db_connected = false;

	# --------------------
	# Make a connection to the database
	function db_connect( $p_hostname, $p_username, $p_password, $p_port, $g_database_name ) {
		global $g_db_connected, $g_db;

		$t_result = $g_db->Connect($p_hostname, $p_username, $p_password, $g_database_name);

		if ( !$t_result ) {
			db_error();
			trigger_error( ERROR_DB_CONNECT_FAILED, ERROR );
			return false;
		}

		$g_db_connected = true;

		return true;
	}

	# --------------------
	# Make a persistent connection to the database
	function db_pconnect( $p_hostname, $p_username, $p_password, $p_port ) {
		global $g_db_connected, $g_db;

		$t_result = $g_db->PConnect($p_hostname, $p_username, $p_password);

		if ( !$t_result ) {
			db_error();
			trigger_error( ERROR_DB_CONNECT_FAILED, ERROR );
			return false;
		}

		$g_db_connected = true;

		return true;
	}

	# --------------------
	# Returns whether a connection to the database exists
	function db_is_connected() {
		global $g_db_connected;

		return $g_db_connected;
	}

	# --------------------
	# execute query, requires connection to be opened
	# If $p_error_on_failure is true (default) an error will be triggered
	#  if there is a problem executing the query.
	function db_query( $p_query, $p_limit = -1, $p_offset = -1 ) {
		global $g_queries_array, $g_db;

		array_push ( $g_queries_array, $p_query );

 		if ( $p_limit != -1 || $p_offset != -1 ) {
			$t_result = $g_db->SelectLimit( $p_query, $p_limit, $p_offset );
 		} else {
 			$t_result = $g_db->Execute( $p_query );
 		}

		if ( !$t_result ) {
			db_error($p_query);
			trigger_error( ERROR_DB_QUERY_FAILED, ERROR );
			return false;
		} else {
			return $t_result;
		}
	}

	# --------------------
	function db_num_rows( $p_result ) {
		global $g_db;
		return $p_result->RecordCount( );
	}

	# --------------------
	function db_affected_rows() {
		global $g_db;
		return $g_db->Affected_Rows( );
	}

	# --------------------
	function db_fetch_array( & $p_result ) {
		global $g_db;
		if ($p_result->EOF) { return false;}
		$test = $p_result->GetRowAssoc(false);
		$p_result->MoveNext();
		return $test;
	}

	# --------------------
	function db_result( $p_result, $p_index1=0, $p_index2=0 ) {
		global $g_db;
		if ( $p_result && ( db_num_rows( $p_result ) > 0 ) ) {
			$p_result->Move($p_index1);
			$t_result = $p_result->GetArray();
			return $t_result[0][$p_index2];
		} else {
			return false;
		}
	}

	# --------------------
	# return the last inserted id
	function db_insert_id($p_table = null) {
		global $g_db_type, $g_db;

		if ( isset($p_table) && ( 'pgsql' == $g_db_type ) ) {
			$query = "SELECT currval('".$p_table."_id_seq')";
			$result = db_query( $query );
			return db_result($result);
		}
		return $g_db->Insert_ID( );
	}

	# --------------------
	function db_field_exists( $p_field_name, $p_table_name, $p_db_name = '') {
		global $g_database_name;

		if ( '' == $p_db_name ) {
			$p_db_name = $g_database_name;
		}

		$fields = mysql_list_fields($p_db_name, $p_table_name);
		$columns = mysql_num_fields($fields);
		for ($i = 0; $i < $columns; $i++) {
			if ( mysql_field_name( $fields, $i ) == $p_field_name ) {
				return true;
			}
		}

		return false;
	}

	# --------------------
	# Check if there is an index defined on the specified table/field and with
	# the specified type.
	#
	# $p_table: Name of table to check
	# $p_field: Name of field to check
	# $p_key: key type to check for (eg: PRI, MUL, ...etc)
	function db_key_exists_on_field( $p_table, $p_field, $p_key ) {
		$c_table = db_prepare_string( $p_table );
		$c_field = db_prepare_string( $p_field );
		$c_key   = db_prepare_string( $p_key );

		$query = "DESCRIBE $c_table";

		$result = db_query( $query );

		$count = db_num_rows( $result );

		for ( $i=0 ; $i < $count ; $i++ ) {
			$row = db_fetch_array( $result );

			if ( $row['field'] == $c_field ) {
				return ( $row['key'] == $c_key );
			}
		}

		return false;
	}

	# --------------------
	function db_error_num() {
		global $g_db;
		return $g_db->ErrorNo();
	}

	# --------------------
	function db_error_msg() {
		global $g_db;
		return $g_db->ErrorMsg();
	}

	# --------------------
	# display both the error num and error msg
	function db_error( $p_query=null ) {
		if ( null !== $p_query ) {
			error_parameters( db_error_num(), db_error_msg(), $p_query );
		} else {
			error_parameters( db_error_num(), db_error_msg() );
		}
	}

	# --------------------
	# close the connection.
	# Not really necessary most of the time since a connection is
	# automatically closed when a page finishes loading.
	function db_close() {
		global $g_db;
		$t_result = $g_db->Close();
	}

	# --------------------
	# prepare a string before DB insertion
	function db_prepare_string( $p_string ) {
		return addslashes( $p_string );
	}

	# --------------------
	# prepare an integer before DB insertion
	function db_prepare_int( $p_int ) {
		return (integer)$p_int;
	}

	# --------------------
	# prepare a boolean before DB insertion
	function db_prepare_bool( $p_bool ) {
		return (int)(bool)$p_bool;
	}

	# --------------------
	# return current timestamp for DB
	function db_now() {
		global $g_db;
		return $g_db->DBTimeStamp(time());
	}

	# --------------------
	# generate a unixtimestamp of a date
	# > SELECT UNIX_TIMESTAMP();
	#    -> 882226357
	# > SELECT UNIX_TIMESTAMP('1997-10-04 22:23:00');
	#    -> 875996580
	function db_timestamp( $p_date=null ) {
		global $g_db;
		if ( null !== $p_date ) {
			$p_timestamp = $g_db->UnixTimeStamp($p_date);
		} else {
			$p_timestamp = time();
		}
		return $g_db->DBTimeStamp($p_timestamp) ;
	}

	function db_unixtimestamp( $p_date=null ) {
		global $g_db;
		if ( null !== $p_date ) {
			$p_timestamp = $g_db->UnixTimeStamp($p_date);
		} else {
			$p_timestamp = time();
		}
		return $p_timestamp ;
	}

	# --------------------
	# helper function to compare two dates against a certain number of days
	# limitstring can be '> 1' '<= 2 ' etc
	# TODO: fix pgsql version of this
	function db_helper_compare_days($p_date1, $p_date2, $p_limitstring) {
		global $g_db_type;
		if ($g_db_type == "mssql") { return "(DATEDIFF(day, $p_date1,$p_date2) ". $p_limitstring . ")"; }
		if ($g_db_type == "mysql") { return "(TO_DAYS($p_date1) - TO_DAYS($p_date2) ". $p_limitstring . ")";  }
		if ($g_db_type == "pgsql") { return "(($p_date1 - $p_date2) ". $p_limitstring . ")"; }
	}

	if ( !isset( $g_skip_open_db ) ) {
		if ( OFF == $g_use_persistent_connections ) {
			db_connect( $g_hostname, $g_db_username, $g_db_password, $g_port, $g_database_name );
		} else {
			db_pconnect( $g_hostname, $g_db_username, $g_db_password, $g_port, $g_database_name );
		}
	}
?>
