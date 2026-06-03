CREATE TABLE llx_lmdbzoning_profile (
	rowid integer AUTO_INCREMENT PRIMARY KEY,
	entity integer DEFAULT 1 NOT NULL,
	ref varchar(128) NOT NULL,
	label varchar(255) NOT NULL,
	fk_referencepoint integer NOT NULL,
	distance_method varchar(32) DEFAULT 'air_distance' NOT NULL,
	unit varchar(16) DEFAULT 'km' NOT NULL,
	description text,
	active tinyint DEFAULT 1 NOT NULL,
	datec datetime,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer,
	fk_user_modif integer,
	import_key varchar(14)
) ENGINE=innodb;
