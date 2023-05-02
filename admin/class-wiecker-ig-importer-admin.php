<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/admin
 */

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use WiIg\Importer\CronSettings;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/admin
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wiecker_Ig_Importer_Admin
{

    use CronSettings;

    /**
     * Store plugin main class to allow admin access.
     *
     * @since    2.0.0
     * @access   private
     * @var Wiecker_Ig_Importer $main The main class.
     */
    protected Wiecker_Ig_Importer $main;

    /**
     * TWIG autoload for PHP-Template-Engine
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Environment $twig TWIG autoload for PHP-Template-Engine
     */
    protected Environment $twig;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private string $version;

    protected $settings;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since      1.0.0
     */
    public function __construct(string $plugin_name, string $version, Wiecker_Ig_Importer $main, Environment $twig)
    {

        $this->basename = $plugin_name;
        $this->version = $version;
        $this->main = $main;
        $this->twig = $twig;
        $this->settings = $this->get_cron_defaults();

    }

    public function register_wp_instagram_admin_menu(): void
    {
        //delete_option('wp_instagram_importer_settings');
        if (!get_option('wp_instagram_importer_settings')) {
            update_option('wp_instagram_importer_settings', $this->settings['cron_settings']);
        }

        add_menu_page(
            __('Instagram', 'wiecker-ig-importer'),
            __('Instagram Import', 'wiecker-ig-importer'),
            get_option('wp_instagram_importer_settings')['selected_user_role'],
            'wp-instagram-imports',
            '',
            $this->get_svg_icons('instagram')
            , 23
        );

        $hook_suffix = add_submenu_page(
            'wp-instagram-imports',
            __('Instagram Settings', 'wiecker-ig-importer'),
            __('Instagram Settings', 'wiecker-ig-importer'),
            get_option('wp_instagram_importer_settings')['selected_user_role'],
            'wp-instagram-imports',
            array($this, 'wp_instagram_imports_startseite'));

        add_action('load-' . $hook_suffix, array($this, 'wp_instagram_import_load_ajax_admin_options_script'));
    }

    public function wp_instagram_imports_startseite(): void
    {

        $data = [
            's' => get_option('wp_instagram_importer_settings'),
            'select' => $this->settings,
            'db' => WP_INSTAGRAM_IMPORTER_DB_VERSION,
            'version' => $this->version
        ];

        try {
            $template = $this->twig->render('@templates/instagram.html.twig', $data);
            echo $this->html_compress_template($template);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function wp_instagram_import_load_ajax_admin_options_script(): void
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        $title_nonce = wp_create_nonce('instagram_import_admin_handle');
        wp_register_script('instagram-importer-admin-ajax-script', '', [], '', true);
        wp_enqueue_script('instagram-importer-admin-ajax-script');
        wp_localize_script('instagram-importer-admin-ajax-script', 'instagram_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $title_nonce,
            'data_table' => plugin_dir_url(__FILE__) . 'js/tools/DataTablesGerman.json',
            'js_lang' => $this->js_language()
        ));
    }

    /**
     * @throws Exception
     */
    public function admin_ajax_InstagramImporter(): void
    {
        check_ajax_referer('instagram_import_admin_handle');
        require 'Ajax/WP_Instagram_Imports_Admin_Ajax.php';
        $adminAjaxHandle = WP_Instagram_Imports_Admin_Ajax::admin_ajax_instance($this->basename, $this->main, $this->twig);
        wp_send_json($adminAjaxHandle->admin_ajax_handle());
    }

    public function set_instagram_oauth_trigger(): void
    {
        global $wp;
        $wp->add_query_var('code');
    }

    public function instagram_importer_oauth_trigger_check(): void
    {
        if (get_query_var('code')) {
            $oauth = get_option($this->basename . '/');
            $code = get_query_var('code');
            $code = substr($code, 0, strrpos($code, '#'));

            // apply_filters($this->basename.'/make_ebay_import_import', get_query_var('code'));
            exit();
        }
    }

    /**
     * Register the Update-Checker for the Plugin.
     *
     * @since    1.0.0
     */
    public function instagram_importer_update_checker()
    {

        if (get_option("{$this->basename}_update_config") && get_option($this->basename . '_update_config')->update->update_aktiv) {
            $securityHeaderUpdateChecker = PucFactory::buildUpdateChecker(
                get_option("{$this->basename}_update_config")->update->update_url_git,
                WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->basename . DIRECTORY_SEPARATOR . $this->basename . '.php',
                $this->basename
            );

            switch (get_option("{$this->basename}_update_config")->update->update_type) {
                case '1':
                    $securityHeaderUpdateChecker->getVcsApi()->enableReleaseAssets();
                    break;
                case '2':
                    $securityHeaderUpdateChecker->setBranch(get_option("{$this->basename}_update_config")->update->branch_name);
                    break;
            }
        }
    }

    /**
     * add plugin upgrade notification
     */

    public function instagram_importer_show_upgrade_notification($current_plugin_metadata, $new_plugin_metadata)
    {

        if (isset($new_plugin_metadata->upgrade_notice) && strlen(trim($new_plugin_metadata->upgrade_notice)) > 0) {
            // Display "upgrade_notice".
            echo sprintf('<span style="background-color:#d54e21;padding:10px;color:#f9f9f9;margin-top:10px;display:block;"><strong>%1$s: </strong>%2$s</span>', esc_attr('Important Upgrade Notice', 'wp-security-header'), esc_html(rtrim($new_plugin_metadata->upgrade_notice)));

        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        wp_enqueue_script('jquery');
        wp_enqueue_style('instagram-importer-admin-bs-style', plugin_dir_url(__FILE__) . 'css/bs/bootstrap.min.css', array(), $this->version, false);
        wp_enqueue_style('instagram-importer-animate', plugin_dir_url(__FILE__) . 'css/tools/animate.min.css', array(), $this->version);
        wp_enqueue_style('instagram-importer-swal2', plugin_dir_url(__FILE__) . 'css/tools/sweetalert2.min.css', array(), $this->version, false);

        wp_enqueue_style('instagram-importer-bootstrap-icons-style', WP_INSTAGRAM_IMPORTER_PLUGIN_URL . 'includes/vendor/twbs/bootstrap-icons/font/bootstrap-icons.css', array(), $this->version);
        wp_enqueue_style('instagram-importer-font-awesome-icons-style', WP_INSTAGRAM_IMPORTER_PLUGIN_URL . 'includes/vendor/components/font-awesome/css/font-awesome.min.css', array(), $this->version);
        wp_enqueue_style('instagram-importer-admin-dashboard-style', plugin_dir_url(__FILE__) . 'css/admin-dashboard-style.css', array(), $this->version, false);
        wp_enqueue_style('instagram-importer-admin-data-table', plugin_dir_url(__FILE__) . 'css/tools/dataTables.bootstrap5.min.css', array(), $this->version, false);

        wp_enqueue_script('instagram-importer-bs', plugin_dir_url(__FILE__) . 'js/bs/bootstrap.bundle.min.js', array(), $this->version, true);
        wp_enqueue_script('instagram-importer-swal2-script', plugin_dir_url(__FILE__) . 'js/tools/sweetalert2.all.min.js', array(), $this->version, true);
        wp_enqueue_script('js-hupa-data-table', plugin_dir_url(__FILE__) . 'js/tools/data-table/jquery.dataTables.min.js', array(), $this->version, true);
        wp_enqueue_script('js-hupa-bs-data-table', plugin_dir_url(__FILE__) . 'js/tools/data-table/dataTables.bootstrap5.min.js', array(), $this->version, true);
        //wp_enqueue_script( 'admin-rss-importer-table', plugin_dir_url( __FILE__ ) . '/js/rssTable.js', false, $this->version, true );
        wp_enqueue_script($this->basename, plugin_dir_url(__FILE__) . 'js/wiecker-ig-importer-admin.js', array('jquery'), $this->version, false);

    }

    /**
     * @param $name
     *
     * @return string
     */
    private static function get_svg_icons($name): string
    {
        $icon = '';
        switch ($name) {
            case'instagram':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
                         <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/>
                         </svg>';
                break;

            default:
        }

        return 'data:image/svg+xml;base64,' . base64_encode($icon);

    }

    protected function html_compress_template(string $string): string
    {
        if (!$string) {
            return $string;
        }

        return preg_replace(['/<!--(.*)-->/Uis', "/[[:blank:]]+/"], ['', ' '], str_replace([
            "\n",
            "\r",
            "\t"
        ], '', $string));
    }

}
