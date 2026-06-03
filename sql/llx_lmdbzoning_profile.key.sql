ALTER TABLE llx_lmdbzoning_profile ADD UNIQUE INDEX uk_lmdbzoning_profile_ref (entity, ref);
ALTER TABLE llx_lmdbzoning_profile ADD INDEX idx_lmdbzoning_profile_entity (entity);
ALTER TABLE llx_lmdbzoning_profile ADD INDEX idx_lmdbzoning_profile_active (active);
ALTER TABLE llx_lmdbzoning_profile ADD INDEX idx_lmdbzoning_profile_referencepoint (fk_referencepoint);
