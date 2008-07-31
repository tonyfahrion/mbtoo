<?php
# Mantis - a php based bugtracking system

# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
# Copyright (C) 2002 - 2008  Mantis Team   - mantisbt-dev@lists.sourceforge.net

# Mantis is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# Mantis is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Mantis.  If not, see <http://www.gnu.org/licenses/>.
?>
<?php
	require_once( 'core.php' );
	$t_core_path = config_get( 'core_path' );

	require_once( $t_core_path.'compress_api.php' );
	require_once( $t_core_path.'filter_api.php' );
	require_once( $t_core_path.'relationship_api.php' );
	require_once( $t_core_path.'current_user_api.php' );
	require_once( $t_core_path.'bug_api.php' );
	require_once( $t_core_path.'string_api.php' );
	require_once( $t_core_path.'date_api.php' );
	require_once( $t_core_path.'tag_api.php' );

	auth_ensure_user_authenticated();

	compress_enable();

	html_page_top1();
	html_page_top2();

	$t_filter = filter_get_default();
	$t_target_field = gpc_get_string( 'target_field', '' );
	if ( !isset( $t_filter[ rtrim( $t_target_field, '[]' ) ] ) ) {
		$t_target_field = '';	
	}

	if ( ON == config_get( 'use_javascript' ) ) {
		?>
		<body onload="SetInitialFocus();">

		<script type="text/javascript" language="JavaScript">
		<!--
		function SetInitialFocus() {
			<?php
			global $t_target_field;
			if ( $t_target_field ) {
				$f_view_type = gpc_get_string( 'view_type', '' );
				if ( ( FILTER_PROPERTY_HIDE_STATUS_ID . '[]' == $t_target_field ) && ( 'advanced' == $f_view_type ) ) {
					echo 'field_to_focus = "', FILTER_PROPERTY_STATUS_ID, '[]";';
				} else {
					echo 'field_to_focus = "', $t_target_field, '";';
				}
			} else {
				print "field_to_focus = null;";
			}
			?>
			if ( field_to_focus ) {
				eval( "document.filters['" + field_to_focus + "'].focus()" );
			}

			SwitchDateFields();
		}

		function SwitchDateFields() {
		    // All fields need to be enabled to go back to the script
			<?php
			echo 'document.filters.', FILTER_PROPERTY_START_MONTH, '.disabled = ! document.filters.', FILTER_PROPERTY_FILTER_BY_DATE, '.checked;';
			echo 'document.filters.', FILTER_PROPERTY_START_DAY, '.disabled = ! document.filters.', FILTER_PROPERTY_FILTER_BY_DATE, '.checked;';
			echo 'document.filters.', FILTER_PROPERTY_START_YEAR, '.disabled = ! document.filters.', FILTER_PROPERTY_FILTER_BY_DATE, '.checked;';
			echo 'document.filters.', FILTER_PROPERTY_END_MONTH, '.disabled = ! document.filters.', FILTER_PROPERTY_FILTER_BY_DATE, '.checked;';
			echo 'document.filters.', FILTER_PROPERTY_END_DAY, '.disabled = ! document.filters.', FILTER_PROPERTY_FILTER_BY_DATE, '.checked;';
			echo 'document.filters.', FILTER_PROPERTY_END_YEAR, '.disabled = ! document.filters.', FILTER_PROPERTY_FILTER_BY_DATE, '.checked;';
			?>

		    return true;
		}
		// -->
		</script>

		<?php
	}

	# @@@ thraxisp - could this be replaced by a call to filter_draw_selection_area2

	$t_filter = current_user_get_bug_filter();
	$t_filter = filter_ensure_valid_filter( $t_filter );
	$t_project_id = helper_get_current_project();

	$t_current_user_access_level = current_user_get_access_level();
	$t_accessible_custom_fields_ids = array();
	$t_accessible_custom_fields_names = array();
	$t_accessible_custom_fields_type = array() ;
	$t_accessible_custom_fields_values = array();
	$t_filter_cols = config_get( 'filter_custom_fields_per_row' );
	$t_custom_cols = 1;
	$t_custom_rows = 0;

	#get valid target fields
	$t_fields = helper_get_columns_to_view();
	$t_n_fields = count( $t_fields );
	for ( $i=0; $i < $t_n_fields; $i++ ) {
		if ( in_array( $t_fields[$i], array( 'selection', 'edit', 'bugnotes_count', 'attachment' ) ) ) {
			unset( $t_fields[$i] );
		}
	}

	if ( ON == config_get( 'filter_by_custom_fields' ) ) {
		$t_custom_cols = $t_filter_cols;
		$t_custom_fields = custom_field_get_linked_ids( $t_project_id );

		foreach ( $t_custom_fields as $t_cfid ) {
			$t_field_info = custom_field_cache_row( $t_cfid, true );
			if ( $t_field_info['access_level_r'] <= $t_current_user_access_level && $t_field_info['filter_by']) {
				$t_accessible_custom_fields_ids[] = $t_cfid;
				$t_accessible_custom_fields_names[] = $t_field_info['name'];
				$t_accessible_custom_fields_types[] = $t_field_info['type'];
				$t_accessible_custom_fields_values[] = custom_field_distinct_values( $t_field_info, $t_project_id );
				$t_fields[] = "custom_" . $t_field_info['name'];
			}
		}

		if ( sizeof( $t_accessible_custom_fields_ids ) > 0 ) {
			$t_per_row = config_get( 'filter_custom_fields_per_row' );
			$t_custom_rows = ceil( sizeof( $t_accessible_custom_fields_ids ) / $t_per_row );
		}
	}

	if ( ! in_array( $t_target_field, $t_fields ) ) {
		$t_target_field = '';
	}
	
	$f_for_screen = gpc_get_bool( 'for_screen', true );

	$t_action  = "view_all_set.php?f=3";

	if ( $f_for_screen == false ) {
		$t_action  = "view_all_set.php";
	}

	$f_default_view_type = 'simple';
	if ( ADVANCED_DEFAULT == config_get( 'view_filters' ) ) {
		$f_default_view_type = 'advanced';
	}

	$f_view_type = gpc_get_string( 'view_type', $f_default_view_type );
	if ( ADVANCED_ONLY == config_get( 'view_filters' ) ) {
		$f_view_type = 'advanced';
	}
	if ( SIMPLE_ONLY == config_get( 'view_filters' ) ) {
		$f_view_type = 'simple';
	}
	if ( ! in_array( $f_view_type, array( 'simple', 'advanced' ) ) ) {
		$f_view_type = $f_default_view_type;
	}	

	$t_select_modifier = '';
	if ( 'advanced' == $f_view_type ) {
		$t_select_modifier = 'multiple="multiple" size="10" ';
	}

	$t_show_version = ( ON == config_get( 'show_product_version' ) )
			|| ( ( AUTO == config_get( 'show_product_version' ) )
						&& ( count( version_get_all_rows_with_subs( $t_project_id ) ) > 0 ) );
