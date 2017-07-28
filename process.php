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


// Load dictionary, Spanish by default
$dictionary = 'assets/configuration/spanish.xml';


// Collect dimensions
$raw_config = file_get_contents ($dictionary);
$xml_config = simplexml_load_string ($raw_config);
$dimensions = $xml_config->dimensions->dimension;


// Configuration
if (isset ($_POST['configuration'])) {
    file_put_contents ('assets/configuration/temp.xml', $_POST['configuration']);
    $dictionary = 'assets/configuration/temp.xml';
}

if (isset ($_POST['configuration_file'])) {
    $dictionary = 'assets/configuration/' . $_POST['configuration_file'] . '.xml';
}


// Argot
// @link http://www.netlingo.com/word/international-online-jargon.php
$argot = [
    '100pre' => 'siempre', 
    'a10' => 'adiós', 
    'a2' => 'adiós', 
    'aki' => 'aquí', 
    'amr' => 'amor', 
    'aora' => 'ahora', 
    'asdc' => 'al salir de clase', 
    'asias' => 'gracias',
    'b' => 'bien',
    'bb' => 'bebé',
    'bbr' => 'bbr',
    'bs, bss' => 'besos',
    'bye' => 'adiós',
    'b7s' => 'besitos',
    'c' => 'sé, se',
    'cam' => 'cámara',
    'chao, chau',
    'd' => 'de',
    'd2' => 'dedos',
    'dcr' => 'decir',
    'dew, dw' => 'adiós',
    'dfcl' => 'difícil',
    'dim' => 'dime',
    'dnd' => 'dónde',
    'exo' => 'hecho',
    'ems' => 'hemos',
    'ers' => 'eres tú',
    'ers2' => 'eres tú',
    'eys' => 'ellos',
    'grrr' => 'enfadado',
    'finde' => 'fin de semana',
    'fsta' => 'fiesta',
    'hl' => 'hasta luego',
    'hla' => 'hola',
    'iwal' => 'igual',
    'k' => 'que, qué',
    'kbza' => 'cabeza',
    'kls' => 'clase',
    'kntm' => 'cuéntame',
    'kyat' => 'cállate', 
    'KO' => 'estoy muerto',
    'km' => 'como',
    'm1ml' => 'mándame un mensaje luego',
    'msj' => 'msnsaje',
    'mxo' => 'mucho',
    'nph' => 'no puedo hablar',
    'npn' => 'no pasa nada',
    'pa' => 'para, padre',
    'pco' => 'poco',
    'pdt' => 'piérdete',
    'pf' => 'por favor',
    'pls' => 'por favor',
    'pq' => 'porque, porqué',
    'q' => 'que, qu.a',
    'q acs?' => '¿Qué haces?',
    'qand, qando' => 'cuando, cuándo',
    'qdms' => 'quedamos',
    'q plomo!' => '¡Qué plomo!',
    'q qrs?' => '¿Qué quieres?',
    'q risa!' => '¡Qué risa!',
    'q sea' => 'qué sea',
    'q tal?' => 'qué tal',
    'sbs?' => '¿sabes?',
    'salu2' => 'saludos',
    'sms' => 'mensaje',
    'spro' => 'espero',
    'tq' => 'te quiero',
    'tqi' => 'tengo que irme',
    'tas OK?' => '¿Estás bien?',
    'tb' => 'también',
    'uni' => 'universidad',
    'vns?' => '¿Vienes?',
    'vos' => 'vosotros',
    'wpa' => '¡Guapa!',
    'xdon' => 'perdón',
    'xfa' => 'por favor',
    'xo' => 'pero',
    'xq' => 'porque, porqué',
    'ymam, ymm',
    'zzz+' => 'dormir'
];


// Configure corrector
$pspell_link = pspell_new ("es", "", "", "", (PSPELL_FAST | PSPELL_RUN_TOGETHER));


/**
 * spell_check
 *
 * @package UMUTextStats
 */
