<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002         Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details
?>
<?php
	# Remove the bugnote and bugnote text and redirect back to
	# the viewing page
?>
<?php include( 'core_API.php' ) ?>
<?php login_cookie_check() ?>
<?php
	check_bugnote_exists( $f_bugnote_id );
	$t_bug_id = get_bugnote_field( $f_bugnote_id, 'bug_id' );
	project_access_check( $t_bug_id );
	check_access( REPORTER );
	check_bug_exists( $t_bug_id );
	
	$result = bugnote_delete( $f_bugnote_id );

	# Determine which view page to redirect back to.
	$t_redirect_url = get_view_redirect_url( $t_bug_id, 1 );
	if ( $result ) {
		print_header_redirect( $t_redirect_url );
	} else {
		print_mantis_error( ERROR_GENERIC );
	}
?>
