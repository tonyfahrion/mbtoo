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
		{if config_is_enabled( 'rss_feed_url' ) }
		<link rel="alternate" type="application/rss+xml" title="RSS" href="{cv rss_feed_url}" />
		{/if}
		{if config_is_enabled( 'window_title' ) }
		<title>{cvs window_title}{if config_is_enabled( 'window_title_section' ) } - {$mantis_window_title_section}{/if}</title>
		{/if}
	</head>
	<body>
		{*
		$t_page = config_get( 'top_include_page' );
	$t_logo_image = config_get( 'logo_image' );
	$t_logo_url = config_get( 'logo_url' );

	if( is_blank( $t_logo_image ) ) {
		$t_show_logo = false;
	} else {
		$t_show_logo = true;
		if( is_blank( $t_logo_url ) ) {
			$t_show_url = false;
		} else {
			$t_show_url = true;
		}
	}

	if( !is_blank( $t_page ) && file_exists( $t_page ) && !is_dir( $t_page ) ) {
		include( $t_page );
	} else if( $t_show_logo ) {
		if( is_page_name( 'login_page' ) ) {
			$t_align = 'center';
		} else {
			$t_align = 'left';
		}

		echo '<div align="', $t_align, '">';
		if( $t_show_url ) {
			echo '<a href="', config_get( 'logo_url' ), '">';
		}
		echo '<img border="0" alt="Mantis Bug Tracker" src="' . helper_mantis_url( config_get( 'logo_image' ) ) . '" />';
		if( $t_show_url ) {
			echo '</a>';
		}
		echo '</div>';
	}

		*}
		{if isset( $top_include_page ) }
		  {include file="mantis:$top_include_page"}
		{elseif config_is_enabled( 'logo_image' ) }
		<div align="{if $mantis_module == 'login'}center{else}left{/if}">
			{if config_is_enabled( 'logo_url' ) }
			<a href="{cv logo_url}"><img src="{url logo_image}" /></a>
			{else}
			<img src="{url logo_image}" />
			{/if}
		</div>
		{/if}
		{es EVENT_LAYOUT_PAGE_HEADER}
