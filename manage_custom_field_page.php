<?php
# Mantis - a php based bugtracking system

# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
# Copyright (C) 2002 - 2007  Mantis Team   - mantisbt-dev@lists.sourceforge.net

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

	# --------------------------------------------------------
	# $Id$
	# --------------------------------------------------------

	require_once( 'core.php' );

	$t_core_path = config_get( 'core_path' );

	require_once( $t_core_path.'custom_field_api.php' );

	auth_reauthenticate();

	access_ensure_global_level( config_get( 'manage_custom_fields_threshold' ) );

	html_page_top1( lang_get( 'manage_custom_field_link' ) );
	html_page_top2();

	print_manage_menu( 'manage_custom_field_page.php' );
?>
	<br />

<!-- List of custom field -->
<table class="width100" cellspacing="1">
	<tr>
		<td class="form-title" colspan="6">
				<?php echo lang_get( 'custom_fields_setup' ) ?>
		</td>
	</tr>
	<tr>
		<td class="category" width="12%">
			<?php echo lang_get( 'custom_field_name' ) ?>
		</td>
		<td class="category" width="12%">
			<?php echo lang_get( 'custom_field_project_count' ) ?>
		</td>
		<td class="category" width="12%">
			<?php echo lang_get( 'custom_field_type' ) ?>
		</td>
		<td class="category" width="40%">
			<?php echo lang_get( 'custom_field_possible_values' ) ?>
		</td>
		<td class="category" width="12%">
			<?php echo lang_get( 'custom_field_default_value' ) ?>
		</td>
		<td class="category" width="12%">
			<?php echo lang_get( 'custom_field_advanced' ) ?>
		</td>
	</tr>
	<?php
		$t_custom_fields = custom_field_get_ids();
		foreach( $t_custom_fields as $t_field_id )
		{
			$t_desc = custom_field_get_definition( $t_field_id );
	?>
		<tr <?php echo helper_alternate_class() ?>>
			<td>
				<a href="manage_custom_field_edit_page.php?field_id=<?php echo $t_field_id ?>"><?php echo string_display( $t_desc['name'] ) ?></a>
			</td>
			<td>
				<?php echo count( custom_field_get_project_ids( $t_field_id ) ) ?>
			</td>
			<td>
				<?php echo get_enum_element( 'custom_field_type', $t_desc['type'] ) ?>
			</td>
			<td>
				<?php echo string_display( $t_desc['possible_values'] ) ?>
			</td>
			<td>
				<?php echo string_display( $t_desc['default_value'] ) ?>
			</td>
			<td align="center">
				<?php echo trans_bool( $t_desc['advanced'] ) ?>
			</td>
		</tr>
	<?php
		} # Create Form END
	?>
</table>

<br />

<form method="post" action="manage_custom_field_create.php">
		<input type="text" name="name" size="32" maxlength="64" />
		<?php helper_show_token() ?>
		<input type="submit" class="button" value="<?php echo lang_get( 'add_custom_field_button' ) ?>" />
</form>

<br />

<?php html_page_bottom1( __FILE__ ) ?>
