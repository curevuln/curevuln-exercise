<!DOCTYPE html>
<html>
<head>
  <title>VueJS serverside template xss</title>
  <link rel="stylesheet" type="text/css" href="/css/semantic.min.css">
  <script src="https://code.jquery.com/jquery-3.1.1.min.js"
    integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8="
    crossorigin="anonymous"
  </script>
  <script src="/js/semantic.min.js"></script>
</head>
<body>
  <div class="ui raised very padded text container segment">
    <ul class="ui list">
      <li><a href="index.php">脆弱なバージョン</a></li>
      <li><a href="safe.php">安全なバージョン</a></li>
    </ul>

    <div class="ui divider"></div>

    <form action="" class="ui form">
      <div class="field">
        <label>
          キーワードを入力
        </label>
        <input
          type="text"
          name="search"
          value="<?= htmlspecialchars((string) $_GET['search']) ?>"
        />
      </div>
      <button class="ui button" type="submit">検索</button>
    </form>

    <div id="app">
      <div>
        検索キーワード:
        <?= htmlspecialchars((string) $_GET['search']) ?>
      </div>
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
