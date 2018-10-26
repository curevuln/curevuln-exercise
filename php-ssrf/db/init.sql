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
INSERT INTO article (`id`, `title`, `content`) VALUES (2, '今日は僕の誕生日です！', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (3, 'みなさんはどう思いますか？', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (4, 'あけましておめでとう！！', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (5, 'SSRFの皆さん！お知恵拝借！', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (6, 'SSRFプレミアム入っちゃいました！！', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (7, '皆様にアンケートです', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (8, '最近めちゃくちゃ落ち込んでいます…', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (9, '手首切りました…', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (10, '家にある睡眠薬全部飲みました…', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (11, 'ありがとう…', '');
INSERT INTO article (`id`, `title`, `content`) VALUES (12, '我はメシア、明日この世界を粛清する。', '');

INSERT INTO webhook (`id`, `url`) VALUES (1, 'https://example.com/');
