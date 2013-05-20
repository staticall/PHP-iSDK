<?php
require_once '../src/isdk.php';

$app = new iSDK;
echo 'connected<br />';
$app->cfgCon('connectionName');
echo 'app connected<br />';


$query  = array('JobTitle' => 'New Order for Contact 123');
$return = array('Id');
$cards=$app->dsQuery('Job', 99, 0, $query, $return);

	echo '<pre>';
	print_r($cards);
	echo '</pre>';