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
 * @subpackage Config
 * @copyright Copyright (C) 2009  Tony Wolf <wolf@os-forge.net>
 * @link http://www.mantisbt.org
 */

$g_config = array(
	'debug'        => false, # if you are a developer and you like to hack on mantis, this could be useful to be "true"
	'default_lang' => 'english',
	'window_title' => 'MantisBT', # our prefix for the top-window-border
	'logo'         => 'images/mantis_logo.gif', # Logo, you are able to set a whole URL here if you need it - to disable it, set this to false
	'logo_url'     => '%default_home_page%', # where should the logo point to? give an address or set it to fales to disable it
	'admin_checks' => true, # Check for admin directory, database upgrades, etc.
	'favicon_icon' => 'images/favicon.ico', # Favicon image

	'template'     => array(
		'dir_smarty'   => 'public/external/smarty/libs/',
		'dir_template' => 'public/templates/default/',
		'dir_compile'  => 'public/compiled/',
		'dir_css'      => 'public/css/',
		'dir_jquery'   => 'public/external/jquery/'
	),
);

?>
