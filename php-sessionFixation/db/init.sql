CREATE DATABASE IF NOT EXISTS shop CHARACTER SET utf8mb4;
USE shop;

CREATE TABLE product(
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    price TEXT NOT NULL
);
CREATE TABLE users(
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    loginid TEXT NOT NULL,
    password TEXT NOT NULL
);
CREATE TABLE review(
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    user_id TEXT NOT NULL,
    title TEXT NOT NULL,
    review TEXT NOT NULL
);

INSERT INTO product (`id`, `title`, `content`, `price`)  VALUES (1, 'たい焼き',    '初めまして！guestです！');
INSERT INTO users   (`id`, `loginid`,   `password`) VALUES (1, 'guest',         '$2y$10$99r270WKG.xMomYqiKr/IuCfrUCoEw2rT1Eyni2b0sfsm3LF6EN16');
INSERT INTO users   (`id`, `loginid`,   `password`) VALUES (1855, 'admin',         '$2y$10$catzjSl5wYyegRq3Mi6Y3eQJvdbAbI3M60SuKGyGjcobdnWsBnUb.');
