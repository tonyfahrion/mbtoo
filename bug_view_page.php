<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002         Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: bug_view_page.php,v 1.7 2002-10-27 23:35:40 jfitzell Exp $
	# --------------------------------------------------------
?>
<?php require_once( 'core.php' ) ?>
<?php login_cookie_check() ?>
<?php
	$f_bug_id		= gpc_get_int( 'f_bug_id' );
	$f_bug_id		= bug_format_id( $f_bug_id );

	if ( ADVANCED_ONLY == config_get( 'show_view' ) ) {
		print_header_redirect ( 'bug_view_advanced_page.php?f_bug_id='.$f_bug_id );
	}

	$f_history	= gpc_get_bool( 'f_history' );

	$c_bug_id = (integer)$f_bug_id;
	project_access_check( $f_bug_id );

	$t_bug_table = config_get( 'mantis_bug_table' );

	$query = "SELECT *, UNIX_TIMESTAMP(date_submitted) as date_submitted,
			UNIX_TIMESTAMP(last_updated) as last_updated
			FROM $t_bug_table
    		WHERE id='$c_bug_id'";
    $result = db_query( $query );

	# check bug exists here, rather than calling bug_ensure_exists() and executing
	# the query twice.
	if ( 0 == db_num_rows( $result ) ) {
		print_header_redirect( 'main_page.php' );
	}

	$row = db_fetch_array( $result );
	extract( $row, EXTR_PREFIX_ALL, 'v' );

	# if bug is private, make sure user can view private bugs
	access_bug_check( $f_bug_id, $v_view_state );

	$t_bug_text_table = config_get( 'mantis_bug_text_table' );

	$query = "SELECT *
    		FROM $t_bug_text_table
    		WHERE id='$v_bug_text_id'";
    $result = db_query( $query );
	$row = db_fetch_array( $result );
	extract( $row, EXTR_PREFIX_ALL, 'v2' );

	$v_os 						= string_display( $v_os );
	$v_os_build					= string_display( $v_os_build );
	$v_platform					= string_display( $v_platform );
	$v_version 					= string_display( $v_version );
	$v_summary 					= string_display( $v_summary );
	$v2_description 			= string_display( $v2_description );
	$v2_steps_to_reproduce 		= string_display( $v2_steps_to_reproduce );
	$v2_additional_information 	= string_display( $v2_additional_information );

	compress_start();
?>
<?php print_page_top1() ?>
<?php print_page_top2() ?>

<br />
<table class="width100" cellspacing="1">
<tr>
	<td class="form-title" colspan="3">
		<?php echo lang_get( 'viewing_bug_simple_details_title' ) ?>
		<span class="small"><?php print_bracket_link( "#bugnotes", lang_get( 'jump_to_bugnotes' ) ) ?></span>
	</td>
	<td class="right" colspan="3">
<?php if ( BOTH == config_get( 'show_view' ) ) { ?>
		<span class="small"><?php print_bracket_link( 'bug_view_advanced_page.php?f_bug_id='.$f_bug_id, lang_get( 'view_advanced_link' ) )?></span>
<?php }?>
	<span class="small"><?php print_bracket_link( 'bug_view_page.php?f_bug_id='.$f_bug_id.'&amp;f_history=1#history', lang_get( 'bug_history' ) ) ?></span>
	<span class="small"><?php print_bracket_link( 'print_bug_page.php?f_bug_id='.$f_bug_id, lang_get( 'print' ) ) ?></span>
	</td>
</tr>
<tr class="row-category">
	<td width="16%">
		<?php echo lang_get( 'id' ) ?>
	</td>
	<td width="16%">
		<?php echo lang_get( 'category' ) ?>
	</td>
	<td width="16%">
		<?php echo lang_get( 'severity' ) ?>
	</td>
	<td width="16%">
		<?php echo lang_get( 'reproducibility' ) ?>
	</td>
	<td width="16%">
		<?php echo lang_get( 'date_submitted' ) ?>
	</td>
	<td width="16%">
		<?php echo lang_get( 'last_update' ) ?>
	</td>
