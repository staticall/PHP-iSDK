<?php
require_once '../src/isdk.php';

$app = new iSDK;

if ($app->cfgCon('connectionName')) {
  $cid    = (int)$_REQUEST['id'];
  $tagId  = 184;
  $result = $app->grpAssign($cid, $tagId);
}	else {
	echo 'connection failed!<br />';
}