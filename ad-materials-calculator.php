<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://agencydigital.tech/
 * @since             1.0.0
 * @package           Ad_Materials_Calculator
 *
 * @wordpress-plugin
 * Plugin Name:       AD Materials Calculator
 * Plugin URI:        https://agencydigital.tech/
 * Description:       Calculates project-specific Terrazzo materials in WooCommerce and adds the calculated products to the cart as a grouped set.
 * Version:           1.3.4
 * Author:            Agency Digital
 * Author URI:        https://agencydigital.tech//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ad-materials-calculator
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AD_MATERIALS_CALCULATOR_VERSION', '1.3.4' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ad-materials-calculator-activator.php
 */
function activate_ad_materials_calculator() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ad-materials-calculator-activator.php';
	Ad_Materials_Calculator_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ad-materials-calculator-deactivator.php
 */
function deactivate_ad_materials_calculator() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ad-materials-calculator-deactivator.php';
	Ad_Materials_Calculator_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ad_materials_calculator' );
register_deactivation_hook( __FILE__, 'deactivate_ad_materials_calculator' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ad-materials-calculator.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ad_materials_calculator() {

	$plugin = new Ad_Materials_Calculator();
	$plugin->run();

}
run_ad_materials_calculator();
