<?php

if(!defined('ABSPATH')){
	exit();
}

$speedycache_ac_config = 'REPLACE_CONFIG';

if(empty($speedycache_ac_config) || !is_array($speedycache_ac_config)){
	$speedycache_ac_config = [];
}