<?php
require_once '../src/isdk.php';

$cid = 36;

$app = new iSDK;
echo 'connected<br />';
$app->cfgCon('connectionName');
echo 'app connected<br />';

$currentDate = date('d-m-Y');
$oDate       = $app->infuDate($currentDate);

echo 'date set<br />';

$newOrder = $app->blankOrder($cid, 'New Order for Contact 123', $oDate, 0, 0);
echo 'New order created = '. $newOrder .'<br />';

$result = $app->addOrderItem($newOrder, 0, 4, 66.66, 1, 'JustinsStuff', 'new stuff!');
echo 'item added<br />';

$operation = $app->manualPmt($newOrder, 66.66, 'Cash', 'fakemoney', false);

if ($operation) {
	echo "payment has been added for invoiceId-".$newOrder;
} else {
	echo "Manual Payment Failed<br />";
}