<?php
require_once '../src/isdk.php';

$app = new iSDK;
echo 'connected<br />';
$app->cfgCon('connectionName');
echo 'app connected<br />';

$cid    = 36;  //contact ID
$merchId= 9;   //merchant account ID
$subId  = 439; //subscription program ID

// Find the newest credit card for the contact
$query  = array('ContactId' => $cid);
$return = array('Id');
$cards  = $app->dsQuery('CreditCard', 99, 0, $query, $return);

echo '<pre>';
print_r($cards);
echo '</pre>';

$newCard = array_pop($cards);

echo 'newCard = '. $newCard .'<br />';

// Create the subscription on contact record
$newProgram = $app->addRecurring($cid, false, $subId, $merchId, $newCard, 0, 0);
echo 'subscription added<br />';

// Generate invoice for the first charge
$newInvoice = $app->recurringInvoice($newProgram);
echo 'subscription invoiced<br />';

// Charge the new invoice
$result = $app->chargeInvoice($newInvoice, 'Customer Paid', $newCard, $merchId, false);
echo 'customerId-'. $cid .' has been charged for invoiceId-'. $newInvoice;