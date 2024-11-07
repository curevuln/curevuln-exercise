CREATE DATABASE chat;
\connect chat;

DROP TABLE IF EXISTS contact;
CREATE TABLE contact(
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    content TEXT NOT NULL
);

DROP TABLE IF EXISTS users;
CREATE TABLE users(
    id SERIAL PRIMARY KEY,
    loginid TEXT NOT NULL,
    password TEXT NOT NULL
);

INSERT INTO contact (id, title, content) VALUES (1, '初めまして！', '初めまして！guestです！');

INSERT INTO users (id, loginid, password) VALUES (1, 'guest', '$2y$10$99r270WKG.xMomYqiKr/IuCfrUCoEw2rT1Eyni2b0sfsm3LF6EN16');
INSERT INTO users (id, loginid, password) VALUES (1855, 'admin', '$2y$10$catzjSl5wYyegRq3Mi6Y3eQJvdbAbI3M60SuKGyGjcobdnWsBnUb.');

