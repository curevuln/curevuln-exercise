## 修正例

今回は `cabe/markdown` によってデータベースに格納されたMarkdown文字列をHTMLに変換して表示しています。  

```php
<div class="description">
  <?php
    $converter = new \cebe\markdown\MarkdownExtra();
    $html = $converter->parse($article['content']);
    echo $html;
  ?>
</div>
```

Markdown から HTML に変換している、この段階で不正なタグやイベントハンドラ、スキーム等を排除します。

もし利用しているライブラリに安全にレンダリングするオプションがあれば、それを利用します。  
もし、そのようなオプションがない場合は DOMPurify や HTML Purifier などを利用して不正なタグ等を排除してからHTMLとして表示します。

`cabe/markdown` には安全にレンダリングするオプションがないため、 HTML Purifier を利用して安全なHTMLにする処理を挟みます。

```diff
diff --git a/templates/index.php b/templates/index.php
index 7c6e91c..94ef662 100644
--- a/templates/index.php
+++ b/templates/index.php
@@ -32,8 +32,9 @@
           <div class="description">
             <?php
               $converter = new \cebe\markdown\MarkdownExtra();
+              $purifier = new HTMLPurifier();
               $html = $converter->parse($article['content']);
-              echo $html;
+              echo $purifier->purify($html);
             ?>
           </div>
         </div>
```
