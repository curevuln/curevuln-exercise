CREATE DATABASE IF NOT EXISTS shop CHARACTER SET utf8mb4;
USE shop;

CREATE TABLE product(
    id      INTEGER AUTO_INCREMENT PRIMARY KEY,
    title   TEXT NOT NULL,
    content TEXT NOT NULL,
    price   TEXT NOT NULL
);
CREATE TABLE shipping(
    id          INTEGER AUTO_INCREMENT PRIMARY KEY,
    user_id     INTEGER NOT NULL,
    product_id  INTEGER NOT NULL,
    num         INTEGER NOT NULL,
    price       TEXT NOT NULL,
    addr        TEXT NOT NULL
);
CREATE TABLE users(
    id      INTEGER AUTO_INCREMENT PRIMARY KEY,
    loginid TEXT NOT NULL,
    name    TEXT NOT NULL,
    addr    TEXT NOT NULL,
    tel     TEXT NOT NULL,
    sex     TEXT NOT NULL,
    password TEXT NOT NULL
);
CREATE TABLE review(
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    review TEXT NOT NULL
);

INSERT INTO product (`id`, `title`, `content`, `price`)  VALUES (1, 'たい焼き',  '甘くて美味しい鯛の形をしたお菓子',100);
INSERT INTO users   (`id`, `loginid`, `name`, `addr`, `tel`, `sex`, `password`) VALUES (1, 'guest','ほげ げすと','東京都渋谷区xxx ','012-888x-0x20','男','$2y$10$99r270WKG.xMomYqiKr/IuCfrUCoEw2rT1Eyni2b0sfsm3LF6EN16');
INSERT INTO users   (`id`, `loginid`, `name`,`addr`, `tel`, `sex`,  `password`) VALUES (2, 'admin','安曇巳 仁夫','東京都港区xxx','0e0-1a34-5c78','男','$2y$10$catzjSl5wYyegRq3Mi6Y3eQJvdbAbI3M60SuKGyGjcobdnWsBnUb.');
