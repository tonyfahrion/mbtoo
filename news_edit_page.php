<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002 - 2004  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: news_edit_page.php,v 1.40 2005-02-12 20:01:06 jlatour Exp $
	# --------------------------------------------------------
?>
<?php
	require_once( 'core.php' );

	$t_core_path = config_get( 'core_path' );

	require_once( $t_core_path.'news_api.php' );
	require_once( $t_core_path.'string_api.php' );
?>
<?php
	$f_news_id = gpc_get_int( 'news_id' );
	$f_action = gpc_get_string( 'action', '' );

	# If deleting item redirect to delete script
	if ( 'delete' == $f_action ) {
		print_header_redirect( 'news_delete.php?news_id='.$f_news_id );
	}

	# Retrieve news item data and prefix with v_
	$row = news_get_row( $f_news_id );
	if ( $row ) {
    	extract( $row, EXTR_PREFIX_ALL, 'v' );
    }

	access_ensure_project_level( config_get( 'manage_news_threshold' ), $v_project_id );

   	$v_headline = string_attribute( $v_headline );
   	$v_body 	= string_textarea( $v_body );
?>
<?php html_page_top1( lang_get( 'edit_news_title' ) ) ?>
<?php html_page_top2() ?>

<?php # Edit News Form BEGIN ?>
<br />
<div align="center">
<form method="post" action="news_update.php">
<table class="width75" cellspacing="1">
<tr>
	<td class="form-title">
		<input type="hidden" name="news_id" value="<?php echo $v_id ?>" />
		<?php echo lang_get( 'headline' ) ?>
	</td>
	<td class="right">
		<?php print_bracket_link( 'news_menu_page.php', lang_get( 'go_back' ) ) ?>
	</td>
</tr>
<tr class="row-1">
	<td class="category" width="25%">
		<span class="required">*</span><?php echo lang_get( 'headline' ) ?>
	</td>
	<td width="75%">
		<input type="text" name="headline" size="64" maxlength="64" value="<?php echo $v_headline ?>" />
	</td>
</tr>
<tr class="row-2">
	<td class="category">
		<span class="required">*</span><?php echo lang_get( 'body' ) ?>
	</td>
	<td>
		<textarea name="body" cols="60" rows="10" wrap="virtual"><?php echo $v_body ?></textarea>
	</td>
</tr>
<tr class="row-1">
	<td class="category">
		<?php echo lang_get( 'post_to' ) ?>
	</td>
	<td>
		<select name="project_id">
		<?php
			$t_sitewide = false;
			if ( access_has_project_level( ADMINISTRATOR ) ) {
				$t_sitewide = true;
			}
			print_project_option_list( $v_project_id, $t_sitewide );
		?>
		</select>
	</td>
</tr>
<tr class="row-2">
	<td class="category">
		<?php echo lang_get( 'announcement' ) ?><br />
		<span class="small"><?php echo lang_get( 'stays_on_top' ) ?></span>
	</td>
	<td>
		<input type="checkbox" name="announcement" <?php check_checked( $v_announcement, 1 ); ?> />
	</td>
</tr>
<tr class="row-1">
	<td class="category" width="25%">
		<?php echo lang_get( 'view_status' ) ?>
	</td>
	<td width="75%">
		<select name="view_state">
			<?php print_enum_string_option_list( 'view_state', $v_view_state ) ?>
		</select>
	</td>
</tr>
<tr>
	<td>
		<span class="required">* <?php echo lang_get( 'required' ) ?></span>
	</td>
	<td class="center">
		<input type="submit" class="button" value="<?php echo lang_get( 'update_news_button' ) ?>" />
	</td>
</tr>
</table>
</form>
</div>
<?php # Edit News Form END ?>

<?php html_page_bottom1( __FILE__ ) ?>
