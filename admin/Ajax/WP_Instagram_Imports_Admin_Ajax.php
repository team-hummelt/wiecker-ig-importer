<?php


/**
 * The admin-specific functionality of the theme.
 *
 * @link       https://wwdh.de
 */

defined('ABSPATH') or die();

use Exception;

use WiIg\Importer\CronSettings;

use stdClass;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use WP_Term_Query;

class WP_Instagram_Imports_Admin_Ajax
{
    private static $admin_ajax_instance;
    private string $method;
    private object $responseJson;
    use CronSettings;

    /**
     * Store plugin main class to allow child access.
     *
     * @var Environment $twig TWIG autoload for PHP-Template-Engine
     */
    protected Environment $twig;

    protected Wiecker_Ig_Importer $main;

	/**
	 * The ID of this Plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $basename The ID of this theme.
	 */
	protected string $basename;

    /**
     * @return static
     */
    public static function admin_ajax_instance(string $basename, Wiecker_Ig_Importer $main, Environment $twig): self
    {
        if (is_null(self::$admin_ajax_instance)) {
            self::$admin_ajax_instance = new self($basename, $main, $twig);
        }
        return self::$admin_ajax_instance;
    }

    public function __construct(string $basename, Wiecker_Ig_Importer $main, Environment $twig)
    {
        $this->main = $main;
        $this->twig = $twig;
		$this->basename = $basename;
        $this->method = filter_input(INPUT_POST, 'method', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
        $this->responseJson = (object)['status' => false, 'msg' => date('H:i:s', current_time('timestamp')), 'type' => $this->method];
    }

    /**
     * @throws Exception
     */
    public function admin_ajax_handle()
    {
        if (!method_exists($this, $this->method)) {
            throw new Exception("Method not found!#Not Found");
        }
        return call_user_func_array(self::class . '::' . $this->method, []);
    }

    private function plugin_settings():object
    {
        $this->responseJson->target = filter_input(INPUT_POST, 'target', FILTER_UNSAFE_RAW);
        $this->responseJson->parent = filter_input(INPUT_POST, 'parent', FILTER_UNSAFE_RAW);

        $taxonomy = apply_filters( $this->basename . '/get_import_taxonomy', 'instagram-kategorie', 'instagram' );
        $nextTime = apply_filters( $this->basename . '/get_next_cron_time', 'instagram_import_sync' );
        $next_time  = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + $nextTime );
        $next_date  = date( 'd.m.Y', strtotime( $next_time ) );
        $next_clock = date( 'H:i:s', strtotime( $next_time ) );
        $data  = [
            's'  => get_option( 'wp_instagram_importer_settings' ),
            'dateTime' => $next_time,
            'select' => [
                'select_api_sync_interval' => $this->get_cron_defaults( 'select_api_sync_interval' ),
                'select_user_role' => $this->get_cron_defaults( 'select_user_role' ),
                'select_max_sync' => $this->get_cron_defaults( 'max_post_sync' ),
                'select_taxonomy' => $taxonomy
            ]
        ];

        try {
            $template  = $this->twig->render( '@templates/importe-settings.html.twig', $data );
            $this->responseJson->template = $this->html_compress_template( $template );
        } catch ( LoaderError|SyntaxError|RuntimeError $e ) {
            $this->responseJson->msg = $e->getMessage();
            return $this->responseJson;
        } catch ( Throwable $e ) {
            $this->responseJson->msg = $e->getMessage();
            return $this->responseJson;
        }

        $this->responseJson->next_date = $next_date;
        $this->responseJson->next_clock = $next_clock;
        $this->responseJson->next_time = $next_time;
        $this->responseJson->status = true;
        return $this->responseJson;
    }

    private function oauth_settings():object
    {
        $this->responseJson->target = filter_input(INPUT_POST, 'target', FILTER_UNSAFE_RAW);
        $this->responseJson->parent = filter_input(INPUT_POST, 'parent', FILTER_UNSAFE_RAW);
        $handle = filter_input(INPUT_POST, 'handle', FILTER_UNSAFE_RAW);
        $this->responseJson->handle = $handle;

        $saveSettings = get_option($this->basename.'/instagram_oauth');
        if($saveSettings){
            $url = $saveSettings['callback_url'];
            $app_id = $saveSettings['app_id'];
            $app_secret = $saveSettings['app_secret'];
            $authentifiziert = $saveSettings['authentifiziert'];
            $access_token = $saveSettings['access_token'];
        } else {
            $url = site_url();
            $app_id = '';
            $app_secret = '';
            $access_token = '';
            $authentifiziert = false;
        }

        $data = [
            'callback_url' => $url,
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'access_token' => $access_token,
            'authentifiziert' => $authentifiziert
        ];
        try {
            $template  = $this->twig->render( '@templates/oauth-settings.html.twig', $data );
            $this->responseJson->template = $this->html_compress_template( $template );
        } catch ( LoaderError|SyntaxError|RuntimeError $e ) {
            $this->responseJson->msg = $e->getMessage();

            return $this->responseJson;
        } catch ( Throwable $e ) {
            $this->responseJson->msg = $e->getMessage();

            return $this->responseJson;
        }

        $this->responseJson->status = true;
        return $this->responseJson;
    }

