<?php

if(!defined('ABSPATH')){
	die('Hacking Attempt!');
}

// NOTE: The Index of the Provider should match the name of the file of the same provider in lib/hybridauth/Provider
// That makes it easy to load the Provider.
$loginizer_login_providers = [
	'LinkedInOpenID' => [
		'name' => 'LinkedIn',
		'color' => '#2867b2',
		'icons' => [],
		'styles' => [
			'default' => 'background-color:#2867b2; color:white;'
		]
	],
	'Google' => [
		'name' => 'Google',
		'color' => '#4285F4',
		'icons' => [],
		'premium' => true,
		'styles' => [
			'default' => 'background-color:#fff; color: #1f1f1f; border: 1px solid #747775;',
			'dark' => 'background-color: #131314; color: #e3e3e3; border: 1px solid #747775; border-color: #8e918f;',
			'neutral' => 'background-color:#F2F2F2; color:#1f1f1f;'
		]
	],
	'Facebook' => [
		'name' => 'Facebook',
		'color' => '#0866ff',
		'icons' => [],
		'premium' => true,
		'tls_only' => true,
		'styles' => [
			'default' => 'background-color:#1877F2; color:#fff; border:1px solid #1877F2;',
			'light' => 'background-color:#fff; color:#1877F2; border:1px solid #1877F2;',
			'dark' => 'background-color:#000; color:#fff;',
			'bodered' => 'background-color:#fff; color:#000; border:1px solid #000;'
		]
	],
	'Twitter' => [
		'name' => 'X',
		'color' => '#14171a',
		'icons' => [],
		'premium' => true,
		'styles' => [
			'default' => 'background-color:#14171a; color:white;'
		]
	],
	'GitHub' => [
		'name' => 'GitHub',
		'color' => '#24292f',
		'icons' => [],
		'premium' => true,
		'styles' => [
			'default' => 'background-color:#24292f; color:white;'
		]
	],
	'TwitchTV' => [
		'name' => 'Twitch',
		'color' => '#9146FF',
		'icons' => [],
		'premium' => true,
		'styles' => [
			'default' => 'background-color:#9146FF; color:white;'
		]
	],
	'Discord' => [
		'name' => 'Discord',
		'color' => '#5865F2',
		'icons' => [],
		'premium' => true,
		'styles' => [
			'default' => 'background-color:#5865F2; color:white;'
		]
	],
	'WordPress' => [
		'name' => 'WordPress.Com',
		'color' => '#2271b1',
		'icons' => [],
		'premium' => true,
		'styles' => [
			'default' => 'background-color:#2271b1; color:white;'
		]
	],
	'MicrosoftGraph' => [
		'name' => 'Microsoft',
		'color' => '#2F2F2F',
		'icons' => [],
		'premium' => true,
		'styles' => [
			'default' => 'background-color:#2F2F2F; color:#fff;',
			'light' => 'background-color:#fff; color:#5E5E5E; border:1px solid #8C8C8C;',
		]
	],
	
];