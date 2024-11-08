<?php
    session_start();
    header("X-XSS-Protection: 0;");
    $author = $_GET['author'];
    try {
        $dbname = 'pgsql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=sampledb;port=' . $_ENV['DATABASE_PORT'];
        $dbh = new PDO($dbname, 'postgres', 'example');
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sqlcode = "SELECT * FROM booklist WHERE author = '$author' ORDER BY id";
        $result = $dbh->query($sqlcode);
?>
<html>
<head>
   <title>検索結果</title>  
</head>
<body>
    <table border=1>
        <tr>
            <th>ID</th>
            <th>タイトル</th>
            <th>筆者</th>
            <th>価格</th>
        </tr>
<?php
while ($row = $result->fetch()) {
    echo "<tr>\n";
    for ($column = 0; $column < 4; $column++) {
        echo "<td>" . $row[$column] . "</td>\n";
    }
    echo "</tr>\n";
}
} catch (PDOException $exception) {
    echo "Exception raised: " . $exception->getMessage() . "\n";
}
?>
    </table>
</body>
</html>
