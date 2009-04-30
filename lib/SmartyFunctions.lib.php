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
		return 'echo \''.lang_get( $t_tag ).'\';';
	}

	public static function config_value( &$p_tag_args, &$p_smarty ) {
		return 'echo config_get(\''.$p_tag_args.'\');';
	}

	public static function config_value_static( &$p_tag_args, &$p_smarty ) {
		$t_value = config_get( $p_tag_args );
		return 'echo \''.str_replace( '\'', '\\\'', $t_value ).'\';';
	}

	public static function helper_url( &$p_tag_args, &$p_smarty ) {
		return 'echo helper_mantis_url( config_get(\''.str_replace( '\'', '\\\'', $p_tag_args ).'\') );';
	}

	/**
	 * This is only a legacy template2event function - don't use it!
	 * @todo this shouldn't be used unless we will provide a legacy support for plugins - 2009-04-30 Tony Wolf
	 * @param string $p_tag_args
	 * @param object $p_smarty
	 * @return string
	 */
	public static function event_signal( &$p_tag_args, &$p_smarty ) {
		ob_start();
		event_signal( $p_tag_args );
		$t_value = ob_get_contents();
		ob_end_clean();
		return 'echo \''.str_replace( '\'', '\\\'', $t_value ).'\';';
	}

}
?>