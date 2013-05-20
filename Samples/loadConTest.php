<?php
require_once '../src/isdk.php';

$app = new iSDK;
echo 'connected<br />';
$app->cfgCon('connectionName');
echo 'app connected<br />';

$cid = 36;

$returnFields = array('Email', 'FirstName', 'LastName');

$conDat = $app->loadCon($cid, $returnFields);

echo '<pre>';
print_r($conDat);
echo '</pre>';