<?php

// Autoload
require "vendor/autoload.php";


// Twitter
define ('TWITTER_KEY', 'f2Jmemv9jXCdSZAqQmwKgnQZu');
define ('TWITTER_SECRET', 'qDXvXA3tyJlvYWwMNCc5hjB3x3Oi0lX8jMhUjW9gEGx7BA79IC');
define ('TWITTER_ACCESS_TOKEN', '290025147-wiq2HHHPRILjS4pPQz3XCt3BkL6u6d4tFQuFETu0');
define ('TWITTER_ACCESS_TOKEN_SECRET', 'A3UzqUHbKjh6PLSndaFVLRal7v4q43W5LohkGwXuOTPZ5');


// Use
use Abraham\TwitterOAuth\TwitterOAuth;


// Keep track of the tweets
$tweets = array ();
$tweets_links = array ();


// Dictionary
if ('en' == $_POST['dictionary']) {
    $dictionary = 'assets/configuration/english.xml';
    
} else {
    $dictionary = 'assets/configuration/spanish.xml';
    
}


exec ("java -jar TextAnalysis-0.0.1-SNAPSHOT.jar -d -c " . $dictionary . " -f %s,", $dimensions); 



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
        "count" => 25, 
        "q" => $query,
        "lang" => "es",
        "result_type" => "recent",
        "include_entities" => false
    ]);
    
    
    // Fetch information
    foreach ($responses->{statuses} as $index => $tweet) {
    
        // Get text
        $tweet_text = $tweet->{text};
        
        
        // Parse string to remove initial junky words
        // like "RT" or the mentions to other persons
        $tweet_text = trim (preg_replace ('/^RT/i', '', $tweet_text));
        $tweet_text = trim (preg_replace ('/^(@\w+)*\:/i', '', $tweet_text));
        
        
        // Store tweets
        $tweets[] = $tweet_text;
        
        
        // Store links (in the same order)
        $tweets_links[] = 'https://twitter.com/statuses/' . $tweet->{id};
        
        
        // Store content
        file_put_contents ('tweets/' . str_pad ($index, 3, "0", STR_PAD_LEFT) . '.txt', $tweet_text);
        
    }    

}


