<?php
    include('vendor/autoload.php');
    $stats = new \Cam5\RidPhp\Service\Analyzer();
    $stats->analyze(file_get_contents('friends-pilot-full.txt'));
    //$data = $stats->retrieve_data(array('PRIMARY'));
?>
<html>
  <body>
  </body>
</html>
