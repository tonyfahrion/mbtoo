<?php
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2009  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

error_reporting( E_ALL );

$g_skip_open_db = true;  # don't open the database in database_api.php

/**
 * MantisBT Core API's
 */
require_once( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'core.php' );

$t_core_path = config_get_global( 'core_path' );

require_once( $t_core_path . 'email_api.php' );
require_once( $t_core_path . 'database_api.php' );

define( 'BAD', 0 );
define( 'GOOD', 1 );
define( 'WARN', 2 );

$f_showall = gpc_get_int( 'showall', false );

$g_failed_test = false;

function print_test_result( $p_result ) {
	global $g_failed_test;
	switch ( $p_result ) {
		case BAD:
			echo '<td bgcolor="#ff0088">BAD</td>';
			$g_failed_test = true;
			break;
		case GOOD:
			echo '<td bgcolor="#00ff88">GOOD</td>';
			break;
		case WARN:
			echo '<td bgcolor="#E56717">WARN</td>';
			break;
	}
}

function print_info_row( $p_description, $p_info = null ) {
	if( $p_info == null ) {
		echo '<tr><td bgcolor="#ffffff" colspan="2">' . $p_description . '</td></tr>';
	} else {
		echo '<tr><td bgcolor="#ffffff">' . $p_description . '</td>';
		echo '<td bgcolor="#ffffff">' . $p_info . '</td></tr>';
	}
}

function print_test_row( $p_description, $p_pass, $p_info = null ) {
	global $f_showall;
	if ( $f_showall == false && $p_pass == true ) {
		return $p_pass;
	}
	echo '<tr><td bgcolor="#ffffff">' .$p_description;
	if( $p_info != null) {
		if( is_array( $p_info ) ) {
			echo '<br /><i>' . $p_info[$p_pass] . '</i>';
		} else {
			echo '<br /><i>' . $p_info . '</i>';
		}
	}
	echo '</td>';

	if( $p_pass ) {
		print_test_result( GOOD );
	} else {
		print_test_result( BAD );
	}

	echo '</tr>';

	return $p_pass;
}

function print_test_warn_row( $p_description, $p_pass, $p_info = null ) {
	global $f_showall;
	if ( $f_showall == false && $p_pass == true ) {
		return $p_pass;
	}
	echo '<tr><td bgcolor="#ffffff">' . $p_description;
	if( $p_info != null) {
		if( is_array( $p_info ) ) {
			echo '<br /><i>' . $p_info[$p_pass] . '</i>';
		} else {
			echo '<br /><i>' . $p_info . '</i>';
		}
	}
	echo '</td>';

	if( $p_pass ) {
		print_test_result( GOOD );
	} else {
		print_test_result( WARN );
	}

	echo '</tr>';

	return $p_pass;
}

function test_bug_download_threshold() {
	$t_pass = true;

	$t_view_threshold = config_get_global( 'view_attachments_threshold' );
	$t_download_threshold = config_get_global( 'download_attachments_threshold' );
	$t_delete_threshold = config_get_global( 'delete_attachments_threshold' );

	if( $t_view_threshold > $t_download_threshold ) {
		$t_pass = false;
	} else {
		if( $t_download_threshold > $t_delete_threshold ) {
			$t_pass = false;
		}
	}

	print_test_row( 'Bug attachments download thresholds (view_attachments_threshold, ' .
		'download_attachments_threshold, delete_attachments_threshold)', $t_pass );

	return $t_pass;
}

function test_bug_attachments_allow_flags() {
	$t_pass = true;

	$t_own_view = config_get_global( 'allow_view_own_attachments' );
	$t_own_download = config_get_global( 'allow_download_own_attachments' );
	$t_own_delete = config_get_global( 'allow_delete_own_attachments' );

	if(( $t_own_delete == ON ) && ( $t_own_download == FALSE ) ) {
		$t_pass = false;
	} else {
		if(( $t_own_download == ON ) && ( $t_own_view == OFF ) ) {
			$t_pass = false;
		}
	}

	print_test_row( 'Bug attachments allow own flags (allow_view_own_attachments, ' .
		'allow_download_own_attachments, allow_delete_own_attachments)', $t_pass );

	return $t_pass;
}