?>
<br />
<form method="post" name="filters" action="<?php echo $t_action; ?>">
<input type="hidden" name="type" value="1" />
<input type="hidden" name="view_type" value="<?php PRINT $f_view_type; ?>" />
<?php
	if ( $f_for_screen == false ) {
		print '<input type="hidden" name="print" value="1" />';
		print '<input type="hidden" name="offset" value="0" />';
	}
?>
<table class="width100" cellspacing="1">
<tr>
	<td class="right" colspan="<?php PRINT ( 8 * $t_custom_cols ); ?>">
	<?php
		$f_switch_view_link = 'view_filters_page.php?target_field=' . $t_target_field . '&amp;view_type=';

		if ( ( SIMPLE_ONLY != config_get( 'view_filters' ) ) && ( ADVANCED_ONLY != config_get( 'view_filters' ) ) ) {
			if ( 'advanced' == $f_view_type ) {
				print_bracket_link( $f_switch_view_link . 'simple', lang_get( 'simple_filters' ) );
			} else {
				print_bracket_link( $f_switch_view_link . 'advanced', lang_get( 'advanced_filters' ) );
			}
		}
	?>
	</td>
</tr>
<tr class="row-category2">
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'reporter' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'monitored_by' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'assigned_to' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 2 * $t_custom_cols ); ?>"><?php echo lang_get( 'category' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'severity' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'resolution' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'profile' ) ?></td>
	<!-- <td colspan="<?php echo ( ( $t_filter_cols - 8 ) * $t_custom_cols ); ?>">&nbsp;</td> -->
