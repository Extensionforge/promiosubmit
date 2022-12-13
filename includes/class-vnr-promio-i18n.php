<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://extensionforge.com
 * @since      1.0.0
 *
 * @package    Vnr_Promio
 * @subpackage Vnr_Promio/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Vnr_Promio
 * @subpackage Vnr_Promio/includes
 * @author     Steve Kraft & Peter Mertzlin <direct@extensionforge.com>
 */
class Vnr_Promio_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'vnr-promio',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
