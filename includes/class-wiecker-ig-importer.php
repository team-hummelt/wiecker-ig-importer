<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/includes
 */

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use WiIg\Endpoint\Srv_Api_Endpoint;
use WiIg\Helper\RSS_Importer_Helper;
use WiIg\Importer\Instagram_Import_Cronjob;
use WiIg\Importer\Register_Instagram_Importer_Callback;
use WiIg\Importer\WP_Instagram_Importer_Rest_Endpoint;
use WiIg\InstagramApi\Instagram_Api_Data;
use WiIg\SrvApi\Endpoint\Make_Remote_Exec;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/includes
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wiecker_Ig_Importer
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Wiecker_Ig_Importer_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected Wiecker_Ig_Importer_Loader $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected string $plugin_name;

    /**
     * The Public API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $id_rsa plugin API ID_RSA.
     */
    private string $id_rsa;

    /**
     * The PLUGIN API ID_RSA.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $id_plugin_rsa plugin API ID_RSA.
     */
    protected string $id_plugin_rsa;

    /**
     * The PLUGIN API ID_RSA.
     *
     * @since    1.0.0
     * @access   protected
     * @var      object $plugin_api_config plugin API ID_RSA.
     */
    protected object $plugin_api_config;

    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $srv_api_dir plugin Slug Path.
     */
    private string $srv_api_dir;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @var object The main class.
     */
    public object $main;

    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_slug plugin Slug Path.
     */
    protected string $plugin_slug;


    /**
     * The current database version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $db_version The current database version of the plugin.
     */
    protected string $db_version;

    /**
     * TWIG autoload for PHP-Template-Engine
     * the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      Environment $twig TWIG autoload for PHP-Template-Engine
     */
    private Environment $twig;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected string $version = '';

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @throws LoaderError
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->plugin_name = WP_INSTAGRAM_IMPORTER_BASENAME;
        $this->plugin_slug = WP_INSTAGRAM_IMPORTER_SLUG_PATH;
        $this->main = $this;

        /**
         * Currently plugin version.
         * Start at version 1.0.0 and use SemVer - https://semver.org
         * Rename this for your plugin and update it as you release new versions.
         */
        $plugin = get_file_data(plugin_dir_path(dirname(__FILE__)) . $this->plugin_name . '.php', array('Version' => 'Version'), false);
        if (!$this->version) {
            $this->version = $plugin['Version'];
        }

        if (defined('WP_INSTAGRAM_IMPORTER_DB_VERSION')) {
            $this->db_version = WP_INSTAGRAM_IMPORTER_DB_VERSION;
        } else {
            $this->db_version = '1.0.0';
        }

        $this->check_dependencies();
        $this->load_dependencies();

        $twigAdminDir = plugin_dir_path(dirname(__FILE__)) . 'admin' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR;
        $twig_loader = new FilesystemLoader($twigAdminDir);
        $twig_loader->addPath($twigAdminDir . 'Templates', 'templates');
        $this->twig = new Environment($twig_loader);
        $language = new TwigFilter('__', function ($value) {
            return __($value, 'wiecker-ig-importer');
        });
        $this->twig->addFilter($language);

        //JOB SRV API
        $this->srv_api_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'admin' . DIRECTORY_SEPARATOR . 'srv-api' . DIRECTORY_SEPARATOR;

        if ( is_file( $this->srv_api_dir . 'id_rsa' . DIRECTORY_SEPARATOR . $this->plugin_name . '_id_rsa' ) ) {
            $this->id_plugin_rsa = base64_encode( $this->srv_api_dir . DIRECTORY_SEPARATOR . 'id_rsa' . $this->plugin_name . '_id_rsa' );
        } else {
            $this->id_plugin_rsa = '';
        }
        if ( is_file( $this->srv_api_dir . 'config' . DIRECTORY_SEPARATOR . 'config.json' ) ) {
            $this->plugin_api_config = json_decode( file_get_contents( $this->srv_api_dir . 'config' . DIRECTORY_SEPARATOR . 'config.json' ) );
        } else {
            $this->plugin_api_config = (object) [];
        }


        $this->set_locale();
        $this->register_wp_remote_exec();
        $this->register_wp_instagram_importer_rest_endpoint();

        $this->register_instagram_wp_importer_helper();
        $this->register_instagram_api();
        $this->register_cron_instagram_importer();
        $this->register_wp_instagram_importer_gutenberg_tools();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Wiecker_Ig_Importer_Loader. Orchestrates the hooks of the plugin.
     * - Wiecker_Ig_Importer_i18n. Defines internationalization functionality.
     * - Wiecker_Ig_Importer_Admin. Defines all hooks for the admin area.
     * - Wiecker_Ig_Importer_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies(): void
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wiecker-ig-importer-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wiecker-ig-importer-i18n.php';

        /**
         * The code that runs during plugin activation.
         * This action is documented in includes/class-hupa-teams-activator.php
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wiecker-ig-importer-activator.php';

        /**
         * The Settings Trait
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/CronSettings.php';



        /**
         * Composer-Autoload
         * Composer Vendor for Theme|Plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/vendor/autoload.php';

        /**
         * The Helper for core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class_instagram_importer_helper.php';

        /**
         * The Cronjob Class
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Cronjob/class_instagram_import_cronjob.php';

        /**
         * Plugin WP Gutenberg Block Callback
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Gutenberg/class_register_instagram_importer_callback.php';

        /**
         * Plugin WP Gutenberg Sidebar|Block
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Gutenberg/class_register_instagram_importer_gutenberg_tools.php';


        /**
         * WP Ebay Importer Login REST-Endpoint
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/Gutenberg/class_wp_instagram_importer_rest_endpoint.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wiecker-ig-importer-admin.php';

        /**
         * The Instagram API class
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Instagram/class_instagram_api_data.php';


        //JOB SRV API Endpoint
        /**
         * SRV WP-Remote Exec
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/srv-api/config/class_make_remote_exec.php';

        /**
         * SRV WP-Remote API
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/srv-api/class_srv_api_endpoint.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wiecker-ig-importer-public.php';

        $this->loader = new Wiecker_Ig_Importer_Loader();

    }

    /**
     * Check PHP and WordPress Version
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function check_dependencies(): void
    {
        global $wp_version;
        if (version_compare(PHP_VERSION, WP_INSTAGRAM_IMPORTER_MIN_PHP_VERSION, '<') || $wp_version < WP_INSTAGRAM_IMPORTER_MIN_WP_VERSION) {
            $this->maybe_self_deactivate();
        }
    }

    /**
     * Self-Deactivate
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function maybe_self_deactivate(): void
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins($this->plugin_slug);
        add_action('admin_notices', array($this, 'self_deactivate_notice'));
    }

    /**
     * Self-Deactivate Admin Notiz
     * of the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function self_deactivate_notice(): void
    {
        echo sprintf('<div class="notice notice-error is-dismissible" style="margin-top:5rem"><p>' . __('This plugin has been disabled because it requires a PHP version greater than %s and a WordPress version greater than %s. Your PHP version can be updated by your hosting provider.', 'wp-rss-feed-importer') . '</p></div>', WP_INSTAGRAM_IMPORTER_MIN_PHP_VERSION, WP_INSTAGRAM_IMPORTER_MIN_WP_VERSION);
        exit();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Wiecker_Ig_Importer_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale(): void
    {

        $plugin_i18n = new Wiecker_Ig_Importer_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks(): void
    {
        $instagramImporterActivator = new Wiecker_Ig_Importer_Activator();
        $this->loader->add_action('init', $instagramImporterActivator, 'register_wp_instagram_post_type');
        $this->loader->add_action('init', $instagramImporterActivator, 'register_wp_instagram_importer_taxonomy');

        $plugin_admin = new Wiecker_Ig_Importer_Admin($this->get_plugin_name(), $this->get_version(), $this->main, $this->twig);
        $this->loader->add_action('admin_menu', $plugin_admin, 'register_wp_instagram_admin_menu');
        $this->loader->add_action('wp_ajax_nopriv_InstagramImporter', $plugin_admin, 'admin_ajax_InstagramImporter');
        $this->loader->add_action('wp_ajax_InstagramImporter', $plugin_admin, 'admin_ajax_InstagramImporter');

        $this->loader->add_action('init', $plugin_admin, 'set_instagram_oauth_trigger');
        $this->loader->add_action('template_redirect', $plugin_admin, 'instagram_importer_oauth_trigger_check');

        $registerWpInstagramEndpoint = new WP_Instagram_Importer_Rest_Endpoint($this->plugin_name, $this->main);
        $this->loader->add_action('rest_api_init', $registerWpInstagramEndpoint, 'register_wp_instagram_importer_routes');

        global $registerInstagramImporterCallback;
        $registerInstagramImporterCallback = Register_Instagram_Importer_Callback::instance($this->plugin_name, $this->version, $this->main);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks(): void
    {

        $plugin_public = new Wiecker_Ig_Importer_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

    }

    /**
     * Register Instagram Helper
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_instagram_wp_importer_helper(): void
    {
        global $instHelper;
        $instHelper = RSS_Importer_Helper::instance($this->plugin_name, $this->version, $this->main);
        $this->loader->add_filter($this->plugin_name . '/get_import_taxonomy', $instHelper, 'fn_get_import_taxonomy', 10, 2);
        $this->loader->add_filter($this->plugin_name . '/get_next_cron_time', $instHelper, 'import_get_next_cron_time');
        $this->loader->add_filter( $this->plugin_name.'/get_random_id', $instHelper, 'getRandomString' );
        $this->loader->add_filter( $this->plugin_name.'/generate_random_id', $instHelper, 'getGenerateRandomId',10,4 );
        $this->loader->add_filter( $this->plugin_name.'/ArrayToObject', $instHelper, 'arrayToObject' );
        $this->loader->add_filter( $this->plugin_name.'/object2Array', $instHelper, 'object2array_recursive' );
        $this->loader->add_filter( $this->plugin_name.'/date_format_language', $instHelper, 'date_format_language', 10, 3 );
        $this->loader->add_filter( $this->plugin_name.'/PregWhitespace', $instHelper, 'fnPregWhitespace' );
        $this->loader->add_filter( $this->plugin_name.'/get_post_by_instagram_id', $instHelper, 'fn_get_post_by_instagram_id',10,2 );
        $this->loader->add_filter( $this->plugin_name.'/get_posts_by_taxonomy', $instHelper, 'get_posts_by_taxonomy',10,2 );

        $this->loader->add_action( 'before_delete_post', $instHelper, 'instagram_import_delete_post_before',10,2);
        $this->loader->add_filter( $this->plugin_name.'/get_instagram_import_meta', $instHelper, 'get_instagram_import_meta', 10 ,2 );
    }

    /**
     * Register all the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_cron_instagram_importer(): void {

        if ($this->check_wp_cron()) {
            $ebayCron = new Instagram_Import_Cronjob($this->plugin_name, $this->main);
            $this->loader->add_filter($this->plugin_name . '/instagram_run_schedule_task', $ebayCron, 'fn_instagram_run_schedule_task');
            $this->loader->add_filter($this->plugin_name . '/instagram_wp_un_schedule_task', $ebayCron, 'fn_instagram_wp_un_schedule_task');
            $this->loader->add_filter($this->plugin_name . '/instagram_wp_delete_task', $ebayCron, 'fn_instagram_wp_delete_task');
        }
    }

    /**
     * Register Eby Importer Gutenberg Tools
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_instagram_importer_gutenberg_tools(): void
    {
        global $gutenbergTools;
        $gutenbergTools = new Register_Instagram_Importer_Gutenberg_Tools($this->plugin_name, $this->version, $this->main);

        //META Fields
        $this->loader->add_action( 'init', $gutenbergTools, 'register_instagram_imports_meta_fields');

        $this->loader->add_action('init', $gutenbergTools, 'instagram_importer_gutenberg_register_sidebar');
        $this->loader->add_action('init', $gutenbergTools, 'register_instagram_importer_block_type');
        $this->loader->add_action('enqueue_block_editor_assets', $gutenbergTools, 'instagram_importer_block_type_scripts');
    }

    private function register_instagram_api(): void
    {
        global $instagramApiData;
        $instagramApiData = Instagram_Api_Data::instance($this->plugin_name, $this->version, $this->main);
        $this->loader->add_filter($this->plugin_name . '/instagram_api_data', $instagramApiData, 'fn_instagram_api_data', 10, 2);
        //Sync Instagram Cronjob
        $this->loader->add_action('instagram_import_sync', $instagramApiData, 'instagram_import_synchronisation',0);
        $this->loader->add_filter($this->plugin_name . '/make_instagram_import_import', $instagramApiData, 'fn_make_instagram_import_synchronisation');
    }

    /**
     * Register API SRV Rest-Api Endpoints
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_remote_exec(): void
    {
        global $wpRemoteExec;
        $wpRemoteExec = Make_Remote_Exec::instance($this->plugin_name, $this->get_version(), $this->main);
    }

    /**
     * Register WP_REST ENDPOINT
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_instagram_importer_rest_endpoint(): void
    {
        global $rss_importer_public_endpoint;
        $rss_importer_public_endpoint = new Srv_Api_Endpoint($this->plugin_name, $this->version, $this->main);
        $this->loader->add_action('rest_api_init', $rss_importer_public_endpoint, 'register_routes');

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run(): void
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name(): string
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Wiecker_Ig_Importer_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader(): Wiecker_Ig_Importer_Loader
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version(): string
    {
        return $this->version;
    }

    public function get_plugin_api_config(): object
    {
        return $this->plugin_api_config;
    }

    /**
     * @return bool
     */
    private function check_wp_cron(): bool
    {
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            return false;
        } else {
            return true;
        }
    }

}
