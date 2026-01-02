<?php
/**
 * Nudges API class
 *
 * Handles pro nudges related REST API endpoints.
 *
 * @package SureRank\Inc\Modules\Nudges
 * @since 1.5.0
 */

namespace SureRank\Inc\Modules\Nudges;

use SureRank\Inc\Functions\Send_Json;
use SureRank\Inc\Traits\Get_Instance;
use SureRank\Inc\API\Api_Base;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Api
 *
 * @package SureRank\Inc\Modules\Nudges
 * @since 1.5.0
 */
class Api extends Api_Base {



	use Get_Instance;

	/**
	 * Register API routes.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function register_routes() {
		// Register a single POST endpoint to disable pro nudges.
		register_rest_route(
			$this->get_api_namespace(),
			'/nudges/disable',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'disable_nudge' ],
				'permission_callback' => [ $this, 'validate_permission' ],
				'args'                => [
					'type' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'enum'              => [ 'upgrade_banner', 'permalink_redirect' ],
					],
				],
			]
		);
	}

	/**
	 * Disable a pro nudge (persist the disabled type with count and next display time).
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request object.
	 * @return void
	 */
	public function disable_nudge( $request ) {
		$type = $request->get_param( 'type' );

		if ( ! in_array( $type, [ 'upgrade_banner', 'permalink_redirect' ], true ) ) {
			Send_Json::error( [ 'message' => __( 'Invalid type provided.', 'surerank' ) ] );
			return;
		}

		$nudges = (array) get_option( SURERANK_NUDGES, [] );

		// Initialize if not set.
		if ( ! isset( $nudges[ $type ] ) ) {
			$nudges[ $type ] = [
				'count'                => 0,
				'next_time_to_display' => 0,
				'display'              => true,
			];
		}

		// Increment count and set next display time.
		$nudges[ $type ]['count']++;
		$nudges[ $type ]['next_time_to_display'] = time() + ( 7 * DAY_IN_SECONDS );
		$nudges[ $type ]['display']              = $nudges[ $type ]['count'] < 2;

		if ( ! update_option( SURERANK_NUDGES, $nudges ) ) {
			Send_Json::error( [ 'message' => __( 'Could not disable the nudge. Please try again.', 'surerank' ) ] );
			return;
		}

		Send_Json::success(
			[
				'message'              => sprintf( /* translators: %s: nudge type */ __( '"%s" has been disabled.', 'surerank' ), $type ),
				'count'                => $nudges[ $type ]['count'],
				'next_time_to_display' => $nudges[ $type ]['next_time_to_display'],
			]
		);
	}
}
