<?php
    include('vendor/autoload.php');
    $stats = new \Cam5\RidPhp\Service\Analyzer();
    $stats->analyze(file_get_contents('friends-pilot-full.txt'));
    header('Content Type: application/xml');
    echo '';
    print_r($stats->tree->dictionary->records->saveXML());
    echo '';
    //$data = $stats->retrieve_data(array('PRIMARY'));
?>
