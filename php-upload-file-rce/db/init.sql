CREATE DATABASE member WITH ENCODING 'UTF8';
\connect member;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    loginId TEXT NOT NULL,
    name TEXT NOT NULL,
    addr TEXT NOT NULL,
    icon TEXT NOT NULL,
    password TEXT NOT NULL
);

