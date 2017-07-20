<?php

/**
 * UMUTExtStats GUI Process
 *
 * This project is a online GUI for the UMUTextStats Tool 
 * used to collect statictics from TEXTs
 * 
 * @author José Antonio García Díaz <joseantonio.garcia8@um.es>
 *
 * @package UMUTextStats-GUI
 */

// Error configuration
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 
 
// Autoload
require "vendor/autoload.php";


// Constants
define ('TWITTER_KEY', 'f2Jmemv9jXCdSZAqQmwKgnQZu');
define ('TWITTER_SECRET', 'qDXvXA3tyJlvYWwMNCc5hjB3x3Oi0lX8jMhUjW9gEGx7BA79IC');
define ('TWITTER_ACCESS_TOKEN', '290025147-wiq2HHHPRILjS4pPQz3XCt3BkL6u6d4tFQuFETu0');
define ('TWITTER_ACCESS_TOKEN_SECRET', 'A3UzqUHbKjh6PLSndaFVLRal7v4q43W5LohkGwXuOTPZ5');


// Use name spaces
use Abraham\TwitterOAuth\TwitterOAuth;


// Keep track of the tweets
$tweets = array ();
$tweets_links = array ();


// Load dictionary, Spanish by default
if (isset ($_POST['dictionary']) && 'en' == $_POST['dictionary']) {
    $dictionary = 'assets/configuration/english.xml';

    
} else {
    $dictionary = 'assets/configuration/spanish.xml';
    
}


// Collect dimensions
$raw_config = file_get_contents ($dictionary);
$xml_config = simplexml_load_string ($raw_config);
$dimensions = $xml_config->dimensions->dimension;
$linear_dimensions = array ();

    
/**
 * build_linear_dimensions
 *
 * @package UMUTextStats-GUI
 */
function build_linear_dimensions ( & $linear_dimensions, $dimension) {
    $linear_dimensions[] = $dimension;
    if (isset ($dimension->dimensions)) foreach ($dimension->dimensions->dimension as $sub_dimension) {
        build_linear_dimensions ($linear_dimensions, $sub_dimension);
    }
}

foreach ($dimensions as $dimension) {
    build_linear_dimensions ($linear_dimensions, $dimension);
}


/**
 * store_tweets
 *
 * @package UMUTextStats Sample
 */
function store_tweets ($query) {

    // Global
    global $tweets;
    global $tweets_links;

    
    // Connect
    $connection = new TwitterOAuth (TWITTER_KEY, TWITTER_SECRET, TWITTER_ACCESS_TOKEN, TWITTER_ACCESS_TOKEN_SECRET);
    $content = $connection->get("account/verify_credentials");

    
    // Fetch data
    $responses = $connection->get("search/tweets", [
        "count" => 10, 
        "q" => $query,
        "lang" => "es",
        "result_type" => "recent",
        "include_entities" => false
    ]);
    
    
    // Fetch information
    foreach ($responses->statuses as $index => $tweet) {
    
        // Get text
        $tweet_text = $tweet->text;
        
        
        // Parse string to remove initial junky words
        // like "RT" or the mentions to other persons
        $tweet_text = trim (preg_replace ('/^RT/i', '', $tweet_text));
        $tweet_text = trim (preg_replace ('/^(@\w+)*\:/i', '', $tweet_text));
        $encoding = mb_detect_encoding ($tweet_text, "auto", false);
        
        
        // Store tweets
        $tweets[] = $tweet_text;
        
        
        // Store links (in the same order)
        $tweets_links[] = 'https://twitter.com/statuses/' . $tweet->id;
        
        
        // Get file name
        $filename = 'tweets/' . str_pad ($index, 3, "0", STR_PAD_LEFT) . '.txt';
        
        
        // Store content
        if ($encoding === 'UTF-8') {
            file_put_contents ($filename, $tweet_text);
            
        } else {
            file_put_contents ($filename, mb_convert_encoding ($tweet_text, 'UTF-8'));
            
        }
    }
}


    
// Remove files
array_map ('unlink', glob("tweets/*"));


// Uploading files
if (isset ($_FILES) && isset ($_FILES[0]) && ! isset ($_FILES[0]['error']) ) {
    
    $file = reset ($_FILES);
    
    switch ($file['type']) {
        default:
        case 'text/plain':
            file_put_contents ('tweets/000.txt', file_get_contents ($file['tmp_name']));
            break;
            
        case 'application/octet-stream':
            $zip = new \ZipArchive;
            $res = $zip->open ($file['tmp_name']);
            
            if (true === $res) {
                $zip->extractTo ('tweets');
                $zip->close();
            }
            break;
    }
    


// Store results
} elseif ($_POST['query']) {
    store_tweets ($_POST['query']);
    
} elseif ($_POST['content']) {
    file_put_contents ('tweets/000.txt', $_POST['content']);

}


// Parse
$then = microtime (true);
exec ("java -jar TextAnalysis-0.0.1-SNAPSHOT.jar -s tweets -c " . $dictionary . " -f %s,", $output); 
$now = microtime (true);

// Header
header ('Content-Type: text/html; charset=utf-8');


?>            
                    
<?php foreach ($output as $index => $line) : ?>
    
    <?php if ($index == 0) : ?>
        <?php continue ?>
    <?php endif ?>

    <tr>
        <th>
            <?= $index == 0 ? '&nbsp;' : '' ?>
            <?php if ($index != 0) : ?>
            
                <?php if (isset ($tweets_links[$index - 1])) : ?>
                <a href="<?= $tweets_links[$index - 1] ?? null ?>" target="_blank">
                <?php endif ?>
                    <strong title="<?= $index ?>. <?= isset ($tweets[$index - 1]) ? htmlentities (str_replace ("\n", "", $tweets[$index - 1])) : "" ?>">
                        <?= str_pad ($index, 6, " ") ?>
                    </strong>
                <?php if (isset ($tweets_links[$index - 1])) : ?>
                </a>
                <?php endif ?>
            <?php endif ?>
        </th>

        <?php foreach (explode (',', $line) as $dimension_index => $output) : ?>
            <?php if ( ! $output) : ?>
                <?php continue ?>
            <?php endif ?>
            <td>
                <span><?= is_numeric ($output) ? number_format ($output, 2) : $output ?></span>
            </td>
        <?php endforeach ?>

    </tr>

<?php endforeach ?>