</tr>
<tr class="row-1">
	<!-- Reporter -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_reporter_id(); ?>
	</td>
	<!-- Monitored by -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_user_monitor(); ?>
	</td>
	<!-- Handler -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_handler_id(); ?>
	</td>
	<!-- Category -->
	<td valign="top" colspan="<?php echo ( 2 * $t_custom_cols ); ?>">
		<?php print_filter_show_category(); ?>
	</td>
    <!-- Severity -->
    <td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_show_severity(); ?>
    </td>
	<!-- Resolution -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_show_resolution(); ?>
	</td>
	<!-- Profile -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_show_profile(); ?>
	</td>
	<!-- <td colspan="<?php echo ( ( $t_filter_cols - 8 ) * $t_custom_cols ); ?>">&nbsp;</td> -->
</tr>

<tr class="row-category2">
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'status' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
	<?php
	if ( 'simple' == $f_view_type ) {
		echo lang_get( 'hide_status' );
	} else {
		echo '&nbsp;';
	}
	?>
	</td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'product_build' ) ?></td>
	<?php if ( $t_show_version ) { ?>
		<td class="small-caption" colspan="<?php echo ( 2 * $t_custom_cols ); ?>"><?php echo lang_get( 'product_version' ) ?></td>
		<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'fixed_in_version' ) ?></td>
	<?php } else { ?>
		<td class="small-caption" colspan="<?php echo ( 2 * $t_custom_cols ); ?>">&nbsp;</td>
		<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">&nbsp;</td>
	<?php } ?>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'priority' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( ( $t_filter_cols - 7 ) * $t_custom_cols ); ?>">&nbsp;</td>
</tr>
<tr class="row-1">
	<!-- Status -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_show_status(); ?>
	</td>
	<!-- Hide Status -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
	<?php
	if ( 'simple' == $f_view_type ) {
		print_filter_hide_status();
	} else {
		echo '&nbsp;';
	}
	?>
	</td>
	<!-- Build -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
 		<?php print_filter_show_build(); ?>
	</td>
	<!-- Version -->
	<td valign="top" colspan="<?php echo ( 2 * $t_custom_cols ); ?>">
 		<?php if ( $t_show_version ) {
 			print_filter_show_version();
 		} else {
 			echo "&nbsp;";
 		} ?>
	</td>
	<!-- Fixed in Version -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
 		<?php if ( $t_show_version ) {
 			print_filter_show_fixed_in_version();
 		} else {
 			echo "&nbsp;";
 		} ?>
 	</td>
	<!-- Priority -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_show_priority(); ?>
	</td>
	<td colspan="<?php echo ( ( $t_filter_cols - 7 ) * $t_custom_cols ); ?>">&nbsp;</td>
</tr>

<tr class="row-category2">
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'show' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'view_status' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'sticky' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'changed' ) ?></td>
	<td class="small-caption" colspan="<?php echo ( 3 * $t_custom_cols ); ?>">
		<input type="checkbox" name="do_filter_by_date" <?php
			check_checked( $t_filter['do_filter_by_date'], 'on' );
			if ( ON == config_get( 'use_javascript' ) ) {
				print "onclick=\"SwitchDateFields();\""; } ?> />
		<?php echo lang_get( 'use_date_filters' ) ?>
	</td>
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php echo lang_get( 'bug_relationships' ) ?>
	</td>
	<!-- <td colspan="<?php echo ( ( $t_filter_cols - 8 ) * $t_custom_cols ); ?>">&nbsp;</td> -->
