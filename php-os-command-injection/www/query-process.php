<html>
<body>
<p>
お問い合わせのメールアドレスを出力します: 
<?php
  $mailaddress = filter_input(INPUT_POST, 'mailaddress');
  system("/bin/echo $mailaddress");
?>
</p>
</body>
