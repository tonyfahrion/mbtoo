<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002 - 2004  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: access_api.php,v 1.33 2004-12-08 13:43:59 marcelloscata Exp $
	# --------------------------------------------------------

	$t_core_dir = dirname( __FILE__ ).DIRECTORY_SEPARATOR;

	require_once( $t_core_dir . 'constant_inc.php' );
	require_once( $t_core_dir . 'helper_api.php' );
	require_once( $t_core_dir . 'authentication_api.php' );
	require_once( $t_core_dir . 'user_api.php' );
	require_once( $t_core_dir . 'bug_api.php' );
	require_once( $t_core_dir . 'project_api.php' );

	### Access Control API ###

	# --------------------
	# Function to be called when a user is attempting to access a page that
	# he/she is not authorised to.  This outputs an access denied message then
	# re-directs to the mainpage.
	function access_denied() {
		if ( !php_version_at_least( '4.1.0' ) ) {
			global $_SERVER;
		}

		if ( !auth_is_user_authenticated() ) {
			if( basename( $_SERVER['SCRIPT_NAME'] ) != 'login_page.php' ) {
				if( !isset( $_SERVER['REQUEST_URI'] ) ) {
					if( !isset( $_SERVER['QUERY_STRING'] ) ) $_SERVER['QUERY_STRING'] = '';
					$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
				}
				$t_return_page = string_url( $_SERVER['REQUEST_URI'] );
				print_header_redirect( 'login_page.php?return=' . $t_return_page );
			}
		} else {
			echo '<center>';
			echo '<p>'.error_string(ERROR_ACCESS_DENIED).'</p>';
			print_bracket_link( 'main_page.php', lang_get( 'proceed' ) );
			echo '</center>';
		}
		exit;
	}

	#===================================
	# Caching
	#===================================

	# SECURITY NOTE: cache globals are initialized here to prevent them
	#   being spoofed if register_globals is turned on

	$g_cache_access_matrix				= array();
	$g_cache_access_matrix_project_ids	= array();
	$g_cache_access_matrix_user_ids		= array();

	# --------------------
	function access_cache_matrix_project( $p_project_id ) {
		global $g_cache_access_matrix, $g_cache_access_matrix_project_ids;

		$c_project_id = db_prepare_int( $p_project_id );

		if ( ALL_PROJECTS == $c_project_id ) {
			return array();
		}

		if ( !in_array( (int)$p_project_id, $g_cache_access_matrix_project_ids ) ) {
			$t_project_user_list_table = config_get( 'mantis_project_user_list_table' );

			$query = "SELECT user_id, access_level
					  FROM $t_project_user_list_table
					  WHERE project_id='$c_project_id'";
			$result = db_query( $query );
			$count = db_num_rows( $result );
			for ( $i=0 ; $i < $count ; $i++ ) {
				$row = db_fetch_array( $result );

				$g_cache_access_matrix[(int)$row['user_id']][(int)$p_project_id] = (int)$row['access_level'];
			}

			$g_cache_access_matrix_project_ids[] = (int)$p_project_id;
		}

		$t_results = array();

		foreach( $g_cache_access_matrix as $t_user ) {
			if ( isset( $t_user[(int)$p_project_id] ) ) {
				$t_results[(int)$p_project_id] = $t_user[(int)$p_project_id];
			}
		}

		return $t_results;
	}

	# --------------------
	function access_cache_matrix_user( $p_user_id ) {
		global $g_cache_access_matrix, $g_cache_access_matrix_user_ids;

		$c_user_id = db_prepare_int( $p_user_id );

		if ( !in_array( (int)$p_user_id, $g_cache_access_matrix_user_ids ) ) {
			$t_project_user_list_table = config_get( 'mantis_project_user_list_table' );

			$query = "SELECT project_id, access_level
					  FROM $t_project_user_list_table
					  WHERE user_id='$c_user_id'";
			$result = db_query( $query );

			$count = db_num_rows( $result );

			# make sure we always have an array to return
			$g_cache_access_matrix[(int)$p_user_id] = array();

			for ( $i=0 ; $i < $count ; $i++ ) {
				$row = db_fetch_array( $result );
				$g_cache_access_matrix[(int)$p_user_id][(int)$row['project_id']] = (int)$row['access_level'];
			}

			$g_cache_access_matrix_user_ids[] = (int)$p_user_id;
		}

		return $g_cache_access_matrix[(int)$p_user_id];
	}

	#===================================
	# Boolean queries and ensures
	#===================================

	# --------------------
	# Get the current user's access
	#
	# This function only checks the user's global access level, ignoring any
	#  overrides they might have at a project level
	function access_get_global_level( $p_user_id = null ) {
		if ( $p_user_id === null ) {
		    $p_user_id = auth_get_current_user_id();
		}

		# Deal with not logged in silently in this case
		# @@@ we may be able to remove this and just error
		#     and once we default to anon login, we can remove it for sure
		if ( !auth_is_user_authenticated() ) {
			return false;
		}

		return user_get_field( $p_user_id, 'access_level' );
	}

	# --------------------
	# Check the current user's access against the given value and return true
	#  if the user's access is equal to or higher, false otherwise.
	#
	function access_has_global_level( $p_access_level, $p_user_id = null ) {
		# Short circuit the check in this case
		if ( NOBODY == $p_access_level ) {
			return false;
		}

		if ( $p_user_id === null ) {
		    $p_user_id = auth_get_current_user_id();
		}

		$t_access_level = access_get_global_level( $p_user_id );

		return ( $t_access_level >= $p_access_level );
	}

	# --------------------
	# Check if the user has the specified global access level
	#  and deny access to the page if not
	function access_ensure_global_level( $p_access_level, $p_user_id = null ) {
		if ( !access_has_global_level( $p_access_level, $p_user_id ) ) {
			access_denied();
		}
	}

	# --------------------
	# Get the current user's access level
	#
	# This function checks the project access level first (for the current project
	#  if none is specified) and if the user is not listed, it falls back on the
	#  user's global access level.
	function access_get_project_level( $p_project_id = null, $p_user_id = null ) {
		# Deal with not logged in silently in this case
		# @@@ we may be able to remove this and just error
		#     and once we default to anon login, we can remove it for sure
		if ( !auth_is_user_authenticated() ) {
			return ANYBODY;
		}

		if ( null === $p_user_id ) {
		    $p_user_id = auth_get_current_user_id();
		}

		if ( null === $p_project_id ) {
			$p_project_id = helper_get_current_project();
		}

		if ( ALL_PROJECTS == $p_project_id ) {
			$t_access_level = access_get_global_level( $p_user_id );
		} else {
			$t_access_level = access_get_local_level( $p_user_id, $p_project_id );
		}

		# Try to use the project access level.
		# If the user is not listed in the project, then try to fall back
		#  to the global access level
		if ( false === $t_access_level ) {
			$t_project_view_state = project_get_field( $p_project_id, 'view_state' );

			# If the project is private and the user isn't listed, then they
			# must have the private_project_threshold access level to get in.
			if ( VS_PRIVATE == $t_project_view_state ) {
				return ( access_has_global_level( config_get( 'private_project_threshold' ), $p_user_id ) );
			} else {
				$t_access_level = user_get_field( $p_user_id, 'access_level' );
			}
		}

		return $t_access_level;
	}

	# --------------------
	# Check the current user's access against the given value and return true
	#  if the user's access is equal to or higher, false otherwise.
	#
	function access_has_project_level( $p_access_level, $p_project_id = null, $p_user_id = null ) {
		# Short circuit the check in this case
		if ( NOBODY == $p_access_level ) {
			return false;
		}

		if ( null === $p_user_id ) {
		    $p_user_id = auth_get_current_user_id();
		}
		if ( null === $p_project_id ) {
			$p_project_id = helper_get_current_project();
		}

		$t_access_level = access_get_project_level( $p_project_id, $p_user_id );

		return ( $t_access_level >= $p_access_level );
	}

	# --------------------
	# Check if the user has the specified access level for the given project
	#  and deny access to the page if not
	function access_ensure_project_level( $p_access_level, $p_project_id = null, $p_user_id = null ) {
		if ( !access_has_project_level(  $p_access_level, $p_project_id, $p_user_id ) ) {
			access_denied();
		}
	}

	# --------------------
	# Check the current user's access against the given value and return true
	#  if the user's access is equal to or higher, false otherwise.
	#
	# This function looks up the bug's project and performs an access check
	#  against that project
	function access_has_bug_level( $p_access_level, $p_bug_id, $p_user_id = null ) {
		# Deal with not logged in silently in this case
		# @@@ we may be able to remove this and just error
		#     and once we default to anon login, we can remove it for sure
		if ( !auth_is_user_authenticated() ) {
			return false;
		}

		if ( $p_user_id === null ) {
		    $p_user_id = auth_get_current_user_id();
		}

		# check limit_Reporter (Issue #4769)
		# reporters can view just issues they reported
		$t_limit_reporters = config_get( 'limit_reporters' );
		$t_report_bug_threshold = config_get( 'report_bug_threshold' );
		if ( (ON === $t_limit_reporters) &&
		     (!bug_is_user_reporter( $p_bug_id, $p_user_id )) &&
		     ( current_user_get_access_level() <= $t_report_bug_threshold ) ) {
		  return false;
		}

		# If the bug is private and the user is not the reporter, then the
		#  the user must also have higher access than private_bug_threshold
		if ( VS_PRIVATE == bug_get_field( $p_bug_id, 'view_state' ) &&
			 !bug_is_user_reporter( $p_bug_id, $p_user_id ) ) {
			$p_access_level = max( $p_access_level, config_get( 'private_bug_threshold' ) );
		}

		$t_project_id = bug_get_field( $p_bug_id, 'project_id' );

		return access_has_project_level( $p_access_level, $t_project_id, $p_user_id );
	}

	# --------------------
	# Check if the user has the specified access level for the given bug
	#  and deny access to the page if not
	function access_ensure_bug_level( $p_access_level, $p_bug_id, $p_user_id=null ) {
		if ( !access_has_bug_level( $p_access_level, $p_bug_id, $p_user_id ) ) {
			access_denied();
		}
 	}

	# --------------------
	# Check the current user's access against the given value and return true
	#  if the user's access is equal to or higher, false otherwise.
	#
	# This function looks up the bugnote's bug and performs an access check
	#  against that bug
	function access_has_bugnote_level( $p_access_level, $p_bugnote_id, $p_user_id=null ) {
		if ( null===$p_user_id ) {
		    $p_user_id = auth_get_current_user_id();
		}

		# If the bug is private and the user is not the reporter, then the
		#  the user must also have higher access than private_bug_threshold
		if ( VS_PRIVATE == bugnote_get_field( $p_bugnote_id, 'view_state' ) &&
			 !bugnote_is_user_reporter( $p_bugnote_id, $p_user_id ) ) {
			$p_access_level = max( $p_access_level, config_get( 'private_bugnote_threshold' ) );
		}

		$t_bug_id = bugnote_get_field( $p_bugnote_id, 'bug_id' );

		return access_has_bug_level( $p_access_level, $t_bug_id, $p_user_id );
	}

	# --------------------
	# Check if the user has the specified access level for the given bugnote
	#  and deny access to the page if not
	function access_ensure_bugnote_level( $p_access_level, $p_bugnote_id, $p_user_id=null ) {
		if ( !access_has_bugnote_level( $p_access_level, $p_bugnote_id, $p_user_id ) ) {
			access_denied();
		}
 	}

	# --------------------
	# Check if the current user can close the specified bug
	function access_can_close_bug ( $p_bug_id, $p_user_id=null ) {
		if ( null===$p_user_id ) {
		    $p_user_id = auth_get_current_user_id();
		}

		# If allow_reporter_close is enabled, then reporters can always close
		#  their own bugs
		if ( ON == config_get( 'allow_reporter_close' ) &&
			bug_is_user_reporter( $p_bug_id, $p_user_id ) ) {
			return true;
		}

		return access_has_bug_level( access_get_status_threshold( CLOSED ), $p_bug_id, $p_user_id );
	}

	# --------------------
	# Make sure that the current user can close the specified bug
	# See access_can_close_bug() for details.
	function access_ensure_can_close_bug( $p_bug_id, $p_user_id=null ) {
		if ( !access_can_close_bug( $p_bug_id, $p_user_id ) ) {
			access_denied();
		}
	}

	# --------------------
	# Check if the current user can reopen the specified bug
	function access_can_reopen_bug ( $p_bug_id, $p_user_id=null ) {
		if ( $p_user_id === null ) {
		    $p_user_id = auth_get_current_user_id();
		}

		# If allow_reporter_reopen is enabled, then reporters can always reopen
		#  their own bugs
		if ( ON == config_get( 'allow_reporter_reopen' ) &&
			bug_is_user_reporter( $p_bug_id, $p_user_id ) ) {
			return true;
		}

		return access_has_bug_level( config_get( 'reopen_bug_threshold' ), $p_bug_id, $p_user_id );
	}

	# --------------------
	# Make sure that the current user can reopen the specified bug
	# See access_can_reopen_bug() for details.
	function access_ensure_can_reopen_bug( $p_bug_id, $p_user_id=null ) {
		if ( !access_can_reopen_bug( $p_bug_id, $p_user_id ) ) {
			access_denied();
		}
	}

	#===================================
	# Data Access
	#===================================

	# get the user's access level specific to this project. 
	# return false (0) if the user has no access override here
	function access_get_local_level( $p_user_id, $p_project_id ) {
		$p_project_id = (int)$p_project_id; # 000001 is different from 1.

		$t_project_level = access_cache_matrix_user( $p_user_id );

		if ( isset( $t_project_level[$p_project_id] ) ) {
			return $t_project_level[$p_project_id];
		} else {
			return false;
		}
	}

	# --------------------
	# get the access level required to change the issue to the new status
	#  If there is no specific differentiated access level, use the 
	#  generic update_bug_status_threshold
	function access_get_status_threshold( $p_status, $p_project_id = ALL_PROJECTS ) {
		$t_thresh_array = config_get( 'set_status_threshold' );
		if ( isset( $t_thresh_array[ $p_status ] ) ) {
			return $t_thresh_array[$p_status];
		} else {
			return config_get( 'update_bug_status_threshold' );
		}
	}
?>
