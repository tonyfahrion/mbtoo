<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002         Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details
?>
<?php require_once( 'core.php' ) ?>
<?php
	$f_username		= gpc_get_string( 'f_username' );
	$f_email		= gpc_get_string( 'f_email' );
	$f_email_domain	= gpc_get_string( 'f_email_domain', false );

	# Check to see if signup is allowed
	if ( OFF == config_get( 'allow_signup' ) ) {
		print_header_redirect( 'login_page.php' );
		exit;
	}

	# check for empty username
	$f_username = trim( $f_username );
	if ( empty( $f_username ) ) {
		print_mantis_error( ERROR_EMPTY_FIELD );
	}

	if ( !empty( $f_email_domain ) ) {
		$f_email = "$f_email@$f_email_domain";
	}

	# Check for a properly formatted email with valid MX record
	if ( !is_valid_email( $f_email ) ) {
		echo $f_email.' '.lang_get( 'invalid_email' ).'<br />';
		echo '<a href="signup_page.php">'.lang_get( 'proceed' ).'</a>';
		exit;
	}

	# Check for duplicate username
    if ( ! user_is_name_unique( $f_username ) ) {
		print_mantis_error( ERROR_USER_NAME_NOT_UNIQUE );
    }

	# Passed our checks.  Insert into DB then send email.
	if ( !user_signup( $f_username, $f_email ) ) {
		echo lang_get( 'account_create_fail' ).'<br />';
		echo '<a href="signup_page.php">'.lang_get( 'proceed' ).'</a>';
		exit;
	}
?>
<?php print_page_top1() ?>
<?php
	print_head_bottom();
	print_body_top();
	print_header( $g_page_title );
	print_top_page( $g_top_include_page );
?>

<br />
<div align="center">
<?php
	echo "[$f_username - $f_email] ".lang_get( 'password_emailed_msg' ).'<br />'.lang_get( 'no_reponse_msg').'<br />';

	print_bracket_link( 'login_page.php', lang_get( 'proceed' ) );
?>
</div>

<?php print_page_bot1( __FILE__ ) ?>
