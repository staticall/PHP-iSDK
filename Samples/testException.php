<?php
require_once '../src/isdk.php';

$app = new iSDK;
$app->cfgCon('connectionName', 'api-key', 'throw');

$query = array('FakeColumn' => 'FakeValue');

try {
  $r = $app->dsQuery('FakeTable', 1, 0, $query, array('FakeColumn'));
} catch (iSDKException $e) {
  echo 'iSDKException code '. $e->getCode().', message: '. $e->getMessage();
  exit;
}

echo 'Test failed: exception should have been thrown';
exit(1);