<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002         Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details
?>
<?php require_once( 'core.php' ) ?>
<?php login_cookie_check() ?>
<?php
	check_access( MANAGER );
	$f_category = urldecode( $f_category );

	helper_ensure_confirmed( lang_get( 'category_delete_sure_msg' ),
							 lang_get( 'delete_category_button' ) );

	$result = category_delete( $f_project_id, $f_category );

    $t_redirect_url = 'manage_proj_edit_page.php?f_project_id='.$f_project_id;
	if ( $result ) {
		print_header_redirect( $t_redirect_url );
	} else {
		print_mantis_error( ERROR_GENERIC );
	}
?>
