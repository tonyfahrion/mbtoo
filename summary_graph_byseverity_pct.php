<?php
include( 'core_API.php' );

login_cookie_check();

# if user is below view summary threshold, then re-direct to mainpage.
if ( !access_level_check_greater_or_equal( $g_view_summary_threshold ) ) {
	print_header_redirect( 'main_page.php' );
}

#centers the chart
$center = 0.30;

#position of the legend
$poshorizontal = 0.10;
$posvertical = 0.09;

if ($g_customize_attributes) {
			# to be deleted when moving to manage_project_page.php	
			$t_project_id = '0000000';

			# custom attributes insertion
			insert_attributes( 'severity', $t_project_id, 'global' );
			insert_attributes( 'severity', $t_project_id, 'str' ) ;
}
create_bug_enum_summary_pct( $s_severity_enum_string, 'severity');
graph_bug_enum_summary_pct($s_by_severity_pct);
?>