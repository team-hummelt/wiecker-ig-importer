<?php

namespace WiIg\InstagramApi;

use Exception;
use stdClass;
use Wiecker_Ig_Importer;
use WiIg\Importer\CronSettings;

class Instagram_Api_Data
{
    private static $instance;

    protected bool $force_delete = true;
    protected static bool $log_aktiv = true;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wiecker_Ig_Importer $main The main class.
     */
    private Wiecker_Ig_Importer $main;

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

    /**
     * TRAIT of Default Settings.
     *
     * @since    1.0.0
     */
    use CronSettings;

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
        $this->basename = $basename;
        $this->version = $version;
        $this->main = $main;

    }

    public function fn_instagram_api_data(string $api_url, string $replace = ''): object
    {

        $return = new stdClass();
        $return->status = false;
        $url = $api_url;
        $args = [
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'sslverify' => true,
            'blocking' => true,
            'headers' => [],
            'body' => []
        ];
        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            $return->msg = $response->get_error_message();
            return $return;
        }

        $return->record = json_decode($response['body'], true);
        $return->status = true;
        return $return;
    }

    /**
     * @throws Exception
     */
    public function instagram_import_synchronisation($args): void
    {
        $this->fn_make_instagram_import_synchronisation($args);
    }

    /**
     * @throws Exception
     */
    public function fn_make_instagram_import_synchronisation($instagram_url = NULL): bool
    {
        set_time_limit(150);
        $oauth = get_option($this->basename . '/instagram_oauth');
        $settings = get_option('wp_instagram_importer_settings');
        $allCatPosts = apply_filters( $this->basename . '/get_posts_by_taxonomy', $settings['term_id'] );
        $client_id = $oauth['app_id'];

        if (empty($oauth['access_token'])) {
            return false;
        }

        $access_token = $oauth['access_token'];
        $url = sprintf('https://graph.instagram.com/me/media?fields=id,media_type,permalink,media_url,caption,thumbnail_url,username,timestamp&access_token=%s', $access_token);
        if ($instagram_url) {
            $url = $instagram_url;
        }
        $term = get_term($settings['term_id']);
        $apiData = $this->fn_instagram_api_data($url);
        $postIds = [];
        $i=0;
        if ($apiData->status) {
            $apiData = $apiData->record;
            foreach ($apiData['data'] as $tmp) {
                $ifPost = apply_filters($this->basename . '/get_post_by_instagram_id', $tmp['id'], $settings['term_id']);
                $postIds[] = $tmp['id'];
                if ($ifPost) {
                    if ($settings['cron_update_post']) {
                        $this->delete_post_attachments($ifPost->ID);
                        $this->ebay_import_delete_post($ifPost->ID);
                    } else {
                        continue;
                    }
                }

                $date = date('Y-m-d H:i:s', strtotime($tmp['timestamp']));
                $username = str_replace(['_',' '],'-',$tmp['username']);
                $tmp['caption'] ? $content = nl2br($tmp['caption']) : $content = '';
                $args = [
                    'post_type' => 'instagram',
                    'post_title' => $username . '_' . date('d-m-Y_H-i-s', strtotime($tmp['timestamp'])),
                    'post_content' => $content,
                    'post_status' => 'publish',
                    'post_category' => array((int)$settings['term_id']),
                    'comment_status' => 'closed',
                    'post_excerpt' => $tmp['caption'] ?? '',
                    'post_date' => $date,
                    'meta_input' => [
                        '_instagram_id' => $tmp['id'],
                        '_instagram_permalink' => $tmp['permalink'],
                        '_instagram_media_url' => $tmp['media_url'],
                        '_instagram_thumbnail_url' => $tmp['thumbnail_url'] ?? '',
                        '_instagram_caption' => $tmp['caption'] ?? '',
                        '_instagram_media_type' => $tmp['media_type'] ?? '',
                        '_instagram_timestamp' => $tmp['timestamp'],
                        '_instagram_username' => $tmp['username']
                    ]
                ];

                $postId = wp_insert_post( $args, true );
                if ( is_wp_error( $postId ) ) {
                    $errMsg = 'import-error|' . $postId->get_error_message() . '|ID|' . $tmp['id'] . '|line|' . __LINE__;
                    self::instagram_import_log( $errMsg );
                } else {
                    wp_set_object_terms( $postId, array( $term->term_id ), $term->taxonomy );
                    $img_item = [
                        'filename' => $tmp['id'],
                        'type'     => 'attachment',
                        'catId'    => $settings['term_id'],
                        'url'      => $tmp['media_url']
                    ];
                   $this->set_import_attachment_images($img_item, $postId);
                }

                $i++;
                if($i == $settings['max_post_sync_selected']){
                    break;
                }
            }
            if ( $allCatPosts ) {
                foreach ($allCatPosts as $all) {
                    $meta = get_post_meta($all->ID, '_instagram_id', true);
                    if (!in_array($meta, $postIds)) {
                        $this->delete_post_attachments($all->ID);
                        $this->ebay_import_delete_post($all->ID);
                    }
                }
            }
            return true;
        }
        return false;
    }

    private function set_import_attachment_images( $images, $postID ): bool {
        if (!$images || ! is_array( $images )) {
            return false;
        }
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $wp_upload_dir = wp_upload_dir();
        $term = get_term( $images['catId'] );
        $remote_file = file_get_contents( $images['url'] );
        $extension = substr($images['url'],0, strrpos($images['url'],'?'));
        $extension = substr($extension, strrpos($extension,'.'));
        $filename = $images['filename'] . $extension;
        $destination = $wp_upload_dir['path'] . '/' . $filename;
        file_put_contents( $destination, $remote_file );
        $wp_filetype = wp_check_filetype( $destination );
        $attachment  = array(
            'guid' => $wp_upload_dir['url'] . '/' . $filename,
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => $images['filename'],
            'post_content' => '',
            'post_status' => 'inherit',
            'post_category'  => array( (int) $images['catId'] ),
        );
        $attach_id = wp_insert_attachment( $attachment, $destination, $postID, true );
        if ( is_wp_error( $attach_id ) ) {
            $errMsg = 'attachment-error|' . $attach_id->get_error_message() . '|ID|' . $postID . '|line|' . __LINE__;
            self::instagram_import_log( $errMsg );
            if ( is_file( $destination ) ) {
                unlink( $destination );
            }
            return false;
        }
        wp_set_object_terms( $attach_id, array( $term->term_id ), $term->taxonomy );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $destination );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        set_post_thumbnail( $postID, $attach_id );

        return true;
    }
    protected function ebay_import_delete_post($postId): void
    {
        wp_delete_post($postId, true);
    }

    /**
     * @throws Exception
     */
    protected function delete_post_attachments($id): void
    {
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'post_parent' => $id
        ));

        foreach ($attachments as $attachment) {
            if (!wp_delete_attachment($attachment->ID, $this->force_delete)) {
                throw new Exception('Anhang konnte nicht gelöscht werden.(' . __LINE__ . ')');
            }
        }
    }

    public static function instagram_import_log( $msg, $type = 'import_error.log' ): void {
        if ( self::$log_aktiv ) {
            $logDir = WP_INSTAGRAM_IMPORTER_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
            if ( ! $logDir ) {
                mkdir( $logDir, 0777, true );
            }
            if ( ! is_file( $logDir . '.htaccess' ) ) {
                $htaccess = 'Require all denied';
                file_put_contents( $logDir . '.htaccess', $htaccess );
            }

            $log = 'LOG: ' . current_time( 'mysql' ) . '|' . $msg . "\r\n";
            $log .= '-------------------' . "\r\n";
            file_put_contents( $logDir . $type, $log, FILE_APPEND | LOCK_EX );
        }
    }
}