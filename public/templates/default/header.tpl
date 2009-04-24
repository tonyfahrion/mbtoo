<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html;charset={lang charset}" />
		{foreach from=$css_files item=css}
		
		<link rel="stylesheet" type="text/css" href="{$css}" />
		{/foreach}
		{if isset( $mantis_redirect ) }
		<meta http-equiv="refresh" content="{$redirect.time};URL={$redirect.url}" />
		{/if}
		{if isset( $mantis_enable_js ) }

		<script type="text/javascript" src="javascript/common.js"></script>
		<script type="text/javascript" src="javascript/ajax.js"></script>
		<script type="text/javascript" src="javascript/projax/prototype.js"></script>
		<script type="text/javascript" src="javascript/projax/scriptaculous.js"></script>
		{/if}
		{if isset( $mantis_rss ) }
		<link rel="alternate" type="application/rss+xml" title="RSS" href="{$mantis_rss}" />
		{/if}

		<title>{if !empty( $mantis_window_title ) }{$mantis_window_title}{if !empty( $mantis_window_title_section ) } - {$mantis_window_title_section}{/if}{/if}</title>
	</head>
	<body>