</tr>
<tr class="row-2">
	<td>
		<?php echo $v_id ?>
	</td>
	<td>
		<?php echo $v_category ?>
	</td>
	<td>
		<?php echo get_enum_element( 'severity', $v_severity ) ?>
	</td>
	<td>
		<?php echo get_enum_element( 'reproducibility', $v_reproducibility ) ?>
	</td>
	<td>
		<?php print_date( config_get( 'normal_date_format' ), $v_date_submitted ) ?>
	</td>
	<td>
		<?php print_date( config_get( 'normal_date_format' ), $v_last_updated ) ?>
	</td>
</tr>
<tr>
	<td class="spacer" colspan="6">
		&nbsp;
	</td>
</tr>
<tr class="row-1">
	<td class="category">
		<?php echo lang_get( 'reporter' ) ?>
	</td>
	<td>
		<?php print_user_with_subject( $v_reporter_id, $f_bug_id ) ?>
	</td>
	<td class="category">
		<?php echo lang_get( 'view_status' ) ?>
	</td>
	<td>
		<?php echo get_enum_element( 'project_view_state', $v_view_state ) ?>
	</td>
	<td colspan="2">
		&nbsp;
	</td>
</tr>
<tr class="row-2">
	<td class="category">
		<?php echo lang_get( 'assigned_to' ) ?>
	</td>
	<td colspan="5">
		<?php print_user_with_subject( $v_handler_id, $f_bug_id ) ?>
	</td>
</tr>
<tr class="row-1">
	<td class="category">
		<?php echo lang_get( 'priority' ) ?>
	</td>
	<td>
		<?php echo get_enum_element( 'priority', $v_priority ) ?>
	</td>
	<td class="category">
		<?php echo lang_get( 'resolution' ) ?>
	</td>
	<td>
		<?php echo get_enum_element( 'resolution', $v_resolution ) ?>
	</td>
	<td colspan="2">
		&nbsp;
	</td>
</tr>
<tr class="row-2">
	<td class="category">
		<?php echo lang_get( 'status' ) ?>
	</td>
	<td bgcolor="<?php echo get_status_color( $v_status ) ?>">
		<?php echo get_enum_element( 'status', $v_status ) ?>
	</td>
	<td class="category">
		<?php echo lang_get( 'duplicate_id' ) ?>
	</td>
	<td>
		<?php print_duplicate_id( $v_duplicate_id ) ?>
	</td>
	<td colspan="2">
		&nbsp;
	</td>
</tr>
<tr>
	<td class="spacer" colspan="6">
		&nbsp;
	</td>
</tr>
<tr class="row-1">
	<td class="category">
		<?php echo lang_get( 'summary' ) ?>
	</td>
	<td colspan="5">
		<?php echo $v_summary ?>
	</td>
</tr>
<tr class="row-2">
	<td class="category">
		<?php echo lang_get( 'description' ) ?>
	</td>
	<td colspan="5">
		<?php echo $v2_description ?>
	</td>
</tr>
<tr class="row-1">
	<td class="category">
		<?php echo lang_get( 'additional_information' ) ?>
	</td>
	<td colspan="5">
		<?php echo $v2_additional_information ?>
	</td>
