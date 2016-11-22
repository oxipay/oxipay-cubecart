<?php
$params = array(
	'_g' => 'remote',
	'type' => 'gateway',
	'cmd' => 'process',
	'module' => 'Oxipay'
);
require('../../../ini.inc.php');
$path = str_replace('/modules/gateway/Oxipay','',$GLOBALS['rootRel']);
header('location: '.$path.'?'.http_build_query(array_merge($params, $_GET)));