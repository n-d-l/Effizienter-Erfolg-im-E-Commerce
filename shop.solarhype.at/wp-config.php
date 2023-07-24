<?php
define('WP_CACHE', true); // WP-Optimize Cache
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */
// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wordpress_16');
/** MySQL database username */
define('DB_USER', 'wordpress_d9');
/** MySQL database password */
define('DB_PASSWORD', '2ZjcIE4e0#');
/** MySQL hostname */
define('DB_HOST', 'localhost:3306');
/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');
/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');
/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'n39!BPv^iZs^Wd@Z@PgJn9AF86FzmnmZ(B@Lc&iGB7)j4)xzZ7ajmK4x^YUpQTAe');
define('SECURE_AUTH_KEY',  'K0tn3DXhBX*4X62P22(d1p30!RuWdsdYXY34S8XSNb7t5FBQwYEwAOn)1VLHTqKu');
define('LOGGED_IN_KEY',    '*qqjO#y2u@TIhjyeZQbuuw3fKoxowGMBw**Vge*LxhAa!mVpyO3i5h5F!ai(Q^Yi');
define('NONCE_KEY',        'GXFlPx!5PKM0D@y)T@1WU81pIf08umWyg9fwiS@rX7wW7zXUIXCX(CvX4j2K4JC*');
define('AUTH_SALT',        'dq@Z(rp4p4)dgIz&1SF8Gj8GTZ2uhinK60j4zk&D@pThXG1y4xwLLId06yxyYaCj');
define('SECURE_AUTH_SALT', 'fjkmZoThcWI4if5Lp%7HsvWJOxuQOOfRfjDqMWqhxCvK3KyhKCZ3C5KltCqEv&ss');
define('LOGGED_IN_SALT',   'BN7F9cCp3Xt&cZEqJ4Wbw82(GvisMXmTG)uogNMN^3xCQ2SgH8z(8!!Bq2bHrNdr');
define('NONCE_SALT',       '%(9MjqcAm*PCdTBRsw9&7xLWYV5riOwk7aI0L)u#mHceSj9iU@EwqjjKrLJ*gebr');
/**#@-*/
/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '1CMhO0406M_';
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);
/* That's all, stop editing! Happy blogging. */
/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');
/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
define( 'WP_ALLOW_MULTISITE', true );
define ('FS_METHOD', 'direct');