function check_zend_optimiser_version() {
	$t_pass = true;

	ob_start();
	phpinfo(INFO_GENERAL);
	$t_output = ob_get_contents();
	ob_end_clean();

	$t_output = str_replace(array("&gt;", "&lt;", "&quot;", "&amp;", "&#039;", "&nbsp;"), array(">", "<", "\"", "&", "'", " "), $t_output);

	define ( 'ZEND_OPTIMIZER_VERSION', '3.3');
	define ( 'ZEND_OPTIMIZER_SUBVERSION', 3);

	if (strstr($t_output, "Zend Optimizer")) {
		$t_version = split("Zend Optimizer",$t_output);
		$t_version = split(",",$t_version[1]);
		$t_version = trim($t_version[0]);

		if (!strstr($t_version,"v")) {
			$t_info = 'Zend Optimizer Detected - Unknown Version.';
			$t_pass = false;
  		} else {
			$t_version = str_replace("v","",$t_version);
			$t_version = explode(".",$t_version);
			$t_subVersion = $t_version[2];
			$t_dummy = array_pop($t_version);
			$t_version = implode(".",$t_version);

			if (!($t_version > ZEND_OPTIMIZER_VERSION) || ($t_version==ZEND_OPTIMIZER_VERSION && $t_subVersion>=ZEND_OPTIMIZER_SUBVERSION)) {
				$t_pass = false;
				$t_info = 'Fail - Installed Version: ' . $t_version . '.' . $t_subVersion . '.';
			}
		}
	} else {
		$t_info = 'Zend Optimiser not detected';
	}

	if (strstr($t_output, 'has been disabled')) {
		$t_info = 'Unable to determine Zend Optimizer version - phpinfo() is disabled.';
		$t_pass = false;
	}

	if( $t_pass == false ) {
		$t_info .= ' Zend Optimizer should be version be ' . ZEND_OPTIMIZER_VERSION . '.' . ZEND_OPTIMIZER_SUBVERSION  . ' or greater! Some old versions cause the view issues page not to display completely. The latest version of Zend Optimizer can be found at www.zend.com';
	}

	print_test_row( 'Checking Zend Optimiser version (if installed)...', $t_pass, $t_info );

	return $t_pass;
}

function test_database_utf8() {
	if ( !db_is_mysql() ) {
		return;
	}

	// table collation/character set check
	$result = db_query_bound( 'SHOW TABLE STATUS' );
	while( $row = db_fetch_array( $result ) ) {
		print_test_row( 'Checking Table Collation is utf8 for ' . $row['Name'] . '....', substr( $row['Collation'], 0, 5 ) === 'utf8_', $row['Collation'] );
	}

	foreach( db_get_table_list() as $t_table ) {
		if( db_table_exists( $t_table ) ) {
			$result = db_query_bound( 'SHOW FULL FIELDS FROM ' . $t_table );
			while( $row = db_fetch_array( $result ) ) {
				if ( $row['Collation'] === null ) {
					continue;
				}
				print_test_row( 'Checking Non-null Column Collation in ' . $t_table . ' is utf8 for ' . $row['Field'] . '....', substr( $row['Collation'], 0, 5 ) === 'utf8_', $row['Collation'] . ' ( ' . $row['Type'] . ')' );
			}
		}
	}
}


	require_once( $t_core_path . 'obsolete.php' );

	html_page_top( 'MantisBT Administration - Check Installation' );

?>
<table class="width75" align="center" cellspacing="1">
<tr>
<td class="form-title" width="30%" colspan="2"><?php echo 'Checking your installation' ?></td>
</tr>

