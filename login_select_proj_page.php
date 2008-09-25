<?php
# Mantis - a php based bugtracking system

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

	/**
	 * Allows the user to select a project that is visible to him
	 * @package MantisBT
	 * @version $Id$
	 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	 * @copyright Copyright (C) 2002 - 2008  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	 * @link http://www.mantisbt.org
	 */
	 /**
	  * Mantis Core API's
	  */
	require_once( 'core.php' );

	auth_ensure_user_authenticated();

	$f_ref = gpc_get_string( 'ref', '' );

	if ( count( current_user_get_accessible_projects() ) == 1) {
		$t_project_ids = current_user_get_accessible_projects();
		$t_project_id = (int) $t_project_ids[0];
		if ( count( current_user_get_accessible_subprojects( $t_project_id ) ) == 0 ) {
			print_header_redirect( "set_project.php?project_id=" . $t_project_id . "&ref=" . string_html_specialchars( $f_ref ), true);
			/* print_header_redirect terminates script execution */
		}
	}

	html_page_top1( lang_get( 'select_project_button' ) );
	html_page_top2();
?>

<!-- Project Select Form BEGIN -->
<br />
<div align="center">
<form method="post" action="set_project.php">
<table class="width50" cellspacing="1">
<tr>
	<td class="form-title" colspan="2">
		<input type="hidden" name="ref" value="<?php echo string_html_specialchars( $f_ref ) ?>" />
		<?php echo lang_get( 'select_project_button' ) ?>
	</td>
</tr>
<tr class="row-1">
	<td class="category" width="40%">
		<?php echo lang_get( 'choose_project' ) ?>
	</td>
	<td width="60%">
		<select name="project_id">
		<?php print_project_option_list( ALL_PROJECTS, false, null, true ) ?>
		</select>
	</td>
</tr>
<tr class="row-2">
	<td class="category">
		<?php echo lang_get( 'make_default' ) ?>
	</td>
	<td>
		<input type="checkbox" name="make_default" />
	</td>
</tr>
<tr>
	<td class="center" colspan="2">
		<input type="submit" class="button" value="<?php echo lang_get( 'select_project_button') ?>" />
	</td>
</tr>
</table>
</form>
</div>

<?php html_page_bottom1( __FILE__ ) ?>
