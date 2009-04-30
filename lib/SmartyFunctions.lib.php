<?php
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package CoreAPI
 * @subpackage TemplateAPI
 * @copyright Copyright (C) 2009  Tony Wolf <wolf@os-forge.net>
 * @link http://www.mantisbt.org
 */

/**
 * Description of SmartyFunctionslib
 *
 * @author dragon-fire
 */
class SmartyFunctions {
  
	public static function compiler_lang( &$p_tag_args, &$p_smarty ) {
		$t_tag = trim( $p_tag_args );
		return 'echo \''.lang_get($t_tag).'\';';
	}

	public static function config_value( &$p_tag_args, &$p_smarty ) {
		return 'echo config_get(\''.$p_tag_args.'\');';
	}

	public static function config_value_static( &$p_tag_args, &$p_smarty ) {
		$t_value = config_get( $p_tag_args );
		return 'echo \''.str_replace( '\'', '\\\'', $t_value).'\';';
	}

}
?>