<?php 

print_test_row( 'MantisBT requires at least <b>PHP ' . PHP_MIN_VERSION . '</b>. You are running <b>PHP ' . phpversion(), $result = version_compare( phpversion(), PHP_MIN_VERSION, '>=' ) );

if ( !print_test_row( 'Checking Config File Exists', file_exists( $g_absolute_path . 'config_inc.php' ), array( false => 'Please use install.php to perform initial installation <a href="install.php">Click here</a>' ) ) ) {
	die;
}

print_test_row( 'Opening connection to database [' . config_get_global( 'database_name' ) . '] on host [' . config_get_global( 'hostname' ) . '] with username [' . config_get_global( 'db_username' ) . ']', @db_connect( config_get_global( 'dsn', false ), config_get_global( 'hostname' ), config_get_global( 'db_username' ), config_get_global( 'db_password' ), config_get_global( 'database_name' ) ) != false );
if( !db_is_connected() ) {
	print_info_row( 'Database is not connected - Can not continue checks' );
}

print_test_warn_row( 'Checking adodb version...', version_compare( $g_db->Version(), '5.05', '>=' ), $g_db->Version() );

print_test_row('Checking using bundled adodb with some drivers...', !(db_is_pgsql() || db_is_mssql() || db_is_db2()) || strstr($ADODB_vers, 'MantisBT Version') !== false );
$t_serverinfo = $g_db->ServerInfo();
	
print_info_row( 'Database Type (adodb)', $g_db->databaseType );
print_info_row( 'Database Provider (adodb)', $g_db->dataProvider );
print_info_row( 'Database Server Description (adodb)', $t_serverinfo['description'] );
print_info_row( 'Database Server Description (version)', $t_serverinfo['version'] );

print_test_row( 'Checking to see if your absolute_path config option has a trailing slash: "' . config_get_global( 'absolute_path' ) . '"', ( "\\" == substr( config_get_global( 'absolute_path' ), -1, 1 ) ) || ( "/" == substr( config_get_global( 'absolute_path' ), -1, 1 ) ) );

// Windows-only checks
if( substr( php_uname(), 0, 7 ) == 'Windows' ) {
	print_test_row( 'validate_email (if ON) requires php 5.3 on windows...',
		OFF == config_get_global( 'validate_email' ) || ON == config_get_global( 'validate_email' ) && version_compare( phpversion(), '5.3.0', '>=' ) );
	print_test_row( 'check_mx_record (if ON) requires php 5.3 on windows...',
		OFF == config_get_global( 'check_mx_record' ) || ON == config_get_global( 'check_mx_record' ) && version_compare( phpversion(), '5.3.0', '>=' ) );
}

$t_vars = array(
	'magic_quotes_gpc',
	'include_path',
);

while( list( $t_foo, $t_var ) = each( $t_vars ) ) {
	print_info_row( $t_var, ini_get( $t_var ) );
}

if ( db_is_mssql() ) {
	if ( print_test_row( 'check mssql textsize in php.ini...', ini_get( 'mssql.textsize' ) != 4096, ini_get( 'mssql.textsize' ) ) ) {
		print_test_warn_row( 'check mssql textsize in php.ini...', ini_get( 'mssql.textsize' ) == 2147483647, ini_get( 'mssql.textsize' ) );
	}
	if ( print_test_row( 'check mssql textsize in php.ini...', ini_get( 'mssql.textlimit' ) != 4096 , ini_get( 'mssql.textlimit' ) ) ) {
		print_test_warn_row( 'check mssql textsize in php.ini...', ini_get( 'mssql.textsize' ) == 2147483647, ini_get( 'mssql.textsize' ) );
	}
}
print_test_row( 'check variables_order includes GPCS', stristr( ini_get( 'variables_order' ), 'G' ) && stristr( ini_get( 'variables_order' ), 'P' ) && stristr( ini_get( 'variables_order' ), 'C' ) && stristr( ini_get( 'variables_order' ), 'S' ), ini_get( 'variables_order' ) );


