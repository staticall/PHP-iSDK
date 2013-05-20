<?php
require_once '../src/isdk.php';

$cid = 36;

$app = new iSDK;
echo 'connected<br />';
$app->cfgCon('connectionName');
echo 'app connected<br />';

//Sets current date
$currentDate = date('d-m-Y');
$oDate = $app->infuDate($currentDate);
echo 'date set<br />';

//Creates blank order
$newOrder = $app->blankOrder($cid, 'New Order for Contact 123', $oDate, 0, 0);
echo 'newOrder='. $newOrder .'<br />';

$newOrder = (int)$newOrder;

// Adds item to order
$result = $app->addOrderItem($newOrder, 53, 4, 66.66, 1, 'JustinsStuff', 'new stuff!');
echo 'item added<br />';

// Finds the newest credit card
$query  = array('ContactId' => $cid);
$return = array('Id');

$cards = $app->dsQuery('CreditCard', 99, 0, $query, $return);

echo "<pre>";
print_r($cards);
echo "</pre>";

$newestCard = array_pop($cards);

echo 'newCard = '. $newestCard .'<br />';

// Charge the invoice for new order with the latest credit card
$result = $app->chargeInvoice($newOrder, 'Customer Paid', $newestCard, 9, false);

echo "<pre>";
print_r($result);
echo "</pre>";

echo 'customerId-'. $cid .' has been charged for invoiceId-'. $newOrder;