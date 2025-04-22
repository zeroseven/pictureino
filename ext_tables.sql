CREATE TABLE tx_picturerino_request (
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

CREATE TABLE tx_picturerino_request_processed (
	uid_local       int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign     int(11) unsigned DEFAULT '0' NOT NULL,
	sorting         int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local, uid_foreign),
	KEY             uid_local (uid_local),
	KEY             uid_foreign (uid_foreign)
);
