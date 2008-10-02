<?php
# Mantis - a php based bugtracking system

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

require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

class XmlImportExportPlugin extends MantisPlugin {
	/**
	 *  A method that populates the plugin information and minimum requirements.
	 */
	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->page = '';

		$this->version = '1.0';
		$this->requires = array(
				'MantisCore' => '1.2.0',
				);

		$this->author = 'Mantis Team';
		$this->contact = 'mantisbt-dev@lists.sourceforge.net';
		$this->url = 'http://www.mantisbt.org';
	}

	/**
	 * Default plugin configuration.
	 */
        function hooks() {
            $hooks = array(
                    'EVENT_MENU_MANAGE' => 'import_issues_menu',
                    'EVENT_MENU_FILTER' => 'export_issues_menu'
                    );
            return $hooks;
        }

	function import_issues_menu() {
		return array(
				'<a href="' . plugin_page( 'import' ) . '">' . plugin_lang_get( 'import' ) . '</a>',
				);
	}

	function export_issues_menu() {
		return array(
				'<a href="' . plugin_page( 'export' ) . '">' . plugin_lang_get( 'export' ) . '</a>',
				);
	}
}
