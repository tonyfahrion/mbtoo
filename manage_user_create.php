<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002 - 2004  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: manage_user_create.php,v 1.20 2004-08-20 13:18:09 thraxisp Exp $
	# --------------------------------------------------------

	require_once( 'core.php' );

	$t_core_path = config_get( 'core_path' );

	require_once( $t_core_path.'email_api.php' );

	access_ensure_global_level( config_get( 'manage_user_threshold' ) );

	$f_username			= gpc_get_string( 'username' );
	$f_realname			= gpc_get_string( 'realname' );
	$f_password			= gpc_get_string( 'password', '' );
	$f_password_verify	= gpc_get_string( 'password_verify', '' );
	$f_email			= gpc_get_string( 'email' );
	$f_access_level		= gpc_get_string( 'access_level' );
	$f_protected		= gpc_get_bool( 'protected' );
	$f_enabled			= gpc_get_bool( 'enabled' );

	# check for empty username
	$f_username = trim( $f_username );
	if ( is_blank( $f_username ) ) {
		trigger_error( ERROR_EMPTY_FIELD, ERROR );
	}

	# Check the name for validity here so we do it before promting to use a
	#  blank password (don't want to prompt the user if the process will fail
	#  anyway)
	user_ensure_name_valid( $f_username );

	if ( $f_password != $f_password_verify ) {
		trigger_error( ERROR_USER_CREATE_PASSWORD_MISMATCH, ERROR );
	}

	$f_email = email_append_domain( $f_email );

	if ( ON == config_get( 'send_reset_password' ) ) {
		# Check code will be sent to the user directly via email. Dummy password set to random
		# Create random password
		$t_seed = $f_email . $f_username;
		$f_password	= auth_generate_random_password( $t_seed );
	}
	else {
		# Password won't to be sent by email. It entered by the admin
		# Now, if the password is empty, confirm that that is what we wanted
		if ( is_blank( $f_password ) ) {
			helper_ensure_confirmed( lang_get( 'empty_password_sure_msg' ),
					 lang_get( 'empty_password_button' ) );
		}
	}

	user_create( $f_username, $f_password, $f_email, $f_access_level, $f_protected, $f_enabled, $f_realname );

	$t_redirect_url = 'manage_user_page.php';

	html_page_top1();
	html_meta_redirect( $t_redirect_url );
	html_page_top2();
?>

<br />
<div align="center">
<?php
	$t_access_level = get_enum_element( 'access_levels', $f_access_level );
	echo lang_get( 'created_user_part1' ) . ' <span class="bold">' . $f_username . '</span> ' . lang_get( 'created_user_part2' ) . ' <span class="bold">' . $t_access_level . '</span><br />';

	print_bracket_link( $t_redirect_url, lang_get( 'proceed' ) );
?>
</div>

<?php html_page_bottom1( __FILE__ ) ?>