    private function oauth_import_settings_handle():object
    {
        $callback_url = filter_input(INPUT_POST, 'callback_url', FILTER_VALIDATE_URL);
        $app_id = filter_input(INPUT_POST, 'app_id', FILTER_UNSAFE_RAW);
        $app_secret = filter_input(INPUT_POST, 'app_secret', FILTER_UNSAFE_RAW);
        $access_token = filter_input(INPUT_POST, 'access_token', FILTER_UNSAFE_RAW);
        if(!$callback_url){
            $this->responseJson->msg = 'Ajax Übertragungsfehler. (Ajx - '.__LINE__.')';
            return $this->responseJson;
        }
       /* if(!$app_id){
            $this->responseJson->msg = 'Ungültige App-ID. (Ajx - '.__LINE__.')';
            return $this->responseJson;
        }
        if(!$app_secret){
            $this->responseJson->msg = 'App-Secret ungültig. (Ajx - '.__LINE__.')';
            return $this->responseJson;
        }*/
        $saveSettings = get_option($this->basename.'/instagram_oauth');
        $authentifiziert = false;
        if($saveSettings){
            if($saveSettings['authentifiziert']){
                $authentifiziert = true;
            }
        }
        $settings = [
            'callback_url' => $callback_url,
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'access_token' => $access_token,
            'authentifiziert' => $authentifiziert
        ];
        $access_token ? $show_btn = true : $show_btn = false;
        update_option($this->basename.'/instagram_oauth', $settings);
        $this->responseJson->callback_url = sprintf('https://api.instagram.com/oauth/authorize?client_id=%s&redirect_uri=%s/&scope=user_profile,user_media&response_type=code"',$app_id, $callback_url);
        $this->responseJson->status = true;
        $this->responseJson->show_btn = $show_btn;
        return $this->responseJson;
    }

    private function sync_instagram_media():object
    {
        $import = apply_filters($this->basename.'/make_instagram_import_import', null);
        $this->responseJson->title = 'Instagram-Synchronisation';
        if($import){
            $this->responseJson->status = true;
            $this->responseJson->msg = 'Synchronisation erfolgreich ausgeführt.';
            return $this->responseJson;
        }
        $this->responseJson->msg = 'Synchronisation konnte nicht ausgeführt werden. (Ajx - '.__LINE__.')';
        return $this->responseJson;
    }

    private function update_plugin_settings():object
    {
        $term_id = filter_input( INPUT_POST, 'term_id', FILTER_VALIDATE_INT );
        $selected_cron_sync_interval = filter_input( INPUT_POST, 'selected_cron_sync_interval', FILTER_UNSAFE_RAW );
        $plugin_min_role = filter_input( INPUT_POST, 'plugin_min_role', FILTER_UNSAFE_RAW );
        $max_post_sync_selected = filter_input( INPUT_POST, 'max_post_sync_selected', FILTER_VALIDATE_INT );

        filter_input( INPUT_POST, 'cron_aktiv', FILTER_UNSAFE_RAW ) ? $cron_aktiv = 1 : $cron_aktiv = 0;
        filter_input( INPUT_POST, 'bootstrap_css_aktiv', FILTER_UNSAFE_RAW ) ? $bootstrap_css_aktiv = 1 : $bootstrap_css_aktiv = 0;
        filter_input( INPUT_POST, 'bootstrap_js_aktiv', FILTER_UNSAFE_RAW ) ? $bootstrap_js_aktiv = 1 : $bootstrap_js_aktiv = 0;
        filter_input( INPUT_POST, 'cron_update_post', FILTER_UNSAFE_RAW ) ? $cron_update_post = 1 : $cron_update_post = 0;

        if ( ! $plugin_min_role ) {
            $plugin_min_role = 'manage_options';
        }
        if ( ! $selected_cron_sync_interval ) {
            $selected_cron_sync_interval = 'daily';
        }

        if(!$max_post_sync_selected){
            $max_post_sync_selected = 5;
        }

        if ( ! $cron_aktiv ) {
            wp_clear_scheduled_hook( 'instagram_import_sync' );
        }
        $settings = get_option( 'wp_instagram_importer_settings' );
        if ( $settings['selected_cron_sync_interval'] != $selected_cron_sync_interval ) {
            wp_clear_scheduled_hook( 'instagram_import_sync' );
            apply_filters( $this->basename . '/instagram_run_schedule_task', false );
        }
        $settings['plugin_min_role'] = $plugin_min_role;
        $settings['selected_cron_sync_interval'] = $selected_cron_sync_interval;
        $settings['cron_aktiv']  = $cron_aktiv;
        $settings['bootstrap_css_aktiv'] = $bootstrap_css_aktiv;
        $settings['bootstrap_js_aktiv'] = $bootstrap_js_aktiv;
        $settings['cron_update_post'] = $cron_update_post;
        $settings['term_id'] = $term_id;
        $settings['max_post_sync_selected'] = (int) $max_post_sync_selected;
        update_option('wp_instagram_importer_settings', $settings);
        $this->responseJson->msg  = 'Einstellungen erfolgreich gespeichert.';
        $this->responseJson->title = 'Gespeichert!';
        $this->responseJson->status = true;
        return $this->responseJson;
    }



    private function html_compress_template(string $string): string
    {
        if (!$string) {
            return $string;
        }
        return preg_replace(['/<!--(.*)-->/Uis', "/[[:blank:]]+/"], ['', ' '], str_replace(["\n", "\r", "\t"], '', $string));
    }
}
