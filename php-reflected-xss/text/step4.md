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
