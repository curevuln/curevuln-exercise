CREATE DATABASE shop;
\connect shop
DROP TABLE IF EXISTS item;
CREATE TABLE item (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    price INTEGER NOT NULL
);

INSERT INTO item (id, name, price) VALUES (1, '葡萄', 400);
INSERT INTO item (id, name, price) VALUES (2, 'リンゴ', 300);
INSERT INTO item (id, name, price) VALUES (3, '梨', 300);
INSERT INTO item (id, name, price) VALUES (4, 'スイカ', 600);
INSERT INTO item (id, name, price) VALUES (5, 'バナナ', 400);
INSERT INTO item (id, name, price) VALUES (6, '苺', 400);

