### 対策方法の解説
セッションに関しては複数の対策や設定の管理が必要になります。
#### セッション固定化攻撃対策
外部からのセッションID強制は完全に防ぐのは困難でしょう。そのため、外部からのセッションID強制をされても問題を起こさないような対策として、認証後にセッションIDを逐次変更する方法をとります。

PHPでは```php session_regenerate_id();```という関数があり引数がtrueであれば実行時にセッションの再生成を行います。

またJavaScriptからのCookie操作を防ぐためにphp.ini内部で```session.cookie_secure = On```や```session.cookie_httponly = On```といった設定を施す必要があります。
php.iniで設定する以外にも、setcookie関数の第6引数にsecure属性が第7引数にhttponly属性の設定ができるようになっています。

Point

- session_regenerate_id(true);を利用したセッションの更新
- session.cookie_secureをOnにする
- session.cookie_httponlyをOnにする
- setcookieの第６引数/第７引数をtrueにする

#### セッションハイジャック対策
セッションハイジャックの対策として、先ほども述べたサイトへのアクセスの全HTTPS化が挙げられます。
その理由として、セッションの盗聴や漏洩はHTTPS化に利用されている暗号スイート自体に脆弱性がない限り、外部から盗聴される事はないからです。

また、先ほどのセッション固定化攻撃対策で行なったことも有効であるので施すことを強く推奨する。

PHP manual : http://php.net/manual/ja/function.setcookie.php

[用語解説]

※ secure属性  :クライアントからの通信が、セキュアなHTTPS接続の場合にのみクッキーが送信されるようにします。

※ httponly属性:HTTP を通してのみクッキーにアクセスできるようになり、JavaScriptのようなスクリプト言語からのアクセスはできなくなります。
