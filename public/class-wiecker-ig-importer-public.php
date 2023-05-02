<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/public
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wiecker_Ig_Importer_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $basename    The ID of this plugin.
	 */
	protected $basename;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $basename       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct(string $basename,string $version ) {

		$this->basename = $basename;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void
    {
        $settings = get_option('wp_instagram_importer_settings');
        if($settings['bootstrap_css_aktiv']){
            wp_enqueue_style('instagram-importer-bs-style', plugin_dir_url(__DIR__) .'/admin/css/bs/bootstrap.min.css', array(), $this->version, false);
        }
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void
    {
        $settings = get_option('wp_instagram_importer_settings');
        if($settings['bootstrap_js_aktiv']){
            wp_enqueue_script( 'instagram-importer-bs-script', plugin_dir_url(__DIR__) .'/admin/js/bs/bootstrap.bundle.min.js', array(), $this->version, false );
        }
	}

}
