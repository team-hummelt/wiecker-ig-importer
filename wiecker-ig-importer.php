<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wwdh.de
 * @since             1.0.0
 * @package           Wiecker_Ig_Importer
 *
 * @wordpress-plugin
 * Plugin Name:       WP Instagram Importer
 * Plugin URI:        https://wwdh/plugins/wiecker-ig-importer
 * Description:       The plugin imports Instagram posts and saves them as a WordPress post.
 * Version:           1.0.0
 * Author:            Jens Wiecker
 * Author URI:        https://wwdh.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wiecker-ig-importer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

const WP_INSTAGRAM_MAX_SYNC_COUNT = 10;

/**
 * Plugin Database-Version.
 */
const WP_INSTAGRAM_IMPORTER_DB_VERSION = '1.0.0';

/**
 * PHP minimum requirement for the plugin.
 */
const WP_INSTAGRAM_IMPORTER_MIN_PHP_VERSION = '7.4';

/**
 * WordPress minimum requirement for the plugin.
 */
const WP_INSTAGRAM_IMPORTER_MIN_WP_VERSION = '5.6';

/**
 * PLUGIN ROOT PATH.
 */
define('WP_INSTAGRAM_IMPORTER_PLUGIN_DIR', dirname(__FILE__));
/**
 * PLUGIN URL.
 */
define('WP_INSTAGRAM_IMPORTER_PLUGIN_URL', plugins_url('wiecker-ig-importer').'/');
/**
 * PLUGIN SLUG.
 */
define('WP_INSTAGRAM_IMPORTER_SLUG_PATH', plugin_basename(__FILE__));
/**
 * PLUGIN Basename.
 */
define('WP_INSTAGRAM_IMPORTER_BASENAME', plugin_basename(__DIR__));

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wiecker-ig-importer-activator.php
 */
function activate_wiecker_ig_importer(): void
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wiecker-ig-importer-activator.php';
	Wiecker_Ig_Importer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wiecker-ig-importer-deactivator.php
 */
function deactivate_wiecker_ig_importer(): void
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wiecker-ig-importer-deactivator.php';
	Wiecker_Ig_Importer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wiecker_ig_importer' );
register_deactivation_hook( __FILE__, 'deactivate_wiecker_ig_importer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wiecker-ig-importer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wiecker_ig_importer(): void
{

	$plugin = new Wiecker_Ig_Importer();
	$plugin->run();

}
run_wiecker_ig_importer();