function spell_check ($text) {
    
    global $pspell_link;
    
    
    return preg_replace_callback ('/\b\w+\b/i', function ($matches) use ($pspell_link) {
        
        if ( ! pspell_check ($pspell_link, reset ($matches))) {
            
            $suggestions = pspell_suggest ($pspell_link, reset ($matches));
            if ($suggestions) {
                return reset ($suggestions);
            }
            
        }

        
        return reset ($matches);
    
    }, $text);
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
    global $argot;
    
    
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
            
            
            // Filtering retweets
            if (0 === strpos ($tweet_text, "RT")) {
                continue;
            }
            
            
            // Delete mentions and replies to other users´ tweets, which are
            // represented by means of strings starting with @
            // @link Automatic detection of satire in Twitter: A psycholinguistic-based approach
            if (0 === strpos ($tweet_text, "@")) {
                continue;
            }
            
            
            // Remove URLs
            if (0 === strpos ($tweet_text, "http")) {
                continue;
            }
            
            
            // The “#” character is removed from all hashtags because often, only the 
            // remainder of the string forms a legible word that contributes
            // to a better understanding of the tweet [29].
            // @link Automatic detection of satire in Twitter: A psycholinguistic-based approach
            $tweet_text = str_replace ('#', '', $tweet_text);
            
            
            // Parse string to remove initial junky words
            $tweet_text = trim (preg_replace ('/^(@\w+)*\:/i', '', $tweet_text));
            
            
            // Fixing argot
            foreach ($argot as $word => $replacement) {
                $tweet_text = preg_replace ('/\b' . $word . '\b/i', $replacement, $tweet_text);
            }
            
            
            $encoding = mb_detect_encoding ($tweet_text, "auto", false);
            
            
            // @todo Apply filters
            if ( ! $tweet_text) {
                continue;
            }
            
            
            // Get last max id
            $max_id = $tweet->id_str;
            
            
            // Filtering duplicates by ID
            if (isset ($tweets[$max_id])) {
                continue;
            }
            
            
            // Filtering duplicates by text
            if (in_array ($tweet_text, $tweets)) {
                continue;
            }
            
            
            // Autocorrect
            $tweet_text = preg_replace_callback ('/\b\w+\b/', function ($matches) use ($pspell_link) {
            
                print_r ($matches);
            
                // 
                $suggestions = pspell_suggest ($pspell_link, reset ($matches));
                
                if ($suggestions) {
                    return reset ($suggestions);
                }
                
                return reset ($matches);
            
            }, $tweet_text);
            
            
            
            // Advance
            $tweet_index++;
            $num_results_in_this_iteration++;
            
            
            // Store tweets
            $tweets[$max_id] = [
                'id' => $tweet->id,
                'text' => $tweet_text
            ];
            
            
            // Get file name
            $filename = 'temp/' . str_pad ($tweet_index, 6, "0", STR_PAD_LEFT) . '.txt';
            
            
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
    
    
    // Remove keys, there are no necessary
    $tweets = array_values ($tweets);

}


// Remove files
array_map ('unlink', glob ("temp/*"));


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
            file_put_contents ('temp/000.txt', $file);
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
                $zip->extractTo ('temp');
                $zip->close();
            }
            break;
    }


// Store results
} elseif (isset ($_POST['query']) && ! empty ($_POST['query'])) {
    store_tweets ($_POST['query'], $_POST['max'] ?? null);

    
} elseif (isset ($_POST['content']) && ! empty ($_POST['content'])) {
    file_put_contents ('temp/000.txt', spell_check ($_POST['content']));

}


// Parse
$then = microtime (true);
exec ("java -jar TextAnalysis-0.0.1-SNAPSHOT.jar -s temp -c " . $dictionary . " -f %s,", $output); 
$now = microtime (true);


// Remove files
array_map ('unlink', glob ("temp/*"));



// Capture output. <th> and <td> are intentionally unclosed
ob_start();

foreach ($output as $index => $line) : ?>
    
    <?php if ($index == 0) : ?>
        <?php continue ?>
    <?php endif ?>

    <tr>
        <th>
            <?= $index == 0 ? '&nbsp;' : '' ?>
            <?php if ($index != 0) : ?>
                <?php if (isset ($tweets[$index - 1])) : ?>
                <a href="https://twitter.com/statuses/<?= $tweets[$index - 1]['id'] ?? null ?>" target="_blank">
                <?php endif ?>
                    <strong title="<?= $index ?>. <?= isset ($tweets[$index - 1]) ? htmlentities (str_replace ("\n", "", $tweets[$index - 1]['text'])) : "" ?>">
                        <?= str_pad ($index, 6, " ") ?>
                    </strong>
                <?php if (isset ($tweets[$index - 1])) : ?>
                </a>
                <?php endif ?>
            <?php endif ?>
        

        <?php foreach (explode (',', $line) as $dimension_index => $output) : ?>
            <?php if ( ! $output) : ?>
                <?php continue ?>
            <?php endif ?>
            <td>
                <span><?= is_numeric ($output) ? number_format ($output, 2, ".", "") : $output ?></span>
            
        <?php endforeach ?>
    </tr>
<?php endforeach ;


// Get output
$html = ob_get_clean ();


// Minimize
$html = preg_replace (['/ {2,}/', '/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s'], [' ', ''], $html);


// Header
header ('Content-Type: text/html; charset=utf-8');
echo $html;
