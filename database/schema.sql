SET NAMES utf8mb4;
SET time_zone = '-05:00';

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role ENUM('client','provider','admin') NOT NULL DEFAULT 'client',
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(190) NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  phone_verified_at DATETIME NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_phone (phone),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(80) NOT NULL,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NOT NULL,
  icon VARCHAR(40) NOT NULL DEFAULT 'tools',
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_active_sort (active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS districts (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  zone VARCHAR(40) NOT NULL DEFAULT 'Lima Metropolitana',
  active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_districts_name (name),
  KEY idx_districts_active_name (active, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_profiles (
  user_id BIGINT UNSIGNED NOT NULL,
  headline VARCHAR(160) NULL,
  bio TEXT NULL,
  years_experience TINYINT UNSIGNED NOT NULL DEFAULT 0,
  availability_text VARCHAR(255) NULL,
  verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  verification_notes VARCHAR(500) NULL,
  verified_at DATETIME NULL,
  verified_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  KEY idx_provider_verification (verification_status),
  CONSTRAINT fk_provider_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_provider_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_categories (
  provider_id BIGINT UNSIGNED NOT NULL,
  category_id SMALLINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (provider_id, category_id),
  KEY idx_pc_category (category_id),
  CONSTRAINT fk_pc_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provider_districts (
  provider_id BIGINT UNSIGNED NOT NULL,
  district_id SMALLINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (provider_id, district_id),
  KEY idx_pd_district (district_id),
  CONSTRAINT fk_pd_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pd_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  category_id SMALLINT UNSIGNED NOT NULL,
  district_id SMALLINT UNSIGNED NOT NULL,
  target_provider_id BIGINT UNSIGNED NULL,
  title VARCHAR(140) NOT NULL,
  description TEXT NOT NULL,
  preferred_date DATE NULL,
  priority ENUM('flexible','soon','urgent') NOT NULL DEFAULT 'flexible',
  budget_min DECIMAL(10,2) NULL,
  budget_max DECIMAL(10,2) NULL,
  location_reference VARCHAR(255) NULL,
  status ENUM('open','matched','in_progress','completed','cancelled') NOT NULL DEFAULT 'open',
  selected_proposal_id BIGINT UNSIGNED NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_requests_client (client_id, created_at),
  KEY idx_requests_market (status, category_id, district_id, created_at),
  KEY idx_requests_target_provider (target_provider_id),
  CONSTRAINT fk_request_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_request_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  CONSTRAINT fk_request_district FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT,
  CONSTRAINT fk_request_target_provider FOREIGN KEY (target_provider_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proposals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id BIGINT UNSIGNED NOT NULL,
  provider_id BIGINT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  available_date DATE NULL,
  status ENUM('pending','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_proposal_request_provider (request_id, provider_id),
  KEY idx_proposal_provider_status (provider_id, status, created_at),
  CONSTRAINT fk_proposal_request FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_proposal_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  provider_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(1000) NULL,
  status ENUM('pending','published','hidden') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_review_request (request_id),
  KEY idx_reviews_provider_status (provider_id, status, created_at),
  CONSTRAINT fk_review_request FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_review_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_review_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key),
  CONSTRAINT fk_setting_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  action_key VARCHAR(80) NOT NULL,
  identifier_hash CHAR(64) NOT NULL,
  attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  window_started_at DATETIME NOT NULL,
  PRIMARY KEY (action_key, identifier_hash),
  KEY idx_rate_window (window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  metadata JSON NULL,
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user_date (user_id, created_at),
  KEY idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (slug, name, description, icon, sort_order) VALUES
('limpieza-hogar', 'Limpieza del hogar', 'Visitas puntuales, recurrentes y tareas específicas.', 'sparkles', 10),
('gasfiteria', 'Gasfitería', 'Fugas, griferías, sanitarios e instalaciones.', 'droplet', 20),
('electricidad', 'Electricidad', 'Tomacorrientes, luminarias y revisiones técnicas.', 'bolt', 30),
('pintura-acabados', 'Pintura y acabados', 'Ambientes, retoques, resanes y acabados.', 'paint', 40),
('armado-reparaciones', 'Armado y reparaciones', 'Muebles, instalaciones y pendientes generales.', 'tools', 50),
('cuidado-personas', 'Cuidado de personas', 'Acompañamiento y apoyo según cada necesidad.', 'care', 60),
('cuidado-mascotas', 'Cuidado de mascotas', 'Paseos, visitas y apoyo durante tus ausencias.', 'paw', 70),
('mudanzas-transporte', 'Mudanzas y transporte', 'Traslados pequeños, carga y apoyo logístico.', 'truck', 80),
('clases-soporte', 'Clases y soporte digital', 'Aprendizaje, configuración y ayuda tecnológica.', 'book', 90),
('otros-servicios', 'Otro servicio', 'Describe tu necesidad para encontrar la categoría adecuada.', 'plus', 100);

INSERT IGNORE INTO districts (name, zone, sort_order) VALUES
('Ancón', 'Lima Norte', 10),
('Ate', 'Lima Este', 20),
('Barranco', 'Lima Centro', 30),
('Breña', 'Lima Centro', 40),
('Carabayllo', 'Lima Norte', 50),
('Chaclacayo', 'Lima Este', 60),
('Chorrillos', 'Lima Sur', 70),
('Cieneguilla', 'Lima Este', 80),
('Comas', 'Lima Norte', 90),
('El Agustino', 'Lima Este', 100),
('Independencia', 'Lima Norte', 110),
('Jesús María', 'Lima Centro', 120),
('La Molina', 'Lima Este', 130),
('La Victoria', 'Lima Centro', 140),
('Lima Cercado', 'Lima Centro', 150),
('Lince', 'Lima Centro', 160),
('Los Olivos', 'Lima Norte', 170),
('Lurigancho-Chosica', 'Lima Este', 180),
('Lurín', 'Lima Sur', 190),
('Magdalena del Mar', 'Lima Centro', 200),
('Miraflores', 'Lima Centro', 210),
('Pachacámac', 'Lima Sur', 220),
('Pucusana', 'Lima Sur', 230),
('Pueblo Libre', 'Lima Centro', 240),
('Puente Piedra', 'Lima Norte', 250),
('Punta Hermosa', 'Lima Sur', 260),
('Punta Negra', 'Lima Sur', 270),
('Rímac', 'Lima Centro', 280),
('San Bartolo', 'Lima Sur', 290),
('San Borja', 'Lima Centro', 300),
('San Isidro', 'Lima Centro', 310),
('San Juan de Lurigancho', 'Lima Este', 320),
('San Juan de Miraflores', 'Lima Sur', 330),
('San Luis', 'Lima Centro', 340),
('San Martín de Porres', 'Lima Norte', 350),
('San Miguel', 'Lima Centro', 360),
('Santa Anita', 'Lima Este', 370),
('Santa María del Mar', 'Lima Sur', 380),
('Santa Rosa', 'Lima Norte', 390),
('Santiago de Surco', 'Lima Centro', 400),
('Surquillo', 'Lima Centro', 410),
('Villa El Salvador', 'Lima Sur', 420),
('Villa María del Triunfo', 'Lima Sur', 430),
('Callao', 'Callao', 440),
('Bellavista', 'Callao', 450),
('Carmen de la Legua-Reynoso', 'Callao', 460),
('La Perla', 'Callao', 470),
('La Punta', 'Callao', 480),
('Mi Perú', 'Callao', 490),
('Ventanilla', 'Callao', 500);

INSERT IGNORE INTO settings (setting_key, setting_value, is_public) VALUES
('site_name', 'Listo', 1),
('support_whatsapp', '', 1),
('support_email', '', 1),
('marketplace_city', 'Lima Metropolitana y Callao', 1),
('reviews_auto_publish', '0', 0);
