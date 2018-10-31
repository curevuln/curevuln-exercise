CREATE DATABASE IF NOT EXISTS sampledb CHARACTER SET utf8mb4;
USE sampledb;

CREATE TABLE booklist(
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    title TEXT NOT NULL,
    author TEXT NOT NULL,
    price INTEGER NOT NULL
);

CREATE TABLE users(
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(256) NOT NULL UNIQUE,
    password VARCHAR(60) NOT NULL
);

INSERT INTO booklist (`id`, `title`, `author`, `price`) VALUES (10000, '日本の四季', 'Atabasca', 1000);
INSERT INTO booklist (`id`, `title`, `author`, `price`) VALUES (10001, '世界を旅して', 'John Doe', 900);
INSERT INTO booklist (`id`, `title`, `author`, `price`) VALUES (10002, 'In Search of Excellence', 'Etaoin Shrdlu', 2000);
INSERT INTO booklist (`id`, `title`, `author`, `price`) VALUES (10003, 'Aim and Fire', 'Atabasca', 3000);

INSERT INTO users (`id`, `username`, `password`) VALUES (1, 'guest', '$2y$10$99r270WKG.xMomYqiKr/IuCfrUCoEw2rT1Eyni2b0sfsm3LF6EN16');
INSERT INTO users (`id`, `username`, `password`) VALUES (1855, 'admin', '$2y$10$catzjSl5wYyegRq3Mi6Y3eQJvdbAbI3M60SuKGyGjcobdnWsBnUb.');
