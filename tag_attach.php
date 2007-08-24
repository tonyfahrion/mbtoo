<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002 - 2007  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: tag_attach.php,v 1.1 2007-08-24 19:04:39 nuclear_eclipse Exp $
	# --------------------------------------------------------

	require_once( 'core.php' );

	$t_core_path = config_get( 'core_path' );

	require_once( $t_core_path . 'tag_api.php' );

	$f_bug_id = gpc_get_int( 'bug_id' );
	$f_tag_select = gpc_get_int( 'tag_select' );
	$t_user_id = auth_get_current_user_id();

	access_ensure_global_level( config_get( 'tag_attach_threshold' ) );

	$t_tags = tag_parse_string( gpc_get_string( 'tag_string' ) );
	$t_can_create = access_has_global_level( config_get( 'tag_create_threshold' ) );
	
	$t_tags_create = array();
	$t_tags_attach = array();
	$t_tags_failed = array();

	foreach ( $t_tags as $t_tag_row ) {
		if ( -1 == $t_tag_row['id'] ) {
			if ( $t_can_create ) {
				$t_tags_create[] = $t_tag_row;
			} else {
				$t_tags_failed[] = $t_tag_row;
			}
		} elseif ( -2 == $t_tag_row['id'] ) {
			$t_tags_failed[] = $t_tag_row;
		} else {
			$t_tags_attach[] = $t_tag_row;
		}
	}

	if ( 0 < $f_tag_select && tag_exists( $f_tag_select ) ) {
		$t_tags_attach[] = tag_get( $f_tag_select );
	}

	if ( count( $t_tags_failed ) > 0 ) {
		html_page_top1( lang_get( 'tag_attach_long' ).' '.bug_format_summary( $f_bug_id, SUMMARY_CAPTION ) );
		html_page_top2();
?>
<br/>
<table class="width75" align="center">
	<tr class="row-category">
	<td colspan="2"><?php echo lang_get( 'tag_attach_failed' ) ?></td>
	</tr>
	<tr class="spacer"><td colspan="2"></td></tr>
<?php		
		$t_tag_string = "";
		foreach( $t_tags_attach as $t_tag_row ) {
			if ( "" != $t_tag_string ) {
				$t_tag_string .= config_get( 'tag_separator' );
			}
			$t_tag_string .= $t_tag_row['name'];
		}

		foreach( $t_tags_failed as $t_tag_row ) {
			echo '<tr ',helper_alternate_class(),'>';
			if ( -1 == $t_tag_row['id'] ) {
				echo '<td class="category">',lang_get( 'tag_invalid_name' ),'</td>';
			} elseif ( -2 == $t_tag_row['id'] ) {
				echo '<td class="category">',lang_get( 'tag_create_denied' ),'</td>';
			}
			echo '<td>',$t_tag_row['name'],'</td></tr>';
			
			if ( "" != $t_tag_string ) {
				$t_tag_string .= config_get( 'tag_separator' );
			}
			$t_tag_string .= $t_tag_row['name'];
		}
?>
	<tr class="spacer"><td colspan="2"></td></tr>
	<tr <?php echo helper_alternate_class() ?>>
	<td class="category"><?php echo lang_get( 'tag_attach_long' ) ?></td>
	<td>
<?php
		print_tag_input( $f_bug_id, $t_tag_string );
?>	
	</td>
	</tr>
</table>
<?php
		html_page_bottom1(__FILE__);
	} else {
		foreach( $t_tags_create as $t_tag_row ) {
			$t_tag_row['id'] = tag_create( $t_tag_row['name'], $t_user_id );
			$t_tags_attach[] = $t_tag_row;
		}

		foreach( $t_tags_attach as $t_tag_row ) {
			if ( ! tag_bug_is_attached( $t_tag_row['id'], $f_bug_id ) ) {
				tag_bug_attach( $t_tag_row['id'], $f_bug_id, $t_user_id );
			}
		}

		print_successful_redirect_to_bug( $f_bug_id );
	}
