<!DOCTYPE html>
<html>
<head>
  <title>VueJS serverside template xss</title>
</head>
<body>
  <ul>
    <li><a href="index.php">脆弱なバージョン</a></li>
  </ul>

  <form action="">
    <label>
      <strong>キーワードを入力</strong>
      <input
        type="text"
        name="search"
        value="<?= htmlspecialchars((string) $_GET['search']) ?>"
      />
      <button>検索</button>
    </label>
  </form>

  <div id="app">
    <div>
      検索結果:
      <?= htmlspecialchars((string) $_GET['search']) ?>
    </div>
  </div>

  <script>
    window.addEventListener('load', function () {
      new Vue({
        el: '#app',
      });
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/vue@2.5.13/dist/vue.js"></script>
</body>
</html>
