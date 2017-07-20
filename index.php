<?php

/**
 * UMUTExtStats GUI
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
 * print_table_header_cell
 *
 * @package UMUTextStats-GUI
 */
function print_table_header_cell ($dimension, $level = 0) {

    $is_composite = isset ($dimension->dimensions);
    $title = '';
    
    if ($is_composite && isset ($dimension->strategy)) {
        $title .= "[composite]" . " [" . $dimension->strategy . "]\n\n";
    } else if (isset ($dimension->class)) {
        $title .= "[" . $dimension->class . "]\n\n";
    }
    
    $title .= trim ($dimension->description);

    ?>
    <th data-level="<?= $level ?>" class="<?= $is_composite ? "th-composite" : "" ?> th-deep-level">
        
        <span data-toggle="tooltip" title="<?= $title ?>" data-placement="bottom">
            <?= $dimension->key ?>
        </span>
        
        <?php if (isset ($dimension->dimensions)) : ?>
            <button type="button" class="toggle-button toggle-cols-action">
                &nbsp;
            </Button>
        <?php endif ?>
    </th>
    
    <?php
    if ($is_composite) {
        foreach ($dimension->dimensions->dimension as $sub_dimension) {
            print_table_header_cell ($sub_dimension, $level + 1);
        }
    }
}


    
// Header
header ('Content-Type: text/html; charset=utf-8');


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
        <style>
            <?php foreach ($linear_dimensions as $index => $dimension) : ?>
                <?php if (isset ($dimension->class) && $dimension->class == 'PercentageWordsCapturedFromDictionary') : ?>
            table tbody td:nth-child(<?= $index + 2 ?>) span:after {
                content: "%";
                font-size: .7em;
                opacity: .7;
            }
                <?php endif ?>
            <?php endforeach ?> 
        </style>
        
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
                        <label for="dictionary">
                            Dictionary
                            <a href="javascript:null" data-toggle="modal" data-target="#config">
                                <span class="fa fa-cog"></span>
                            </a>
                        </label>
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
                            
                            <div class="stats">
                                Time ellapsed: <?= $now - $then ?> <em>ms</em>
                            </div>
                            
                        <?php endif ?>
                    </div>
                </form>
            </div>
            
            
            <!-- Right side -->
            <table class="table table-bordered table-hover table-striped table-responsive ">
            
                <colgroup>
                    <col span="1" style="background-color: #eee">
                </colgroup>
                
                <!-- Head -->
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <?php foreach ($dimensions as $dimension) : ?>
                            <?php print_table_header_cell ($dimension) ?>
                        <?php endforeach ?>
                    </tr>
                </thead>
                    
                    
                <!-- TBody -->
                <tbody></tbody>
            
            </table>
        </main>
        
        
        <!-- Config -->
        <div class="modal fade" id="config" tabindex="-1" role="dialog" aria-labelledby="config">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Config
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <textarea class="form-control"><?= $raw_config ?></textarea>
                    </div>
                    
                </div>
            </div>
        </div>

        
        <!-- Javascripts -->
        <script src="js/vendor/jquery-1.12.0.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>
        <script>
            $(document).ready (function () {
            
                var form = $('form');
                var submit = form.find ('[type="submit"]');
                var table = $('table');
                var body = table.find ('tbody');
                
                // Handle submit
                form.submit (function (e) {
                
                    // Prevent default
                    e.preventDefault ();
                    
                    submit.prop ('disabled', true);
                    
                    $.ajax ({
                        method: 'POST',
                        url: 'process.php', 
                        dataType: "html",
                        data:  {
                            'query': form.find ('[name="query"]').val (),
                            'content': form.find ('[name="content"]').val (),
                        },
                        success: function (html) {
                            body.html (html);
                            submit.prop ('disabled', false);
                        }
                    });
                    
                    
                    return false;
                });
            
            
            
                
                
                
                $('.export-csv-action').click (function () {
                    
                    var headers = $("thead th span", table).map (function () {return $.trim(this.innerHTML);}).get()

                    var rows = $("tbody > tr", table).map (function () { 
                        return [$("td span", this).map (function () { 
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