### 実際の対策方法
#### session_regenerate_idによる固定化対策
session_regenerate_idは現在使用されているセッションを新しいものに置き換えを行う関数です。
セッション固定化攻撃では、セッションが古いものから更新されずに認証情報などが付与されてしまう事によって、攻撃が成功してしまいます。そのため、認証情報を付与される前に、この関数を利用することで攻撃者の設定したセッションから新しいセッションに更新することができます。

例
```php
//~~~前略~~~
session_regenerate_id(true);
/*~~情報の付与~~
例 :
$_SESSION['id'] = $user_info['id'];
$_SESSION['name'] = $user_info['name']
*/
//~~後略~~
```

[session_regenerate_id php.net](http://php.net/manual/ja/function.session-regenerate-id.php)

#### HTTP OnlyとSecure
今回のセッション固定化攻撃ではXSSを利用したセッション書き換えが行われました。そのような攻撃を防ぐためにcookieにはHttpOnly属性というものがあります。
この属性は、サーバー以外からのcookieアクセスを制限するもので、今回のようなセッションの書き換えなどのJavaScriptからの直接参照/操作を防ぎます。
また、cookieの送信をHTTPSのみに限りたい場合にはSecure属性を付与する事でそれが可能になります。
HTTPSの時以外にcookieを付与しない事により、http通信の際に第三者による通信の閲覧を困難なものにでき、またhttp通信によるクッキーの上書き攻撃であるクッキーインジェクションにも対応できます。

例
php.ini
```php.ini
session.cookie_httponly = 1
session.cookie_secure = 1
```
php(非推奨)
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
session_start();
```
