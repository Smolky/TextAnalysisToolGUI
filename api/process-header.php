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
ini_set ('display_errors', 1);
ini_set ('display_startup_errors', 1);
error_reporting (E_ALL);


// Autoload
require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/config.php";



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
function print_table_header_cell ($dimension, $parent_dimension_key='', $level = 0) {
    
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
    <th 
        data-key="<?= $dimension->key ?>" 
        data-key-full="<?= $parent_dimension_key ?><?= $dimension->key ?>"
        data-toggled="false"
        data-level="<?= $level ?>" 
        class="<?= $is_composite ? "th-composite" : "" ?> th-deep-level"
    >
        
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
            <a href="<?= DICTIONARY_URL . $dimension->dictionary ?? $key ?>.txt" target="_blank">
                <span class="fa fa-list"></span>
            </a>
        <?php endif ?>

    </th>
    
    <?php
    if ($is_composite) {
        foreach ($dimension->dimensions->dimension as $sub_dimension) {
            print_table_header_cell ($sub_dimension, $parent_dimension_key . $dimension->key . '|', $level + 1);
        }
    }
}


// Get style columns index
$column_indexes = [];
foreach ($linear_dimensions as $index => $dimension) {
    if (isset ($dimension->class) && 'PercentageWordsCapturedFromDictionary' == $dimension->class) {
        $column_indexes[] = $index + 2;
    }
}

if ($column_indexes) {
?><style id="inline-style-sheet">
    <?php foreach ($column_indexes as $index => $dimension_index) : ?>
        table tbody td:nth-child(<?= $dimension_index ?>) span:after<?= (count ($column_indexes) - 1) != $index ? ',' : '' ?>
    <?php endforeach ?> {
        content: "%";
        font-size: .7em;
        opacity: .7;
    }
    
</style>
<?php } ?>

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