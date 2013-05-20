<?php
require_once '../src/isdk.php';

$app = new iSDK;
echo 'connected<br />';
$app->cfgCon('connectionName');
echo 'app connected<br />';

$query  = array('Company' => '');
$return = array('Id', 'FirstName');

$cards = $app->dsQuery('Contact', 99, 0, $query, $return);

echo '<pre>';
print_r($cards);
echo '</pre>';