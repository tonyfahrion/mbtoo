<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000, 2001  Kenzaburo Ito - kenito@300baud.org
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details
?>
<?php
	# Make the specified profile the default
	# Redirect to account_prof_menu_page.php3
?>
<?php include( "core_API.php" ) ?>
<?php login_cookie_check() ?>
<?php
	db_connect( $g_hostname, $g_db_username, $g_db_password, $g_database_name );
	$f_user_id = get_current_user_field( "id" );

	# Clear Defaults
	$query = "UPDATE $g_mantis_user_pref_table
    		SET default_profile='0000000'
    		WHERE user_id='$f_user_id'";
    $result = db_query( $query );

    # Set Defaults
	$query = "UPDATE $g_mantis_user_pref_table
    		SET default_profile='$f_id'
    		WHERE user_id='$f_user_id'";
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