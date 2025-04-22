CREATE TABLE tx_picturerino_requests (
    uid int(11) unsigned NOT NULL auto_increment,
    identifier varchar(255) DEFAULT '' NOT NULL,
    width int(11) DEFAULT '0' NOT NULL,
    height int(11) DEFAULT '0' NOT NULL,
    viewport int(11) DEFAULT '0' NOT NULL,
    ratio varchar(255) DEFAULT '' NOT NULL,
		width_processed int(11) DEFAULT '0' NOT NULL,
		height_processed int(11) DEFAULT '0' NOT NULL,
		file varchar(255) DEFAULT '' NOT NULL,
    ip varchar(255) DEFAULT '' NOT NULL,
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (width,ratio)
);