test_bug_download_threshold();
test_bug_attachments_allow_flags();

print_test_row( 'check mail configuration: send_reset_password = ON requires allow_blank_email = OFF',
	( ( OFF == config_get_global( 'send_reset_password' ) ) || ( OFF == config_get_global( 'allow_blank_email' ) ) ) );
print_test_row( 'check mail configuration: send_reset_password = ON requires enable_email_notification = ON',
	( OFF == config_get_global( 'send_reset_password' ) ) || ( ON == config_get_global( 'enable_email_notification' ) ) );
print_test_row( 'check mail configuration: allow_signup = ON requires enable_email_notification = ON',
	( OFF == config_get_global( 'allow_signup' ) ) || ( ON == config_get_global( 'enable_email_notification' ) ) );
print_test_row( 'check mail configuration: allow_signup = ON requires send_reset_password = ON',
	( OFF == config_get_global( 'allow_signup' ) ) || ( ON == config_get_global( 'send_reset_password' ) ) );
print_test_row( 'check language configuration: fallback_language is not \'auto\'',
	'auto' <> config_get_global( 'fallback_language' ) );
print_test_row( 'check configuration: allow_anonymous_login = ON requires anonymous_account to be set',
	( OFF == config_get_global( 'allow_anonymous_login' ) ) || ( strlen( config_get_global( 'anonymous_account') ) > 0 ) );

$t_anon_user = false;

print_test_row( 'check configuration: anonymous_account is a valid username if set',
	( (strlen( config_get_global( 'anonymous_account') ) > 0 ) ? ( ($t_anon_user = user_get_id_by_name( config_get_global( 'anonymous_account') ) ) !== false ) : TRUE ) );
print_test_row( 'check configuration: anonymous_account should not be an administrator',
	( $t_anon_user ? ( !access_compare_level( user_get_field( $t_anon_user, 'access_level' ), ADMINISTRATOR) ) : TRUE ) );
print_test_row( '$g_bug_link_tag is not empty ("' . config_get_global( 'bug_link_tag' ) . '")',
	'' <> config_get_global( 'bug_link_tag' ) );
print_test_row( '$g_bugnote_link_tag is not empty ("' . config_get_global( 'bugnote_link_tag' ) . '")',
	'' <> config_get_global( 'bugnote_link_tag' ) );
print_test_row( 'filters: dhtml_filters = ON requires use_javascript = ON',
	( OFF == config_get_global( 'dhtml_filters' ) ) || ( ON == config_get_global( 'use_javascript' ) ) );
print_test_row( 'Phpmailer sendmail configuration requires escapeshellcmd. Please use a different phpmailer method if this is blocked.',
	( PHPMAILER_METHOD_SENDMAIL != config_get( 'phpMailer_method' ) || ( PHPMAILER_METHOD_SENDMAIL == config_get( 'phpMailer_method' ) ) && function_exists( 'escapeshellcmd' ) ) );
print_test_row( 'Phpmailer sendmail configuration requires escapeshellarg. Please use a different phpmailer method if this is blocked.',
	( PHPMAILER_METHOD_SENDMAIL != config_get( 'phpMailer_method' ) || ( PHPMAILER_METHOD_SENDMAIL == config_get( 'phpMailer_method' ) ) && function_exists( 'escapeshellarg' ) ) );

check_zend_optimiser_version();

