## 対策

とはいえ，様々な形式のIPアドレスを全てブラックリストで登録するのは効率的ではありません．  
多くの場合，言語の標準ライブラリはRare IP Addressのパースに対応しています．  

しかし，Ruby.2.4の `Resolv.getaddresses()` では一部のIPアドレスに対してバイパスが可能になります．  
(*) Ruby2.5では `Resolv::ResolvError` と例外が返されます.

対策として`Resolv.getaddresses()`の代わりにOSのリゾルバ(`Addrinfo.getaddrinfo`など)を使うと正しく処理されます．  

```ruby
server_ips = Addrinfo.getaddrinfo(uri.hostname, 80, nil, :STREAM).map(&:ip_address)
```

#### 参考

- [CVE - CVE-2017-0904](http://www.cve.mitre.org/cgi-bin/cvename.cgi?name=2017-0904)
- [Server Side Request Forgery - OWASP](https://www.owasp.org/index.php/Server_Side_Request_Forgery)
