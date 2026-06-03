ALTER TABLE llx_lmdbzoning_profile_zone ADD UNIQUE INDEX uk_lmdbzoning_profile_zone_code (entity, fk_profile, zone_code);
ALTER TABLE llx_lmdbzoning_profile_zone ADD INDEX idx_lmdbzoning_profile_zone_entity (entity);
ALTER TABLE llx_lmdbzoning_profile_zone ADD INDEX idx_lmdbzoning_profile_zone_profile (fk_profile);
ALTER TABLE llx_lmdbzoning_profile_zone ADD INDEX idx_lmdbzoning_profile_zone_priority (priority);
ALTER TABLE llx_lmdbzoning_profile_zone ADD INDEX idx_lmdbzoning_profile_zone_active (active);
