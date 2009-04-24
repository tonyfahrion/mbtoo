<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html;charset={if isset($mantis_charset)}$mantis_charset{else}utf-8{/if}" />

		{foreach from=$css_files value=css}
		<link rel="stylesheet" type="text/css" href="{$css}" />
		{/foreach}

		{if isset( $mantis_redirect ) }
		<meta http-equiv="refresh" content="{$redirect.time};URL={$redirect.url}" />
		{/if}

		<title>{$window_title}</title>
	</head>
	<body>
		
	</body>
</html>