</tr>
<?php
	$t_user_id = current_user_get_field ( 'id' );
	$t_show_attachments = ( ( $v_reporter_id == $t_user_id ) || access_level_check_greater_or_equal( config_get( 'view_attachments_threshold' ) ) );

	if ( $t_show_attachments ) {
?>
<tr class="row-2">
	<td class="category">
		<?php echo lang_get( 'attached_files' ) ?>
	</td>
	<td colspan="5">
		<?php file_list_attachments ( $f_bug_id ); ?>
	</td>
</tr>
<?php } ?>
<tr class="row-1">
	<td class="category">
		<?php echo lang_get( 'bug_relationships' ) ?>
	</td>
	<td colspan="5">
		<?php
			$result = relationship_fetch_all_src( $v_id );
			$relationship_count = db_num_rows( $result );
			for ($i=0;$i<$relationship_count;$i++) {
				$row = db_fetch_array( $result );
				extract( $row, EXTR_PREFIX_ALL, 'v2' );

				$t_bug_link = string_get_bug_view_link( $v2_destination_bug_id );
				switch ( $v2_relationship_type ) {
				case BUG_DUPLICATE:	$t_description = str_replace( '%id', $t_bug_link, lang_get( 'duplicate_of' ) );
									break;
				case BUG_RELATED:	$t_description = str_replace( '%id', $t_bug_link, lang_get( 'related_to' ) );
									break;
				case BUG_DEPENDANT:	$t_description = str_replace( '%id', $t_bug_link, lang_get( 'dependant_on' ) );
									break;
				default:			$t_description = str_replace( '%id', $t_bug_link, lang_get( 'duplicate_of' ) );
				}

				PRINT "$t_description<br />";
			}
		?>
		<?php
			$result = relationship_fetch_all_dest( $v_id );
			$relationship_count = db_num_rows( $result );
			for ($i=0;$i<$relationship_count;$i++) {
				$row = db_fetch_array( $result );
				extract( $row, EXTR_PREFIX_ALL, 'v2' );

				$t_bug_link = string_get_bug_view_link( $v2_source_bug_id );
				switch ( $v2_relationship_type ) {
				case BUG_DUPLICATE:	$t_description = str_replace( '%id', $t_bug_link, lang_get( 'has_duplicate' ) );
									break;
				case BUG_RELATED:	$t_description = str_replace( '%id', $t_bug_link, lang_get( 'related_to' ) );
									break;
				case BUG_DEPENDANT:	$t_description = str_replace( '%id', $t_bug_link, lang_get( 'blocks' ) );
									break;
				default:			$t_description = str_replace( '%id', $t_bug_link, lang_get( 'has_duplicate' ) );
				}

				PRINT "$t_description<br />";
			}
		?>
	</td>
</tr>
<tr align="center">
	<td colspan="6">
		<table width="100%">
			<tr align="center">
<?php # UPDATE form BEGIN ?>
<?php if ( access_level_check_greater_or_equal( config_get( 'update_bug_threshold' ) ) && ( $v_status < RESOLVED ) ) { ?>
	<td class="center">
		<form method="post" action="<?php echo string_get_bug_update_page() ?>">
		<input type="hidden" name="f_bug_id" value="<?php echo $f_bug_id ?>" />
		<input type="hidden" name="f_bug_text_id" value="<?php echo $v_bug_text_id ?>" />
		<input type="submit" value="<?php echo lang_get( 'update_bug_button' ) ?>" />
		</form>
	</td>
<?php
	}
	# UPDATE form END
?>
<?php # ASSIGN form BEGIN ?>
<?php if ( access_level_check_greater_or_equal( config_get( 'handle_bug_threshold' ) ) && ( $v_status < RESOLVED ) ) { ?>
	<td class="center">
		<?php #check if current user already assigned to the bug ?>
		<?php if ( $t_user_id != $v_handler_id ) { ?>
		<form method="post" action="bug_assign.php">
		<input type="hidden" name="f_bug_id" value="<?php echo $f_bug_id ?>" />
		<input type="hidden" name="f_date_submitted" value="<?php echo $v_date_submitted ?>" />
		<input type="submit" value="<?php echo lang_get( 'bug_assign_button' ) ?>" />
		</form>
		<?php } #end of checking if current user already assigned ?>
	</td>
<?php
	} # ASSIGN form END
?>
<?php # RESOLVE form BEGIN ?>
<?php if ( access_level_check_greater_or_equal( config_get( 'handle_bug_threshold' ) ) && ( $v_status < RESOLVED ) ) { ?>
	<td class="center">
		<form method="post" action="bug_resolve_page.php">
		<input type="hidden" name="f_bug_id" value="<?php echo $f_bug_id ?>" />
		<input type="submit" value="<?php echo lang_get( 'resolve_bug_button' ) ?>" />
		</form>
	</td>
<?php
	} # RESOLVE form END
