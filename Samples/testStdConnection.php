<?php
$app_name = 'applicationName';
$api_key  = 'e6256f1d838a342155f51d800945c777';

require_once '../src/isdk.php';

$app = new iSDK;
echo 'created object!<br />';

if($app->cfgCon($app_name, $api_key)) {
	echo 'app connected!<br />';
} else {
	echo 'connection failed!<br />';
}