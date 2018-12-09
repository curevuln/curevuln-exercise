### 脆弱性の悪用


#### 脆弱なアプリケーションの起動

画面右下のターミナルで`docker-compose up`と入力し，アプリケーションを立ち上げましょう．  
その後，画面右上の疑似ブラウザの更新ボタンを押すと脆弱なアプリケーションが表示されます．  

#### 攻撃

このアプリケーションでは商品の検索機能にXSS脆弱性が存在します．  
検索フォームに次のようなJavaScriptを入力して検索することで，検索結果ページでそのJavaScriptコードが実行されてしまいます．

```js
"><script>alert(1)</script>
```

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

このアプリケーションでXSS脆弱性が存在するのは，template_search.phpで検索文字列を表示している箇所です．

```php
<p><?php echo $name; ?>の検索結果</p>
```

ここで，ユーザーが検索した文字列をエスケープ処理を施さずに出力しているため，以下のようなパラメータを受け取った時にXSS脆弱性が生じます．

```js
?name=<script>alert(1)</script>
```

XSS脆弱性を生じないためには，`htmlspecialchars`を使用して出力します．

```php
<p><?php echo htmlspecialchars($name, ENT_QUOTES, 'utf-8'); ?></p>
```
