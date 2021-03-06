## 対策

まずはユーザー入力値から動的にテンプレートエンジンを介してHTMLを生成しないことです。  
HTMLを生成するために `eval` や `ERB.new()` などを利用している場合は「何かがおかしい」と思うようにしましょう。

また、ユーザーにテンプレートエンジンの機能を提供する場合は非常に気をつける必要があります。  
例えば Smarty には様々な [セキュリティ機構](https://www.smarty.net/docs/ja/advanced.features.tpl#advanced.features.security) があり、これらを利用することで緩和策となります。

今回のように信頼されないディレクトリからファイルを読み出すことを禁止するためには以下のような設定を入れます。

```php
$smarty->enableSecurity();
```

ただし、Smartyの組込変数を利用することでこれらの機構をバイパスできることは広く知られています。
