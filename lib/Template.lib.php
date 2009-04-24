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

require_once('Smarty.class.php');
require_once('SmartyFunctions.lib.php');

/**
 * Description of template_api
 *
 * @author dragon-fire
 */
class Template extends Smarty {

	public function __construct(){
		parent::__construct();

		global $g_config_template;
		$this->template_dir = $g_config_template['template_dir'];
		$this->compile_dir  = $g_config_template[ 'compile_dir'];

		# we need compile_id to improve performance for multi-language support
		$this->compile_id   = lang_get_current();

		global $g_debug;
		if ( $g_debug === true ) {
			$this->force_compile   = true;
			$this->debugging_ctrl  = 'URL';
			$this->caching         = 0;
			$this->error_reporting = true;
		}

		# now let's register our functions
		$t_lang = array('SmartyFunctions', 'compiler_lang');
		$this->register_compiler_function( 'lang', $t_lang );
	}

	public function mantis_enable_html_header() {
		$this->assign_by_ref( 'charset', lang_get('charset') );
		global $g_use_javascript;
		$this->assign_by_ref( 'mantis_enable_js', $g_use_javascript );
		global $g_css_include_file;
		$this->assign_by_ref( 'css_files', $g_css_include_file );

		global $g_rss_feed_url;
		if( $g_rss_feed_url !== null ) {
			$this->assign_by_ref( 'mantis_rss', $g_rss_feed_url );
		}
	}

	public function mantis_enable_html_body_head() {
		global $g_page_title;
		$this->assign_by_ref( 'mantis_window_title', $g_page_title );
	}
	
}
?>
