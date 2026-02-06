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
define( 'DB_NAME', 'jivr' );

/** MySQL database username */
define( 'DB_USER', 'jivr' );

/** MySQL database password */
define( 'DB_PASSWORD', 'jivr' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'aKhD,2jmLpvPXgLC2{%H{{&J5oDK8^hj} lmgO@Wk[EyajH,Vf>3C8&8;9x%_jE7' );
define( 'SECURE_AUTH_KEY',  '}8v8vp$yM?nu^K4NGU]j3VtVE9aMD/^Cg&UV&UXKXf}o4+`b#h%hX$2v.,ACh/N[' );
define( 'LOGGED_IN_KEY',    'R]sM=KK;2[4j]a/d9GFP&8e$p? 6=RL<4Ok:{c!8gj3LSC(1u!3@]?Ww6fE&Y2Px' );
define( 'NONCE_KEY',        'DU+}#eYNJhzorWCIc</`no6V;6v5/n1Hk@{Ve8SYIX=w@>uf5.Y4L;//y8w$AtTc' );
define( 'AUTH_SALT',        'V||ZNO_` lA&XW]gwX:{ex/W{CcR_8$D/TF%4TlcK_ZfVj56(dg8^gl?{c=aBbOt' );
define( 'SECURE_AUTH_SALT', ')6+_/d|j7#dkQ5ROj;Zl>Y1FN?h,pWfzt7kh1jXH2RytsVT^m58,p<,rq]iA#9LI' );
define( 'LOGGED_IN_SALT',   '(I51:tSWvh^K:!d[8S|K]K-hB0|eKv{Bk2^9xd>D ._0V]Hr[<xo?7R_9g9a5S%.' );
define( 'NONCE_SALT',       ';rKb[ 14>ff$l@T6N/mvpss~qSXL*!W<Of|RWg<GXQVK]:2k4e;iCc#tMx$>.,>I' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
