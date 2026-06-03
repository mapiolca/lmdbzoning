-- Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>

ALTER TABLE llx_categorie_contract ADD PRIMARY KEY (fk_categorie, fk_contract);
ALTER TABLE llx_categorie_contract ADD INDEX idx_categorie_contract_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_contract ADD INDEX idx_categorie_contract_fk_contract (fk_contract);
