<?php

/**
 * Download XML
 *
 * This project is a online GUI for the UMUTextStats Tool 
 * used to collect statictics from TEXTs
 * 
 * @author José Antonio García Díaz <joseantonio.garcia8@um.es>
 *
 * @package UMUTextStats-GUI
 */

header('Content-type: text/xml');
header('Content-Disposition: attachment; filename="text.xml"');
echo $_POST['configuration'];
