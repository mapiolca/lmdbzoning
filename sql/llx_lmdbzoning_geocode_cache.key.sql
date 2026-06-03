ALTER TABLE llx_lmdbzoning_geocode_cache ADD UNIQUE INDEX uk_lmdbzoning_geocode_cache_hash (entity, address_hash);
ALTER TABLE llx_lmdbzoning_geocode_cache ADD INDEX idx_lmdbzoning_geocode_cache_entity (entity);
ALTER TABLE llx_lmdbzoning_geocode_cache ADD INDEX idx_lmdbzoning_geocode_cache_status (status);
ALTER TABLE llx_lmdbzoning_geocode_cache ADD INDEX idx_lmdbzoning_geocode_cache_tms (tms);
