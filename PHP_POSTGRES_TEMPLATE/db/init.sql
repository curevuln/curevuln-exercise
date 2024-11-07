CREATE DATABASE chat ENCODING 'UTF8';
\connect chat

CREATE TABLE blog(
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    content TEXT NOT NULL
);

CREATE TABLE "user"(
    id SERIAL PRIMARY KEY,
    username VARCHAR(256) NOT NULL UNIQUE,
    password VARCHAR(60) NOT NULL
);

INSERT INTO blog (id, title, content) VALUES (1, '初めまして！', 'こんにちはこんにちは');
INSERT INTO "user" (id, username, password) VALUES (1, 'guest', '$2y$10$99r270WKG.xMomYqiKr/IuCfrUCoEw2rT1Eyni2b0sfsm3LF6EN16');
INSERT INTO "user" (id, username, password) VALUES (1855, 'admin', '$2y$10$catzjSl5wYyegRq3Mi6Y3eQJvdbAbI3M60SuKGyGjcobdnWsBnUb.');

