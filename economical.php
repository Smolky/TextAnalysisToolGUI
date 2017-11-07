<?php

/**
 * Store economical tweets
 *
 * @package
 */

$accounts = [
    '@miquelroig',
    '@dlacalle',
    '@JuanRallo',
    '@josecdiez',
    '@rodriguezbraun',
    '@juanma_lz',
    '@mcantalapiedra',
    '@mariadelamiel',
    '@_perpe_',
    '@david_cano_m',
    '@Lenjetias',
    '@elpais_economia',
    '@elEconomistaes',
    '@ICCAWorld',
    '@_minecogob',
    '@Economia_EA',
    '@FMInoticias',
    '@EconomiaJusta',
    '@FinancialTimes',
    '@CivicScience',
];


// Get query
$query = implode (' OR ', array_map (function ($account) { return 'from:' . $account; }, $accounts));


// Prepare request
$url = 'http://155.54.205.90/umutextstats-gui/api/process.php';
$data = array ('query' => $query, 'max' => 1000, 'database' => true);
$options = array (
    'http' => array (
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query ($data)
    )
);
$context  = stream_context_create ($options);
$result = file_get_contents ($url, false, $context);


