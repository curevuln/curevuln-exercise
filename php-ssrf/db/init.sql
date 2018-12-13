CREATE DATABASE IF NOT EXISTS sampledb CHARACTER SET utf8mb4;
USE sampledb;

CREATE TABLE article (
    id INT AUTO_INCREMENT NOT NULL,
    title TEXT NOT NULL,
    content TEXT,
    PRIMARY KEY (id)
);

CREATE TABLE webhook (
    id INTEGER AUTO_INCREMENT,
    url TEXT,
    PRIMARY KEY (id)
);

INSERT INTO article (`id`, `title`, `content`) VALUES (1, 'SSRFデビュー', 'こんにちはこんにちは');
INSERT INTO article (`id`, `title`, `content`) VALUES (2, '今日は僕の誕生日です！', 'ワイワイ');
INSERT INTO article (`id`, `title`, `content`) VALUES (3, 'みなさんはどう思いますか？', 'ウェイ');
INSERT INTO article (`id`, `title`, `content`) VALUES (4, 'あけましておめでとう！！', '');

INSERT INTO webhook (`id`, `url`) VALUES (1, 'https://example.com/');
