# CureVuln Exercise

---

# ローカルでの実行

## コンテナのビルド

```sh
make build
```

で全体のコンテナがビルドできます

## 各問題のポート

| 問題名 | ポート | URL |
|:--------------------------------------|:-----|:---------------------|
| content-security-policy               | 8000 |http://localhost:8000 |
| nginx-http-header-injection           | 8001 |http://localhost:8001 |
| php-access                            | 8002 |http://localhost:8002 |
| php-csrf                              | 8003 |http://localhost:8003 |
| php-docroot                           | 8004 |http://localhost:8004 |
| php-dom-based-xss                     | 8005 |http://localhost:8005 |
| php-markdown-xss                      | 8006 |http://localhost:8006 |
| php-os-command-injection              | 8007 |http://localhost:8007 |
| php-reflected-xss                     | 8008 |http://localhost:8008 |
| php-reflected-xss-form                | 8009 |http://localhost:8009 |
| php-sessionFixation                   | 8010 |http://localhost:8010 |
| php-sql-injection                     | 8011 |http://localhost:8011 |
| php-ssrf                              | 8012 |http://localhost:8012 |
| php-stored-xss                        | 8013 |http://localhost:8013 |
| php-upload-file-rce                   | 8014 |http://localhost:8014 |
| rails-javascript-scheme-xss           | 8015 |http://localhost:8015 |
| rails-ssrf                            | 8016 |http://localhost:8016 |
| server-side-template-injection-smarty | 8017 |http://localhost:8017 |
| vuejs-template-injection-on-php       | 8018 |http://localhost:8018 |


---

# 構成

## 必須

`docker-compose.yml` がディレクトリトップに必須です。

## Example

以下はPHPアプリケーションを動作させる例です。設定ファイルは `index.yaml` という名前である必要があります。

```
vaersion: 1
title: PHPにおけるXSS対策の基本
description: PHPにおけるXSS対策の基本を学びます
difficulty: beginner
language: php
tag:
    - php
    - xss
text:
    - title: XSS概要
      file: text/step1.md
    - title: 悪用例
      file: text/step2.md
    - title: 根本的対策
      file: text/step3.md
    - title: PHPにおける対策
      file: text/step4.md
    - title: 修正
      file: text/step5.md
files:
    - www/index.php
    - www/template_index.php
    - www/search.php
    - www/template_search.php
```

この例では

- 演習で表示するテキスト(text)に5つのファイルを指定しています。
- 演習で使用するファイル(files)に4つのPHPファイルを指定しています。
  - ここに存在しないファイルは演習で扱うことができません。

## テンプレート

- [PHPとMySQLのテンプレート](./PHP_MYSQL_TEMPLATE)
- [PHPとPostgreSQLのテンプレート](./PHP_POSTGRES_TEMPLATE)
