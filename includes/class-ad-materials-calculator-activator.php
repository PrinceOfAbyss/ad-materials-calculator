<?php

/**
 * Fired during plugin activation
 *
 * @link       https://agencydigital.tech/
 * @since      1.0.0
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/includes
 * @author     Agency Digital <info@agencydigital.tech>
 */
class Ad_Materials_Calculator_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ad-materials-calculator-share-links.php';

		Ad_Materials_Calculator_Share_Links::install_table();
	}

}
