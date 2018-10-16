CREATE DATABASE IF NOT EXISTS shop CHARACTER SET utf8mb4;
USE shop;

CREATE TABLE item(
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    price INTEGER NOT NULL
);


-- Item
INSERT INTO item (id, name, price) VALUES (1, "葡萄", 400);
INSERT INTO item (id, name, price) VALUES (2, "リンゴ", 300);
INSERT INTO item (id, name, price) VALUES (3, "梨", 300);
INSERT INTO item (id, name, price) VALUES (4, "スイカ", 600);
INSERT INTO item (id, name, price) VALUES (5, "バナナ", 400);
INSERT INTO item (id, name, price) VALUES (6, "苺", 400);
