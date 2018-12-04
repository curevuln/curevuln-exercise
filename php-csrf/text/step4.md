### 実際の対策方法
#### CSRF Tokenの実装
対策方法として有力なものとしてCSRFTokenというものがあります。
この対策では攻撃者により、容易に推測ができないTokenを利用し発信者と発信元が一緒であることを確認することができます。
Tokenを生成する際にtimeやloginidなどを利用した生成だと容易に推測できるわけではないが、ランダムに生成されたものにくらべ推測されやすいという欠点があります。
```php
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

```php
/*
* 生成後のToken埋め込み(php)
*/
$_SSSION['csrfToken']   =  csrftoken();
```

```html
<!--
* 生成後のToken埋め込み(html)
-->
<input type="hidden" name="csrfToken" value="<?php echo $_SESSION['csrfToken'] ?>">
```
