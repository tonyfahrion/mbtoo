<?php
	require_once( 'core.php' );

	login_cookie_check();

	# if user is below view summary threshold, then re-direct to mainpage.
	if ( !access_level_check_greater_or_equal( config_get( 'view_summary_threshold' ) ) ) {
		access_denied();
	}

	create_bug_enum_summary( lang_get( 'severity_enum_string' ), 'severity' );
	graph_bug_enum_summary( lang_get( 'by_severity' ) );
?>