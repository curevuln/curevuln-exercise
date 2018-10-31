# 攻撃方法

HTTPヘッダを組み立てる際、外部からのパラメータについて、特に改行文字の処理が不適切だと、不正なレスポンスヘッダの追加やレスポンスボディの偽造を行うことが可能になります。

一例として、PerlのCGIコードで

```perl
print "Location: $url\n\n"; # $url は外部から与えられるURL文字列
```

というコードを使ってHTTPのLocationヘッダを作っている場合を考えてみます。

このPerlコードで、外部からのURL文字列`$url`に、`http://example.jp/%0D%0ALocation: http://trap.example.com/`という文字列を与えると、以下のようなヘッダができてしまいます。

```http
Location: http://example.jp/
Location: http://trap.example.com/
```

このヘッダを読み込んだHTTPサーバ（Apacheなど）は、2番目のLocationヘッダを有効とするため、最初の本来のURLを無効とし、`Location: http://trap.example.com/`というヘッダのみが有効となり、外部からの攻撃による強制リダイレクトが可能になってしまいます。

同様の手法でSet-Cookieヘッダを使った不正なクッキーの設定や、改行(`%0D%0A`)を2回繰り返すことでレスポンスボディを強制的に不正に作り出すことも可能となります。
