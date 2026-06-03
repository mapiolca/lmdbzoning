-- Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>

CREATE TABLE llx_categorie_contract(
	fk_categorie integer NOT NULL,
	fk_contract integer NOT NULL,
	import_key varchar(14)
) ENGINE=innodb;
