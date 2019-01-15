### 修正例
この脆弱性で修正する箇所は多くあります。
4つの例をあげて進めていきます。
#### setting.php
生成と比較を行う関数を追加する。
```php
//~~~前略~~~//
/*
*生成用の関数
*/
function csrftoken () {

    return bin2hex(openssl_random_pseudo_bytes(128));
}
/*
* 比較用関数
*/
function csrftokenCheck () {
    if ( hash_equals($_SESSION['csrfToken'], $_POST['csrftoken'] ) ) {
        return (bool)true;
    }
    return (bool)false;
}
```
#### comment.php
DB操作や重要な処理の前に比較をする。
```php
if ( !csrftokenCheck() ) {
    header('Location: /');
    exit();
}
```

#### shopping.php
Formのあるページを読み込む前にTokenを設定する。
```php
$_SSSION['csrfToken']   =  csrftoken();
```

#### template_shopping.php
Formの中に追加をする。
```html
<input type="hidden" name="csrfToken" value="<?php echo $_SESSION['csrfToken'] ?>">
```
