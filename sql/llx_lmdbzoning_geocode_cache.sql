CREATE TABLE llx_lmdbzoning_geocode_cache (
	rowid integer AUTO_INCREMENT PRIMARY KEY,
	entity integer DEFAULT 1 NOT NULL,
	address_hash varchar(64) NOT NULL,
	address_raw text,
	address_normalized text,
	latitude double,
	longitude double,
	source varchar(64),
	confidence_score double,
	status varchar(32) NOT NULL,
	message text,
	datec datetime,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer,
	fk_user_modif integer,
	import_key varchar(14)
) ENGINE=innodb;
