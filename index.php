<?php

// Autoload
require "vendor/autoload.php";


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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
if (isset ($_POST['dictionary']) && 'en' == $_POST['dictionary']) {
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
    foreach ($responses->statuses as $index => $tweet) {
    
        // Get text
        $tweet_text = $tweet->text;
        
        
        // Parse string to remove initial junky words
        // like "RT" or the mentions to other persons
        $tweet_text = trim (preg_replace ('/^RT/i', '', $tweet_text));
        $tweet_text = trim (preg_replace ('/^(@\w+)*\:/i', '', $tweet_text));
        
        
        // Store tweets
        $tweets[] = $tweet_text;
        
        
        // Store links (in the same order)
        $tweets_links[] = 'https://twitter.com/statuses/' . $tweet->id;
        
        
        // Store content
        file_put_contents ('tweets/' . str_pad ($index, 3, "0", STR_PAD_LEFT) . '.txt', $tweet_text);
        
    }
}


// Connect
if ("submit" == ($_POST['form-action'] ?? null)) {
    
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
    exec ("java -jar TextAnalysis-0.0.1-SNAPSHOT.jar -s tweets -c " . $dictionary . " -f %s,", $output); 

}
?><!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>UMUTextStats GUI</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="apple-touch-icon.png">

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        
        
        <link rel="stylesheet" href="css/main.css?v=<?= rand (1, 1000) ?>">
        <script src="js/vendor/modernizr-2.8.3.min.js"></script>
    </head>
    <body>
        
        <main>
            
            <div class="filter-wrapper">
            
                <!-- Title -->
                <h1>
                    <a href="/umutextstats-gui/">
                        UMUTextStats GUI
                    </a>
                </h1>
        
                <p>
                    This is a GUI tool to use the <em>UMUTextStats</em> tool. 
                </p>
                
                <form method="post" enctype="multipart/form-data">
                    
                    <!-- Hidden fields -->
                    <input type="hidden" name="form-action" value="submit">
                    
                    
                    <!-- Select dictionary -->
                    <div class="form-group">
                        <label for="dictionary">Dictionary</label>
                        <select name="dictionary" class="form-control">
                            <?php foreach (array ('es' => 'Spanish', 'en' => 'English') as $key => $language) : ?>
                                <option 
                                    data-url="https://github.com/Smolky/TextAnalysisTool/tree/master/assets/dictionaries/<?= $key ?>" 
                                    <?= ($_POST['dictionary'] ?? '') == $key ? 'selected' : '' ?> 
                                    value="<?= $key ?>">
                                    <?= $language ?>
                                </option>
                            <?php endforeach ?>
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
                            value="<?= htmlspecialchars ($_POST['query'] ?? '') ?>" 
                        />
                    </div>
                    
                    
                    <!-- File -->
                    <div class="form-group">
                        <label for="query">
                            File
                            <small>allows .txt. .zip</small>
                        </label>
                        <input 
                            type="file" 
                            class="form-control" 
                            name="file" 
                            placeholder="Fichero .txt o .rar" 
                            accept=".txt,.csv,application/zip,application/rar"
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
                        ><?= htmlspecialchars ($_POST['content'] ?? '') ?></textarea>
                            
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            Send
                        </button>
                        
                        <?php if (isset ($output)) : ?>
                            <button type="button" class="btn btn-default export-csv-action">
                                Export to CSV
                                <span class="fa fa-download"></span>
                            </button>
                        <?php endif ?>
                        
                    </div>
                </form>
            </div>

        
        
            <!-- Right side -->
            <?php if (isset ($output)) : ?>
                <table class="table table-bordered table-hover table-striped table-responsive">
                
                    <colgroup>
                        <col span="1" style="background-color: #eee">
                    </colgroup>
                
                    <?php foreach ($output as $index => $line) : ?>
                    
                        <?php if ($index == 0) : ?>
                            <thead>
                        <?php elseif ($index == 1) : ?>
                            <tbody>
                        <?php endif ?>
                    
                        <tr>
                            <th>
                                <?= $index == 0 ? '&nbsp;' : '' ?>
                                <?php if ($index != 0) : ?>
                                
                                    <?php if (isset ($tweets_links[$index + 1])) : ?>
                                    <a href="<?= $tweets_links[$index + 1] ?? null ?>" target="_blank">
                                    <?php endif ?>
                                        <strong title="<?= $index ?>. <?= isset ($tweets[$index + 1]) ? str_replace ("\n", "", $tweets[$index + 1]) : "" ?>">
                                            <?= str_pad ($index, 6, " ") ?>
                                        </strong>
                                    <?php if (isset ($tweets_links[$index + 1])) : ?>
                                    </a>
                                    <?php endif ?>
                                <?php endif ?>
                            </th>

                            <?php foreach (explode (',', $line) as $dimension_index => $output) : ?>
                            
                                <?php if ($index == 0) : ?>
                                
                                    <?php $full_description = $dimensions[$dimension_index] ?? null ?>
                                    <?php $ident = strlen($full_description)-strlen(ltrim($full_description)); ?>
                                
                                    <?php preg_match_all ("/\[([^\]]*)\]/", $full_description, $parts); ?>
                                    
                                    <?php
                                    
                                        $dimension_key = $parts[0][0] ?? null;
                                        $is_composite = $dimension_key == '[CompositeDimension]';
                                    
                                        // Prepare title
                                        $title = $dimension_key;
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
                                        <span><?= $output ?></span>
                                        
                                        <?php if ($is_composite) : ?>
                                            <button type="button" class="toggle-button toggle-cols-action">
                                                &nbsp;
                                            </Button>
                                        <?php endif ?>
                                        
                                    </th>
                                <?php else : ?>
                                    <td>
                                        <?= is_numeric ($output) ? number_format ($output, 2) : $output ?>
                                    </td>
                                <?php endif ?>
                            <?php endforeach ?>

                        </tr>
                        
                        <?php if ($index == 0) : ?>
                            </thead>
                        <?php endif ?>
                        
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php endif ?>
        </main>

        
        <!-- Javascripts -->
        <script src="js/vendor/jquery-1.12.0.min.js"></script>
        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>
        <script>
            $(document).ready (function () {
            
                var table = $('table');
                
                
                $('.export-csv-action').click (function () {
                    
                    var headers = $("thead th span", table).map (function () {return $.trim(this.innerHTML);}).get()

                    var rows = $("tbody > tr", table).map (function () { 
                        return [$("td", this).map (function () { 
                            return $.trim (this.innerHTML);
                        }).get()];
                    }).get();
                    
                    var csv = "";
                    csv = csv + headers.join (';') + "\n";
                    $.each (rows, function (index, row) {
                        csv = csv + row.join (';') + "\n";
                    });
                    
                    $.ajax ({
                        url: 'export-csv.php',
                        type: 'POST',
                        data: {
                            csv: csv
                        },
                        success: function (result) {
                            var blob=new Blob([result]);
                            var link=document.createElement('a');
                            link.href=window.URL.createObjectURL(blob);
                            link.download="umutextstats.csv";
                            link.click();
                        }
                    });
                });
            
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