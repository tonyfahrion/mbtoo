<?php include( "core_API.php" ) ?>
<?php
	if ( !isset( $f_action ) ) {
		$res = setcookie( "testcookie", "blah" );
	} else if ( $f_action=="test" ) {
		$cookieval = $HTTP_COOKIE_VARS["testcookie"];
	}
?>
<h2>This file will try to identify the possible problems that windows users are having with not being able to login.</h2>
<p>
<?php if ( $f_action=="test" ) { ?>
	An attempt was made to set a cookie.
	<p>
	The value is :<b><?php echo $cookieval ?></b>:.  Should be :<b>blah</b>:.
	<p>
	Result is:
	<?php
		if ($cookieval=="blah") {
			PRINT "<b>PASSED</b>";
		} else {
			PRINT "<b>FAILED</b>";
		}
	?>
	<p>
	If the test is failed then your browser may have cookies turned off.  Additionally, your webserver or PHP may be configured incorrectly.
<?php } # endif f_action ?>
<p>
<a href="admin_cookiecheck.php3?f_action=test">Click here</a> to reload the page and see if the value was set correctly.
