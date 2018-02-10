## 攻撃

このアプリケーションでは既に `blockd_url?` によってループバックIPアドレスは弾くようにされています．  
本来であれば内部IPアドレスも弾くようにしなければいけませんが，ここでは割愛します．  
引数で与えられたURlを解析し，`Resolve.getaddress()` でIPアドレスを取得しています．

```ruby
blocked_ips = ["127.0.0.1", "::1", "0.0.0.0"]
blocked_ips.concat(Socket.ip_address_list.map(&:ip_address))

begin
    uri = Addressable::URI.parse(url)
    server_ips = Resolv.getaddresses(uri.hostname)
```

これは一見正しい実装に見えますが，脆弱です．

### Rare IP Address

RFCにはRere IP Address Formatsという特殊なフォーマットのIPアドレスについて記述があります．  
[https://tools.ietf.org/html/rfc3986#page-45](https://tools.ietf.org/html/rfc3986#page-45)

これは `2130706433` であったり `0x7f.1` のような形式もIPアドレスとみなされます．  

試しに `http://0x7f.1:3000/` と入力すると127.0.0.1:3000からのレスポンスが返ってきます．  
また， `http://①②⑦.0.0.①/` のような形や `http://127。0。0。1/` もIPアドレスとして認識されます．  
このようなRare IP Addressも考慮した実装をしなければいけません．  

