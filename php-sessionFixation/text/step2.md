### 脆弱性の悪用
#### 脆弱なアプリケーションの起動

画面右下のターミナルで`docker-compose up`と入力し，アプリケーションを立ち上げましょう．  
その後，画面右上の疑似ブラウザの更新ボタンを押すと脆弱なアプリケーションが表示されます。

#### 攻撃
XSSの存在するページではこの二つの攻撃が容易に行えます。
この攻撃の再現では、ECサイトをもした、XSSの存在するログインを利用し体験してもらいます。

**セッション固定化攻撃**
1. ユーザー```guest```でログインを行なってください。
```
LoginID : guest
Pssword : guest_password
```

2. たい焼きのレビュー画面（詳細はこちらをクリック）へ移動し、レビューに下のscriptを書き込みます。

```html
<script>document.cookie='PHPSESSID=abcd;'</script>
```

3. 一度ログアウトし、再度、たい焼きのレビュー画面へ遷移します

この時点で手順2のスクリプトが動作し、未ログインの状態のユーザーに `PHPSESSID = abcd` の Cookie が付与されます。

4. ユーザー `admin` でLoginを行います。

```
LoginID : admin
Pssword : Adm1n_paSsw0Rd
```

このとき、新規にセッションの張替えを行っていないため、 `admin` の `PHPSESSID` は `abcd` のままです。

5. 別のブラウザ、あるいはシークレットモードで疑似ブラウザに表示されているURLを開き、Cookieに `PHPSESSID=abcd` をセットしてリロードします。

#### Ex) cookieの変更方法

- developerToolsを利用した方法

Windows:Mac共通 : F12 ボタン
Mac            : commando + option + i

##### 手順

1. developerToolsを開く → Application → Cookies → 対象のURL → PHPSESSIDを先ほど設定したものに変更

![developerToolsを利用した方法](https://raw.githubusercontent.com/curevuln/curevuln-exercise/master/php-sessionFixation/text/images/image.gif)

これでAdminでLoginできたら攻撃の成功です。
