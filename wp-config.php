<?php
define('WP_CACHE', true); // Added by SpeedyCache

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'inalncwh_lifeandstyle' );

/** Database username */
define( 'DB_USER', 'inalncwh_lifeandstyle' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'kbzo6zarje7c3ncn6iocx3esza8wsu4wfqjq15c8lbqrcuizfwbjodg4tzv7mjtp' );
define( 'SECURE_AUTH_KEY',  'n1yqh2wwlob8ohgvls9lmecibztpwb0jyxtofxgmhxhxgslwnvvicx5p0zwrpvdh' );
define( 'LOGGED_IN_KEY',    'uegxgkakgxfzfiecgsmw8gf4f8z6hhabfycejcnu6fupcaqvtx8uocxiaue5aove' );
define( 'NONCE_KEY',        'l8mtqkbgizk91wxsz6o9gpmzvbwknoygb1hwtwgevq800vjx0pfujbcwblxhlrzu' );
define( 'AUTH_SALT',        'btucwueoltog2t3diu0eljvwybue9mgayke2yppgi2ddzxewtebpyndz1elkiede' );
define( 'SECURE_AUTH_SALT', 'jxwsrzuotx9dx33vw72q8mkv60ndngj3leqzpv9yaeuakjorw4i0pzzo8cjbki9o' );
define( 'LOGGED_IN_SALT',   '0hhs5aorporeqqs8c3j4hoxwh5or2q0chqe9cm5bjmov9cx9yozmzr1x9us9kpro' );
define( 'NONCE_SALT',       '6j9z7rxnpylqcqzxjaowcw00sst0vi6me6th6jb7bcpqttcj5kfs3art2rambpb7' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'lns_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
define('DISALLOW_FILE_EDIT', true);
