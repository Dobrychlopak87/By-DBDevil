-- Etap 4: wersjonowanie migracji i właścicielstwo treści.

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` varchar(120) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- W instalacjach utworzonych z modules/ads/schema.sql i
-- modules/chronicle/schema.sql kolumny owner_id są już częścią schematu.
-- Dla starszych instalacji poniższe ALTER-y należy wykonać jako osobną,
-- wcześniej zweryfikowaną migrację zależną od stanu INFORMATION_SCHEMA.
