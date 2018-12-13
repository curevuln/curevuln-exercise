CREATE DATABASE IF NOT EXISTS blog CHARACTER SET utf8mb4;
USE blog;

CREATE TABLE article (
    id INT AUTO_INCREMENT NOT NULL,
    title TEXT NOT NULL,
    content TEXT,
    PRIMARY KEY (id)
);

INSERT INTO article (`id`, `title`, `content`) VALUES (1, 'ウェイ', '<h1>こんにちはこんにちは</h1>');
INSERT INTO article (`id`, `title`, `content`) VALUES (2, '今日は僕の誕生日です！', '<a href="#">link</a>');
INSERT INTO article (`id`, `title`, `content`) VALUES (3, 'みなさんはどう思いますか？', 'ワイワイ');
INSERT INTO article (`id`, `title`, `content`) VALUES (4, 'あけましておめでとう！！', 'ウェイ;');
