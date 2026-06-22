USE sql12831179;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','member') NOT NULL DEFAULT 'member'
);

CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  author VARCHAR(150) NOT NULL,
  year SMALLINT NOT NULL,
  genre VARCHAR(80) NOT NULL DEFAULT 'Uncategorised',
  created_by INT NULL,
  CONSTRAINT fk_books_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actor_id INT NULL,
  action VARCHAR(50) NOT NULL,
  target VARCHAR(80) NULL,
  ip_address VARCHAR(45) NULL,
  detail VARCHAR(500) NULL,
  INDEX idx_action (action),
  INDEX idx_actor (actor_id)
) ENGINE=InnoDB;

INSERT INTO users (name, email, password_hash, role) VALUES 
('Admin User', 'admin@books.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Member User', 'member@books.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member');


INSERT INTO books (title, author, year, genre, created_by) VALUES 
('Clean Code', 'Robert C. Martin', 2008, 'Programming', 1),
('The Pragmatic Programmer', 'Andrew Hunt', 1999, 'Programming', 2);

ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;