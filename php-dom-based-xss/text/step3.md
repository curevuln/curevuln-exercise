## 防御策

* `innerHTML`や`document.write`などの使用を避け、`textContent`プロパティを使う、あるいはHTMLのメタ文字を適切にHTMLエスケープするなどの方法を使い、適切にDOM操作やHTMLのメタ文字のエスケープを行います。
* `eval()`、`setTimeout()`、`setInterval()`、`Function`コンストラクタなどの引数に文字列形
式で外部からの値を渡さないようにします。
* URLのスキームをhttpあるいはhttpsのみに限定します。
* jQueryのセレクタを動的に生成しないようにします。
* XMLHttpRequestのURLを実行前に確認して安全なもの以外は動作させないようにします。
* jQuery等を使う場合は、脆弱性が改善された最新のライブラリを使います。
