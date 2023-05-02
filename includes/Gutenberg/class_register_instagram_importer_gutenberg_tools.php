<?php

class Register_Instagram_Importer_Gutenberg_Tools
{

    protected Wiecker_Ig_Importer $main;

    /**
     * The ID of this theme.
     *
     * @since    2.0.0
     * @access   private
     * @var      string $basename The ID of this theme.
     */
    protected string $basename;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    protected string $version;

    public function __construct(string $version,string $basename,Wiecker_Ig_Importer $main)
    {
        $this->main = $main;
        $this->version = $version;
        $this->basename = $basename;
    }

    public function instagram_importer_gutenberg_register_sidebar(): void
    {
        $plugin_asset = require WP_INSTAGRAM_IMPORTER_PLUGIN_DIR . '/includes/Gutenberg/Sidebar/build/index.asset.php';
         wp_register_script(
             'instagram-importer-sidebar',
             WP_INSTAGRAM_IMPORTER_PLUGIN_URL . '/includes/Gutenberg/Sidebar/build/index.js',
             $plugin_asset['dependencies'], $plugin_asset['version'], true
         );

		 wp_register_style(
			 'instagram-importer-sidebar-style',
             WP_INSTAGRAM_IMPORTER_PLUGIN_URL . '/includes/Gutenberg/Sidebar/build/index.css', array(), $plugin_asset['version']
		 );
    }
    /**
     * Register TAM MEMBERS REGISTER GUTENBERG BLOCK TYPE
     *
     * @since    1.0.0
     */
    public function register_instagram_importer_block_type(): void
    {
        global $registerInstagramImporterCallback;
        register_block_type('instagram/importer-block', array(
            'render_callback' => [$registerInstagramImporterCallback, 'callback_instagram_importer_block_type'],
            'editor_script' => 'instagram-importer-gutenberg-block',
        ));
        add_filter('gutenberg_block_instagram_importer_callback', array($registerInstagramImporterCallback, 'gutenberg_block_instagram_importer_filter'), 10, 4);
    }

    public function instagram_importer_block_type_scripts(): void
    {
       $plugin_asset = require WP_INSTAGRAM_IMPORTER_PLUGIN_DIR . '/includes/Gutenberg/Block/build/index.asset.php';
        wp_enqueue_script(
            'instagram-importer-gutenberg-block',
            WP_INSTAGRAM_IMPORTER_PLUGIN_URL . '/includes/Gutenberg/Block/build/index.js',
            $plugin_asset['dependencies'], $plugin_asset['version'], true
        );

	    /*if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('ebay-importer-gutenberg-block', 'wp-ebay-classifieds', WP_EBAY_CLASSIFIED_PLUGIN_DIR . '/languages');
		}*/

        wp_localize_script('instagram-importer-gutenberg-block',
            'WIGEndpoint',
            array(
                'url' => esc_url_raw(rest_url('wp-instagram-importer/v1/')),
                'nonce' => wp_create_nonce('wp_rest')
            )
        );

        wp_enqueue_style(
            'instagram-importer-gutenberg-block',
            WP_INSTAGRAM_IMPORTER_PLUGIN_URL . '/includes/Gutenberg/Block/build/index.css', array(), $plugin_asset['version']);

	    wp_enqueue_script('instagram-importer-sidebar');
	    wp_enqueue_style('instagram-importer-sidebar-style');
    }


	public function register_instagram_imports_meta_fields(): void {
		register_post_meta(
			'instagram',
			'_instagram_id',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'instagram',
			'_instagram_permalink',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'instagram',
			'_instagram_media_url',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'instagram',
			'_instagram_thumbnail_url',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'instagram',
			'_instagram_caption',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'instagram',
			'_instagram_media_type',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'instagram',
			'_instagram_timestamp',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
		register_post_meta(
			'instagram',
			'_instagram_username',
			array(
				'type' => 'string',
				'single' => true,
				'show_in_rest' => true,
				'default' => '',
				'auth_callback' => array($this, 'import_post_permissions_check')
			)
		);
	}


	/**
	 * Check if a given request has access.
	 *
	 * @return bool
	 */
	public function import_post_permissions_check(): bool
	{
		return current_user_can('edit_posts');
	}

}