</tr>
<tr class="row-2">
	<!-- Number of bugs per page -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_per_page(); ?>
	</td>
	<!-- View Status -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_view_state(); ?>
	</td>
	<!-- Show Sticky bugs -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_sticky_issues(); ?>
	</td>
	<!-- Highlight changed bugs -->
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<?php print_filter_highlight_changed(); ?>
	</td>
	<td valign="top" class="left" colspan="<?php echo ( 3 * $t_custom_cols ); ?>">
		<?php print_filter_do_filter_by_date( true ); # hide checkbox as it's already been shown ?>
	</td>
	<td valign="top" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
        <?php print_filter_relationship_type(); ?>
	</td>
	<!-- <td colspan="<?php echo ( ( $t_filter_cols - 8 ) * $t_custom_cols ); ?>">&nbsp;</td> -->
</tr>

<?php
if ( ON == config_get( 'filter_by_custom_fields' ) ) {
?>
	<?php # -- Custom Field Searching -- ?>
	<?php
	if ( sizeof( $t_accessible_custom_fields_ids ) > 0 ) {
		$t_per_row = config_get( 'filter_custom_fields_per_row' );
		$t_num_rows = ceil( sizeof( $t_accessible_custom_fields_ids ) / $t_per_row );
		$t_base = 0;

		for ( $i = 0; $i < $t_num_rows; $i++ ) {
			?>
			<tr class="row-category2">
			<?php
			for( $j = 0; $j < $t_per_row; $j++ ) {
				echo '<td class="small-caption" colspan="' . ( 1 * $t_filter_cols ) . '">';
				if ( isset( $t_accessible_custom_fields_names[$t_base + $j] ) ) {
					echo string_display( lang_get_defaulted( $t_accessible_custom_fields_names[$t_base + $j] ) );
				} else {
					echo '&nbsp;';
				}
				echo '</td>';
			}
			?>
			</tr>
			<tr class="row-2">
			<?php
			for ( $j = 0; $j < $t_per_row; $j++ ) {
				echo '<td colspan="' . ( 1 * $t_filter_cols ) . '">';
				if ( isset( $t_accessible_custom_fields_ids[$t_base + $j] ) ) {
					print_filter_custom_field($t_accessible_custom_fields_ids[$t_base + $j]);
				} else {
					echo '&nbsp;';
				}
				echo '</td>';
			}

			?>
			</tr>
			<?php
			$t_base += $t_per_row;
		}
	}
}

if ( 'simple' == $f_view_type ) {
	$t_project_cols = 0;
} else {
	$t_project_cols = 3;
}
?>

<tr class="row-1">
	<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>" valign="top">
		<?php PRINT lang_get( 'sort' ) ?>:
	</td>
	<td valign="top" colspan="<?php echo ( ( $t_filter_cols - 1 - $t_project_cols ) * $t_custom_cols ); ?>">
		<?php
			print_filter_show_sort();
		?>
	</td>
	<?php
		if ( 'advanced' == $f_view_type ) {
	?>
			<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>" valign="top">
				<?php PRINT lang_get( 'email_project' ) ?>:
			</td>
			<td valign="top" colspan="<?php echo( 2 * $t_custom_cols ); ?>">
				<?php
					print_filter_project_id();
				?>
			</td>
	<?php
		}
	?>
</tr>
<tr class="row-category2">
<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"><?php echo lang_get( 'search' ) ?></td>
<td class="small-caption" colspan="<?php echo ( ( $t_filter_cols - 2 ) * $t_custom_cols ); ?>"><?php echo lang_get( 'tags' ) ?></td>
<td class="small-caption" colspan="<?php echo ( 1 * $t_custom_cols ); ?>"></td>
</tr>
<tr>
	<!-- Search field -->
	<td colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<input type="text" size="16" name="search" value="<?php echo string_html_specialchars( $t_filter['search'] ); ?>" />
	</td>

	<td class="small-caption" colspan="<?php echo ( ( $t_filter_cols - 2 ) * $t_custom_cols ); ?>"><?php print_filter_tag_string() ?></td>

	<!-- Submit button -->
	<td class="right" colspan="<?php echo ( 1 * $t_custom_cols ); ?>">
		<input type="submit" name="filter" class="button" value="<?php echo lang_get( 'filter_button' ) ?>" />
	</td>
</tr>
</table>
</form>

<?php html_page_bottom1( __FILE__ ) ?>
