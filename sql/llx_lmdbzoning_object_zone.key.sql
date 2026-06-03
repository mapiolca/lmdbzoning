ALTER TABLE llx_lmdbzoning_object_zone ADD UNIQUE INDEX uk_lmdbzoning_object_zone_object_profile (entity, element_type, fk_element, fk_profile);
ALTER TABLE llx_lmdbzoning_object_zone ADD INDEX idx_lmdbzoning_object_zone_entity (entity);
ALTER TABLE llx_lmdbzoning_object_zone ADD INDEX idx_lmdbzoning_object_zone_object (element_type, fk_element);
ALTER TABLE llx_lmdbzoning_object_zone ADD INDEX idx_lmdbzoning_object_zone_profile (fk_profile);
ALTER TABLE llx_lmdbzoning_object_zone ADD INDEX idx_lmdbzoning_object_zone_zone (zone_code);
ALTER TABLE llx_lmdbzoning_object_zone ADD INDEX idx_lmdbzoning_object_zone_status (calculation_status);
ALTER TABLE llx_lmdbzoning_object_zone ADD INDEX idx_lmdbzoning_object_zone_override (manual_override);
ALTER TABLE llx_lmdbzoning_object_zone ADD INDEX idx_lmdbzoning_object_zone_datecalc (date_calculation);
