<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002 - 2004  Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the README and LICENSE files for details

	# --------------------------------------------------------
	# $Id: filter_api.php,v 1.18 2004-03-05 01:26:17 jlatour Exp $
	# --------------------------------------------------------

	$t_core_dir = dirname( __FILE__ ).DIRECTORY_SEPARATOR;
	
	require_once( $t_core_dir . 'current_user_api.php' );

	###########################################################################
	# Filter API
	###########################################################################

	# @@@ Had to make all these parameters required because we can't use
	#  call-time pass by reference anymore.  I really preferred not having
	#  to pass all the params in if you didn't want to, but I wanted to get
	#  rid of the errors for now.  If we can think of a better way later
	#  (maybe return an object) that would be great.
	#
	# $p_page_numer
	#   - the page you want to see (set to the actual page on return)
	# $p_per_page
	#   - the number of bugs to see per page (set to actual on return)
	#     -1   indicates you want to see all bugs
	#     null indicates you want to use the value specified in the filter
	# $p_page_count
	#   - you don't need to give a value here, the number of pages will be
	#     stored here on return
	# $p_bug_count
	#   - you don't need to give a value here, the number of bugs will be
	#     stored here on return
	function filter_get_bug_rows( &$p_page_number, &$p_per_page, &$p_page_count, &$p_bug_count ) {
		$t_bug_table			= config_get( 'mantis_bug_table' );
		$t_bug_text_table		= config_get( 'mantis_bug_text_table' );
		$t_bugnote_table		= config_get( 'mantis_bugnote_table' );
		$t_bugnote_text_table	= config_get( 'mantis_bugnote_text_table' );
		$t_project_table		= config_get( 'mantis_project_table' );
		$t_limit_reporters		= config_get( 'limit_reporters' );
		$t_report_bug_threshold		= config_get( 'report_bug_threshold' );

		$t_filter = current_user_get_bug_filter();

		if ( false === $t_filter ) {
			return false; # signify a need to create a cookie
			#@@@ error instead?
		}

		$t_project_id	= helper_get_current_project();
		$t_user_id		= auth_get_current_user_id();

		$t_where_clauses = array( "$t_project_table.enabled = 1", "$t_project_table.id = $t_bug_table.project_id" );
		$t_select_clauses = array( "$t_bug_table.*" );
		$t_join_clauses = array();

		if ( ALL_PROJECTS == $t_project_id ) {
			if ( ! current_user_is_administrator() ) {
				$t_projects = current_user_get_accessible_projects();

				if ( 0 == sizeof( $t_projects ) ) {
					return array();  # no accessible projects, return an empty array
				} else {
					$t_clauses = array();

					#@@@ use project_id IN (1,2,3,4) syntax if we can
					for ( $i=0 ; $i < sizeof( $t_projects ) ; $i++) {
						array_push( $t_clauses, "($t_bug_table.project_id='$t_projects[$i]')" );
					}

					array_push( $t_where_clauses, '('. implode( ' OR ', $t_clauses ) .')' );
				}
			}
		} else {
			access_ensure_project_level( VIEWER, $t_project_id );

			array_push( $t_where_clauses, "($t_bug_table.project_id='$t_project_id')" );
		}

		# private bug selection
		if ( ! access_has_project_level( config_get( 'private_bug_threshold' ) ) ) {
			$t_public = VS_PUBLIC;
			$t_private = VS_PRIVATE;
			array_push( $t_where_clauses, "($t_bug_table.view_state='$t_public' OR ($t_bug_table.view_state='$t_private' AND $t_bug_table.reporter_id='$t_user_id'))" );
		}

		# reporter
		if ( 'any' != $t_filter['reporter_id'] ) {
			$c_reporter_id = db_prepare_int( $t_filter['reporter_id'] );
			array_push( $t_where_clauses, "($t_bug_table.reporter_id='$c_reporter_id')" );
		}

		# limit reporter
		if ( ( ON === $t_limit_reporters ) && ( current_user_get_access_level() <= $t_report_bug_threshold ) ) {
			$c_reporter_id = db_prepare_int( auth_get_current_user_id() );
			array_push( $t_where_clauses, "($t_bug_table.reporter_id='$c_reporter_id')" );
		}

		# handler
		if ( 'none' == $t_filter['handler_id'] ) {
			array_push( $t_where_clauses, "$t_bug_table.handler_id=0" );
		} else if ( 'any' != $t_filter['handler_id'] ) {
			$c_handler_id = db_prepare_int( $t_filter['handler_id'] );
			array_push( $t_where_clauses, "($t_bug_table.handler_id='$c_handler_id')" );
		}

		# hide closed
		if ( ( 'on' == $t_filter['hide_closed'] ) && ( CLOSED != $t_filter['show_status'] ) ) {
			$t_closed = CLOSED;
			array_push( $t_where_clauses, "($t_bug_table.status<>'$t_closed')" );
		}

		# hide resolved
		if ( ( 'on' == $t_filter['hide_resolved'] ) && ( RESOLVED != $t_filter['show_status'] ) ) {
			$t_resolved = RESOLVED;
			array_push( $t_where_clauses, "($t_bug_table.status<>'$t_resolved')" );
		}

		# category
		if ( 'any' != $t_filter['show_category'] ) {
			$c_show_category = db_prepare_string( $t_filter['show_category'] );
			array_push( $t_where_clauses, "($t_bug_table.category='$c_show_category')" );
		}

		# severity
		if ( 'any' != $t_filter['show_severity'] ) {
			$c_show_severity = db_prepare_string( $t_filter['show_severity'] );
			array_push( $t_where_clauses, "($t_bug_table.severity='$c_show_severity')" );
		}

		# status
		if ( 'any' != $t_filter['show_status'] ) {
			$c_show_status = db_prepare_string( $t_filter['show_status'] );
			array_push( $t_where_clauses, "($t_bug_table.status='$c_show_status')" );
		}

		# Simple Text Search - Thnaks to Alan Knowles
		if ( !is_blank( $t_filter['search'] ) ) {
			$c_search = db_prepare_string( $t_filter['search'] );
			array_push( $t_where_clauses,
							"((summary LIKE '%$c_search%')
							 OR ($t_bug_text_table.description LIKE '%$c_search%')
							 OR ($t_bug_text_table.steps_to_reproduce LIKE '%$c_search%')
							 OR ($t_bug_text_table.additional_information LIKE '%$c_search%')
							 OR ($t_bug_table.id LIKE '%$c_search%')
							 OR ($t_bugnote_text_table.note LIKE '%$c_search%'))" );
			array_push( $t_where_clauses, "($t_bug_text_table.id = $t_bug_table.bug_text_id)" );

			$t_from_clauses = array( $t_bug_text_table, $t_project_table );

			array_push( $t_join_clauses, ",($t_bug_table LEFT JOIN $t_bugnote_table ON $t_bugnote_table.bug_id = $t_bug_table.id)" );

			array_push( $t_join_clauses, "LEFT JOIN $t_bugnote_text_table ON $t_bugnote_text_table.id = $t_bugnote_table.bugnote_text_id" );
		} else {
			$t_from_clauses = array( $t_bug_table, $t_project_table );
		}


		$t_select	= implode( ', ', array_unique( $t_select_clauses ) );
		$t_from		= 'FROM ' . implode( ', ', array_unique( $t_from_clauses ) );
		$t_join		= implode( ' ', $t_join_clauses );
		if ( sizeof( $t_where_clauses ) > 0 ) {
			$t_where	= 'WHERE ' . implode( ' AND ', $t_where_clauses );
		} else {
			$t_where	= '';
		}

		# Get the total number of bugs that meet the criteria.
		$query = "SELECT COUNT( DISTINCT $t_bug_table.id ) as count $t_from $t_join $t_where";
		$result = db_query( $query );
		$bug_count = db_result( $result );

		# write the value back in case the caller wants to know
		$p_bug_count = $bug_count;

		if ( null === $p_per_page ) {
			$p_per_page = (int)$t_filter['per_page'];
		} else if ( -1 == $p_per_page ) {
			$p_per_page = $bug_count;
		}

		# Guard against silly values of $f_per_page.
		if ( 0 == $p_per_page ) {
			$p_per_page = 1;
		}
		$p_per_page = (int)abs( $p_per_page );


		# Use $bug_count and $p_per_page to determine how many pages
		# to split this list up into.
		# For the sake of consistency have at least one page, even if it
		# is empty.
		$t_page_count = ceil($bug_count / $p_per_page);
		if ( $t_page_count < 1 ) {
			$t_page_count = 1;
		}

		# write the value back in case the caller wants to know
		$p_page_count = $t_page_count;

		# Make sure $p_page_number isn't past the last page.
		if ( $p_page_number > $t_page_count ) {
			$p_page_number = $t_page_count;
		}

		# Make sure $p_page_number isn't before the first page
		if ( $p_page_number < 1 ) {
			$p_page_number = 1;
		}

		$query2  = "SELECT DISTINCT $t_select
					$t_from
					$t_join
					$t_where";

		# Now add the rest of the criteria i.e. sorting, limit.
		$c_sort = db_prepare_string( $t_filter['sort'] );
		
		if ( 'DESC' == $t_filter['dir'] ) {
			$c_dir = 'DESC';
		} else {
			$c_dir = 'ASC';
		}

		$query2 .= " ORDER BY $c_sort $c_dir";

		# Figure out the offset into the db query
		#
		# for example page number 1, per page 5:
		#     t_offset = 0
		# for example page number 2, per page 5:
		#     t_offset = 5
		$c_per_page = db_prepare_int( $p_per_page );
		$c_page_number = db_prepare_int( $p_page_number );
		$t_offset = ( ( $c_page_number - 1 ) * $c_per_page );

		# perform query
		$result2 = db_query( $query2, $c_per_page, $t_offset );

		$row_count = db_num_rows( $result2 );

		$rows = array();

		for ( $i=0 ; $i < $row_count ; $i++ ) {
			$row = db_fetch_array( $result2 );
			$row['last_updated'] = db_unixtimestamp ( $row['last_updated'] );
			array_push( $rows, $row );
		}

		return $rows;
	}

	# --------------------
	# return true if the filter cookie exists and is of the correct version,
	#  false otherwise
	function filter_is_cookie_valid() {
		$t_view_all_cookie = gpc_get_cookie( config_get( 'view_all_cookie' ), '' );

		# check to see if the cookie does not exist
		if ( is_blank( $t_view_all_cookie ) ) {
			return false;
		}

		# check to see if new cookie is needed
		$t_setting_arr 			= explode( '#', $t_view_all_cookie );
		if ( $t_setting_arr[0] != config_get( 'cookie_version' ) ) {
			return false;
		}

		return true;
	}

	# --------------------
	# Will print the filter selection area for both the bug list view screen, as well
	# as the bug list print screen. This function was an attempt to make it easier to
	# add new filters and rearrange them on screen for both pages.
	function filter_draw_selection_area( $p_page_number, $p_for_screen = true )
	{
		$t_filter = current_user_get_bug_filter();

		$t_sort = $t_filter['sort'];
		$t_dir = $t_filter['dir'];

		$t_tdclass = "small-caption";
		$t_trclass = "row-category2";
		$t_action  = "view_all_set.php?f=3";

		if ( $p_for_screen == false ) 
		{
			$t_tdclass = "print";
			$t_trclass = "";
			$t_action  = "view_all_set.php";
		}
?>
		<br />
		<form method="post" name="filters" action="<?php echo $t_action; ?>">
		<input type="hidden" name="type" value="1" />
		<?php 
			if ( $p_for_screen == false ) 
			{
				print "<input type=\"hidden\" name=\"print\" value=\"1\" />";
				print "<input type=\"hidden\" name=\"offset\" value=\"0\" />";
			}	
		?>
		<input type="hidden" name="sort" value="<?php echo $t_sort ?>" />
		<input type="hidden" name="dir" value="<?php echo $t_dir ?>" />
		<input type="hidden" name="page_number" value="<?php echo $p_page_number ?>" />
		<table class="width100" cellspacing="0">

        <?php # -- Filter Form Header Row -- ?>
        <tr <?php echo "class=\"" . $t_trclass . "\""; ?>>
            <td class="small-caption"><?php echo lang_get( 'reporter' ) ?></td>
            <td class="small-caption"><?php echo lang_get( 'assigned_to' ) ?></td>
            <td class="small-caption"><?php echo lang_get( 'category' ) ?></td>
            <td class="small-caption"><?php echo lang_get( 'severity' ) ?></td>
            <td class="small-caption"><?php echo lang_get( 'status' ) ?></td>
            <td class="small-caption"><?php echo lang_get( 'show' ) ?></td>
            <td class="small-caption"><?php echo lang_get( 'changed' ) ?></td>
            <td class="small-caption"><?php echo lang_get( 'hide_status' ) ?></td>
        </tr>

		<?php # -- Filter Form Fields -- ?>
        <tr>
            <?php # -- Reporter -- ?>
            <td>
                <select name="reporter_id">
                    <option value="any"><?php echo lang_get( 'any' ) ?></option>
                    <option value="any"></option>
                    <?php print_reporter_option_list( $t_filter['reporter_id'] ) ?>
                </select>
            </td>
        
            <?php # -- Handler -- ?>
            <td>
                <select name="handler_id">
                    <option value="any"><?php echo lang_get( 'any' ) ?></option>
                    <option value="none" <?php check_selected( $t_filter['handler_id'], 'none' ); ?>><?php echo lang_get( 'none' ) ?></option>
                    <option value="any"></option>
                    <?php print_assign_to_option_list( $t_filter['handler_id'] ) ?>
                </select>
            </td>

            <?php # -- Category -- ?>
            <td>
                <select name="show_category">
                    <option value="any"><?php echo lang_get( 'any' ) ?></option>
                    <option value="any"></option>
                    <?php # This shows orphaned categories as well as selectable categories ?>
                    <?php print_category_complete_option_list( $t_filter['show_category'] ) ?>
                </select>
            </td>

            <?php # -- Severity -- ?>
            <td>
                <select name="show_severity">
                    <option value="any"><?php echo lang_get( 'any' ) ?></option>
                    <option value="any"></option>
                    <?php print_enum_string_option_list( 'severity', $t_filter['show_severity'] ) ?>
                </select>
            </td>

            <?php # -- Status -- ?>
            <td>
                <select name="show_status">
                    <option value="any"><?php echo lang_get( 'any' ) ?></option>
                    <option value="any"></option>
                    <?php print_enum_string_option_list( 'status', $t_filter['show_status'] ) ?>
                </select>
            </td>

            <?php # -- Number of bugs per page -- ?>
            <td>
                <input type="text" name="per_page" size="3" maxlength="7" value="<?php echo $t_filter['per_page'] ?>" />
            </td>

            <?php # -- Highlight changed bugs -- ?>
            <td>
                <input type="text" name="highlight_changed" size="3" maxlength="7" value="<?php echo $t_filter['highlight_changed'] ?>" />
            </td>

            <?php # -- Hide closed bugs -- ?>
            <td>
                <input type="checkbox" name="hide_resolved" <?php check_checked( $t_filter['hide_resolved'], 'on' ); ?> />&nbsp;<?php echo lang_get( 'filter_resolved' ); ?>
                <input type="checkbox" name="hide_closed" <?php check_checked( $t_filter['hide_closed'], 'on' ); ?> />&nbsp;<?php echo lang_get( 'filter_closed' ); ?>
            </td>
        </tr>

        <?php # -- Search and Date Header Row -- ?>
        <tr <?php echo "class=\"" . $t_trclass . "\""; ?>>
            <td class="small-caption" colspan="2"><?php echo lang_get( 'search' ) ?></td>
            <td class="small-caption" colspan="2"><!--Start Date--></td>
            <td class="small-caption" colspan="2"><!--End Date--></td>
            <td class="small-caption" colspan="2">&nbsp;</td>
        </tr>

        <?php # -- Search and Date fields -- ?>
        <tr>
            <?php # -- Text search -- ?>
            <td colspan="2">
                <input type="text" size="16" name="search" value="<?php echo $t_filter['search']; ?>" />
            </td>

            <?php # -- Start date -- ?>
            <td class="left" colspan="2">
            <!--
                <select name="start_month">
                    <?php print_month_option_list( $t_filter['start_month'] ) ?>
                </select>
                <select name="start_day">
                    <?php print_day_option_list( $t_filter['start_day'] ) ?>
                </select>
                <select name="start_year">
                    <?php print_year_option_list( $t_filter['start_year'] ) ?>
                </select>
            -->
            </td>
        
            <?php # -- End date -- ?>
            <td class="left" colspan="2">
            <!--
                <select name="end_month">
                    <?php print_month_option_list( $t_filter['end_month'] ) ?>
                </select>
                <select name="end_day">
                    <?php print_day_option_list( $t_filter['end_day'] ) ?>
                </select>
                <select name="end_year">
                    <?php print_year_option_list( $t_filter['end_year'] ) ?>
                </select>
            -->
            </td>

            <?php # -- SUBMIT button -- ?>
            <td class="right" colspan="2">
                <input type="submit" name="filter" value="<?php echo lang_get( 'filter_button' ) ?>" />
            </td>
        </tr>
        </table>
        </form>
<?php
	}
?>
