<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002 - 2004  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: bug_report_page.php,v 1.47 2004-10-24 19:04:36 thraxisp Exp $
	# --------------------------------------------------------

	# This file POSTs data to report_bug.php

	require_once( 'core.php' );

	$t_core_path = config_get( 'core_path' );

	require_once( $t_core_path.'file_api.php' );
	require_once( $t_core_path.'custom_field_api.php' );

	$f_master_bug_id = gpc_get_int( 'm_id', 0 );

	# this page is invalid for the 'All Project' selection except if this is a clone
	if ( ( ALL_PROJECTS == helper_get_current_project() ) && ( 0 == $f_master_bug_id ) ) {
		print_header_redirect( 'login_select_proj_page.php?ref=bug_report_page.php' );
	}

	if ( ADVANCED_ONLY == config_get( 'show_report' ) ) {
		print_header_redirect ( 'bug_report_advanced_page.php' .
				( 0 == $f_master_bug_id ) ? '' : '?m_id=' . $f_master_bug_id );
	}

	access_ensure_project_level( config_get( 'report_bug_threshold' ) );

	if( $f_master_bug_id > 0 ) {
		# master bug exists...
		bug_ensure_exists( $f_master_bug_id );

		# master bug is not read-only...
		if ( bug_is_readonly( $f_master_bug_id ) ) {
			error_parameters( $f_master_bug_id );
			trigger_error( ERROR_BUG_READ_ONLY_ACTION_DENIED, ERROR );
		}

		# the user can at least update the master bug (needed to add the relationship)...
		access_ensure_bug_level( config_get( 'update_bug_threshold' ), $f_master_bug_id );

		$t_bug = bug_prepare_display( bug_get( $f_master_bug_id, true ) );

		$f_product_version		= $t_bug->version;
		$f_category				= $t_bug->category;
		$f_reproducibility		= $t_bug->reproducibility;
		$f_severity				= $t_bug->severity;
		$f_priority				= $t_bug->priority;
		$f_summary				= $t_bug->summary;
		$f_description			= $t_bug->description;
		$f_additional_info		= $t_bug->additional_information;
		$f_view_state			= $t_bug->view_state;

		$t_project_id			= $t_bug->project_id;

		if( $t_project_id != helper_get_current_project() ) {
			# in case the current project is not the same project of the bug we are cloning...
			# ...se set on fly the current project. This to avoid problems with categories and handlers lists etc.
			$t_redirect_url = "set_project.php?project_id=" . $t_project_id .
				"&make_default=no&ref=" . urlencode( "bug_report_page.php?m_id=" . $f_master_bug_id );
			print_header_redirect( $t_redirect_url );
		}
	}
	else {
		$f_product_version		= gpc_get_string( 'product_version', '' );
		$f_category				= gpc_get_string( 'category', '' );
		$f_reproducibility		= gpc_get_int( 'reproducibility', 0 );
		$f_severity				= gpc_get_int( 'severity', config_get( 'default_bug_severity' ) );
		$f_priority				= gpc_get_int( 'priority', config_get( 'default_bug_priority' ) );
		$f_summary				= gpc_get_string( 'summary', '' );
		$f_description			= gpc_get_string( 'description', '' );
		$f_additional_info		= gpc_get_string( 'additional_info', '' );
		$f_view_state			= gpc_get_int( 'view_state', config_get( 'default_bug_view_status' ) );

		$t_project_id			= helper_get_current_project();
	}

	$f_report_stay			= gpc_get_bool( 'report_stay' );

	html_page_top1( lang_get( 'report_bug_link' ) );
	html_page_top2();
?>

<br />
<div align="center">
<form name="report_bug_form" method="post" <?php if ( file_allow_bug_upload() ) { echo 'enctype="multipart/form-data"'; } ?> action="bug_report.php">
<table class="width75" cellspacing="1">


