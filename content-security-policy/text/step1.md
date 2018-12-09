# CSP - Content Security Policy

## 概要

X-Content-Security-Policyヘッダは、W3CのContent Security Policy（CSP）を実装するためのHTTPのレスポンスヘッダです。CSPの一例としては、Webアプリケーションの作者やサーバー管理者が、アプリケーションを実行するクライアント（ブラウザ）側で、リソースをどのソースから読み込んで欲しいかについてクライアントに伝えることがあります。

CSPに対応しているクライアントやブラウザであれば、JavaScriptの実行などについてこのヘッダに宣言されているポリシーに準拠して動作するため、XSS等の攻撃を抑制できることが期待できます。
