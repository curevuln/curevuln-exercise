## 攻撃方法

利用できるマークアップ言語からレンダリングするHTMLを推測し、JavaScript コードを挿入します。

### Markdown の例

```html
[link](javascript:alert(1))
# => <a href="javascript:alert(1)">link</a>

![image" onClick=alert(1)](x)
# => <img src="x" alt="image" onClick=alert(1)">
```
