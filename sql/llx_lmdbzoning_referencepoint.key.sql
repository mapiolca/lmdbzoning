ALTER TABLE llx_lmdbzoning_referencepoint ADD UNIQUE INDEX uk_lmdbzoning_referencepoint_ref (entity, ref);
ALTER TABLE llx_lmdbzoning_referencepoint ADD INDEX idx_lmdbzoning_referencepoint_entity (entity);
ALTER TABLE llx_lmdbzoning_referencepoint ADD INDEX idx_lmdbzoning_referencepoint_active (active);
ALTER TABLE llx_lmdbzoning_referencepoint ADD INDEX idx_lmdbzoning_referencepoint_town (town);
