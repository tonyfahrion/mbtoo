<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000, 2001  Kenzaburo Ito - kenito@300baud.org
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details
?>
<?php
	# This file adds a new profile and redirects to account_proj_menu_page.php3
?>
<?php include( "core_API.php" ) ?>
<?php login_cookie_check() ?>
<?php
	db_connect( $g_hostname, $g_db_username, $g_db_password, $g_database_name );
	check_access( REPORTER );

	# get protected state
	$t_protected = get_current_user_field( "protected" );

	# protected account check
	if ( ON == $t_protected ) {
		print_mantis_error( ERROR_PROTECTED_ACCOUNT );
	}

	# " character poses problem when editting so let's just convert them
	$f_platform		= string_prepare_text( $f_platform );
	$f_os			= string_prepare_text( $f_os );
	$f_os_build		= string_prepare_text( $f_os_build );
	$f_description	= string_prepare_textarea( $f_description );

	# get user id
	$t_user_id = get_current_user_field( "id" );

	# Add profile
	$query = "INSERT
			INTO $g_mantis_user_profile_table
    		( id, user_id, platform, os, os_build, description )
			VALUES
			( null, '$t_user_id', '$f_platform', '$f_os', '$f_os_build', '$f_description' )";
    $result = db_query( $query );

    $t_redirect_url = $g_account_profile_menu_page;
?>
<?php print_page_top1() ?>
<?php
	if ( $result ) {
		print_meta_redirect( $t_redirect_url );
	}
?>
<?php print_page_top2() ?>

<?php print_proceed( $result, $query, $t_redirect_url ) ?>

<?php print_page_bot1( __FILE__ ) ?>