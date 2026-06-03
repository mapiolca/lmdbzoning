CREATE TABLE llx_lmdbzoning_calculation_log (
	rowid integer AUTO_INCREMENT PRIMARY KEY,
	entity integer DEFAULT 1 NOT NULL,
	fk_object_zone integer,
	event_code varchar(64) NOT NULL,
	element_type varchar(64),
	fk_element integer,
	message text,
	context_data text,
	datec datetime,
	fk_user_creat integer,
	import_key varchar(14)
) ENGINE=innodb;