<!-- Title -->
<tr>
	<td class="form-title">
		<input type="hidden" name="m_id" value="<?php echo $f_master_bug_id ?>" />
		<input type="hidden" name="project_id" value="<?php echo $t_project_id ?>" />
		<input type="hidden" name="handler_id" value="0" />
		<?php echo lang_get( 'enter_report_details_title' ) ?>
	</td>
	<td class="right">
		<?php
			if ( BOTH == config_get( 'show_report' ) ) {
				print_bracket_link( 'bug_report_advanced_page.php' .
					( $f_master_bug_id > 0 ? '?m_id=' . $f_master_bug_id : '' ), lang_get( 'advanced_report_link' ) );
			}
		?>
	</td>
</tr>


<!-- Category -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category" width="30%">
		<?php echo lang_get( 'category' ) ?> <?php print_documentation_link( 'category' ) ?>
	</td>
	<td width="70%">
		<select tabindex="1" name="category">
			<?php print_category_option_list( $f_category ) ?>
		</select>
	</td>
</tr>


<!-- Reproducibility -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'reproducibility' ) ?> <?php print_documentation_link( 'reproducibility' ) ?>
	</td>
	<td>
		<select tabindex="2" name="reproducibility">
			<?php print_enum_string_option_list( 'reproducibility', $f_reproducibility ) ?>
		</select>
	</td>
</tr>


<!-- Severity -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'severity' ) ?> <?php print_documentation_link( 'severity' ) ?>
	</td>
	<td>
		<select tabindex="3" name="severity">
			<?php print_enum_string_option_list( 'severity', $f_severity ) ?>
		</select>
	</td>
</tr>


<!-- Priority (if permissions allow) -->
<?php if ( access_has_project_level( config_get( 'handle_bug_threshold' ) ) ) { ?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'priority' ) ?> <?php print_documentation_link( 'priority' ) ?>
	</td>
	<td>
		<select tabindex="4" name="priority">
			<?php print_enum_string_option_list( 'priority', $f_priority ) ?>
		</select>
	</td>
</tr>
<?php } ?>


<!-- spacer -->
<tr>
	<td class="spacer" colspan="2">&nbsp;</td>
</tr>

<?php
	$t_show_version = ( ON == config_get( 'show_product_version' ) )
			|| ( ( AUTO == config_get( 'show_product_version' ) )
						&& ( count( version_get_all_rows( $t_project_id ) ) > 0 ) );
	if ( $t_show_version ) {
?>
<!-- Product Version -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'product_version' ) ?>
	</td>
	<td>
		<select tabindex="9" name="product_version">
			<?php print_version_option_list( $f_product_version, $t_project_id, VERSION_RELEASED ) ?>
		</select>
	</td>
</tr>
<?php
	}
?>

<!-- spacer -->
<tr>
	<td class="spacer" colspan="2">&nbsp;</td>
</tr>


<!-- Summary -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<span class="required">*</span><?php echo lang_get( 'summary' ) ?> <?php print_documentation_link( 'summary' ) ?>
	</td>
	<td>
		<input tabindex="5" type="text" name="summary" size="105" maxlength="128" value="<?php echo $f_summary ?>" />
	</td>
</tr>


<!-- Description -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<span class="required">*</span><?php echo lang_get( 'description' ) ?> <?php print_documentation_link( 'description' ) ?>
	</td>
	<td>
		<textarea tabindex="6" name="description" cols="80" rows="10" wrap="virtual"><?php echo $f_description ?></textarea>
	</td>
</tr>


<!-- Additional information -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'additional_information' ) ?> <?php print_documentation_link( 'additional_information' ) ?>
	</td>
	<td>
		<textarea tabindex="7" name="additional_info" cols="80" rows="10" wrap="virtual"><?php echo $f_additional_info ?></textarea>
	</td>
</tr>


<!-- spacer -->
<tr>
	<td class="spacer" colspan="2">&nbsp;</td>
</tr>


