<?
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000, 2001  Kenzaburo Ito - kenito@300baud.org
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details
?>
<? include( "core_API.php" ) ?>
<? login_cookie_check() ?>
<?
	db_connect( $g_hostname, $g_db_username, $g_db_password, $g_database_name );
?>
<? print_html_top() ?>
<? print_head_top() ?>
<? print_title( $g_window_title ) ?>
<? print_css( $g_css_include_file ) ?>
<? include( $g_meta_include_file ) ?>
<? print_head_bottom() ?>
<? print_body_top() ?>
<? print_header( $g_page_title ) ?>
<? print_top_page( $g_top_include_page ) ?>

<? print_menu( $g_menu_include_file ) ?>

<?
	### Select the news posts
	$query = "SELECT *, UNIX_TIMESTAMP(date_posted) as date_posted
			FROM $g_mantis_news_table
			WHERE (project_id='$g_project_cookie_val' OR project_id='0000000') AND id='$f_id'";
	$result = db_query( $query );
    $news_count = db_num_rows( $result );

    ### Loop through results
	for ($i=0;$i<$news_count;$i++) {
		$row = db_fetch_array($result);
		extract( $row, EXTR_PREFIX_ALL, "v" );

		$v_headline 	= string_display( $v_headline );
		$v_body 		= string_display( $v_body );
		$v_date_posted 	= date( $g_normal_date_format, $v_date_posted );

		## grab the username and email of the poster
    	$row2 = get_user_info_by_id_arr( $v_poster_id );
		if ( $row2 ) {
			$t_poster_name	= $row2["username"];
			$t_poster_email	= $row2["email"];
		}
?>
<p>
<div align="center">
<table width="75%" bgcolor="<? echo $g_primary_border_color ?>" <? echo $g_primary_table_tags ?>>
<tr>
	<td bgcolor="<? echo $g_primary_color_dark ?>">
		<b><? echo $v_headline ?></b> -
		<i><? echo $v_date_posted ?></i> -
		<a href="mailto:<? echo $t_poster_email ?>"><? echo $t_poster_name ?></a>
	</td>
</tr>
<tr>
	<td bgcolor="<? echo $g_primary_color_light ?>">
		<br>
		<blockquote>
			<? echo $v_body ?>
		</blockquote>
	</td>
</tr>
</table>
</div>
<?
	}  ### end for loop
?>

<p>
<div align="center">
	<? print_bracket_link( $g_news_list_page, $s_back_link ) ?>
</div>

<? print_bottom_page( $g_bottom_include_page ) ?>
<? print_footer(__FILE__) ?>
<? print_body_bottom() ?>
<? print_html_bottom() ?>