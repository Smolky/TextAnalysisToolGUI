<?php 

// Define routes
define ('CONFIG_URL', __DIR__  . '/../assets/configuration/');
define ('DICTIONARY_URL', 'assets/dictionaries/es/');
define ('TEMP_URL', __DIR__  . '/../temp/');


// Constants
define ('TWITTER_KEY', 'f2Jmemv9jXCdSZAqQmwKgnQZu');
define ('TWITTER_SECRET', 'qDXvXA3tyJlvYWwMNCc5hjB3x3Oi0lX8jMhUjW9gEGx7BA79IC');
define ('TWITTER_ACCESS_TOKEN', '290025147-wiq2HHHPRILjS4pPQz3XCt3BkL6u6d4tFQuFETu0');
define ('TWITTER_ACCESS_TOKEN_SECRET', 'A3UzqUHbKjh6PLSndaFVLRal7v4q43W5LohkGwXuOTPZ5');



// Load dictionary, Spanish by default
$dictionary = CONFIG_URL . 'spanish.xml';

// Configuration
if (isset ($_POST['configuration'])) {
    file_put_contents (CONFIG_URL . 'temp.xml', $_POST['configuration']);
    $dictionary = CONFIG_URL . 'temp.xml';
}

if (isset ($_POST['configuration_file'])) {
    $dictionary = CONFIG_URL . $_POST['configuration_file'] . '.xml';
}


// Collect dimensions
$raw_config = file_get_contents ($dictionary);
$xml_config = simplexml_load_string ($raw_config);
$dimensions = $xml_config->dimensions->dimension;
$linear_dimensions = array ();