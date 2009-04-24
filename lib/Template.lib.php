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

	private $c_config;

	public function __construct(){
		parent::__construct();

		global $g_config;
		$this->c_config =& $g_config;
		$this->template_dir =& $this->c_config['template']['dir_template'];
		$this->compile_dir  =& $this->c_config['template']['dir_compile'];

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
		$this->register_compiler_function( 'tr', $t_lang );
	}

	public function mantis_enable_html_header() {
		$this->assign( 'mantis_show_html_wrapper', true );

		global $g_use_javascript;
		$this->assign_by_ref( 'mantis_enable_js', $g_use_javascript );
		global $g_css_include_file;
		$this->assign_by_ref( 'css_files', $g_css_include_file );

		global $g_rss_feed_url;
		if( $g_rss_feed_url !== null ) {
			$this->assign_by_ref( 'mantis_rss', $g_rss_feed_url );
		}
		$this->assign_by_ref( 'mantis_window_title', $this->c_config['window_title'] );
	}

	public function mantis_enable_html_body_head() {

		$this->assign( 'mantis_show_body_head', true );
		$this->assign_by_ref( 'mantis_logo', $this->c_config['logo'] );
	}

	public function mantis_set_module($p_page) {
		$this->assign('mantis_module', $p_page);
	}
	
}
?>
