ALTER TABLE llx_lmdbzoning_profile_zone ADD COLUMN IF NOT EXISTS fk_categorie_societe integer;
ALTER TABLE llx_lmdbzoning_profile_zone ADD COLUMN IF NOT EXISTS fk_categorie_contact integer;
ALTER TABLE llx_lmdbzoning_profile_zone ADD COLUMN IF NOT EXISTS fk_categorie_facture integer;
