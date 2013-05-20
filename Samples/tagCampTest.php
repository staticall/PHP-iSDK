<?php
require_once '../src/isdk.php';

$app = new iSDK;
echo 'connected<br />';
$app->cfgCon('connectionName');
echo 'app connected<br />';

$cid     = 36;
$groupId = 99;

$result = $app->grpAssign($cid, $groupId);
echo 'tag added<br />';

$campId = 83;
$result = $app->campAssign($cid, $campId);
echo 'FUS added<br />';