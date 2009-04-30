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
 * @todo documentation and bug fixing :)
 * @author Tony Wolf <wolf@os-forge.net>
 */
class Output extends Smarty {

	private $config;
	private $plugins;
	private $plugins_enabled;

	public function __construct(){
		parent::__construct();

		global $g_config;
		$this->config =& $g_config;
		$this->template_dir =& $this->config['template']['dir_template'];
		$this->compile_dir  =& $this->config['template']['dir_compile'];

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

		$this->plugins = array();
		$this->plugins_enabled = false;
	}

	/**
	 * The plugins function get_keys() should return something like that:
	 * \code
	 * array(
	 *   array(
	 *     'key' => 'mantis_logo',
	 *     'function' => 'my_logo_manipulator'
	 *   )
	 * );
	 * \codeend
	 *
	 * So if 'mantis_logo' would be assigned to $this it calles my_logo_manipulator('mantis_logo', $p_content).
	 *
	 * To be able to modify the content you should request a reference!
	 * So implement your my_logo_manipulator like this:
	 * \code
	 * public function my_logo_manipulator(&$p_id, &$p_content) { }
	 * \codeend
	 *
	 * @todo better phpdoc
	 * @param string $p_name
	 * @param object $p_object
	 * @return bool
	 */
	public function register_plugin($p_name, &$p_object) {

		if ( !is_object( $p_object ) ) {
			return false;
		}

		$t_keys = $p_object->get_keys();
		foreach ( $t_keys as $t_key_n_function ) {
			$this->plugins[$t_key_n_function['key']][] = array('object' => &$p_object, 'function' => $t_key_n_function['function']);
		}
		$this->plugins_enabled = true;
		return true;
	}

	public function assign($p_id, $p_content) {
		if ( $this->plugins_enabled && isset( $this->plugins[$p_id] ) ) {
			foreach( $this->plugins[$p_id] as $call ) {
				$call['object']->$call['function']( $p_id, $p_content );
			}
		}
		parent::assign($p_id, $p_content);
	}

	public function assign_by_ref($p_id, &$p_content) {
		if ( $this->plugins_enabled && isset( $this->plugins[$p_id] ) ) {
			foreach( $this->plugins[$p_id] as $call ) {
				$call['object']->$call['function']( $p_id, $p_content );
			}
		}
		parent::assign_by_ref($p_id, $p_content);
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
		$this->assign_by_ref( 'mantis_window_title', $this->config['window_title'] );
	}

	public function mantis_enable_html_body_head() {
		$this->assign( 'mantis_show_body_head', true );
		$this->assign_by_ref( 'mantis_logo', $this->config['logo'] );
	}

	public function mantis_set_module($p_page) {
		$this->assign('mantis_module', $p_page);
	}
	
}
?>
