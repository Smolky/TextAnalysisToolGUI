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


// Default dictionary
$dictionary = 'assets/configuration/spanish.xml';

// Configuration
if (isset ($_POST['configuration'])) {
    file_put_contents ('assets/configuration/temp.xml', $_POST['configuration']);
    $dictionary = 'assets/configuration/temp.xml';
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
    
    // Init vars
    $key = trim ($dimension->key);
    $strategy = $dimension->strategy ?? "";
    $is_composite = isset ($dimension->dimensions);
    $class = $dimension->class ?? "";
    $description = trim ($dimension->description ?? "");
    $has_dimensions = isset ($dimension->dimensions);
    $title = '';
    
    
    // Prepare title
    if ($is_composite && $strategy) {
        $title .= "[composite]" . " [" . $strategy . "]\n\n";
        
    } else if ($class) {
        $title .= "[" . $class . "]\n\n";
    }
    
    $title .= $description;

    ?>
    <th data-level="<?= $level ?>" class="<?= $is_composite ? "th-composite" : "" ?> th-deep-level">
        
        <?php if ($has_dimensions) : ?>
            <a href="javascript:0" class="toggle-button toggle-cols-action">
        <?php endif ?>
        
        <span data-toggle="tooltip" title="<?= $title ?>" data-placement="bottom">
            <?= $key ?>
        </span>
        
        <?php if ($has_dimensions) : ?>
            <span class="fa fa-angle-down"></span>
            </a>
        <?php endif ?>
        
        <?php if ("PercentageWordsCapturedFromDictionary" == $class) : ?>
            <a href="assets/dictionaries/es/<?= $dimension->dictionary ?? $key ?>.txt" target="_blank">
                <span class="fa fa-list"></span>
            </a>
        <?php endif ?>

    </th>
    
    <?php
    if ($is_composite) {
        foreach ($dimension->dimensions->dimension as $sub_dimension) {
            print_table_header_cell ($sub_dimension, $level + 1);
        }
    }
}


?><table class="table table-bordered table-hover table-striped table-responsive ">

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