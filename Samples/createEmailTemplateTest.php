<?php
require_once '../src/isdk.php';

$app = new iSDK;
$app->cfgCon('connectionName');

// DEPRECATED, but you still can use it, just uncomment it
/**
$tmpId = $app->createEmailTemplate(
  'This is my API template',
  0,
  'Info@test.com',
  '~Contact.Email~',
  '',
  '',
  'html',
  'My Test Email',
  '<b>This is my test body</b>',
  ''
);
 */

$tmpId = $app->addEmailTemplate(
  'This is my API template',
  0,
  'Info@test.com',
  '~Contact.Email~',
  '',
  '',
  'html',
  'My Test Email',
  '<b>This is my test body</b>',
  ''
);

echo 'Template '. $tmpId .' has been created!';