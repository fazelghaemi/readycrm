-- Migration to v2 (run on existing database)

ALTER TABLE rooms MODIFY type ENUM('group','channel','dm') NOT NULL DEFAULT 'group';

CREATE TABLE IF NOT EXISTS dm_pairs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_a INT UNSIGNED NOT NULL,
  user_b INT UNSIGNED NOT NULL,
  room_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pair (user_a, user_b),
  FOREIGN KEY (user_a) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (user_b) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_profiles (
  user_id INT UNSIGNED PRIMARY KEY,
  display_name VARCHAR(64) NOT NULL DEFAULT '',
  bio VARCHAR(160) NOT NULL DEFAULT '',
  avatar_path VARCHAR(255) NOT NULL DEFAULT '',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO user_profiles (user_id, display_name, bio, avatar_path)
SELECT id, '', '', '' FROM users;
