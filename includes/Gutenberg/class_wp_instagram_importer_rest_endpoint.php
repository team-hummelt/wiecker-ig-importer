<?php

namespace WiIg\Importer;

use stdClass;
use WP_Error;
use Wiecker_Ig_Importer;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Term_Query;

class WP_Instagram_Importer_Rest_Endpoint {

	protected Wiecker_Ig_Importer $main;
	protected string $basename;

    use CronSettings;
	public function __construct( string $basename, Wiecker_Ig_Importer $main ) {
		$this->main     = $main;
		$this->basename = $basename;
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_wp_instagram_importer_routes(): void {
		$version   = '1';
		$namespace = 'wp-instagram-importer/v' . $version;
		$base      = '/';

		@register_rest_route(
			$namespace,
			$base . '(?P<method>[\S]+)',

			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'wp_instagram_importer_endpoint_get_response' ),
				'permission_callback' => array( $this, 'permissions_check' )
			)
		);
	}

	/**
	 * Get one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function wp_instagram_importer_endpoint_get_response( WP_REST_Request $request ) {

		$method = (string) $request->get_param( 'method' );
		if ( ! $method ) {
			return new WP_Error( 404, ' Method failed' );
		}

		return $this->get_method_item( $method );

	}

	/**
	 * GET Post Meta BY ID AND Field
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_method_item( $method ) {
		if ( ! $method ) {
			return new WP_Error( 404, ' Method failed' );
		}
		$response = new stdClass();
		switch ( $method ) {
			case 'get-data':
                $term_args = array(
                    'taxonomy' => 'instagram-kategorie',
                    'hide_empty' => false,
                    'fields' => 'all'
                );

                $term_query = new WP_Term_Query($term_args);
                $termArr = [];
                foreach ($term_query->terms as $tmp) {
                    $item = [
                        'label' => $tmp->name,
                        'value' => $tmp->term_id
                    ];
                    $termArr[] = $item;
                }
				$select = [
					'0' => [
						'label' => 'auswählen ...',
						'value' => 0
					]
				];

				$sortOut = [
					'0' => [
						'label' => __('Datum absteigend', 'wp-ebay-classifieds'),
						'value' => 1
					],
					'1' => [
						'label' => __('Datum aufsteigend', 'wp-ebay-classifieds'),
						'value' => 2
					],
					'2' => [
						'label' => __('Menu Order', 'wp-ebay-classifieds'),
						'value' => 3
					],
				];

				$response->count = $this->get_cron_defaults('select_gb_count_output');
				$response->order = $sortOut;
				$response->categories = $termArr;

				break;
		}

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return void
	 */
	public
	function get_items(
		WP_REST_Request $request
	) {


	}

	/**
	 * Check if a given request has access.
	 *
	 * @return bool
	 */
	public
	function permissions_check(): bool {
		return current_user_can( 'edit_posts' );
	}
}