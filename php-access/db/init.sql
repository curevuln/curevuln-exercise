CREATE DATABASE info;
\connect info;

DROP TABLE IF EXISTS users;
CREATE TABLE users(
    id INTEGER AUTO_INCREMENT PRIMARY KEY,
    loginid TEXT NOT NULL,
    name TEXT NOT NULL,
    addr    TEXT NOT NULL,
    tel     TEXT NOT NULL,
    sex     TEXT NOT NULL,
    password TEXT NOT NULL
);
INSERT INTO users   (`id`, `loginid`, `name`, `addr`, `tel`, `sex`, `password`) VALUES (1, 'guest','ほげ げすと','東京都渋谷区xxx ','012-888x-0x20','男','$2y$10$99r270WKG.xMomYqiKr/IuCfrUCoEw2rT1Eyni2b0sfsm3LF6EN16');
INSERT INTO users   (`id`, `loginid`, `name`,`addr`, `tel`, `sex`,  `password`) VALUES (2, 'admin','安曇巳 仁夫','東京都港区xxx','0e0-1a34-5c78','男','$2y$10$catzjSl5wYyegRq3Mi6Y3eQJvdbAbI3M60SuKGyGjcobdnWsBnUb.');
INSERT INTO users   (`id`, `loginid`, `name`, `addr`, `tel`, `sex`, `password`) VALUES (3, 'alice','alice','不思議の国','012-888x-0x20','女','$2y$10$lcFsxXp3In2jWRY5NSf5A.zsf8chwhT3YbP/3CsTk9ZSp834N9aY.');
INSERT INTO users   (`id`, `loginid`, `name`, `addr`, `tel`, `sex`, `password`) VALUES (4, 'bob','bob',' 東京都台東区 xxxx','0p2-0x0d-0x0a','男','$2y$10$Q56TtbNHEdF6s7vu/M.ndeRm.SevFyPj1dF0..eOny2nDhO5JOXdG');
