-- Tech Lab Miami member identity, entitlements, workspace and engagement foundation.
-- Shared database contract for techlabmiami.com and Ophyra. Safe to rerun.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS ecosystem_memberships (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  tenant_key VARCHAR(64) NOT NULL,
  role_level TINYINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NULL,
  status ENUM('ACTIVE','SUSPENDED','LEFT') NOT NULL DEFAULT 'ACTIVE',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_selected_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ecosystem_membership_user_tenant (user_id,tenant_key),
  KEY idx_ecosystem_membership_tenant_status (tenant_key,status),
  KEY idx_ecosystem_membership_account (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ecosystem_workspaces (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  membership_id BIGINT UNSIGNED NOT NULL,
  owner_user_id INT NOT NULL,
  tenant_key VARCHAR(64) NOT NULL,
  status ENUM('ACTIVE','READ_ONLY','SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
  provisioned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ecosystem_workspace_membership (membership_id),
  KEY idx_ecosystem_workspace_owner_tenant (owner_user_id,tenant_key,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ecosystem_entitlements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  membership_id BIGINT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  product_key VARCHAR(64) NOT NULL,
  source_tenant VARCHAR(64) NOT NULL,
  granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  activated_at DATETIME NULL,
  expires_at DATETIME NULL,
  status ENUM('GRANTED','ACTIVE','EXPIRED','REVOKED') NOT NULL DEFAULT 'GRANTED',
  notice_90_at DATETIME NULL,
  notice_30_at DATETIME NULL,
  notice_7_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ecosystem_entitlement_membership_product (membership_id,product_key),
  KEY idx_ecosystem_entitlement_expiry (status,expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ecosystem_software_registry (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_key VARCHAR(64) NOT NULL,
  product_key VARCHAR(64) NOT NULL,
  name VARCHAR(140) NOT NULL,
  short_description VARCHAR(255) NOT NULL,
  icon VARCHAR(255) NULL,
  access_url VARCHAR(255) NULL,
  required_level TINYINT UNSIGNED NOT NULL DEFAULT 2,
  product_status ENUM('AVAILABLE','BETA','COMING_SOON') NOT NULL DEFAULT 'AVAILABLE',
  sort_order INT NOT NULL DEFAULT 100,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ecosystem_software_tenant_product (tenant_key,product_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ecosystem_sso_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token_hash CHAR(64) NOT NULL,
  membership_id BIGINT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  audience VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  consumed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ecosystem_sso_token_hash (token_hash),
  KEY idx_ecosystem_sso_expiry (audience,expires_at,consumed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tech_lab_member_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  membership_id BIGINT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  request_type ENUM('GUEST','SPONSOR','CONSULTANT') NOT NULL,
  current_step SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  payload_json JSON NULL,
  status ENUM('DRAFT','SUBMITTED','IN_REVIEW','CONTACTED','CLOSED') NOT NULL DEFAULT 'DRAFT',
  assigned_to INT NULL,
  submitted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tech_lab_request_member (membership_id,request_type,status),
  KEY idx_tech_lab_request_queue (status,request_type,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tech_lab_event_rsvps (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  membership_id BIGINT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  venue_event_id INT NOT NULL,
  status ENUM('GOING','WAITLISTED','CANCELLED') NOT NULL DEFAULT 'GOING',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tech_lab_rsvp_member_event (membership_id,venue_event_id),
  KEY idx_tech_lab_rsvp_event_status (venue_event_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tech_lab_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  venue_event_id INT NOT NULL,
  tenant_key VARCHAR(64) NOT NULL DEFAULT 'miamitechlab',
  capacity INT UNSIGNED NULL,
  status ENUM('DRAFT','PUBLISHED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tech_lab_event_tenant (tenant_key,venue_event_id),
  KEY idx_tech_lab_event_status (tenant_key,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tech_lab_saved_tool_results (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  membership_id BIGINT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  tool_key VARCHAR(64) NOT NULL,
  label VARCHAR(140) NULL,
  input_json JSON NOT NULL,
  result_json JSON NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tech_lab_tool_member (membership_id,tool_key,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tech_lab_featured_content (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cms_content_id INT NOT NULL,
  tenant_key VARCHAR(64) NOT NULL DEFAULT 'miamitechlab',
  sort_order INT NOT NULL DEFAULT 100,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tech_lab_featured_content (tenant_key,cms_content_id),
  KEY idx_tech_lab_featured_active (tenant_key,is_active,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ecosystem_software_registry
  (tenant_key,product_key,name,short_description,icon,access_url,required_level,product_status,sort_order,is_active)
VALUES
  ('miamitechlab','ophyra','Ophyra','Run clients, service work and operations from one focused workspace.','fa-solid fa-diagram-project','/dashboard/ophyra/activate',2,'AVAILABLE',10,1)
ON DUPLICATE KEY UPDATE name=VALUES(name),short_description=VALUES(short_description),icon=VALUES(icon),access_url=VALUES(access_url),required_level=VALUES(required_level),product_status=VALUES(product_status),sort_order=VALUES(sort_order),is_active=1;

SELECT 'memberships' entity,COUNT(*) total FROM ecosystem_memberships WHERE tenant_key='miamitechlab'
UNION ALL SELECT 'software',COUNT(*) FROM ecosystem_software_registry WHERE tenant_key='miamitechlab' AND is_active=1
UNION ALL SELECT 'workspaces',COUNT(*) FROM ecosystem_workspaces WHERE tenant_key='miamitechlab';
