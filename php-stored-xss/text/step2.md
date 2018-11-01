## 攻撃方法

![持続型XSSの説明図](https://raw.githubusercontent.com/jj1bdx/curevuln-exercise/jj1bdx-php-stored-xss/php-stored-xss/images/stored-xss.jpg)

* a) 持続型XSSでは、攻撃者はまず攻撃用のJavaScriptコードをWebメールなどの形にして投稿し、攻撃対象サイトに直接蓄積されるようにします。
* b) 利用者は攻撃対象サイトの内容を閲覧するなどの行為を通じてアクセスすることで、攻撃用のJavaScriptコードを受け取ります。
* c) 被害者は攻撃対象サイトからの攻撃用JavaScriptコードをブラウザで実行してしまい、攻撃が成功します。
