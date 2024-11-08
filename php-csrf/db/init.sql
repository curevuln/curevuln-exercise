CREATE DATABASE shop;
\connect shop;

DROP TABLE IF EXISTS product;
CREATE TABLE product(
    id      SERIAL PRIMARY KEY,
    title   TEXT NOT NULL,
    content TEXT NOT NULL,
    details TEXT NOT NULL,
    price   TEXT NOT NULL,
    image   TEXT NOT NULL
);
DROP TABLE IF EXISTS shipping;
CREATE TABLE shipping(
    id          SERIAL PRIMARY KEY,
    user_id     INTEGER NOT NULL,
    product_id  INTEGER NOT NULL,
    name        TEXT NOT NULL,
    num         INTEGER NOT NULL,
    price       INTEGER NOT NULL,
    addr        TEXT NOT NULL
);
DROP TABLE IF EXISTS users;
CREATE TABLE users(
    id      SERIAL PRIMARY KEY,
    loginid TEXT NOT NULL,
    name    TEXT NOT NULL,
    addr    TEXT NOT NULL,
    tel     TEXT NOT NULL,
    sex     TEXT NOT NULL,
    password TEXT NOT NULL
);
DROP TABLE IF EXISTS review;
CREATE TABLE review(
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    review TEXT NOT NULL
);

INSERT INTO product (id, title, content, details, price, image)  VALUES (1, 'たい焼き',        '甘くて美味しい鯛の形をしたお菓子',      'お店一押しの商品！美味しさは保証します！<br>発送は冷凍にて送らせていただきますので、お家の電子レンジで温めてからお召し上がりください。<br>店頭販売ではより美味しく召し上がっていただくために、作り置きをせずにその場で焼かせていただいています。',100,'taiyaki');
INSERT INTO product (id, title, content, details, price, image)  VALUES (2, 'どら焼き',        '甘くて美味しい銅鑼の形をしたお菓子',     '北海道産の小豆と小麦、沖縄産の黒糖をふんだんに使ったどら焼きです。たい焼きと並ぶ、2大人気としてお店の自信作です。',80,'dorayaki');
INSERT INTO product (id, title, content, details, price, image)  VALUES (3, '水羊羹',          'みずみずしくすっきりとした甘味',      '',200,'youkan');
INSERT INTO product (id, title, content, details, price, image)  VALUES (4, '和菓子詰め合わせ',  '贈り物にいかがでしょうか？',       '',1000,'wagasi');
INSERT INTO product (id, title, content, details, price, image)  VALUES (5, '洋菓子詰め合わせ',  'クッキーやチョコなどの詰め合わせ',    '',1000,'cookie');
INSERT INTO users   (id, loginid, name, addr, tel, sex, password) VALUES (1, 'guest','ほげ げすと','東京都渋谷区xxx ','012-888x-0x20','男','$2y$10$39WvEh2RjPCqYLQ8o4LB4.8tM92.GTxQaINZZglQvBXPBpH93vi3C');
INSERT INTO users   (id, loginid, name,addr, tel, sex,  password) VALUES (2, 'admin','安曇巳 仁夫','東京都港区xxx','0e0-1a34-5c78','男','$2y$10$catzjSl5wYyegRq3Mi6Y3eQJvdbAbI3M60SuKGyGjcobdnWsBnUb.');
