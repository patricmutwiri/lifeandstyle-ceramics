<?php

namespace CookieAdmin;

if(!defined('COOKIEADMIN_VERSION') || !defined('ABSPATH')){
	die('Hacking Attempt');
}

class Database{
	
	static $wpdb = '';
	static $scanned_cookies_table = '';
	
	static function activate(){
		
		global $wpdb;
		
		self::$wpdb = $wpdb;
		self::$scanned_cookies_table = esc_sql(self::$wpdb->prefix . 'cookieadmin_cookies');
		self::cookieadmin_create_tables();
	}
	
	static function cookieadmin_create_tables() {
		
		$charset_collate = self::$wpdb->get_charset_collate();
		$db_path = !defined('SITEPAD') ? ABSPATH . 'wp-admin/includes/upgrade.php' : ABSPATH . 'site-admin/includes/upgrade.php';
		require_once($db_path);
		
		//Create scanned Cookies table
		$sql = "CREATE TABLE IF NOT EXISTS ".self::$scanned_cookies_table." (
			id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- Auto Increment ID
			cookie_name VARCHAR(100) NOT NULL,           -- Cookie name (e.g., CookieConsent, td)
			category VARCHAR(50) DEFAULT NULL,   	 
			description VARCHAR(500) DEFAULT NULL,   	 
			domain VARCHAR(255) NOT NULL,
			path VARCHAR(255) NULL DEFAULT '/',
			expires DATETIME NULL DEFAULT NULL,
			max_age INT(11) NULL DEFAULT NULL,
			samesite VARCHAR(10) NULL DEFAULT NULL,
			secure TINYINT(1) NOT NULL DEFAULT 0,
			httponly TINYINT(1) NOT NULL DEFAULT 0,
			raw_name VARCHAR(255) NULL,
			edited TINYINT(1) NULL DEFAULT 0,   	 
			patterns VARCHAR(255) NOT NULL DEFAULT '[]',
			scan_timestamp INT(11) NULL DEFAULT 0
		) {$charset_collate};";
		
		dbDelta($sql);
	}
}


