CREATE DATABASE IF NOT EXISTS shop CHARACTER SET utf8mb4;
USE shop;

CREATE TABLE contact(
    id INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    content TEXT NOT NULL
);
INSERT INTO contact (`id`, `title`, `content`) VALUES (1, 'こんにちは', 'やぁみんな');
INSERT INTO contact (`id`, `title`, `content`) VALUES (2, 'こんにちは', 'やぁみんな');
