## 脆弱性の実習

#### 脆弱なアプリケーションの起動

画面右下のターミナルで`docker-compose up`と入力し，アプリケーションを立ち上げましょう．  
その後，画面右上の疑似ブラウザの更新ボタンを押すと脆弱なアプリケーションが表示されます．  

#### 攻撃

このアプリケーションはお問い合わせフォームを表示する際にXSS脆弱性が存在します。

以下の方法で攻撃することができます。

1. ログイン画面にてLoginIDを `guest`、Passwordを`guest_password`としてログインします。  
2. 「お問い合わせ一覧」の画面が出ます。ここで「問い合わせ入力はこちら」をクリックします。  
3. 「お問い合わせフォーム」が出ます。タイトルと内容の入力を求められますが、ここでタイトルにJavaScriptコード `<script>alert(1)</script>` を入力します。内容は入力せずに、「送信」ボタンをクリックします。  
4. 入力に成功した画面に遷移するので、「確認はこちらから。」をクリックします。  
5. 入力したJavaScriptコードが実行されます。これはデータベースに保存されているものが出力されているため、攻撃コードは持続することになります。  


### 対策方法

XSS脆弱性における根本的対策はユーザによる入力値を出力する際に以下の5つの文字列をエスケープ処理(HTMLエンティティへの置換)を行うことです．  

| 特別な文字 | HTMLエンティティ(置換後) |
|:----------:|:-------------------------|
| `<`        | `&lt;`                   |
| `>`        | `&gt;`                   |
| `&`        | `&amp;`                  |
| `"`        | `&quot;`                 |
| `'`        | `&#39;`                  |

たとえば、

```javascript
<script>alert('XSS')</script>
```

という文字列を出力する場合には、エスケープ処理を施して

```html
&lt;script&gt;alert(&#39;XSS&#39;)&lt;/script&gt;
```

と出力します．

このアプリケーションではこの対策で十分ですが，リンクを出力する際などは，この対策だけでは不十分です．

### PHPにおけるXSS対策の基本

では，このアプリケーションに存在する脆弱性を修正しましょう．

PHPには先程の5つの文字をHTMLエンティティに変換する `htmlspecialchars` という関数があります．  
ユーザー入力値を出力する箇所に，この関数を使用することがPHPにおけるXSS脆弱性対策の基本です．  

```php
$s = '"><script>alert(1)</script>';
echo $s;
// "><script>alert(1)</script>
echo htmlspecialchars($s, ENT_QUOTES, 'utf-8');
// &lt;script&gt;alert(1)&lt;/script&gt;
```

[htmlspecialchars - php.net](http://php.net/manual/ja/function.htmlspecialchars.php)

では，右のエディタで脆弱性がある箇所を修正してみましょう．  

### 修正例

このアプリケーションでXSS脆弱性が存在するのは，template_auth_index.phpで検索文字列を表示している箇所です．

```php
    <?php
    foreach ($contacts as $key => $contact) {
        $html   = "";
        $html  .= "<div class='contacts'><div class='title'> <p> タイトル : ";
        $html  .= $contact['title'];
        $html  .= "</p></div><div class='content'> <p>";
        $html  .= $contact['content'];
        $html  .= "</p></div></div>";
        echo $html;
    }
```

ここで，ユーザーが検索した文字列をエスケープ処理を施さずに出力しているため，以下のようなパラメータを受け取った時にXSS脆弱性が生じます．

```js
// contentでも同様です
?title=<script>alert(1)</script>
```

XSS脆弱性を生じないためには，`htmlspecialchars`を使用して出力します．

```php
        $html  .= htmlspecialchars($contact['title'], ENT_QUOTES, 'utf-8');
```
