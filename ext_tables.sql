CREATE TABLE tx_pictureino_request (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	identifier varchar(255) DEFAULT '' NOT NULL,
	width int(11) DEFAULT '0' NOT NULL,
	height int(11) DEFAULT '0' NOT NULL,
	aspect_ratio varchar(255) DEFAULT '' NOT NULL,
	width_evaluated int(11) DEFAULT '0' NOT NULL,
	height_evaluated int(11) DEFAULT '0' NOT NULL,
	version varchar(255) DEFAULT '' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	count int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY request (identifier, width, height, width_evaluated, height_evaluated),
	);

CREATE TABLE tx_pictureino_request_processed (
	request int(11) unsigned DEFAULT '0' NOT NULL,
	processedfile int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (request, processedfile)
);

CREATE TABLE tt_content (
	aspect_ratio varchar(255) DEFAULT '' NOT NULL,
);
