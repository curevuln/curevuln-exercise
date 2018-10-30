## 攻撃例

SSTI は erb や jinja2 などあらゆるテンプレートエンジンで生じます。  
ここでは PHP のテンプレートエンジンである Smarty を例に紹介します。  

SSTI はユーザー入力値を元に動的にテンプレートエンジンを介してHTMLを生成することによって生じます。

ここではユーザーに `smartyの機能を提供する` ブログのプレビュー機能を例に挙げます。

```php
$html = $_POST['html'];
...
$smarty->assign('html', $html);
```

```php
{eval var=$html}
```

ユーザー入力値を HTML として表示するために `eval` を利用しています。

ここで、Smarty が提供している関数 `fetch` を利用してみましょう。

```php
{fetch file="file:///etc/passwd"}
```

するとサーバーサイドの `/etc/passwd` を抜き出すことができます。  