// Connect
if ("submit" == $_POST['form-action']) {
    
    // Remove files
    array_map ('unlink', glob("tweets/*"));


    // Store results
    if ($_POST['query']) {
        store_tweets ($_POST['query']);
        
    } elseif ($_POST['content']) {
        file_put_contents ('tweets/000.txt', $_POST['content']);
    
    }
    

    
    // Parse
    exec ("java -jar TextAnalysis-0.0.1-SNAPSHOT.jar -s tweets -c " . $dictionary . " -f %s,", $output); 

}
?><!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>UMTextStats Sample</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="apple-touch-icon.png">

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        
        <link rel="stylesheet" href="css/main.css?v=<?= rand (1, 1000) ?>">
        <script src="js/vendor/modernizr-2.8.3.min.js"></script>
    </head>
    <body>
        
        <main>
            <div class="row">
                <div class="col-md-12">
                
                    <!-- Title -->
                    <h1>UMTextStats</h1>
                    <p>
                        This is a sample tool to validate <em>UMTextStats</em> with sample input. These input
                        will be fetched using the Twitter API and recovering the most popular tweets 
                        based on query string.
                    </p>
                    
                    <p>
                        Spanish dictionaries can be found at: 
                        <a href="https://github.com/Smolky/TextAnalysisTool/tree/master/assets/dictionaries/es" target="_Blank">
                            https://github.com/Smolky/TextAnalysisTool/tree/master/assets/dictionaries/es
                        </a>
                    </p>
                    
                    
                    <!-- Form -->
                    <div class="row">
                        <div class="col-md-4">
                    
                            <form method="post">
                                
                                <!-- Hidden fields -->
                                <input type="hidden" name="form-action" value="submit">
                                
                                
                                <!-- Select dictionary -->
                                <div class="form-group">
                                    <label for="dictionary">Dictionary</label>
                                    <select name="dictionary" class="form-control">
                                        <option <?= $_POST['dictionary'] == 'es' ? 'selected' : '' ?> value="es">Spanish</option>
                                        <option <?= $_POST['dictionary'] == 'en' ? 'selected' : '' ?> value="en">English</option>
                                    </select>
                                </div>
                                
                                
                                <!-- Text -->
                                <div class="form-group">
                                    <label for="query">Twitter query string</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        name="query" 
                                        placeholder="Cadena de búsqueda para tweets" 
                                        value="<?= htmlspecialchars ($_POST['query']) ?>" 
                                    />
                                </div>
                                
                                
                                <!-- Content -->
                                <div class="form-group">
                                    <label for="query">General purpose content</label>
                                    
                                    <p>
                                        Alternatively, you can copy paste a long text to be validated
                                        directly
                                    </p>
                                
                                    <textarea 
                                        rows="3"
                                        name="content" 
                                        class="form-control"
                                        placeholder="Cadena de texto"
                                    ><?= htmlspecialchars ($_POST['content']) ?></textarea>
                                        
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        Enviar
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        
                        <!-- Right side -->
                        <div class="col-md-8">
                        
                            <!-- Results -->
                            <h3>Results</h3>
                            
                            
                            <?php if ($output) : ?>
                                <table class="table table-bordered table-hover table-striped table-responsive">
                                
                                    <colgroup>
                                        <col span="1" style="background-color: #eee">
                                    </colgroup>
                                
                                    <?php foreach ($output as $index => $line) : ?>
                                        <tr>
                                            <th>
                                                <?= $index == 0 ? '&nbsp;' : '' ?>
                                                <?php if ($index != 0) : ?>
                                                
                                                    <?php if ($tweets_links[$index + 1]) : ?>
                                                    <a href="<?= $tweets_links[$index + 1] ?>" target="_blank">
                                                    <?php endif ?>
                                                        <strong title="<?= $index ?>. <?= str_replace ("\n", "", $tweets[$index + 1]) ?>">
                                                            <?= str_pad ($index, 6, " ") ?>
                                                        </strong>
                                                    <?php if ($tweets_links[$index + 1]) : ?>
                                                    </a>
                                                    <?php endif ?>
                                                <?php endif ?>
                                            </th>
                
                                            <?php foreach (explode (',', $line) as $dimension_index => $output) : ?>
                                            
                                                <?php if ($index == 0) : ?>
                                                
                                                    <?php $full_description = $dimensions[$dimension_index] ?>
                                                    <?php $ident = strlen($full_description)-strlen(ltrim($full_description)); ?>
                                                
                                                    <?php preg_match_all ("/\[([^\]]*)\]/", $full_description, $parts); ?>
                                                    
                                                    <?php
                                                    
                                                        $is_composite = $parts[0][0] == '[CompositeDimension]';
                                                    
                                                        // Prepare title
                                                        $title = $parts[0][0];
                                                        if (1 == count ($parts[0])) {
                                                        
                                                        } else if (2 == count ($parts[0])) {
                                                            $title .= "&#13;" . $parts[0][1];
                                                        
                                                        } else if (3 == count ($parts[0])) {
                                                            $title .= " " 
                                                                . $parts[0][1] 
                                                                . "&#13;&#13;" 
                                                                . $parts[0][2]
                                                            ;
                                                        }
                                                    ?>
                                                    
                                                    <th data-level="<?= $ident / 4?>" title="<?= $title ?>" class="<?= $is_composite ? "th-composite" : "" ?> th-deep-level">
                                                        <?= $output ?>
                                                        
                                                        <?php if ($is_composite) : ?>
                                                            <button type="button" class="toggle-button toggle-cols-action">
                                                                &nbsp;
                                                            </Button>
                                                        <?php endif ?>
                                                        
                                                    </th>
                                                <?php else : ?>
                                                    <td><?= $output ?></td>
                                                <?php endif ?>
                                            <?php endforeach ?>
                
                                        </tr>
                                    <?php endforeach ?>
                                </table>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        
        <!-- Javascripts -->
        <script src="js/vendor/jquery-1.12.0.min.js"></script>
        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>
        <script>
            $(document).ready (function () {
            
                var table = $('table');
            
                // Toggle
                table.find ('.toggle-cols-action').click (function () {
                    
                    // Get elements
                    var self = $(this);
                    var parent = self.closest ('th');
                    var level = parent.attr ('data-level') * 1;
                    var found_same_level = false;
                    
                    
                    // Toggle
                    self.toggleClass ('is-toggled');
                    
                    
                    // Fetch childs
                    var childs = parent.nextAll ().filter (function() {
                    
                        var current_level = $(this).attr("data-level") * 1;
                        
                        if (found_same_level) {
                            return false;
                        }
                    
                        if (current_level == level) {
                            found_same_level = true;
                            return false;
                        }
                    
                    
                        return current_level > level;
                        
                    });
                    
                    
                    // Toggle
                    childs.each (function () {
                        var index = $(this).index () + 1;
                        table.find ('tr:first-child th:nth-child(' + index + ')').toggle ( ! self.hasClass ('is-toggled'));
                        table.find ('td:nth-child(' + index + ')').toggle ( ! self.hasClass ('is-toggled'));
                    });
                    
                });
            
            });
        </script>

    </body>
</html>