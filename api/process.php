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
require __DIR__ . "/../vendor/autoload.php";
require "config.php";


// Use name spaces
use \Abraham\TwitterOAuth\TwitterOAuth;


// Keep track of the tweets
$tweets = array ();


// Collect dimensions
$raw_config = file_get_contents ($dictionary);
$xml_config = simplexml_load_string ($raw_config);
$dimensions = $xml_config->dimensions->dimension;


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
$pspell_link = pspell_new ("es", "", "", "utf-8", (PSPELL_FAST | PSPELL_RUN_TOGETHER));


/**
 * spell_check
 *
 * @package UMUTextStats
 */
function spell_check ($text) {
    
    global $pspell_link;
    
    
    // Get the words from the string
    // @link https://stackoverflow.com/questions/11649019/preg-match-with-international-characters-and-accents
    return preg_replace_callback ('/\b\w+\b/ui', function ($matches) use ($pspell_link) {
        
        // Word has the first match
        $word = reset ($matches);
        
        
        // It's valid?
        if (pspell_check ($pspell_link, $word)) {
            return $word;
        }
        
        
        // IF word it's not valid, maybe it's a name
        // We will check it against the first letter
        $chr = mb_substr ($word, 0, 1, "UTF-8");
        if (mb_strtolower ($chr, "UTF-8") != $chr) {
            return $word;
        }
        
        
        // Fetch suggestions
        $suggestions = pspell_suggest ($pspell_link, $word);
        if ($suggestions) {
            return reset ($suggestions);
        }
    
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
    global $pspell_link;
    
    
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
            
            
            // Remove tweets formed merely by URLs
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
            
            
            $regex = "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?).*$)@";
            $tweet_text = preg_replace ($regex, '', $tweet_text);
            
            
            // Maybe the tweet was empty after removing data
            if ( ! $tweet_text) {
                continue;
            }
            
            
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
            $tweet_text = spell_check ($tweet_text);
            
            
            // Advance
            $tweet_index++;
            $num_results_in_this_iteration++;
            
            
            // Store tweets
            $tweets[$max_id] = [
                'id' => $tweet->id,
                'text' => $tweet_text
            ];
            
            
            // Get file name
            $filename = TEMP_URL . str_pad ($tweet_index, 6, "0", STR_PAD_LEFT) . '.txt';
            
            
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
    

    try {
        
        $hostname='localhost';
        $username='root';
        $password='nsseaplp';
        
        $db = new PDO ("mysql:host=$hostname;dbname=economialtweets;charset=utf8", $username, $password);
        $db->setAttribute (PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        
        // Store in database
        $sql = 'INSERT INTO tweets (twitter_id, tweet) VALUES ';
        $values = array ();
        $count = count ($tweets);

        foreach ($tweets as $tweet) {
            $values[] =  "(" . $db->quote ($tweet['id']) . ", " . $db->quote ($tweet['text']) . ")";
        }
        
        
        $sql = $sql . implode (', ', $values) . '  ON DUPLICATE KEY UPDATE twitter_id=twitter_id';
        
        $stmt = $db->exec ($sql);

    } catch (PDOException $e) {
        die ($e->getMessage());
    }
}


// Remove files
array_map ('unlink', glob (TEMP_URL + '*'));


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
            file_put_contents (TEMP_URL . '0000.txt', $file);
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
            
            echo ' ' . ZipArchive::ER_NOZIP;
            
            if (true === $res) {
                $zip->extractTo (TEMP_URL);
                $zip->close();
            }
            break;
    }


// Store results
} elseif (isset ($_POST['query']) && ! empty ($_POST['query'])) {
    store_tweets ($_POST['query'], $_POST['max'] ?? null);

    
} elseif (isset ($_POST['content']) && ! empty ($_POST['content'])) {
    $content = spell_check ($_POST['content']);
    $tweets[]['text'] = $content;
    file_put_contents (TEMP_URL . '0000.txt', $content);

}


// Parse
echo $dictionary;

$then = microtime (true);
exec ("java -jar TextAnalysis-0.0.1-SNAPSHOT.jar -s " . TEMP_URL . " -c " . $dictionary . " -f %s,", $output); 
$now = microtime (true);


// Remove files
array_map ('unlink', glob (TEMP_URL . "*"));



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
                <strong title="<?= $index ?>">
                    <?= str_pad ($index, 6, " ") ?>
                </strong>
            <?php endif ?>
        <th>
            <?php if (isset ($tweets[$index - 1])) : ?>
                <a href="https://twitter.com/statuses/<?= $tweets[$index - 1]['id'] ?? null ?>" target="_blank">
                    <span>
                        <?= $tweets[$index - 1]['id'] ?? null ?>
                    </span>
                </a>
            <?php else : ?>
                &nbsp;
            <?php endif ?>
        
        <th>
            <span>
                <?= isset ($tweets[$index - 1]) ? htmlentities (str_replace ("\n", "", $tweets[$index - 1]['text'])) : "" ?>
            </spn>

        <?php foreach (explode (',', $line) as $dimension_index => $output) : ?>
            <?php if ( ! $output) : ?>
                <?php continue ?>
            <?php endif ?>
            <td>
                <span><?= $output == -1 ? "-" : (is_numeric ($output) ? number_format ($output, 2, ".", "") : $output) ?></span>
            
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
