<?
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000  Kenzaburo Ito - kenito@300baud.org
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details
?>
<? include( "core_API.php" ) ?>
<? login_cookie_check() ?>
<?
	db_mysql_connect( $g_hostname, $g_db_username, $g_db_password, $g_database_name );

	if ( !access_level_check_greater_or_equal( "administrator" ) ) {
		### need to replace with access error page
		header( "Location: $g_logout_page" );
		exit;
	}

	### delete account
    if ( $f_protected!="on" ) {
	    ### Remove aaccount
    	$query = "DELETE
    			FROM $g_mantis_user_table
    			WHERE id='$f_id'";
	    $result = mysql_query( $query );

	    ### Remove associated profiles
	    $query = "DELETE
	    		FROM $g_mantis_user_profile_table
	    		WHERE user_id='$f_id'";
	    $result = mysql_query( $query );

		### Remove associated preferences
    	$query = "DELETE
    			FROM $g_mantis_user_pref_table
    			WHERE user_id='$f_id'";
    	$result = mysql_query( $query );
    }
?>
<? print_html_top() ?>
<? print_head_top() ?>
<? print_title( $g_window_title ) ?>
<? print_css( $g_css_include_file ) ?>
<?
	if ( $result ) {
		print_meta_redirect( $g_manage_page, $g_wait_time );
	}
?>
<? include( $g_meta_include_file ) ?>
<? print_head_bottom() ?>
<? print_body_top() ?>
<? print_header( $g_page_title ) ?>

<p>
<? print_menu( $g_menu_include_file ) ?>

<p>
<div align=center>
<?
	if ( $f_protected=="on" ) {
		PRINT "Account protected. Cannot delete.<p>";
	}
	else if ( $result ) {
		PRINT "Account deleted...<p>";
	}
	else {
		PRINT "ERROR DETECTED: Report this sql statement to <a href=\"<? echo $g_administrator_email ?>\">administrator</a><p>";
		echo $query;
	}
?>
<p>
<a href="<? echo $g_manage_page ?>">Click here to proceed</a>
</div>

<? print_footer() ?>
<? print_body_bottom() ?>
<? print_html_bottom() ?>