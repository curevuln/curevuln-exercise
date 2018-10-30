## 演習

### アプリケーションの起動

以下のコマンドをターミナルで実行してみましょう。

```bash
docker-compose up
```

### アプリケーションの概要

このアプリケーションはHTMLのページ1つでできている簡単なものです。
フラグメント演算子(#)の後の内容をJavaScriptコードで取得し、表示します。
ここではinnerHTMLを使っているため、フラグメント演算子の後の内容をJavaScriptコードで実行することができてしまいます。

### 攻撃

1. `http://127.0.0.1/innerHTML.html#<img src=/ onerror=alert("wow")>` をブラウザのURLに指定し、アクセスする。
2. 実行された結果alertダイアログが表示される。

### 攻撃の対策

HTMLページ中の`innerHTML`プロパティを`textContent`プロパティに置き換えることで、DOM-Based XSSの対策を行うことができます。

