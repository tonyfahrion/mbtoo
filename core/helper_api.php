<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002         Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the files README and LICENSE for details

	# --------------------------------------------------------
	# $Id: helper_api.php,v 1.30 2002-12-29 09:26:46 jfitzell Exp $
	# --------------------------------------------------------

	###########################################################################
	# Helper API
	###########################################################################

	# These are miscellaneous functions to help the package

	# --------------------
	# alternate color function
	#  If no index is given, continue alternating based on the last index given
	function helper_alternate_colors( $p_index=null, $p_odd_color, $p_even_color ) {
		static $t_index = 1;

		if ( null !== $p_index ) {
			$t_index = $p_index;
		}

		if ( 1 == $t_index++ % 2 ) {
			return $p_odd_color;
		} else {
			return $p_even_color;
		}
	}
	# --------------------
	# alternate classes for table rows
	#  If no index is given, continue alternating based on the last index given
	function helper_alternate_class( $p_index=null, $p_odd_class="row-1", $p_even_class="row-2" ) {
		static $t_index = 1;
		
		if ( null !== $p_index ) {
			$t_index = $p_index;
		}

		if ( 1 == $t_index++ % 2 ) {
			return "class=\"$p_odd_class\"";
		} else {
			return "class=\"$p_even_class\"";
		}
	}
	# --------------------
	# get the color string for the given status
	function get_status_color( $p_status ) {
		$t_status_enum_string = config_get( 'status_enum_string' );
		$t_status_colors = config_get( 'status_colors' );

		# This code creates the appropriate variable name
		# then references that color variable
		# You could replace this with a bunch of if... then... else
		# statements

		$t_color_str = 'closed';
		$t_arr = explode_enum_string( $t_status_enum_string );
		$t_arr_count = count( $t_arr );
		for ( $i=0;$i<$t_arr_count;$i++ ) {
			$elem_arr = explode_enum_arr( $t_arr[$i] );
			if ( $elem_arr[0] == $p_status ) {
				# now get the appropriate translation
				$t_color_str = $elem_arr[1];
				break;
			}
		}

		$t_color_variable_name = $t_color_str.'_color';
		if ( config_is_set( $t_color_variable_name ) ) {
			return config_get( $t_color_variable_name );
		} elseif ( isset ( $t_status_colors[$t_color_str] ) ) {
			return $t_status_colors[$t_color_str];
		}

		return '#ffffff';
	}
	# --------------------
	# Given a enum string and num, return the appropriate string
	function get_enum_element( $p_enum_name, $p_val ) {
		$config_var = config_get( $p_enum_name.'_enum_string' );
		$string_var = lang_get(  $p_enum_name.'_enum_string' );

		# use the global enum string to search
		$t_arr = explode_enum_string( $config_var );
		$t_arr_count = count( $t_arr );
		for ( $i=0;$i<$t_arr_count;$i++ ) {
			$elem_arr = explode_enum_arr( $t_arr[$i] );
			if ( $elem_arr[0] == $p_val ) {
				# now get the appropriate translation
				return get_enum_to_string( $string_var, $p_val );
			}
		}
		return '@null@';
	}
	# --------------------
	# If $p_var and $p_val are euqal to each other then we echo SELECTED
	# This is used when we want to know if a variable indicated a certain
	# option element is selected
	function check_selected( $p_var, $p_val ) {
		if ( $p_var == $p_val ) {
			echo ' selected="selected" ';
		}
	}
	# --------------------
	# If $p_var and $p_val are euqal to each other then we echo CHECKED
	# This is used when we want to know if a variable indicated a certain
	# element is checked
	function check_checked( $p_var, $p_val ) {
		if ( $p_var == $p_val ) {
			echo ' checked="checked" ';
		}
	}
	# --------------------
	# Return the current project id as stored in a cookie
	#  If no cookie exists, the user's default project is returned
	function helper_get_current_project() {
		$t_cookie_name = config_get( 'project_cookie' );

		$t_project_id = gpc_get_cookie( $t_cookie_name, null );

		if ( null === $t_project_id ) {
			return (int)current_user_get_pref( 'default_project' );
		} else {
			return (int)$t_project_id;
		}
	}
	# --------------------
	# Clear all known user preference cookies
	function helper_clear_pref_cookies() {
		gpc_clear_cookie( 'project_cookie' );
		gpc_clear_cookie( 'view_all_cookie' );
		gpc_clear_cookie( 'manage_cookie' );
	}
	# --------------------
	# Check whether the user has confirmed this action.
	#
	# If the user has not confirmed the action, generate a page which asks
	#  the user to confirm and then submits a form back to the current page
	#  with all the GET and POST data and an additional field called _confirmed
	#  to indicate that confirmation has been done.
	function helper_ensure_confirmed( $p_message, $p_button_label ) {
		if (true == gpc_get_bool( '_confirmed' ) ) {
			return true;
		}

		global $PHP_SELF;
		if ( ! php_version_at_least( '4.1.0' ) ) {
			global $_POST, $_GET;
		}

		print_page_top1();
		print_page_top2();

		echo "<br />\n<div align=\"center\">\n";
		print_hr();
		echo "\n$p_message\n";

		echo '<form method="post" action="' . $PHP_SELF . "\">\n";

		print_hidden_inputs( gpc_strip_slashes( $_POST ) );
		print_hidden_inputs( gpc_strip_slashes( $_GET ) );

		echo "<input type=\"hidden\" name=\"_confirmed\" value=\"1\" />\n";
		echo '<br /><br /><input type="submit" value="' . $p_button_label . '" />';
		echo "\n</form>\n";

		print_hr();
		echo "</div>\n";
		print_page_bot1();
		exit;
	}
?>
