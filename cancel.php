<?php
$params = array(
	'_a' => 'checkout'
);
require('../../../ini.inc.php');
$path = str_replace('/modules/gateway/Oxipay','',$GLOBALS['rootRel']);
header('location: '.$path.'?'.http_build_query($params);