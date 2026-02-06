<?php
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
define('DB_NAME', 'inbiz');

/** MySQL database username */
define('DB_USER', 'inbiz');

/** MySQL database password */
define('DB_PASSWORD', 'inbiz');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

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
define('AUTH_KEY',         'nm%nXP1>@o~/z2sk0Y+@?{~?~c(D/Qj?sy>uz5W^.M|kA?*iXBbYABKCy>4Z*:n>');
define('SECURE_AUTH_KEY',  'UNd/,_zp,8fko;(+C7WbN&yWKxQc% l(h*S~ce`#zPZN4C}A?r>o.sBsMj?K*rP!');
define('LOGGED_IN_KEY',    'Z>mV^||0^FrH+oBEHZBDv!w>YxhGvr8??u .QeLDw:GL7HHhTH#hW_:Evi|!.Vq%');
define('NONCE_KEY',        '.fVd4DI*sZvO[F;Q!@p<sCks6$h!rh;pGVr(726nV/lA4=ST;cLZKAxc6ibk,![]');
define('AUTH_SALT',        'K-`/iO!48Zq@`OUk~jcVZ`rs6&aVwg~H{hnFM4MXpxX_K$ffhkTU;85zOB.vmK8!');
define('SECURE_AUTH_SALT', '!NdU|,YYhpu]<%$Lusf&~WKXlH/xPiA_AvJMy T7RmbwQ=gTJ}<>Y[f:<CR;EKS9');
define('LOGGED_IN_SALT',   '{=!3G.;8lWCl&E#&K8En dLcfo|^S>#$PN/_O7,iKVq b#@HiBKJGg@XjKRm&z#8');
define('NONCE_SALT',       'R&Avu^;DaD]7,%DGS8{L(K=1xlmQ/p>QM-y#PxE/a4mz$y{IJArc7do2:(}9@^Y!');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

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
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

define( 'WP_POST_REVISIONS', 0 );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