?>
<?php # REOPEN form BEGIN ?>
<?php if ( ( $v_status >= RESOLVED ) &&
		( access_level_check_greater_or_equal( config_get( 'reopen_bug_threshold' ) ) ||
		( $v_reporter_id == $t_user_id  && ON == config_get( 'allow_reporter_reopen' ) ) ) ) { ?>
	<td class="center">
		<form method="post" action="bug_reopen_page.php">
		<input type="hidden" name="f_bug_id" value="<?php echo $f_bug_id ?>" />
		<input type="submit" value="<?php echo lang_get( 'reopen_bug_button' ) ?>" />
		</form>
	</td>
<?php
	} # REOPEN form END
?>
<?php # CLOSE form BEGIN ?>
<?php if ( ( access_level_check_greater_or_equal( config_get( 'close_bug_threshold' ) ) ||
		( ON == config_get( 'allow_reporter_close' ) &&
		  $v_reporter_id == $t_user_id ) ) &&
		( RESOLVED == $v_status ) ) { ?>
	<td class="center">
		<form method="post" action="bug_close_page.php">
		<input type="hidden" name="f_bug_id" value="<?php echo $f_bug_id ?>" />
		<input type="submit" value="<?php echo lang_get( 'close_bug_button' ) ?>" />
		</form>
	</td>
<?php
	} # CLOSE form END
?>
<?php # MONITOR form BEGIN ?>
<?php
 if ( (( PUBLIC == $v_view_state && access_level_check_greater_or_equal( config_get( 'monitor_bug_threshold' ) ) ) || ( PRIVATE == $v_view_state && access_level_check_greater_or_equal( config_get( 'private_bug_threshold' ) ) )) && ! user_is_monitoring_bug( $t_user_id, $f_bug_id ) ) {
?>
	<td class="center">
		<form method="post" action="bug_monitor.php">
		<input type="hidden" name="f_bug_id" value="<?php echo $f_bug_id ?>" />
		<input type="hidden" name="f_action" value="add" />
		<input type="submit" value="<?php echo lang_get( 'monitor_bug_button' ) ?>" />
		</form>
	</td>
<?php
	}
	# MONITOR form END
?>
<?php # UNMONITOR form BEGIN ?>
<?php if ( access_level_check_greater_or_equal( config_get( 'monitor_bug_threshold' ) ) && user_is_monitoring_bug( $t_user_id, $f_bug_id ) ) { ?>
	<td class="center">
		<form method="post" action="bug_monitor.php">
		<input type="hidden" name="f_bug_id" value="<?php echo $f_bug_id ?>" />
		<input type="hidden" name="f_action" value="delete" />
		<input type="submit" value="<?php echo lang_get( 'unmonitor_bug_button' ) ?>" />
		</form>
	</td>
<?php
	}
	# MONITOR form END
?>
<?php # DELETE form BEGIN ?>
<?php if ( access_level_check_greater_or_equal( config_get( 'allow_bug_delete_access_level' ) ) ) { ?>
	<td class="center">
		<form method="post" action="bug_delete.php">
		<input type="hidden" name="f_bug_id" value="<?php echo $f_bug_id ?>" />
		<input type="submit" value="<?php echo lang_get( 'delete_bug_button' ) ?>" />
		</form>
	</td>
<?php
	} # DELETE form END
?>
			</tr>
		</table>
	</td>
</tr>
</table>

<?php
	if ( empty( $f_check ) && $t_show_attachments && $v_status < RESOLVED && access_level_check_greater_or_equal( REPORTER ) ) {
		include( config_get( 'bug_file_upload_inc' ) );
	}

	include( config_get( 'bugnote_include_file' ) );

	if ( $f_history ) {
		include( config_get( 'history_include_file' ) );
	}
?>
<?php print_page_bot1( __FILE__ ) ?>
<?php compress_stop(); ?>
