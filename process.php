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


// Configuration
if (isset ($_POST['configuration'])) {
    file_put_contents ('assets/configuration/temp.xml', $_POST['configuration']);
    $dictionary = 'assets/configuration/temp.xml';
}

    
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
 * @link https://dev.twitter.com/rest/reference/get/search/tweets
 *
 * @package UMUTextStats Sample
 */
function store_tweets ($query, $max_results = null) {

    // Global
    global $tweets;
    global $tweets_links;
    
    
    // Max ID will store the last tweet for pagination
    $max_id = null;
    $tweet_index = 0;
    
    
    // Connect
    $connection = new TwitterOAuth (TWITTER_KEY, TWITTER_SECRET, TWITTER_ACCESS_TOKEN, TWITTER_ACCESS_TOKEN_SECRET);
    $content = $connection->get ("account/verify_credentials");
    
    
    // Get max results
    while (true) {
    
        // Fetch data
        $response = $connection->get ("search/tweets", [
            "count" => 100, 
            "q" => $query,
            "lang" => "es",
            "result_type" => "recent",
            "include_entities" => false,
            "max_id" => $max_id
        ]);
        
        
        // No results
        // @link https://dev.twitter.com/rest/public/rate-limiting
        if ( ! $response || isset ($response->error)) {
            header ('HTTP/1.1 503 Service Temporarily Unavailable');
            header ('Status: 503 Service Temporarily Unavailable');
            break;
        }
        
        
        // restart number of results
        $num_results_in_this_iteration = 0;
        
        
        // Fetch information
        foreach ($response->statuses as $index => $tweet) {
        
            // Get text
            $tweet_text = $tweet->text;
            
            
            // Parse string to remove initial junky words
            // like "RT" or the mentions to other persons
            $tweet_text = trim (preg_replace ('/^RT/i', '', $tweet_text));
            $tweet_text = trim (preg_replace ('/^(@\w+)*\:/i', '', $tweet_text));
            $encoding = mb_detect_encoding ($tweet_text, "auto", false);
            
            
            if ( ! $tweet_text) {
                continue;
            }
            
            
            // Get last max id
            $max_id = $tweet->id_str;
            
            
            // Store already in the set
            if (isset ($tweets[$max_id])) {
                continue;
            }
            
            
            // Advance
            $tweet_index++;
            $num_results_in_this_iteration++;
            
            
            // Store tweets
            $tweets[$max_id] = $tweet_text;
            
            
            
            // Store links (in the same order)
            $tweets_links[] = 'https://twitter.com/statuses/' . $tweet->id;
            
            
            // Get file name
            $filename = 'tweets/' . str_pad ($tweet_index, 6, "0", STR_PAD_LEFT) . '.txt';
            
            
            // Store content
            if ($encoding === 'UTF-8') {
                file_put_contents ($filename, $tweet_text);
                
            } else {
                file_put_contents ($filename, mb_convert_encoding ($tweet_text, 'UTF-8'));
                
            }
            
            
            // When to exist? I've got everything i want
            if (count ($tweets) >= $max_results) {
                break 2;
            }
        }
        
        
        // Failure
        if ( ! $num_results_in_this_iteration) {
            break;
        }
        
        
        // Give a break
        usleep (.5 * 1000000);
        
        
    }
}


    
// Remove files
array_map ('unlink', glob("tweets/*"));


// Uploading files
if (isset ($_POST['file'])) {
    
    
    // Get file
    $file = base64_decode ($_POST['file']);
    
    
    // Get mime type
    $f = finfo_open();
    $mime_type = finfo_buffer ($f, $file, FILEINFO_MIME_TYPE);
    
    
    // According to the mime type
    switch ($mime_type) {
        
        // Text files
        default:
        case 'text/plain':
            file_put_contents ('tweets/000.txt', $file);
            break;
        
        
        // Zips
        case 'application/octet-stream':
        
            $file = base64_decode (str_replace ('data:;base64,', '', $_POST['file']));
            $temp_file = tmpfile ();
            fwrite ($temp_file, $file);
            $temp_file_url = stream_get_meta_data ($temp_file);
            $temp_file_url = $temp_file_url['uri'];
            
            $zip = new \ZipArchive;
            $res = $zip->open ($temp_file_url);
            
            echo $res;
            echo ' ' . ZipArchive::ER_NOZIP;
            
            if (true === $res) {
                echo 'test';
                $zip->extractTo ('tweets');
                $zip->close();
            }
            break;
    }


// Store results
} elseif (isset ($_POST['query']) && ! empty ($_POST['query'])) {
    store_tweets ($_POST['query'], $_POST['max'] ?? null);
    
} elseif (isset ($_POST['content']) && ! empty ($_POST['content'])) {
    file_put_contents ('tweets/000.txt', $_POST['content']);

}


// Parse
$then = microtime (true);
exec ("java -jar TextAnalysis-0.0.1-SNAPSHOT.jar -s tweets -c " . $dictionary . " -f %s,", $output); 
$now = microtime (true);

// Header
header ('Content-Type: text/html; charset=utf-8');


foreach ($output as $index => $line) : ?>
    
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
                <span><?= is_numeric ($output) ? number_format ($output, 2, ".", "") : $output ?></span>
            </td>
        <?php endforeach ?>

    </tr>

<?php endforeach ?>
