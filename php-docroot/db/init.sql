CREATE DATABASE IF NOT EXISTS master CHARACTER SET utf8mb4;
USE master;
CREATE TABLE users(
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    loginid TEXT NOT NULL,
    password TEXT NOT NULL
);
INSERT INTO users   (`id`, `loginid`,   `password`) VALUES (1, '4Dm1n1S7r4T0r', '$2y$10$WJPCVrUx4.ufWoJSH7QgK.tmeojp78SFqMP6nsxUXGi7vbPyMn4nq');
