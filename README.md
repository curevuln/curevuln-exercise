# CureVuln Exercise

---

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
