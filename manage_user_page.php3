<?
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000  Kenzaburo Ito - kenito@300baud.org
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details
?>
<? include( "core_API.php" ) ?>
<? login_cookie_check() ?>
<? print_html_top() ?>
<? print_head_top() ?>
<? print_title( $g_window_title ) ?>
<? print_css( $g_css_include_file ) ?>
<? include( $g_meta_include_file ) ?>
<? print_head_bottom() ?>
<? print_body_top() ?>
<? print_header( $g_page_title ) ?>
<?
	db_connect( $g_hostname, $g_db_username, $g_db_password, $g_database_name );

	if ( !access_level_check_greater_or_equal( "administrator" ) ) {
		### need to replace with access error page
		header( "Location: $g_logout_page" );
		exit;
	}

	### grab user data and prefix with u_
    $query = "SELECT *
    		FROM $g_mantis_user_table
			WHERE id='$f_id'";
    $result = db_mysql_query($query);
	$row = mysql_fetch_array($result);
	extract( $row, EXTR_PREFIX_ALL, "u" );
?>

<p>
<? print_menu( $g_menu_include_file ) ?>

<p>
<div align=center>
<table width=50% bgcolor=<? echo $g_primary_border_color." ".$g_primary_table_tags ?>>
<tr>
	<td bgcolor=<? echo $g_white_color ?>>
	<table width=100%>
	<form method=post action="<? echo $g_manage_user_update ?>">
	<input type=hidden name=f_id value="<? echo $u_id ?>">
	<tr>
		<td colspan=3 bgcolor=<? echo $g_table_title_color ?>>
			<b><? echo $s_edit_user_title ?></b>
		</td>
	</tr>
	<tr bgcolor=<? echo $g_primary_color_dark ?>>
		<td>
			<? echo $s_username ?>:
		</td>
		<td colspan=2>
			<input type=text size=16 name=f_username value="<? echo $u_username ?>">
		</td>
	</tr>
	<tr bgcolor=<? echo $g_primary_color_light ?>>
		<td>
			<? echo $s_email ?>:
		</td>
		<td colspan=2>
			<input type=text size=32 name=f_email value="<? echo $u_email ?>">
		</td>
	</tr>
	<tr bgcolor=<? echo $g_primary_color_dark ?>>
		<td>
			<? echo $s_access_level ?>:
		</td>
		<td colspan=2>
			<select name=f_access_level>
				<option value="viewer" <? if ( $u_access_level=="viewer" ) echo "SELECTED" ?>>viewer
				<option value="reporter" <? if ( $u_access_level=="reporter" ) echo "SELECTED" ?>>reporter
				<option value="updater" <? if ( $u_access_level=="updater" ) echo "SELECTED" ?>>updater
				<option value="developer" <? if ( $u_access_level=="developer" ) echo "SELECTED" ?>>developer
				<option value="administrator" <? if ( $u_access_level=="administrator" ) echo "SELECTED" ?>>administrator
			</select>
		</td>
	</tr>
	<tr bgcolor=<? echo $g_primary_color_light ?>>
		<td>
			<? echo $s_enabled ?>
		</td>
		<td colspan=2>
			<input type=checkbox name=f_enabled <? if ( $u_enabled=="on" ) echo "CHECKED" ?>>
		</td>
	</tr>
	<tr bgcolor=<? echo $g_primary_color_dark ?>>
		<td>
			<? echo $s_protected ?>
		</td>
		<td colspan=2>
			<input type=checkbox name=f_protected <? if ( $u_protected=="on" ) echo "CHECKED" ?>>
		</td>
	</tr>
	<tr align=center>
		<td>
			<input type=submit value="<? echo $s_update_user_button ?>">
		</td>
			</form>
			<form method=post action="<? echo $g_manage_user_reset ?>">
		<td>
			<input type=hidden name=f_id value="<? echo $u_id ?>">
			<input type=hidden name=f_protected value="<? echo $u_protected ?>">
			<input type=submit value="<? echo $s_reset_password_button ?>">
		</td>
			</form>
			<form method=post action="<? echo $g_manage_user_delete_page ?>">
		<td>
			<input type=hidden name=f_id value="<? echo $u_id ?>">
			<input type=hidden name=f_protected value="<? echo $u_protected ?>">
			<input type=submit value="<? echo $s_delete_user_button ?>">
		</td>
			</form>
	</tr>
	</table>
	</td>
</tr>
</table>
</div>

<p>
<div align=center>
<? echo $s_reset_password_msg ?>
</div>

<? print_footer(__FILE__) ?>
<? print_body_bottom() ?>
<? print_html_bottom() ?>