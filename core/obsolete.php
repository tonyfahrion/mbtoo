<?php
	# Mantis - a php based bugtracking system
	# Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	# Copyright (C) 2002         Mantis Team   - mantisbt-dev@lists.sourceforge.net
	# This program is distributed under the terms and conditions of the GPL
	# See the files README and LICENSE for details

	# --------------------------------------------------------
	# $Id: obsolete.php,v 1.2 2002-08-29 09:46:16 jfitzell Exp $
	# --------------------------------------------------------
	
	###########################################################################
	# Check that obsolete configs are not used.
	# THIS FILE ASSUMES THAT THE CONFIGURATION IS INCLUDED AS WELL AS THE
	# config_api.php.
	###########################################################################

	# Check for obsolete variables
	config_obsolete( 'notify_developers_on_new', 'notify_flags' );
	config_obsolete( 'notify_on_new_threshold', 'notify_flags' );
	config_obsolete( 'notify_admin_on_new', 'notify_flags' );
?>