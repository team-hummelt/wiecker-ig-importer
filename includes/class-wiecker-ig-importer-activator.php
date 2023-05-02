<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wiecker_Ig_Importer
 * @subpackage Wiecker_Ig_Importer/includes
 * @author     Jens Wiecker <plugins@wwdh.de>
 */
class Wiecker_Ig_Importer_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        self::register_wp_instagram_post_type();
        self::register_wp_instagram_importer_taxonomy();
        flush_rewrite_rules();

        $srvLog = self::plugin_dir() . 'log';
        if (!is_dir($srvLog)) {
            mkdir($srvLog, 0777, true);
        }
        if (!is_file($srvLog . '/.htaccess')) {
            $htaccess = 'Require all denied';
            file_put_contents($srvLog . DIRECTORY_SEPARATOR . '.htaccess', $htaccess);
        }
        self::activated_api_plugin();
	}

    private static function activated_api_plugin()
    {
        $idRsa = self::plugin_dir() . 'id_rsa/public_id_rsa';
        if (is_file($idRsa)) {
            $idRsa = base64_encode(file_get_contents($idRsa));

            self::get_srv_api_data($idRsa);
        }
    }

    private static function get_srv_api_data($idRsa)
    {
        $url = 'https://start.hu-ku.com/theme-update/api/v2/public/token/' . $idRsa;
        $args = [
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'sslverify' => true,
            'blocking' => true,
            'body' => []
        ];

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            $message = 'error|'.date('d.m.Y H:i:s', current_time('timestamp')).'|' . $response->get_error_message()."\n";
            file_put_contents(self::plugin_dir() . 'log' . DIRECTORY_SEPARATOR . 'api.log', $message);
            return;
        }

        if (isset($response['body'])) {
            $response = json_decode($response['body']);
            if($response->access_token){
                self::send_api_plugin_aktiviert($response->access_token);
            }
        }
    }

    private static function send_api_plugin_aktiviert($token)
    {
        $log = '';
        $plugin = get_file_data(plugin_dir_path(dirname(__FILE__)) . WP_INSTAGRAM_IMPORTER_BASENAME . '.php', array('Version' => 'Version'), false);
        $l = self::plugin_dir() . 'log' . DIRECTORY_SEPARATOR . 'api.log';
        if(is_file($l)){
            $log = file($l);
            $log = json_encode($log);
        }

        $body = [
            'basename' => WP_INSTAGRAM_IMPORTER_BASENAME,
            'type' => 'activates',
            'site_url' => site_url(),
            'version' => $plugin['Version'],
            'command' => 'plugin_aktiviert',
            'log' => $log
        ];
        $args = [
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'sslverify' => true,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => "Bearer $token"
            ],
            'body' => $body
        ];
        $response = wp_remote_post('https://start.hu-ku.com/theme-update/api/v2/public', $args);
        if (is_wp_error($response)) {
            $message = 'error|'.date('d.m.Y H:i:s', current_time('timestamp')).'|' . $response->get_error_message()."\n";
            file_put_contents(self::plugin_dir() . 'log' . DIRECTORY_SEPARATOR . 'api.log', $message);
            return;
        }
        if (isset($response['body'])) {
            $response = json_decode($response['body']);
            if($response->status){
                $message = 'aktiviert|'.date('d.m.Y H:i:s', current_time('timestamp'))."\n";
                file_put_contents(self::plugin_dir() . 'log' . DIRECTORY_SEPARATOR . 'api.log', $message, FILE_APPEND);
            }
        }
    }

    public static function register_wp_instagram_post_type()
    {
        register_post_type(
            'instagram',
            array(
                'labels' => array(
                    'name' => __('Instagram', 'wiecker-ig-importer'),
                    'singular_name' => __('Instagram', 'wiecker-ig-importer'),
                    'menu_name' => __('Instagram Posts', 'wiecker-ig-importer'),
                    'parent_item_colon' => __('Parent Item:', 'wiecker-ig-importer'),
                    'edit_item' => __('Bearbeiten', 'wiecker-ig-importer'),
                    'update_item' => __('Aktualisieren', 'wiecker-ig-importer'),
                    'all_items' => __('Alle Beiträge', 'wiecker-ig-importer'),
                    'items_list_navigation' => __('Instagram Posts navigation', 'wiecker-ig-importer'),
                    'add_new_item' => __('neuen Beitrag hinzufügen', 'wiecker-ig-importer'),
                    'archives' => __('Instagram Archiv', 'wiecker-ig-importer'),
                ),
                'public' => true,
                'publicly_queryable' => true,
                'show_in_rest' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'has_archive' => true,
                'query_var' => true,
                'show_in_nav_menus' => true,
                'exclude_from_search' => false,
                'hierarchical' => false,
                'capability_type' => 'post',
                'menu_icon' => self::get_svg_icon('instagram'),
                'menu_position' => 24,
                'can_export' => true,
                'show_in_admin_bar' => true,
                'supports' => array(
                    'title', 'excerpt', 'page-attributes', 'editor', 'thumbnail', 'custom-fields'
                ),
                'taxonomies' => array('instagram-kategorie'),
            )
        );
    }

    public static function register_wp_instagram_importer_taxonomy(): void {
        $labels = array(
            'name' => __('Instagram Kategorie', 'wiecker-ig-importer'),
            'singular_name' => __('Instagram Kategorie', 'wiecker-ig-importer'),
            'search_items' => __('Kategorie suchen', 'wiecker-ig-importer'),
            'all_items' => __('Alle Instagram Kategorien', 'wiecker-ig-importer'),
            'parent_item' => __('Eltern-Kategorie', 'wiecker-ig-importer'),
            'parent_item_colon' => __('Eltern-Kategorie:', 'wiecker-ig-importer'),
            'edit_item' => __('Kategorie bearbeiten', 'wiecker-ig-importer'),
            'update_item' => __('Kategorie aktualisieren', 'wiecker-ig-importer'),
            'add_new_item' => __('neue Kategorie hinzufügen', 'wiecker-ig-importer'),
            'new_item_name' => __('Neue Kategorie', 'wiecker-ig-importer'),
            'menu_name' => __('Kategorien', 'wiecker-ig-importer'),
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'sort' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'args' => array('orderby' => 'term_order'),
            'show_admin_column' => true,
            'publicly_queryable' => true,
            'show_in_nav_menus' => true,
        );
        register_taxonomy('instagram-kategorie', array('attachment', 'instagram'), $args);

        $terms = [
            '0' => [
                'name' => __('Instagram Post', 'wiecker-ig-importer'),
                'slug' => 'instagram-post'
            ]
        ];

        foreach ($terms as $term) {
            if (!term_exists($term['name'], 'instagram-kategorie')) {
                wp_insert_term(
                    $term['name'],
                    'instagram-kategorie',
                    array(
                        'description' => __('Instagram Kategorie', 'wiecker-ig-importer'),
                        'slug' => $term['slug']
                    )
                );
            }
        }
    }


    private static function get_svg_icon($type):string
    {
        switch ($type){
            case'instagram':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
                         <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/>
                         </svg>';
                break;
            case'cart':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="bi bi-cart" viewBox="0 0 16 16">
    			 <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
				 </svg>';
                break;
            default:
                $icon = '';
        }

        return 'data:image/svg+xml;base64,' . base64_encode($icon);
    }
    private static function plugin_dir():string
    {
        return plugin_dir_path(__DIR__) . 'admin' . DIRECTORY_SEPARATOR . 'srv-api' . DIRECTORY_SEPARATOR;
    }
}
