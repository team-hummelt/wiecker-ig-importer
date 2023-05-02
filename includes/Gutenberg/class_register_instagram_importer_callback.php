<?php

namespace WiIg\Importer;


use Wiecker_Ig_Importer;

class Register_Instagram_Importer_Callback
{
    private static $instance;
    use CronSettings;

    /**
     * The ID of this Plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this Plugin.
     */
    protected string $basename;

    /**
     * The version of this Plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this theme.
     */
    protected string $version;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wiecker_Ig_Importer $main The main class.
     */
    private Wiecker_Ig_Importer $main;

    /**
     * @return static
     */
    public static function instance(string $basename, string $version, Wiecker_Ig_Importer $main): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($basename, $version, $main);
        }

        return self::$instance;
    }

    public function __construct(string $basename, string $version, Wiecker_Ig_Importer $main)
    {
        $this->main = $main;
        $this->version = $version;
        $this->basename = $basename;
    }

    public function callback_instagram_importer_block_type($attributes)
    {

        global $posts;
        $metaArr = [];
        isset($attributes['className']) && $attributes['className'] ? $className = $attributes['className'] : $className = '';
        isset($attributes['selectedCategory']) && $attributes['selectedCategory'] ? $selectedCategory = $attributes['selectedCategory'] : $selectedCategory = '';
        isset($attributes['selectedOrder']) && $attributes['selectedOrder'] ? $selectedOrder = $attributes['selectedOrder'] : $selectedOrder = 2;
        isset($attributes['selectedCount'])  ? $selectedCount = $attributes['selectedCount'] : $selectedCount = -1;
        isset($attributes['individuell']) && $attributes['individuell'] ? $individuell = $attributes['individuell'] : $individuell = '';

        if($selectedCount == 0){
            $selectedCount = $individuell;
        }
        $attr = [
            'className' => $className,
            'selectedCategory' => $selectedCategory,
            'selectedOrder' => $selectedOrder,
            'selectedCount' => $selectedCount,
            'individuell' => $individuell
        ];

        switch ($selectedOrder) {
            case 1:
                $orderBy = 'date';
                $order = 'DESC';
                break;
            case 2:
                $orderBy = 'date';
                $order = 'ASC';
                break;
            case 3:
                $orderBy = 'menu_order';
                $order = 'DESC';
                break;
            default:
                $order = 'date';
                $orderBy = 'DESC';
        }
        if(!$selectedCategory){
            $settings = get_option('wp_instagram_importer_settings');
            $selectedCategory = $settings['term_id'];
        }
        $term = get_term($selectedCategory);

        $postArgs = [
            'post_type' => 'instagram',
            'numberposts' => $selectedCount,
            'orderby' => $orderBy,
            'order' => $order,
            'tax_query' => [
                [
                    'taxonomy' => $term->taxonomy,
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ]
            ]
        ];
        $posts = get_posts($postArgs);
        if ($posts) {
            foreach ($posts as $tmp) {
                $meta = apply_filters($this->basename . '/get_instagram_import_meta', $tmp->ID);
                $metaArr[] = $meta;
            }
        }
        return apply_filters('gutenberg_block_instagram_importer_callback', $posts, $attr, $metaArr, $term);
    }

    /**
     * @return false|string
     */
    public function gutenberg_block_instagram_importer_filter()
    {

        ob_start();
        return ob_get_clean();
    }
}
