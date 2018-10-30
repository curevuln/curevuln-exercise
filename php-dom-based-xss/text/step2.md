## 攻撃方法

攻撃者は外部から設定できるURLのフラグメント演算子(`#`)以下の部分やGETメソッドのパラメータ
（`?`以下の部分）に攻撃用JavaScriptコードを含ませることにより、脆弱性のあるWebアプリケーションを使って攻撃用コードを実行させます。

DOM-Based XSSの脆弱性の原因としては以下があります。

* DOM操作の際外部から指定したHTMLタグなどが有効になる機能（関数やプロパティ）を用いている
* 外部から指定したJavaScriptコードが動く`eval`などの機能を用いている
* XMLHttpRequestのURLを検証していない
* `location.href`やsrc属性、href属性のURLを検証していない

HTMLタグなどが有効になる機能の例としては以下があります。

* `document.write()` / `document.writeln()`
* `innerHTML` / `outerHTML`
* jQueryの`html()`
* jQueryの`jQuery()`や`$()`

Eval機能の対象となる機能の例としては以下があります。

* `eval()`
* `setTimeout()` / `setInterval()`
* `Function`コンストラクタ

URLのスキームに`javascript`や`vbscript`を指定した場合JavaScriptが実行できる機能としては以下があります。

* JavaScriptの`location.href`
* HTMLのa要素のhref属性やiframe要素のsrc属性など
