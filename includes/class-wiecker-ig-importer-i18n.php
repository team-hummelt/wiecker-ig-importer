<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/includes
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wiecker_Ig_Importer_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain(): void
    {

		load_plugin_textdomain(
			'wiecker-ig-importer',
			false,
			dirname(plugin_basename(__FILE__), 2) . '/languages/'
		);

	}



}
