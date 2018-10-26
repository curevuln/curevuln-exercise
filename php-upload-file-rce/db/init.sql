CREATE DATABASE IF NOT EXISTS member CHARACTER SET utf8mb4;
USE member;

CREATE TABLE users (
    id          INTEGER AUTO_INCREMENT PRIMARY KEY,
    loginId     TEXT NOT NULL,
    name        TEXT NOT NULL,
    addr        TEXT NOT NULL,
    icon        TEXT NOT NULL,
    password    TEXT NOT NULL
);
