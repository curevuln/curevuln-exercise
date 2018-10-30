CREATE DATABASE IF NOT EXISTS sampledb CHARACTER SET utf8mb4;
USE sampledb;

CREATE TABLE booklist(
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    title TEXT NOT NULL,
    author TEXT NOT NULL,
    price INTEGER NOT NULL
);

INSERT INTO booklist (`id`, `title`, `author`, `price`) VALUES (10000, '日本の四季', 'Atabasca', 1000);
INSERT INTO booklist (`id`, `title`, `author`, `price`) VALUES (10001, '世界を旅して', 'John Doe', 900);
INSERT INTO booklist (`id`, `title`, `author`, `price`) VALUES (10002, 'In Search of Excellence', 'Etaoin Shrdlu', 2000);
INSERT INTO booklist (`id`, `title`, `author`, `price`) VALUES (10003, 'Aim and Fire', 'Atabasca', 3000);
