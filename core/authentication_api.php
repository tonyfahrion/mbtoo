<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002 - 2004  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: authentication_api.php,v 1.35 2004-04-08 20:52:50 prescience Exp $
	# --------------------------------------------------------

	### Authentication API ###

	#===================================
	# Boolean queries and ensures
	#===================================

	# --------------------
	# Check that there is a user logged-in and authenticated
	#  If the user's account is disabled they will be logged out
	#  If there is no user logged in, redirect to the login page
	#  If parameter is given it is used as a URL to redirect to following
	#   successful login.  If none is given, the URL of the current page is used
	function auth_ensure_user_authenticated( $p_return_page = '' ) {
		if ( !php_version_at_least( '4.1.0' ) ) {
			global $_SERVER;
		}

		# if logged in
		if ( auth_is_user_authenticated() ) {
			# check for access enabled
			#  This also makes sure the cookie is valid
			if ( OFF == current_user_get_field( 'enabled' ) ) {
				print_header_redirect( 'logout_page.php' );
			}
		} else { # not logged in
			if ( is_blank( $p_return_page ) ) {
				$p_return_page = $_SERVER['REQUEST_URI'];
			}
			$p_return_page = string_url( $p_return_page );
			print_header_redirect( 'login_page.php?return=' . $p_return_page );
		}
	}

	# --------------------
	# Return true if there is a currently logged in and authenticated user,
	#  false otherwise
	function auth_is_user_authenticated() {
		if ( '' == auth_get_current_user_cookie() ) {
			return false;
		} else {
			return true;
		}
	}


	#===================================
	# Login / Logout
	#===================================

	# --------------------
	# Attempt to login the user with the given password
	#  If the user fails validation, false is returned
	#  If the user passes validation, the cookies are set and
	#   true is returned.  If $p_perm_login is true, the long-term
	#   cookie is created.
	function auth_attempt_login( $p_username, $p_password, $p_perm_login=false ) {
		$t_user_id = user_get_id_by_name( $p_username );

		$t_login_method = config_get( 'login_method' );

		if ( false === $t_user_id ) {
			if ( BASIC_AUTH == $t_login_method ) {
				# attempt to create the user if using BASIC_AUTH
				$t_cookie_string = user_create( $p_username, $p_password );

				if ( false === $t_cookie_string ) {
					# it didn't work
					return false;
				}

				# ok, we created the user, get the row again
				$t_user_id = user_get_id_by_name( $p_username );

				if ( false === $t_user_id ) {
					# uh oh, something must be really wrong

					# @@@ trigger an error here?

					return false;
				}
			} else {
				return false;
			}
		}

		$t_user = user_get_row( $t_user_id );

		# check for disabled account
		if ( OFF == $t_user['enabled'] ) {
			return false;
		}
		$t_anon_account = config_get( 'anonymous_account' );
		$t_anon_allowed = config_get( 'allow_anonymous_login' );
		# check for anonymous login
		if ( !( ( ON == $t_anon_allowed ) && ( $t_anon_account == $p_username)  ) ) {
			# anonymous login didn't work, so check the password

			if ( !auth_does_password_match( $t_user_id, $p_password ) ) {
				return false;
			}
		}

		# ok, we're good to login now

		# increment login count
		user_increment_login_count( $t_user_id );

		# set the cookies
		auth_set_cookies( $t_user_id, $p_perm_login );

		return true;
	}

	# --------------------
	# Logout the current user and remove any remaining cookies from their browser
	# Returns true on success, false otherwise
	function auth_logout() {
		auth_clear_cookies();
		helper_clear_pref_cookies();
		return true;
	}

	#===================================
	# Password functions
	#===================================

	# --------------------
	# Return true if the password for the user id given matches the given
	#  password (taking into account the global login method)
	function auth_does_password_match( $p_user_id, $p_test_password ) {
		$t_configured_login_method = config_get( 'login_method' );

		if ( LDAP == $t_configured_login_method ) {
			return ldap_authenticate( $p_user_id, $p_test_password );
		}

		$t_password			= user_get_field( $p_user_id, 'password' );
		$t_login_methods	= Array(MD5, CRYPT, PLAIN);
		foreach ( $t_login_methods as $t_login_method ) {

			# pass the stored password in as the salt
			if ( auth_process_plain_password( $p_test_password, $t_password, $t_login_method ) == $t_password ) {
				# Check for migration to another login method and test whether the password was encrypted
				# with our previously insecure implemention of the CRYPT method
				if ( ( $t_login_method != $t_configured_login_method ) ||
					( ( CRYPT == $t_configured_login_method ) && substr( $t_password, 0, 2 ) == substr( $p_test_password, 0, 2 ) ) ) {
					user_set_password( $p_user_id, $p_test_password, true );
				}

				return true;
			}
		}

		return false;
	}

	# --------------------
	# Encrypt and return the plain password given, as appropriate for the current
	#  global login method.
	#
	# When generating a new password, no salt should be passed in.
	# When encrypting a password to compare to a stored password, the stored
	#  password should be passed in as salt.  If the auth method is CRYPT then
	#  crypt() will extract the appropriate portion of the stored password as its salt
	function auth_process_plain_password( $p_password, $p_salt=null, $p_method=null ) {
		$t_login_method = config_get( 'login_method' );
		if ( $p_method !== null ) {
			$t_login_method = $p_method;
		}

		switch ( $t_login_method ) {
			case CRYPT:
				# a null salt is the same as no salt, which causes a salt to be generated
				# otherwise, use the salt given
				$t_processed_password = crypt( $p_password, $p_salt );
				break;
			case MD5:
				$t_processed_password = md5( $p_password );
				break;
			case BASIC_AUTH:
			case PLAIN:
			default:
				$t_processed_password = $p_password;
				break;
		}

		# cut this off to 32 cahracters which the largest possible string in the database
		return substr( $t_processed_password, 0, 32 );
	}

	# --------------------
	# Generate a random 12 character password
	# p_email is unused
	function auth_generate_random_password( $p_email ) {
		$t_val = mt_rand( 0, mt_getrandmax() ) + mt_rand( 0, mt_getrandmax() );
		$t_val = md5( $t_val );

		return substr( $t_val, 0, 12 );
	}


	#===================================
	# Cookie functions
	#===================================

	# --------------------
	# Set login cookies for the user
	#  If $p_perm_login is true, a long-term cookie is created
	function auth_set_cookies( $p_user_id, $p_perm_login=false ) {
		$t_cookie_string = user_get_field( $p_user_id, 'cookie_string' );

		$t_cookie_name = config_get( 'string_cookie' );

		if ( $p_perm_login ) {
			# set permanent cookie (1 year)
			gpc_set_cookie( $t_cookie_name, $t_cookie_string, true );
		} else {
			# set temp cookie, cookie dies after browser closes
			gpc_set_cookie( $t_cookie_name, $t_cookie_string, false );
		}
	}

	# --------------------
	# Clear login cookies
	function auth_clear_cookies() {
		$t_cookie_name =  config_get( 'string_cookie' );
		$t_cookie_path = config_get( 'cookie_path' );

		gpc_clear_cookie( $t_cookie_name, $t_cookie_path );
	}

	# --------------------
	# Generate a string to use as the identifier for the login cookie
	# It is not guaranteed to be unique and should be checked
	# The string returned should be 64 characters in length
	function auth_generate_cookie_string() {
		$t_val = mt_rand( 0, mt_getrandmax() ) + mt_rand( 0, mt_getrandmax() );
		$t_val = md5( $t_val ) . md5( time() );

		return substr( $t_val, 0, 64 );
	}

	# --------------------
	# Generate a UNIQUE string to use as the identifier for the login cookie
	# The string returned should be 64 characters in length
	function auth_generate_unique_cookie_string() {
		do {
			$t_cookie_string = auth_generate_cookie_string();
		} while ( !auth_is_cookie_string_unique( $t_cookie_string ) );

		return $t_cookie_string;
	}

	# --------------------
	# Return true if the cookie login identifier is unique, false otherwise
	function auth_is_cookie_string_unique( $p_cookie_string ) {
		$t_user_table = config_get( 'mantis_user_table' );

		$c_cookie_string = db_prepare_string( $p_cookie_string );

		$query = "SELECT COUNT(*)
				  FROM $t_user_table
				  WHERE cookie_string='$c_cookie_string'";
		$result = db_query( $query );
		$t_count = db_result( $result );

		if ( $t_count > 0 ) {
			return false;
		} else {
			return true;
		}
	}

	# --------------------
	# Return the current user login cookie string,
	# if no user is logged in and anonymous login is enabled, returns cookie for anonymous user
	# otherwise returns '' (an empty string)
	function auth_get_current_user_cookie() {
		$t_cookie_name = config_get( 'string_cookie' );
		$t_cookie = gpc_get_cookie( $t_cookie_name, '' );

		# if cookie not found, and anonymous login enabled, use cookie of anonymous account.
		if ( '' === $t_cookie ) {
			if ( ON == config_get( 'allow_anonymous_login' ) ) {
				$query = sprintf('SELECT id, cookie_string FROM %s WHERE username = "%s"',
						config_get( 'mantis_user_table' ), config_get( 'anonymous_account' ) );
				$result = db_query( $query );

				if ( 1 == db_num_rows( $result ) ) {
					$row		= db_fetch_array( $result );
					$t_cookie	= $row['cookie_string'];
				}
			}
		}

		return $t_cookie;
	}


	#===================================
	# Data Access
	#===================================

	#########################################
	# SECURITY NOTE: cache globals are initialized here to prevent them
	#   being spoofed if register_globals is turned on
	#
	$g_cache_current_user_id = null;

	function auth_get_current_user_id() {
		global $g_cache_current_user_id;

		if ( null !== $g_cache_current_user_id ) {
			return $g_cache_current_user_id;
		}

		$t_user_table = config_get( 'mantis_user_table' );

		$t_cookie_string = auth_get_current_user_cookie();

		# @@@ error with an error saying they aren't logged in?
		#     Or redirect to the login page maybe?

		$c_cookie_string = db_prepare_string( $t_cookie_string );

		$query = "SELECT id
				  FROM $t_user_table
				  WHERE cookie_string='$c_cookie_string'";
		$result = db_query( $query );

		# The cookie was invalid. Clear the cookie (to allow people to log in again)
		# and give them an Access Denied message.
		if ( db_num_rows( $result ) < 1 ) {
			auth_clear_cookies();
			access_denied();
			return false;
		}

		$t_user_id = (int)db_result( $result );
		$g_cache_current_user_id = $t_user_id;

		return $t_user_id;
	}
?>