if( ON == config_get_global( 'use_jpgraph' ) ) {
	$t_jpgraph_path = config_get_global( 'jpgraph_path' );

	if( !file_exists( $t_jpgraph_path ) ) {
		$t_jpgraph_path = '..' . DIRECTORY_SEPARATOR . $t_jpgraph_path;
	}

	if( !file_exists( $t_jpgraph_path . 'jpgraph.php') ) {
		print_test_row( 'checking we can find jpgraph class files...', false );
	} else {
		require_once( $t_jpgraph_path . 'jpgraph.php' );

		print_test_row( 'Checking Jpgraph version (if installed)...', version_compare(JPG_VERSION, '2.3.0') ? true : false, JPG_VERSION );
	}

	print_test_row( 'check configuration: jpgraph (if used) requires php bundled gd for antialiasing support',
		( config_get_global( 'jpgraph_antialias' ) == OFF || function_exists('imageantialias') ) );

}

print_test_row( 'Checking if ctype is enabled in php (required for rss feeds)....', extension_loaded('ctype') );

print_test_row( 'Checking for mysql is at least version 4.1...', !(db_is_mysql() && version_compare( $t_serverinfo['version'], '4.1.0', '<' ) ) );
print_test_row( 'Checking for broken mysql version ( bug 10250)...', !(db_is_mysql() && $t_serverinfo['version'] == '4.1.21') );

test_database_utf8();

print_test_row( 'Checking Register Globals is set to off', ! ini_get_bool( 'register_globals' ) );

print_test_row( 'Checking CRYPT_FULL_SALT is NOT logon method', ! ( CRYPT_FULL_SALT == config_get_global( 'login_method' ) ) );

print_test_warn_row( 'Warn if passwords are stored in PLAIN text', ! ( PLAIN == config_get_global( 'login_method' ) ) );
print_test_warn_row( 'Warn if CRYPT is used (not MD5) for passwords', ! ( CRYPT == config_get_global( 'login_method' ) ) );

if ( config_get_global( 'allow_file_upload' ) ) {
	print_test_row( 'Checking that fileuploads are allowed in php (enabled in mantis config)', ini_get_bool( 'file_uploads' ) );
	
	print_info_row( 'PHP variable "upload_max_filesize"', ini_get_number( 'upload_max_filesize' ) );
	print_info_row( 'PHP variable "post_max_size"', ini_get_number( 'post_max_size' ) );
	print_info_row( 'MantisBT variable "max_file_size"', config_get_global( 'max_file_size' ) );

	print_test_row( 'Checking MantisBT upload file size is less than php', ( config_get_global( 'max_file_size' ) <= ini_get_number( 'post_max_size' ) ) && ( config_get_global( 'max_file_size' ) <= ini_get_number( 'upload_max_filesize' ) ) );

	if( DATABASE == config_get_global( 'file_upload_method' ) ) {
		print_info_row( 'There may also be settings in your web server and database that prevent you from  uploading files or limit the maximum file size.  See the documentation for those packages if you need more information.');
		if( 500 < min( ini_get_number( 'upload_max_filesize' ), ini_get_number( 'post_max_size' ), config_get_global( 'max_file_size' ) ) ) {
			print_info_row( '<span class="error">Your current settings will most likely need adjustments to the PHP max_execution_time or memory_limit settings, the MySQL max_allowed_packet setting, or equivalent.' );
		}
	}
	
	print_info_row( 'There may also be settings in your web server that prevent you from  uploading files or limit the maximum file size.  See the documentation for those packages if you need more information.');
}
?>
</table>
<?php
	if ( $g_failed_test ) {
?>
	 <table width="100%" bgcolor="#222222" border="0" cellpadding="20" cellspacing="1">
	<tr>
		<td bgcolor="#f4f4f4">Some Tests Failed. Please correct failed tests before using MantisBT.</td>
	</tr>
	</table>
<?php
	} else {
?>
	 <table width="100%" bgcolor="#222222" border="0" cellpadding="20" cellspacing="1">
	<tr>
		<td bgcolor="#f4f4f4">All Tests Passed. If you would like to view passed tests click <a href="check.php?showall=1">here</a>.</td>
	</tr>
	</table>
<?php	
	}
?>
</body>
</html>
