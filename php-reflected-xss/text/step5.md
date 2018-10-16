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

#### 参考

- [安全なウェブサイトの作り方 - IPA](https://www.ipa.go.jp/files/000017316.pdf)
- [Cross-site Scripting(XSS) - OWASP](https://www.owasp.org/index.php/Cross-site_Scripting_(XSS))
