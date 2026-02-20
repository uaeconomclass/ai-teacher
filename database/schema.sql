CREATE DATABASE IF NOT EXISTS ai_teacher
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ai_teacher;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  display_name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS levels (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code ENUM('A1','A2','B1','B2','C1','C2') NOT NULL UNIQUE,
  title VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS topics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  level_id BIGINT UNSIGNED NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(160) NOT NULL,
  position INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_topics_level FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grammar_topics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  level_id BIGINT UNSIGNED NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(180) NOT NULL,
  position INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_grammar_level FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lessons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  speaking_goal VARCHAR(255) NOT NULL,
  grammar_focus VARCHAR(255) NULL,
  position INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lessons_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dialogues (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  lesson_id BIGINT UNSIGNED NULL,
  level_code ENUM('A1','A2','B1','B2','C1','C2') NOT NULL,
  topic_slug VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dialogues_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_dialogues_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dialogue_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dialogue_id BIGINT UNSIGNED NOT NULL,
  sender ENUM('user','assistant') NOT NULL,
  text TEXT NOT NULL,
  audio_url VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_dialogue FOREIGN KEY (dialogue_id) REFERENCES dialogues(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_progress (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  topic_id BIGINT UNSIGNED NOT NULL,
  completed_lessons INT UNSIGNED NOT NULL DEFAULT 0,
  total_lessons INT UNSIGNED NOT NULL DEFAULT 0,
  accuracy_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  fluency_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_topic (user_id, topic_id),
  CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_progress_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO levels (id, code, title) VALUES
  (1, 'A1', 'Beginner'),
  (2, 'A2', 'Elementary'),
  (3, 'B1', 'Intermediate'),
  (4, 'B2', 'Upper Intermediate'),
  (5, 'C1', 'Advanced'),
  (6, 'C2', 'Proficient');

INSERT IGNORE INTO grammar_topics (level_id, slug, title, position) VALUES
  ((SELECT id FROM levels WHERE code='A1'), 'a1-to-be', 'To be', 1),
  ((SELECT id FROM levels WHERE code='A1'), 'a1-present-simple', 'Present Simple', 2),
  ((SELECT id FROM levels WHERE code='A1'), 'a1-articles-a-an-the', 'Articles a/an/the', 3),
  ((SELECT id FROM levels WHERE code='A1'), 'a1-there-is-there-are', 'There is/There are', 4),
  ((SELECT id FROM levels WHERE code='A1'), 'a1-can-can-not', 'Can/Can not', 5),
  ((SELECT id FROM levels WHERE code='A2'), 'a2-past-simple', 'Past Simple', 1),
  ((SELECT id FROM levels WHERE code='A2'), 'a2-going-to-and-will', 'Going to and Will', 2),
  ((SELECT id FROM levels WHERE code='A2'), 'a2-present-continuous', 'Present Continuous', 3),
  ((SELECT id FROM levels WHERE code='A2'), 'a2-comparatives', 'Comparatives', 4),
  ((SELECT id FROM levels WHERE code='A2'), 'a2-countable-and-uncountable', 'Countable and Uncountable', 5),
  ((SELECT id FROM levels WHERE code='B1'), 'b1-present-perfect', 'Present Perfect', 1),
  ((SELECT id FROM levels WHERE code='B1'), 'b1-past-continuous', 'Past Continuous', 2),
  ((SELECT id FROM levels WHERE code='B1'), 'b1-first-conditional', 'First Conditional', 3),
  ((SELECT id FROM levels WHERE code='B1'), 'b1-second-conditional', 'Second Conditional', 4),
  ((SELECT id FROM levels WHERE code='B1'), 'b1-modal-verbs', 'Modal Verbs', 5),
  ((SELECT id FROM levels WHERE code='B2'), 'b2-past-perfect', 'Past Perfect', 1),
  ((SELECT id FROM levels WHERE code='B2'), 'b2-passive-voice', 'Passive Voice', 2),
  ((SELECT id FROM levels WHERE code='B2'), 'b2-reported-speech', 'Reported Speech', 3),
  ((SELECT id FROM levels WHERE code='B2'), 'b2-mixed-conditionals', 'Mixed Conditionals', 4),
  ((SELECT id FROM levels WHERE code='B2'), 'b2-advanced-modals', 'Advanced Modals', 5),
  ((SELECT id FROM levels WHERE code='C1'), 'c1-advanced-clauses', 'Advanced Clauses', 1),
  ((SELECT id FROM levels WHERE code='C1'), 'c1-inversion', 'Inversion', 2),
  ((SELECT id FROM levels WHERE code='C1'), 'c1-hedging-language', 'Hedging Language', 3),
  ((SELECT id FROM levels WHERE code='C1'), 'c1-discourse-markers', 'Discourse Markers', 4),
  ((SELECT id FROM levels WHERE code='C1'), 'c1-cleft-sentences', 'Cleft Sentences', 5),
  ((SELECT id FROM levels WHERE code='C2'), 'c2-rhetorical-structures', 'Rhetorical Structures', 1),
  ((SELECT id FROM levels WHERE code='C2'), 'c2-nuanced-modality', 'Nuanced Modality', 2),
  ((SELECT id FROM levels WHERE code='C2'), 'c2-register-shifting', 'Register Shifting', 3),
  ((SELECT id FROM levels WHERE code='C2'), 'c2-complex-conditionals', 'Complex Conditionals', 4),
  ((SELECT id FROM levels WHERE code='C2'), 'c2-advanced-cohesion', 'Advanced Cohesion', 5);
