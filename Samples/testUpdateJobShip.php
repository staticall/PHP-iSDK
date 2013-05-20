<?php
require_once '../src/isdk.php';
$app = new iSDK;

if ($app->cfgCon('connectionName')) {
  $orderId   = 673;
  $firstName = 'API';
  $lastName  = 'Has Updated Me';
  $street    = '333 API st';
  $city      = 'gilbert';

  $data = array(
    'ShipFirstName' => $firstName,
    'ShipLastName'  => $lastName,
    'ShipStreet1'   => $street,
    'ShipCity'      => $city,
  );

  $jobId = $app->dsUpdate('Job', $orderId, $data);

  echo 'JobId = '. $jobId .'<br />';
}