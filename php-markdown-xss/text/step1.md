# MarkdownをHTMLレンダリングする際のXSS

## 概要

Markdown や RDoc, reStructuredText などのマークアップ言語にはHTMLの各種タグを利用でき、HTMLとしてレンダリングする際に任意の JavaScript コードを実行できるようになります。

例えば以下のようにリンクを `http:` と `https:` のみで制限していない場合はXSSが生じます。

```markdown
[link](javascript:alert(1))
```

↓

```html
<a href="javscript:alert(1)">link</a>
```

通常のXSSの対策を行えば良いですが、ライブラリによってデフォルトでエスケープしてくれるものと、そうでないものがあり、見落としがちです。
