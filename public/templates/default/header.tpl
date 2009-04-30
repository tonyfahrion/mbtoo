{*
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
*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html;charset={tr charset}" />
		{foreach from=$css_files item=css}
		
		<link rel="stylesheet" type="text/css" href="{$css}" />
		{/foreach}
		{if isset( $mantis_redirect ) }
		<meta http-equiv="refresh" content="{$redirect.time};URL={$redirect.url}" />
		{/if}
		{if config_get( 'use_javascript' ) }

		<script type="text/javascript" src="javascript/common.js"></script>
		<script type="text/javascript" src="javascript/ajax.js"></script>
		<script type="text/javascript" src="javascript/projax/prototype.js"></script>
		<script type="text/javascript" src="javascript/projax/scriptaculous.js"></script>
		{/if}
		{if isset( $mantis_rss ) }
		<link rel="alternate" type="application/rss+xml" title="RSS" href="{$mantis_rss}" />
		{/if}
		{if config_is_enabled( 'window_title' ) }
		<title>{cvs window_title}{if config_is_enabled( 'window_title_section' ) } - {$mantis_window_title_section}{/if}</title>
		{/if}
	</head>
	<body>