<!-- Custom Fields -->
<?php
	$t_custom_fields_found = false;
	$t_related_custom_field_ids = custom_field_get_linked_ids( $t_project_id );
	foreach( $t_related_custom_field_ids as $t_id ) {
		$t_def = custom_field_get_definition( $t_id );
		if( ( ( $t_def['display_report'] && !$t_def['advanced'] ) || $t_def['require_report']) && custom_field_has_write_access_to_project( $t_id, $t_project_id ) ) {
			$t_custom_fields_found = true;
?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php if( $t_def['require_report'] ) { ?>
			<span class="required">*</span>
		<?php } ?>
		<?php echo lang_get_defaulted( $t_def['name'] ) ?>
	</td>
	<td>
		<?php print_custom_field_input( $t_def, $f_master_bug_id ) ?>
	</td>
</tr>
<?php
		} # if (!$t_def['advanced']) && has write access
	} # foreach( $t_related_custom_field_ids as $t_id )
?>


<?php if ( $t_custom_fields_found ) { ?>
<!-- spacer -->
<tr>
	<td class="spacer" colspan="2">&nbsp;</td>
</tr>
<?php } ?>


<!-- File Upload (if enabled) -->
<?php if ( file_allow_bug_upload() ) { 
	$t_max_file_size = (int)min( ini_get_number( 'upload_max_filesize' ), ini_get_number( 'post_max_size' ), config_get( 'max_file_size' ) );
?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'upload_file' ) ?>
		<?php echo '<span class="small">(' . lang_get( 'max_file_size' ) . ': ' . number_format( $t_max_file_size/1000 ) . 'k)</span>'?>
	</td>
	<td>
		<input type="hidden" name="max_file_size" value="<?php $t_max_file_size ?>" />
		<input tabindex="8" name="file" type="file" size="60" />
	</td>
</tr>
<?php } ?>


<!-- View Status -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'view_status' ) ?>
	</td>
	<td>
<?php
	if ( access_has_project_level( config_get( 'set_view_status_threshold' ) ) ) {
?>
		<input tabindex="9" type="radio" name="view_state" value="<?php echo VS_PUBLIC ?>" <?php check_checked( $f_view_state, VS_PUBLIC ) ?> /> <?php echo lang_get( 'public' ) ?>
		<input tabindex="10" type="radio" name="view_state" value="<?php echo VS_PRIVATE ?>" <?php check_checked( $f_view_state, VS_PRIVATE ) ?> /> <?php echo lang_get( 'private' ) ?>
<?php
	} else {
		echo get_enum_element( 'project_view_state', $f_view_state );
	}
?>
	</td>
</tr>

<!-- Relationship (in case of cloned bug creation...) -->
<?php
	if( $f_master_bug_id > 0 ) {
?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'relationship_with_parent' ) ?>
	</td>
	<td>
		<?php relationship_list_box_for_cloned_bug( BUG_BLOCKS ) ?>
		<?php PRINT '<b>' . lang_get( 'bug' ) . ' ' . bug_format_id( $f_master_bug_id ) . '</b>' ?>
	</td>
</tr>
<?php
	}
?>

<!-- Report Stay (report more bugs) -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'report_stay' ) ?> <?php print_documentation_link( 'report_stay' ) ?>
	</td>
	<td>
		<input tabindex="11" type="checkbox" name="report_stay" <?php check_checked( $f_report_stay ) ?> /> (<?php echo lang_get( 'check_report_more_bugs' ) ?>)
	</td>
</tr>


<!-- Submit Button -->
<tr>
	<td class="left">
		<span class="required"> * <?php echo lang_get( 'required' ) ?></span>
	</td>
	<td class="center">
		<input tabindex="12" type="submit" class="button" value="<?php echo lang_get( 'submit_report_button' ) ?>" />
	</td>
</tr>


</table>
</form>
</div>

<!-- Autofocus JS -->
<script type="text/javascript" language="JavaScript">
window.document.report_bug_form.category.focus();
</script>

<?php html_page_bottom1( __FILE__ ) ?>
