# phpMyAdmin MySQL-Dump
# http://phpwizard.net/phpMyAdmin/
#

# --------------------------------------------------------
#
# Table structure for table 'mantis_bug_table'
#

DROP TABLE IF EXISTS mantis_bug_table;
CREATE TABLE mantis_bug_table (
   id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL auto_increment,
   reporter_id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL,
   handler_id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL,
   duplicate_id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL,
   priority enum('immediate','urgent','high','normal','low','none') DEFAULT 'normal' NOT NULL,
   severity enum('block','crash','major','minor','tweak','text','trivial','feature') DEFAULT 'minor' NOT NULL,
   reproducibility enum('always','sometimes','random','have not tried','unable to duplicate') DEFAULT 'always' NOT NULL,
   status enum('new','acknowledged','confirmed','assigned','resolved') DEFAULT 'new' NOT NULL,
   resolution enum('open','fixed','reopened','unable to duplicate','not fixable','duplicate','not a bug','suspended') DEFAULT 'open' NOT NULL,
   projection enum('redesign','major rework','minor fix','tweak') DEFAULT 'minor fix' NOT NULL,
   category enum('bugtracker','other') DEFAULT 'other' NOT NULL,
   date_submitted timestamp(14),
   last_updated timestamp(14),
   eta enum('none','< 1 day','2-3 days','< 1 week','< 1 month','> 1 month') DEFAULT 'none' NOT NULL,
   bug_text_id mediumint(7) unsigned zerofill DEFAULT '0000000' NOT NULL,
   os varchar(32) NOT NULL,
   os_build varchar(16) NOT NULL,
   platform varchar(32) NOT NULL,
   version varchar(16) NOT NULL,
   build tinyint(4) DEFAULT '0' NOT NULL,
   votes tinyint(4) DEFAULT '0' NOT NULL,
   summary varchar(128) NOT NULL,
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Table structure for table 'mantis_bug_text_table'
#

DROP TABLE IF EXISTS mantis_bug_text_table;
CREATE TABLE mantis_bug_text_table (
   id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL auto_increment,
   description text NOT NULL,
   steps_to_reproduce text NOT NULL,
   additional_information text NOT NULL,
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Table structure for table 'mantis_bugnote_table'
#

DROP TABLE IF EXISTS mantis_bugnote_table;
CREATE TABLE mantis_bugnote_table (
   id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL auto_increment,
   bug_id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL,
   reporter_id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL,
   bugnote_text_id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL,
   date_submitted timestamp(14),
   last_modified timestamp(14),
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Table structure for table 'mantis_bugnote_text_table'
#

DROP TABLE IF EXISTS mantis_bugnote_text_table;
CREATE TABLE mantis_bugnote_text_table (
   id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL auto_increment,
   note text NOT NULL,
   PRIMARY KEY (id)
);

# --------------------------------------------------------
#
# Table structure for table 'mantis_news_table'
#

DROP TABLE IF EXISTS mantis_news_table;
CREATE TABLE mantis_news_table (
   id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL auto_increment,
   poster_id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL,
   date_posted timestamp(14),
   last_modified timestamp(14),
   headline varchar(64) NOT NULL,
   body text NOT NULL,
   PRIMARY KEY (id),
   KEY id (id)
);

# --------------------------------------------------------
#
# Table structure for table 'mantis_user_table'
#

DROP TABLE IF EXISTS mantis_user_table;
CREATE TABLE mantis_user_table (
   id int(7) unsigned zerofill DEFAULT '0000000' NOT NULL auto_increment,
   username varchar(32) NOT NULL,
   email varchar(64) NOT NULL,
   password varchar(32) NOT NULL,
   date_created timestamp(14),
   last_visit timestamp(14),
   enabled char(3) DEFAULT 'on' NOT NULL,
   access_level enum('viewer','reporter','updater','developer','administrator') DEFAULT 'viewer' NOT NULL,
   cookie_string varchar(128) NOT NULL,
   PRIMARY KEY (id)
);

#
# Dumping data for table 'mantis_user_table'
#

INSERT INTO mantis_user_table VALUES( '0000001', 'administrator', 'admin', 'MNYm8SfoJlvIY', '20001121161430', '20001121161430', 'on', 'administrator', 'MN91uSF/JIhos8bcda8acc2ead8d60749ad019e56b54fadkPGTyoBgNBQf91563adc2f7337f566cc3d2c792bc3c-10728595');

