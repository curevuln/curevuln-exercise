## 演習

### アプリケーションの起動

以下のコマンドをターミナルで実行しましょう

```bash
docker-compose up
```

### アプリケーションの概要

このアプリケーションはブログ投稿サービスです。  
投稿時に指定したURLにブログタイトルを送信できる Webhook 機能があり、 `Settings` から設定できます。  
Webhook の結果は成功したか失敗したかをユーザーに伝えるためにレスポンスを表示するようにされています。

そして内部ネットワークに `customer` と呼ばれる顧客管理のためのロールが存在し、このアプリケーションは外部からは接続できないようになっています。

```yaml
customer:
    image: "nginx:alpine"
    volumes:
        - "./customer/www:/var/www"
        - "./customer/nginx/nginx.conf:/etc/nginx/nginx.conf:ro"
    depends_on:
        - "php-fpm"
```


### 攻撃

1. `http://customer/index.html` を Webhook に設定してみる
2. 記事を投稿してみる

すると、外部から接続できないはずの `customer` にアクセスでき、その内容を取得することができました。  
今回は簡単のために外部から接続できないアプリケーションが内部に存在する、という内容でしたが、実際は EC2 や GCE の metadata API などが狙わたり、ポート番号を指定することでポートスキャンを行うような攻撃が行われます。  


### 対策

1. 入力値がドメインであったり hosts に記載されている alias である場合もあるので、IPアドレスを引く
2. プライベートIPアドレス、もしくは利用しているクラウドメタサービスAPIのIPアドレスであれば拒否する

今回だと `post.php` において以下のような `is_private_ipaddr` を追加します。

```php
function is_private_ipaddr($ipddr) {
  return filter_var($ipddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE);
}

...

$ip = gethostbyname($result['url']);
if is_private_ipaddr($ip) {
  $response = webhook($result['url'], $_POST['title']);
} else {
i  // エラー処理
}
```
