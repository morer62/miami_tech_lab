-- Miami Tech Lab shows vertical. Apply to the shared database before enabling admin writes.
-- Every public/admin query is site-key scoped; no records from sibling properties are eligible.
CREATE TABLE IF NOT EXISTS mtl_shows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_key VARCHAR(64) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  title VARCHAR(190) NOT NULL,
  tagline VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  cover_image_url VARCHAR(500) DEFAULT NULL,
  status ENUM('DRAFT','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  published_at DATETIME DEFAULT NULL,
  created_by BIGINT UNSIGNED DEFAULT NULL,
  updated_by BIGINT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mtl_shows_site_slug (site_key, slug),
  KEY idx_mtl_shows_public (site_key, status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mtl_show_guests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_key VARCHAR(64) NOT NULL,
  show_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  role_title VARCHAR(190) DEFAULT NULL,
  company VARCHAR(190) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  profile_url VARCHAR(500) DEFAULT NULL,
  photo_url VARCHAR(500) DEFAULT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_mtl_guests_show (site_key, show_id, is_published),
  CONSTRAINT fk_mtl_guests_show FOREIGN KEY (show_id) REFERENCES mtl_shows(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mtl_show_episodes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_key VARCHAR(64) NOT NULL,
  show_id BIGINT UNSIGNED NOT NULL,
  guest_id BIGINT UNSIGNED DEFAULT NULL,
  slug VARCHAR(160) NOT NULL,
  title VARCHAR(190) NOT NULL,
  summary TEXT DEFAULT NULL,
  episode_number INT UNSIGNED DEFAULT NULL,
  duration_seconds INT UNSIGNED DEFAULT NULL,
  media_url VARCHAR(500) DEFAULT NULL,
  thumbnail_url VARCHAR(500) DEFAULT NULL,
  transcript_text LONGTEXT DEFAULT NULL,
  transcript_status ENUM('NONE','DRAFT','REVIEWED','PUBLISHED') NOT NULL DEFAULT 'NONE',
  status ENUM('DRAFT','SCHEDULED','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  published_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mtl_episodes_site_slug (site_key, slug),
  KEY idx_mtl_episodes_show (site_key, show_id, status, published_at),
  CONSTRAINT fk_mtl_episodes_show FOREIGN KEY (show_id) REFERENCES mtl_shows(id) ON DELETE CASCADE,
  CONSTRAINT fk_mtl_episodes_guest FOREIGN KEY (guest_id) REFERENCES mtl_show_guests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mtl_show_recordings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_key VARCHAR(64) NOT NULL,
  show_id BIGINT UNSIGNED NOT NULL,
  episode_id BIGINT UNSIGNED DEFAULT NULL,
  title VARCHAR(190) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME DEFAULT NULL,
  timezone VARCHAR(64) NOT NULL DEFAULT 'America/New_York',
  location_name VARCHAR(190) DEFAULT NULL,
  location_address VARCHAR(255) DEFAULT NULL,
  access_level ENUM('PUBLIC','COMMUNITY','PRIVATE','PREMIUM','PARTNER') NOT NULL DEFAULT 'PUBLIC',
  capacity INT UNSIGNED DEFAULT NULL,
  status ENUM('PLANNED','CONFIRMED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PLANNED',
  notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_mtl_recordings_schedule (site_key, starts_at, status),
  CONSTRAINT fk_mtl_recordings_show FOREIGN KEY (show_id) REFERENCES mtl_shows(id) ON DELETE CASCADE,
  CONSTRAINT fk_mtl_recordings_episode FOREIGN KEY (episode_id) REFERENCES mtl_show_episodes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
