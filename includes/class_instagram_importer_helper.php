<?php
namespace WiIg\Helper;

use DateTime;
use DateTimeZone;
use Exception;
use IntlDateFormatter;
use Wiecker_Ig_Importer;
use WiIg\Importer\CronSettings;
use WP_Post;
use WP_Query;
use WP_Term_Query;

class RSS_Importer_Helper
{
	private static $instance;
	protected Wiecker_Ig_Importer $main;
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
	 * @return static
	 */
	public static function instance(string $basename, string $version, Wiecker_Ig_Importer $main): self
	{
		if (is_null(self::$instance)) {
			self::$instance = new self($basename, $version, $main);
		}
		return self::$instance;
	}

	public function __construct(string $basename, string $version,Wiecker_Ig_Importer $main)
	{
		$this->main = $main;
		$this->basename = $basename;
		$this->version = $version;
	}
    /**
     * @param $instagramId
     * @param $term_id
     *
     * @return int|void|WP_Post
     */
    public function fn_get_post_by_instagram_id( $instagramId, $term_id ) {
        $args = array(
            'post_type'   => 'instagram',
            'numberposts' => 1,
            'meta_query'  => array(
                array(
                    'key'     => '_instagram_id',
                    'value'   => $instagramId,
                    'compare' => '==',
                )
            ),
            'tax_query'   => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'instagram-kategorie',
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                )
            )
        );

        $query = new WP_Query( $args );
        if ( $query->post_count ) {
            return $query->posts[0];
        }
    }

	public function import_get_next_cron_time(string $cron_name)
	{
		foreach (_get_cron_array() as $timestamp => $crons) {
			if (in_array($cron_name, array_keys($crons))) {
				return $timestamp - time();
			}
		}
		return false;
	}

    /**
     * @throws Exception
     */
    public function getRandomString(): string
    {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes(16);
            $str = bin2hex($bytes);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(16);
            $str = bin2hex($bytes);
        } else {
            $str = md5(uniqid('wp_ebay_importer_rand', true));
        }

        return $str;
    }
    public function getGenerateRandomId($passwordlength = 12, $numNonAlpha = 1, $numNumberChars = 4, $useCapitalLetter = true): string
    {
        $numberChars = '123456789';
        //$specialChars = '!$&?*-:.,+@_';
        $specialChars = '!$%&=?*-;.,+~@_';
        $secureChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
        $stack = $secureChars;
        if ($useCapitalLetter == true) {
            $stack .= strtoupper($secureChars);
        }
        $count = $passwordlength - $numNonAlpha - $numNumberChars;
        $temp = str_shuffle($stack);
        $stack = substr($temp, 0, $count);
        if ($numNonAlpha > 0) {
            $temp = str_shuffle($specialChars);
            $stack .= substr($temp, 0, $numNonAlpha);
        }
        if ($numNumberChars > 0) {
            $temp = str_shuffle($numberChars);
            $stack .= substr($temp, 0, $numNumberChars);
        }

        return str_shuffle($stack);
    }

	public function fn_get_rss_import_post_type($values)
	{
		$args = array(
			'post_type' => $values->post_type,
			'numberposts' => $values->number_posts,

		);

		$posts = get_posts($args);
	}

	public function fn_get_import_post_types($type = '', $term_id = ''): array
	{
		$args = array(
			'public' => true,
			'_builtin' => false,
		);

		$output = 'objects'; // names or objects, note names is the default
		$operator = 'and'; // 'and' or 'or'
		$post_types = get_post_types($args, $output, $operator);
		$notTypes = ['starter_header', 'starter_footer', 'hupa_design', 'page'];
		$typesArr = [];
		foreach ($post_types as $tmp) {
			if (in_array($tmp->name, $notTypes)) {
				continue;
			}
			$postItems = [
				'post_type' => $tmp->name,
				'label' => $tmp->label,
				'taxonomie' => $tmp->taxonomies[0]
			];
			$typesArr[] = $postItems;
		}
		if($type){
			$term_args = array(
				'term_id' => $term_id,
				'hide_empty' => false,
				'fields' => 'all'
			);
		} else {
			$term_args = array(
				'taxonomy' => $typesArr[0]['taxonomie'],
				'hide_empty' => false,
				'fields' => 'all'
			);
		}


		$term_query = new WP_Term_Query($term_args);
		$cats = $term_query->terms;


		$post = [
			'post_type' => 'post',
			'label' => 'Beiträge',
			'taxonomie' => 'category'
		];

		$typesArr[] = $post;
		$return = [];
		$return['post_type'] = $typesArr;
		$return['post_taxonomies'] = $cats;

		return $return;

	}

	public function fn_get_import_taxonomy($taxonomie, $post_type): array
	{
		if (!$taxonomie || !$post_type) {
			return [];
		}

		$term_args = array(
			'post_type' => $post_type,
			'taxonomy' => $taxonomie,
			'hide_empty' => false,
			'fields' => 'all'
		);

		$term_query = new WP_Term_Query($term_args);
		$taxArr = [];

		if(!$term_query->terms){
			return $taxArr;
		}

		foreach ($term_query->terms as $tmp) {
			$item = [
				'term_id' => $tmp->term_id,
				'slug' => $tmp->slug,
				'name' => $tmp->name
			];
			$taxArr[] = $item;
		}

		return $taxArr;
	}

    /**
     * @param $term_id
     *
     * @return int[]|void
     */
    public function get_posts_by_taxonomy( $term_id ) {
        $args = array(
            'post_type'   => 'instagram',
            'numberposts' => - 1,
            'tax_query'   => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'instagram-kategorie',
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                )
            )
        );

        $query = new WP_Query( $args );
        if ( $query->post_count ) {
            return $query->posts;
        }
    }

    /**
     * @throws Exception
     */
    public function instagram_import_delete_post_before( $postid ): void {

        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'posts_per_page' => - 1,
            'post_status'    => 'any',
            'post_parent'    => $postid
        ) );

        foreach ( $attachments as $attachment ) {
            if ( ! wp_delete_attachment( $attachment->ID, true ) ) {
                throw new Exception( 'Anhang konnte nicht gelöscht werden.(' . __LINE__ . ')' );
            }
        }
    }

    public function get_instagram_import_meta($postId, $meta = ''):array
    {
        $return = [
            '_instagram_id' => get_post_meta($postId, '_instagram_id', true),
            '_instagram_permalink' => get_post_meta($postId, '_instagram_permalink', true),
            '_instagram_media_url' => get_post_meta($postId, '_instagram_media_url', true),
            '_instagram_thumbnail_url' => get_post_meta($postId, '_instagram_thumbnail_url', true),
            '_instagram_caption' => get_post_meta($postId, '_instagram_caption', true),
            '_instagram_media_type' => get_post_meta($postId, '_instagram_media_type', true),
            '_instagram_timestamp' => get_post_meta($postId, '_instagram_timestamp', true),
            '_instagram_username' => get_post_meta($postId, '_instagram_username', true),
        ];

        if($meta){
            foreach ($return as $key => $val){
                if($key == $meta){
                    return [$key => $val];
                }
            }
        }
        return $return;
    }

    /**
     * @param $array
     *
     * @return object
     */
    public function arrayToObject($array): object
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::arrayToObject($value);
            }
        }

        return (object)$array;
    }

    /**
     * @param $object
     * @return array
     */
    public function object2array_recursive($object):array
    {
        if(!$object) {
            return  [];
        }
        return json_decode(json_encode($object), true);
    }

    public function date_format_language(DateTime $dt, string $format, string $language = 'en'): string
    {
        $curTz = $dt->getTimezone();
        if ($curTz->getName() === 'Z') {
            //INTL don't know Z
            $curTz = new DateTimeZone('Europe/Berlin');
        }

        $formatPattern = strtr($format, array(
            'D' => '{#1}',
            'l' => '{#2}',
            'M' => '{#3}',
            'F' => '{#4}',
        ));
        $strDate = $dt->format($formatPattern);
        $regEx = '~\{#\d}~';
        while (preg_match($regEx, $strDate, $match)) {
            $IntlFormat = strtr($match[0], array(
                '{#1}' => 'E',
                '{#2}' => 'EEEE',
                '{#3}' => 'MMM',
                '{#4}' => 'MMMM',
            ));
            $fmt = datefmt_create($language, IntlDateFormatter::FULL, IntlDateFormatter::FULL,
                $curTz, IntlDateFormatter::GREGORIAN, $IntlFormat);
            $replace = $fmt ? datefmt_format($fmt, $dt) : "???";
            $strDate = str_replace($match[0], $replace, $strDate);
        }

        return $strDate;
    }

    public function fnPregWhitespace($string): string
    {
        if (!$string) {
            return '';
        }
        return trim(preg_replace('/\s+/', ' ', $string));
    }

}