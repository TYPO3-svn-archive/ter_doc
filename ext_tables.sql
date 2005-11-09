
#
# Table structure for table 'tx_terdoc_manuals'
#
CREATE TABLE tx_terdoc_manuals ( 
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  extensionkey varchar(30) DEFAULT '' NOT NULL,
  version varchar(11) DEFAULT '' NOT NULL,
  title varchar(50) DEFAULT '' NOT NULL,
  language char(2) DEFAULT '' NOT NULL,
  modificationdate int(11) DEFAULT '0' NOT NULL,
  authorname tinytext NOT NULL,
  authoremail tinytext NOT NULL,
  abstract text NOT NULL,
  t3xfilemd5 varchar(32) DEFAULT '' NOT NULL,

  PRIMARY KEY (uid),
  KEY extkey (extensionkey),
  KEY extversion (version),
);

#
# Table structure for table 'tx_terdoc_categories'
#
CREATE TABLE tx_terdoc_categories ( 
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  title varchar(100) DEFAULT '' NOT NULL,
  description text NOT NULL,
  viewpid int(11) unsigned DEFAULT '0' NOT NULL,
  isdefault int(4) unsigned DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
);

#
# Table structure for table 'tx_terdoc_manualscategories'
#
CREATE TABLE tx_terdoc_manualscategories ( 
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  extensionkey varchar(30) DEFAULT '' NOT NULL,
  categoryuid int(11) unsigned DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
);

#
# Table structure for table 'tx_terdoc_manualspagecache'
#
CREATE TABLE tx_terdoc_manualspagecache ( 
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  extensionkey varchar(30) DEFAULT '' NOT NULL,
  version varchar(11) DEFAULT '' NOT NULL,

  PRIMARY KEY (uid),
  KEY extkey (extensionkey),
  KEY extversion (version),
);