#
# Table structure for table 'tx_vgevitalstatistics_processes'
#
CREATE TABLE tx_vgevitalstatistics_processes (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	type varchar(255) DEFAULT '' NOT NULL,
	user blob NOT NULL,
	status int(11) DEFAULT '0' NOT NULL,
    paymentlib_trx_uid int(11) DEFAULT '0' NOT NULL,
	formdata mediumtext NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);